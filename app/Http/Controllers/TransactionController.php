<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use App\Services\PosService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    protected PosService $posService;

    public function __construct(PosService $posService)
    {
        $this->posService = $posService;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $cashierId = $request->input('cashier_id');
        $paymentMethod = $request->input('payment_method');
        $status = $request->input('status');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Transaction::with(['user', 'customer', 'items']);

        // Jika user adalah kasir dan bukan admin, kasir hanya melihat transaksi shift-nya atau miliknya sendiri
        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        } elseif ($cashierId) {
            $query->where('user_id', $cashierId);
        }

        if ($search) {
            $query->where('invoice_number', 'LIKE', "%{$search}%");
        }

        if ($paymentMethod) {
            $query->where('payment_method', $paymentMethod);
        }

        if ($status) {
            $query->where('payment_status', $status);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('transaction_date', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        }

        $transactions = $query->latest('transaction_date')->paginate(15)->withQueryString();
        $cashiers = User::where('is_active', true)->get();

        return view('transactions.index', compact(
            'transactions',
            'cashiers',
            'search',
            'cashierId',
            'paymentMethod',
            'status',
            'startDate',
            'endDate'
        ));
    }

    public function show($id)
    {
        $transaction = Transaction::with(['user', 'customer', 'items.product', 'voidedByUser', 'cashShift'])->findOrFail($id);
        return view('transactions.show', compact('transaction'));
    }

    public function void(Request $request, $id)
    {
        $request->validate([
            'void_reason' => 'required|string|min:5|max:255',
        ]);

        $transaction = Transaction::findOrFail($id);

        try {
            $this->posService->voidTransaction($transaction, $request->input('void_reason'), auth()->user());
            return redirect()->route('transactions.show', $transaction->id)->with('success', "Transaksi {$transaction->invoice_number} berhasil dibatalkan (void). Stok barang telah dikembalikan.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function exportCsv(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());

        $transactions = Transaction::with(['user', 'customer', 'items'])
            ->whereBetween('transaction_date', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ])
            ->latest('transaction_date')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="riwayat_transaksi_' . date('Ymd_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            $columns = ['No. Invoice', 'Tanggal & Waktu', 'Kasir', 'Pelanggan', 'Metode Bayar', 'Status', 'Subtotal', 'Diskon', 'Pajak', 'Total Belanja'];
            if (auth()->user()->isAdmin()) {
                $columns[] = 'Total HPP';
                $columns[] = 'Laba Kotor';
            }
            fputcsv($handle, $columns);

            foreach ($transactions as $t) {
                $row = [
                    $t->invoice_number,
                    $t->transaction_date->format('Y-m-d H:i:s'),
                    $t->user->name ?? '-',
                    $t->customer->name ?? 'Umum',
                    strtoupper($t->payment_method),
                    strtoupper($t->payment_status),
                    $t->subtotal,
                    $t->discount_amount,
                    $t->tax_amount,
                    $t->total_amount,
                ];

                if (auth()->user()->isAdmin()) {
                    $row[] = $t->total_cost;
                    $row[] = $t->gross_profit;
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
