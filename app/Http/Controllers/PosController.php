<?php

namespace App\Http\Controllers;

use App\Models\CashShift;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\Transaction;
use App\Services\PosService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;

class PosController extends Controller
{
    protected PosService $posService;
    protected WhatsAppService $whatsAppService;

    public function __construct(PosService $posService, WhatsAppService $whatsAppService)
    {
        $this->posService = $posService;
        $this->whatsAppService = $whatsAppService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        // Cek status shift kasir
        $activeShift = CashShift::where('status', 'open')
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        // Jika tidak ada shift sendiri, cek apakah ada shift kasir lain yang terbuka
        if (!$activeShift) {
            $activeShift = CashShift::where('status', 'open')->latest()->first();
        }

        $categories = Category::where('is_active', true)->get();
        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        $taxPercentage = (float) StoreSetting::get('tax_percentage', '0');

        // Daftar produk aktif untuk POS
        $products = Product::active()
            ->with('category')
            ->orderBy('name')
            ->get(['id', 'category_id', 'sku', 'barcode', 'name', 'selling_price', 'stock', 'unit']);

        return view('pos.index', compact('categories', 'customers', 'products', 'activeShift', 'taxPercentage'));
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'customer_id' => 'nullable|exists:customers,id',
            'payment_method' => 'required|in:cash,qris,transfer',
            'cash_received' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $transaction = $this->posService->processCheckout($validated, auth()->user());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transaksi berhasil diselesaikan!',
                    'invoice_number' => $transaction->invoice_number,
                    'transaction_id' => $transaction->id,
                    'receipt_url' => route('pos.receipt', $transaction->id),
                    'total_amount' => $transaction->total_amount,
                    'cash_change' => $transaction->cash_change,
                ]);
            }

            return redirect()->route('pos.receipt', $transaction->id)
                ->with('success', "Transaksi {$transaction->invoice_number} berhasil diselesaikan!");
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function receipt($id)
    {
        $transaction = Transaction::with(['items.product', 'user', 'customer'])->findOrFail($id);
        $storeSettings = [
            'name' => StoreSetting::get('store_name', 'Kasir Pintar Minimarket'),
            'tagline' => StoreSetting::get('store_tagline', 'Belanja Hemat & Lengkap Setiap Hari'),
            'address' => StoreSetting::get('store_address', 'Jl. Merdeka No. 45 Jakarta'),
            'phone' => StoreSetting::get('store_phone', '0812-3456-7890'),
            'header' => StoreSetting::get('receipt_header', ''),
            'footer' => StoreSetting::get('receipt_footer', 'Terima kasih atas kunjungan Anda!'),
        ];

        return view('pos.receipt', compact('transaction', 'storeSettings'));
    }

    public function sendReceiptWhatsApp(Request $request, $id)
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        $transaction = Transaction::with(['items', 'user', 'customer'])->findOrFail($id);
        $result = $this->whatsAppService->sendReceipt($transaction, $request->input('phone'));

        return response()->json($result);
    }
}
