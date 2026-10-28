<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CashShift;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class PosService
{
    /**
     * Memproses transaksi POS kasir secara atomik dan konsisten.
     *
     * @param array $data [
     *   'items' => [ ['product_id' => int, 'quantity' => int] ],
     *   'customer_id' => ?int,
     *   'payment_method' => 'cash'|'qris'|'transfer',
     *   'cash_received' => float,
     *   'discount_amount' => float,
     *   'notes' => ?string,
     * ]
     * @param User $cashier
     * @return Transaction
     * @throws Exception
     */
    public function processCheckout(array $data, User $cashier): Transaction
    {
        if (empty($data['items']) || !is_array($data['items'])) {
            throw new Exception('Keranjang belanja masih kosong.');
        }

        return DB::transaction(function () use ($data, $cashier) {
            // 1. Dapatkan shift aktif kasir jika ada
            $activeShift = CashShift::where('user_id', $cashier->id)
                ->where('status', 'open')
                ->latest()
                ->first();

            // Jika kasir belum buka shift, periksa apakah ada shift terbuka lainnya
            if (!$activeShift) {
                $activeShift = CashShift::where('status', 'open')->latest()->first();
            }

            // 2. Kunci baris produk untuk validasi stok secara atomik
            $productIds = collect($data['items'])->pluck('product_id')->toArray();
            $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

            $itemsToInsert = [];
            $subtotal = 0;
            $totalCost = 0;

            foreach ($data['items'] as $item) {
                $productId = $item['product_id'];
                $qty = (int) ($item['quantity'] ?? 1);

                if ($qty <= 0) {
                    throw new Exception("Kuantitas barang harus lebih dari 0.");
                }

                if (!isset($products[$productId])) {
                    throw new Exception("Produk dengan ID {$productId} tidak ditemukan.");
                }

                $product = $products[$productId];

                if (!$product->is_active) {
                    throw new Exception("Produk {$product->name} saat ini tidak aktif.");
                }

                if ($product->stock < $qty) {
                    throw new Exception("Stok untuk {$product->name} tidak mencukupi! Tersedia: {$product->stock} {$product->unit}, diminta: {$qty}.");
                }

                $lineSubtotal = (float) $product->selling_price * $qty;
                $lineCost = (float) $product->cost_price * $qty;
                $lineProfit = $lineSubtotal - $lineCost;

                $subtotal += $lineSubtotal;
                $totalCost += $lineCost;

                $itemsToInsert[] = [
                    'product' => $product,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'cost_price' => $product->cost_price,
                    'unit_price' => $product->selling_price,
                    'quantity' => $qty,
                    'subtotal' => $lineSubtotal,
                    'profit' => $lineProfit,
                ];
            }

            // 3. Hitung diskon, pajak, dan total akhir
            $discountAmount = max(0, (float) ($data['discount_amount'] ?? 0));
            $taxPercent = (float) StoreSetting::get('tax_percentage', '0');
            $taxAmount = 0;
            if ($taxPercent > 0) {
                $taxAmount = round(($subtotal - $discountAmount) * ($taxPercent / 100), 2);
            }

            $totalAmount = max(0, $subtotal - $discountAmount + $taxAmount);
            $grossProfit = $totalAmount - $totalCost;

            $paymentMethod = $data['payment_method'] ?? 'cash';
            $cashReceived = (float) ($data['cash_received'] ?? 0);

            if ($paymentMethod === 'cash') {
                if ($cashReceived < $totalAmount) {
                    throw new Exception("Jumlah uang tunai yang diterima (Rp " . number_format($cashReceived, 0, ',', '.') . ") kurang dari total belanja (Rp " . number_format($totalAmount, 0, ',', '.') . ").");
                }
                $cashChange = $cashReceived - $totalAmount;
            } else {
                // Non-tunai (QRIS / Transfer)
                $cashReceived = $totalAmount;
                $cashChange = 0;
            }

            // 4. Generate nomor invoice unik
            $invoiceNumber = Transaction::generateInvoiceNumber();

            // 5. Simpan transaksi
            $transaction = Transaction::create([
                'invoice_number' => $invoiceNumber,
                'user_id' => $cashier->id,
                'cash_shift_id' => $activeShift ? $activeShift->id : null,
                'customer_id' => $data['customer_id'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'total_cost' => $totalCost,
                'gross_profit' => $grossProfit,
                'payment_method' => $paymentMethod,
                'payment_status' => 'paid',
                'cash_received' => $cashReceived,
                'cash_change' => $cashChange,
                'transaction_date' => Carbon::now(),
                'notes' => $data['notes'] ?? null,
            ]);

            // 6. Buat item transaksi & potong stok produk secara atomik
            foreach ($itemsToInsert as $item) {
                $transaction->items()->create([
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_sku' => $item['product_sku'],
                    'cost_price' => $item['cost_price'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                    'profit' => $item['profit'],
                ]);

                // Kurangi stok & catat kartu stok
                $product = $item['product'];
                $beforeStock = $product->stock;
                $afterStock = $beforeStock - $item['quantity'];

                $product->decrement('stock', $item['quantity']);

                InventoryMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $cashier->id,
                    'transaction_id' => $transaction->id,
                    'type' => 'sale',
                    'quantity' => -$item['quantity'],
                    'before_stock' => $beforeStock,
                    'after_stock' => $afterStock,
                    'reason' => "Penjualan invoice #{$transaction->invoice_number}",
                ]);
            }

            // 7. Update akumulasi shift kasir jika ada shift aktif
            if ($activeShift) {
                if ($paymentMethod === 'cash') {
                    $activeShift->increment('cash_sales', $totalAmount);
                } else {
                    $activeShift->increment('non_cash_sales', $totalAmount);
                }
                $activeShift->recalculateExpectedCash();
                $activeShift->save();
            }

            // 8. Update akumulasi belanja pelanggan
            if (!empty($data['customer_id'])) {
                $customer = Customer::find($data['customer_id']);
                if ($customer) {
                    $customer->increment('total_spent', $totalAmount);
                }
            }

            // 9. Catat audit log
            AuditLog::record("checkout_completed", $transaction, null, [
                'invoice' => $transaction->invoice_number,
                'total' => $totalAmount,
                'items_count' => count($itemsToInsert),
                'method' => $paymentMethod,
            ]);

            return $transaction->load('items', 'user', 'customer');
        });
    }

    /**
     * Membatalkan / Void transaksi yang sudah selesai dengan jejak audit dan pengembalian stok.
     *
     * @param Transaction $transaction
     * @param string $reason
     * @param User $user
     * @return Transaction
     * @throws Exception
     */
    public function voidTransaction(Transaction $transaction, string $reason, User $user): Transaction
    {
        if ($transaction->payment_status !== 'paid') {
            throw new Exception("Hanya transaksi dengan status 'paid' yang dapat dibatalkan.");
        }

        return DB::transaction(function () use ($transaction, $reason, $user) {
            // 1. Kembalikan stok produk
            foreach ($transaction->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $beforeStock = $product->stock;
                    $afterStock = $beforeStock + $item->quantity;

                    $product->increment('stock', $item->quantity);

                    InventoryMovement::create([
                        'product_id' => $product->id,
                        'user_id' => $user->id,
                        'transaction_id' => $transaction->id,
                        'type' => 'return',
                        'quantity' => $item->quantity,
                        'before_stock' => $beforeStock,
                        'after_stock' => $afterStock,
                        'reason' => "Pembatalan (Void) transaksi #{$transaction->invoice_number}: {$reason}",
                    ]);
                }
            }

            // 2. Sesuaikan shift jika transaksi terjadi di shift terkait
            if ($transaction->cash_shift_id) {
                $shift = CashShift::find($transaction->cash_shift_id);
                if ($shift && $shift->isOpen()) {
                    if ($transaction->payment_method === 'cash') {
                        $shift->decrement('cash_sales', $transaction->total_amount);
                        $shift->increment('cash_refunds', $transaction->total_amount);
                    } else {
                        $shift->decrement('non_cash_sales', $transaction->total_amount);
                    }
                    $shift->recalculateExpectedCash();
                    $shift->save();
                }
            }

            // 3. Update status transaksi
            $oldStatus = $transaction->payment_status;
            $transaction->update([
                'payment_status' => 'void',
                'void_reason' => $reason,
                'voided_by' => $user->id,
                'voided_at' => Carbon::now(),
            ]);

            // 4. Catat audit log
            AuditLog::record("transaction_voided", $transaction, ['status' => $oldStatus], [
                'status' => 'void',
                'reason' => $reason,
                'voided_by' => $user->name,
            ]);

            return $transaction;
        });
    }
}
