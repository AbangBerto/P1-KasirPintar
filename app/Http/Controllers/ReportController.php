<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function daily(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $cashierId = $request->input('cashier_id');
        $targetDate = Carbon::parse($date);

        $query = Transaction::with(['items', 'user', 'customer'])
            ->whereDate('transaction_date', $targetDate)
            ->where('payment_status', 'paid');

        if ($cashierId) {
            $query->where('user_id', $cashierId);
        }

        $transactions = $query->get();

        $grossSales = (float) $transactions->sum('subtotal');
        $discounts = (float) $transactions->sum('discount_amount');
        $taxes = (float) $transactions->sum('tax_amount');
        $netSales = (float) $transactions->sum('total_amount');
        $totalCost = (float) $transactions->sum('total_cost');
        $grossProfit = (float) $transactions->sum('gross_profit');
        $transactionCount = $transactions->count();
        $aov = $transactionCount > 0 ? round($netSales / $transactionCount) : 0;

        // Payment breakdown
        $cashSales = (float) $transactions->where('payment_method', 'cash')->sum('total_amount');
        $qrisSales = (float) $transactions->where('payment_method', 'qris')->sum('total_amount');
        $transferSales = (float) $transactions->where('payment_method', 'transfer')->sum('total_amount');

        // Top products for this day
        $topProducts = TransactionItem::whereHas('transaction', function ($q) use ($targetDate, $cashierId) {
            $q->whereDate('transaction_date', $targetDate)->where('payment_status', 'paid');
            if ($cashierId) $q->where('user_id', $cashierId);
        })
            ->select('product_name', 'product_sku', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_revenue'), DB::raw('SUM(profit) as total_profit'))
            ->groupBy('product_name', 'product_sku')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        // Hourly breakdown (08:00 - 22:00)
        $hourlyReport = [];
        $peakHour = null;
        $peakSales = 0;

        for ($h = 8; $h <= 22; $h++) {
            $hourTransactions = $transactions->filter(function ($t) use ($h) {
                return (int) $t->transaction_date->format('H') === $h;
            });

            $hSales = (float) $hourTransactions->sum('total_amount');
            $hCount = $hourTransactions->count();

            if ($hSales > $peakSales) {
                $peakSales = $hSales;
                $peakHour = sprintf('%02d:00 - %02d:59', $h, $h);
            }

            $hourlyReport[] = [
                'hour' => sprintf('%02d:00 - %02d:59', $h, $h),
                'hour_num' => $h,
                'count' => $hCount,
                'sales' => $hSales,
            ];
        }

        $cashiers = User::where('is_active', true)->get();

        return view('reports.daily', compact(
            'date',
            'cashierId',
            'cashiers',
            'grossSales',
            'discounts',
            'taxes',
            'netSales',
            'totalCost',
            'grossProfit',
            'transactionCount',
            'aov',
            'cashSales',
            'qrisSales',
            'transferSales',
            'topProducts',
            'hourlyReport',
            'peakHour',
            'peakSales'
        ));
    }

    public function periodic(Request $request)
    {
        $period = $request->input('period', '7days'); // '7days', 'this_month', 'last_month', 'custom'
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($period === 'this_month') {
            $from = Carbon::today()->startOfMonth();
            $to = Carbon::today()->endOfDay();
            $title = 'Bulan Ini (' . Carbon::today()->translatedFormat('F Y') . ')';
        } elseif ($period === 'last_month') {
            $from = Carbon::today()->subMonth()->startOfMonth();
            $to = Carbon::today()->subMonth()->endOfMonth();
            $title = 'Bulan Lalu (' . $from->translatedFormat('F Y') . ')';
        } elseif ($period === 'custom' && $startDate && $endDate) {
            $from = Carbon::parse($startDate)->startOfDay();
            $to = Carbon::parse($endDate)->endOfDay();
            $title = $from->format('d/m/Y') . ' - ' . $to->format('d/m/Y');
        } else {
            $period = '7days';
            $from = Carbon::today()->subDays(6)->startOfDay();
            $to = Carbon::today()->endOfDay();
            $title = '7 Hari Terakhir';
        }

        $transactions = Transaction::where('payment_status', 'paid')
            ->whereBetween('transaction_date', [$from, $to])
            ->get();

        $totalSales = (float) $transactions->sum('total_amount');
        $totalCost = (float) $transactions->sum('total_cost');
        $totalProfit = (float) $transactions->sum('gross_profit');
        $totalCount = $transactions->count();
        $aov = $totalCount > 0 ? round($totalSales / $totalCount) : 0;

        // Daily trend data points for chart
        $chartDates = [];
        $chartSales = [];
        $chartProfits = [];
        $chartCounts = [];

        $current = clone $from;
        while ($current <= $to) {
            $dateStr = $current->toDateString();
            $chartDates[] = $current->format('d M');

            $dayTrx = $transactions->filter(function ($t) use ($dateStr) {
                return $t->transaction_date->toDateString() === $dateStr;
            });

            $chartSales[] = (float) $dayTrx->sum('total_amount');
            $chartProfits[] = (float) $dayTrx->sum('gross_profit');
            $chartCounts[] = $dayTrx->count();

            $current->addDay();
        }

        // Top categories
        $topCategories = TransactionItem::whereHas('transaction', function ($q) use ($from, $to) {
            $q->where('payment_status', 'paid')->whereBetween('transaction_date', [$from, $to]);
        })
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select('categories.name as category_name', DB::raw('SUM(transaction_items.quantity) as total_qty'), DB::raw('SUM(transaction_items.subtotal) as total_revenue'))
            ->groupBy('categories.name')
            ->orderByDesc('total_revenue')
            ->get();

        return view('reports.periodic', compact(
            'period',
            'startDate',
            'endDate',
            'title',
            'totalSales',
            'totalCost',
            'totalProfit',
            'totalCount',
            'aov',
            'chartDates',
            'chartSales',
            'chartProfits',
            'chartCounts',
            'topCategories'
        ));
    }

    public function exportDailyCsv(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $targetDate = Carbon::parse($date);

        $transactions = Transaction::with(['items', 'user'])
            ->whereDate('transaction_date', $targetDate)
            ->where('payment_status', 'paid')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"laporan_harian_{$date}.csv\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($transactions, $date) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ["LAPORAN PENJUALAN HARIAN — KASIR PINTAR"]);
            fputcsv($handle, ["Tanggal:", $date]);
            fputcsv($handle, []);
            fputcsv($handle, ['No. Invoice', 'Waktu', 'Kasir', 'Metode Bayar', 'Total Belanja', 'HPP (Modal)', 'Laba Kotor']);

            foreach ($transactions as $t) {
                fputcsv($handle, [
                    $t->invoice_number,
                    $t->transaction_date->format('H:i:s'),
                    $t->user->name ?? '-',
                    strtoupper($t->payment_method),
                    $t->total_amount,
                    $t->total_cost,
                    $t->gross_profit,
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['TOTAL:', '', '', '', $transactions->sum('total_amount'), $transactions->sum('total_cost'), $transactions->sum('gross_profit')]);

            fclose($handle);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
