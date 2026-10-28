<?php

namespace App\Http\Controllers;

use App\Models\CashShift;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->input('period', 'today'); // 'today', '7days', '30days', 'custom'
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $now = Carbon::now();

        if ($period === '7days') {
            $from = Carbon::today()->subDays(6)->startOfDay();
            $to = Carbon::today()->endOfDay();
            $prevFrom = Carbon::today()->subDays(13)->startOfDay();
            $prevTo = Carbon::today()->subDays(7)->endOfDay();
            $periodLabel = '7 Hari Terakhir';
        } elseif ($period === '30days') {
            $from = Carbon::today()->subDays(29)->startOfDay();
            $to = Carbon::today()->endOfDay();
            $prevFrom = Carbon::today()->subDays(59)->startOfDay();
            $prevTo = Carbon::today()->subDays(30)->endOfDay();
            $periodLabel = '30 Hari Terakhir';
        } elseif ($period === 'custom' && $startDate && $endDate) {
            $from = Carbon::parse($startDate)->startOfDay();
            $to = Carbon::parse($endDate)->endOfDay();
            $diffDays = $from->diffInDays($to) + 1;
            $prevFrom = (clone $from)->subDays($diffDays);
            $prevTo = (clone $from)->subSecond();
            $periodLabel = $from->format('d/m/Y') . ' - ' . $to->format('d/m/Y');
        } else {
            // Default: Hari ini
            $period = 'today';
            $from = Carbon::today()->startOfDay();
            $to = Carbon::today()->endOfDay();
            $prevFrom = Carbon::yesterday()->startOfDay();
            $prevTo = Carbon::yesterday()->endOfDay();
            $periodLabel = 'Hari Ini (' . Carbon::today()->translatedFormat('d M Y') . ')';
        }

        // Query transaksi periode aktif (paid)
        $transactions = Transaction::with('items')
            ->where('payment_status', 'paid')
            ->whereBetween('transaction_date', [$from, $to])
            ->get();

        // Periode pembanding (untuk perbandingan performa)
        $prevTransactions = Transaction::where('payment_status', 'paid')
            ->whereBetween('transaction_date', [$prevFrom, $prevTo])
            ->get();

        $totalOmzet = (float) $transactions->sum('total_amount');
        $prevOmzet = (float) $prevTransactions->sum('total_amount');
        $omzetGrowth = $prevOmzet > 0 ? round((($totalOmzet - $prevOmzet) / $prevOmzet) * 100, 1) : null;

        $transactionCount = $transactions->count();
        $prevCount = $prevTransactions->count();
        $countGrowth = $prevCount > 0 ? round((($transactionCount - $prevCount) / $prevCount) * 100, 1) : null;

        $averageOrderValue = $transactionCount > 0 ? round($totalOmzet / $transactionCount) : 0;
        $grossProfit = (float) $transactions->sum('gross_profit');

        // Total penerimaan kas tunai vs non-tunai
        $cashSales = (float) $transactions->where('payment_method', 'cash')->sum('total_amount');
        $qrisSales = (float) $transactions->where('payment_method', 'qris')->sum('total_amount');
        $transferSales = (float) $transactions->where('payment_method', 'transfer')->sum('total_amount');

        // Transaksi terbaru
        $recentTransactions = Transaction::with(['user', 'customer'])
            ->latest('transaction_date')
            ->limit(6)
            ->get();

        // Produk stok menipis & habis
        $lowStockProducts = Product::active()->lowStock()->with('category')->take(5)->get();
        $outOfStockCount = Product::active()->outOfStock()->count();

        // Produk terlaris periode ini
        $topProducts = TransactionItem::whereHas('transaction', function ($q) use ($from, $to) {
            $q->where('payment_status', 'paid')->whereBetween('transaction_date', [$from, $to]);
        })
            ->select('product_id', 'product_name', 'product_sku', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_revenue'))
            ->groupBy('product_id', 'product_name', 'product_sku')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // Data Grafik: Distribusi Penjualan per Jam (08:00 - 22:00)
        $hourlyLabels = [];
        $hourlyData = [];
        $peakHour = null;
        $peakHourSales = 0;

        for ($h = 8; $h <= 22; $h++) {
            $hourLabel = sprintf('%02d:00', $h);
            $hourlyLabels[] = $hourLabel;

            $hourSales = $transactions->filter(function ($t) use ($h) {
                return (int) $t->transaction_date->format('H') === $h;
            })->sum('total_amount');

            $hourlyData[] = (float) $hourSales;

            if ($hourSales > $peakHourSales) {
                $peakHourSales = $hourSales;
                $peakHour = sprintf('%02d:00 - %02d:59', $h, $h);
            }
        }

        // Shift kasir saat ini
        $activeShift = CashShift::where('status', 'open')->latest()->first();

        return view('dashboard.index', compact(
            'period',
            'periodLabel',
            'startDate',
            'endDate',
            'totalOmzet',
            'omzetGrowth',
            'transactionCount',
            'countGrowth',
            'averageOrderValue',
            'grossProfit',
            'cashSales',
            'qrisSales',
            'transferSales',
            'recentTransactions',
            'lowStockProducts',
            'outOfStockCount',
            'topProducts',
            'hourlyLabels',
            'hourlyData',
            'peakHour',
            'peakHourSales',
            'activeShift'
        ));
    }
}
