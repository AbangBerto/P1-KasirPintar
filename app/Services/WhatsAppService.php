<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\WhatsappMessageLog;
use App\Models\WhatsappSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Mengirim atau membuat draft laporan harian toko.
     *
     * @param string|null $date 'Y-m-d'
     * @return array
     */
    public function sendDailyReport(?string $date = null): array
    {
        $targetDate = $date ? Carbon::parse($date) : Carbon::today();
        $dateFormatted = $targetDate->translatedFormat('d F Y');

        // Query transaksi paid pada tanggal target
        $transactions = Transaction::whereDate('transaction_date', $targetDate)
            ->where('payment_status', 'paid')
            ->get();

        $netSales = $transactions->sum('total_amount');
        $trxCount = $transactions->count();
        $aov = $trxCount > 0 ? round($netSales / $trxCount) : 0;
        $grossProfit = $transactions->sum('gross_profit');

        // Rincian pembayaran
        $cashSales = $transactions->where('payment_method', 'cash')->sum('total_amount');
        $qrisSales = $transactions->where('payment_method', 'qris')->sum('total_amount');
        $transferSales = $transactions->where('payment_method', 'transfer')->sum('total_amount');

        // Produk terlaris hari ini
        $topProducts = TransactionItem::whereHas('transaction', function ($q) use ($targetDate) {
            $q->whereDate('transaction_date', $targetDate)->where('payment_status', 'paid');
        })
            ->selectRaw('product_name, SUM(quantity) as total_qty')
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->limit(3)
            ->get();

        // Stok menipis
        $lowStockProducts = Product::active()->lowStock()->limit(5)->get();

        // Susun format pesan
        $msg = "📢 *LAPORAN HARIAN — KASIR PINTAR*\n";
        $msg .= "Toko: " . StoreSetting::get('store_name', 'Minimarket Kasir Pintar') . "\n";
        $msg .= "Tanggal: {$dateFormatted}\n\n";

        $msg .= "💰 *Ringkasan Penjualan:*\n";
        $msg .= "• Total penjualan neto: Rp " . number_format($netSales, 0, ',', '.') . "\n";
        $msg .= "• Jumlah transaksi berhasil: {$trxCount}\n";
        $msg .= "• Rata-rata transaksi: Rp " . number_format($aov, 0, ',', '.') . "\n";
        $msg .= "• Estimasi laba kotor: Rp " . number_format($grossProfit, 0, ',', '.') . "\n\n";

        $msg .= "💳 *Metode Pembayaran:*\n";
        $msg .= "• Tunai: Rp " . number_format($cashSales, 0, ',', '.') . "\n";
        $msg .= "• QRIS: Rp " . number_format($qrisSales, 0, ',', '.') . "\n";
        $msg .= "• Transfer/Debit: Rp " . number_format($transferSales, 0, ',', '.') . "\n\n";

        $msg .= "🏆 *Produk Terlaris:*\n";
        if ($topProducts->isEmpty()) {
            $msg .= "- Belum ada produk terjual\n";
        } else {
            foreach ($topProducts as $tp) {
                $msg .= "• {$tp->product_name}: {$tp->total_qty} unit\n";
            }
        }
        $msg .= "\n";

        $msg .= "⚠️ *Stok Menipis / Perlu Restok:*\n";
        if ($lowStockProducts->isEmpty()) {
            $msg .= "- Semua stok dalam batas aman\n";
        } else {
            foreach ($lowStockProducts as $lp) {
                $msg .= "• {$lp->name} (Sisa: {$lp->stock} {$lp->unit})\n";
            }
        }
        $msg .= "\n_Laporan dibuat otomatis oleh sistem Kasir Pintar._";

        $recipient = WhatsappSetting::get('recipient_number', '6281234567890');
        return $this->sendMessage($recipient, $msg, 'daily_report');
    }

    /**
     * Mengirim notifikasi peringatan stok menipis.
     */
    public function sendLowStockAlert(Product $product): array
    {
        $storeName = StoreSetting::get('store_name', 'Kasir Pintar');
        $msg = "⚠️ *PERINGATAN STOK MENIPIS — {$storeName}*\n\n";
        $msg .= "Produk: *{$product->name}*\n";
        $msg .= "SKU: {$product->sku}\n";
        $msg .= "Sisa Stok: *{$product->stock} {$product->unit}*\n";
        $msg .= "Batas Minimum: {$product->min_stock} {$product->unit}\n\n";
        $msg .= "Mohon segera lakukan pengadaan / restok kembali barang ini.";

        $recipient = WhatsappSetting::get('recipient_number', '6281234567890');
        return $this->sendMessage($recipient, $msg, 'stock_alert');
    }

    /**
     * Mengirim struk digital belanja ke WhatsApp pelanggan.
     */
    public function sendReceipt(Transaction $transaction, string $phoneNumber): array
    {
        $storeName = StoreSetting::get('store_name', 'Kasir Pintar');
        $phoneStore = StoreSetting::get('store_phone', '');

        $msg = "🧾 *STRUK DIGITAL — {$storeName}*\n";
        $msg .= "No. Invoice: *{$transaction->invoice_number}*\n";
        $msg .= "Waktu: " . $transaction->transaction_date->format('d/m/Y H:i') . "\n";
        $msg .= "Kasir: " . ($transaction->user->name ?? 'Kasir') . "\n";
        $msg .= "----------------------------------------\n";

        foreach ($transaction->items as $item) {
            $sub = number_format($item->subtotal, 0, ',', '.');
            $msg .= "{$item->quantity}x {$item->product_name}\n";
            $msg .= "   @ Rp " . number_format($item->unit_price, 0, ',', '.') . " = Rp {$sub}\n";
        }

        $msg .= "----------------------------------------\n";
        $msg .= "Subtotal: Rp " . number_format($transaction->subtotal, 0, ',', '.') . "\n";
        if ($transaction->discount_amount > 0) {
            $msg .= "Diskon: -Rp " . number_format($transaction->discount_amount, 0, ',', '.') . "\n";
        }
        if ($transaction->tax_amount > 0) {
            $msg .= "Pajak: Rp " . number_format($transaction->tax_amount, 0, ',', '.') . "\n";
        }
        $msg .= "*TOTAL: Rp " . number_format($transaction->total_amount, 0, ',', '.') . "*\n";
        $msg .= "Metode: " . strtoupper($transaction->payment_method) . "\n";
        $msg .= "Bayar: Rp " . number_format($transaction->cash_received, 0, ',', '.') . "\n";
        $msg .= "Kembalian: Rp " . number_format($transaction->cash_change, 0, ',', '.') . "\n\n";
        $msg .= "Terima kasih telah berbelanja di {$storeName}!\n";
        if ($phoneStore) {
            $msg .= "Layanan Pelanggan: {$phoneStore}\n";
        }

        return $this->sendMessage($phoneNumber, $msg, 'receipt');
    }

    /**
     * Dispatcher utama WhatsApp: Menangani WhatsApp Cloud API & fallback demo/direct link.
     */
    public function sendMessage(string $recipientPhone, string $content, string $messageType = 'test'): array
    {
        // Normalisasi nomor telepon (hilangkan tanda +, spasi, dash)
        $cleanPhone = preg_replace('/[^0-9]/', '', $recipientPhone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        }

        $token = WhatsappSetting::get('access_token');
        $phoneId = WhatsappSetting::get('phone_number_id');
        $endpoint = WhatsappSetting::get('api_endpoint', 'https://graph.facebook.com/v19.0');
        $demoMode = WhatsappSetting::get('demo_mode', '1') === '1';

        // Direct wa.me link URL
        $waMeUrl = "https://wa.me/{$cleanPhone}?text=" . urlencode($content);

        // Jika token belum diatur atau dalam demo mode, simpan log simulasi
        if ($demoMode || empty($token) || empty($phoneId)) {
            $log = WhatsappMessageLog::create([
                'recipient_phone' => $cleanPhone,
                'message_type' => $messageType,
                'content' => $content,
                'status' => 'demo_dispatched',
                'response_payload' => json_encode([
                    'mode' => 'demo_or_token_unconfigured',
                    'note' => 'Pesan tercatat di simulator. Klik tautan WhatsApp Direct untuk mengirim langsung ke nomor target via WhatsApp Web / App.',
                    'wa_link' => $waMeUrl,
                ]),
            ]);

            return [
                'success' => true,
                'mode' => 'demo',
                'message' => 'Pesan berhasil dicatat dalam Mode Demo / Siap dikirim via tautan langsung.',
                'wa_url' => $waMeUrl,
                'log_id' => $log->id,
                'content' => $content,
            ];
        }

        // Pengiriman via Meta Cloud API resmi
        try {
            $url = rtrim($endpoint, '/') . "/{$phoneId}/messages";
            $response = Http::withToken($token)
                ->timeout(10)
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to' => $cleanPhone,
                    'type' => 'text',
                    'text' => ['body' => $content],
                ]);

            $isSuccess = $response->successful();
            $status = $isSuccess ? 'sent' : 'failed';

            $log = WhatsappMessageLog::create([
                'recipient_phone' => $cleanPhone,
                'message_type' => $messageType,
                'content' => $content,
                'status' => $status,
                'response_payload' => $response->body(),
            ]);

            return [
                'success' => $isSuccess,
                'mode' => 'live_cloud_api',
                'status_code' => $response->status(),
                'message' => $isSuccess ? 'Pesan WhatsApp berhasil dikirim melalui Cloud API.' : 'Gagal mengirim melalui Cloud API.',
                'wa_url' => $waMeUrl,
                'log_id' => $log->id,
                'content' => $content,
            ];
        } catch (\Throwable $e) {
            Log::error('WhatsApp Cloud API Exception: ' . $e->getMessage());

            $log = WhatsappMessageLog::create([
                'recipient_phone' => $cleanPhone,
                'message_type' => $messageType,
                'content' => $content,
                'status' => 'failed',
                'response_payload' => json_encode(['error' => $e->getMessage()]),
            ]);

            return [
                'success' => false,
                'mode' => 'live_cloud_api',
                'error' => $e->getMessage(),
                'wa_url' => $waMeUrl,
                'log_id' => $log->id,
                'content' => $content,
            ];
        }
    }
}
