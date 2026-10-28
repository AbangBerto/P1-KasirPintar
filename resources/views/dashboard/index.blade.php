@extends('layouts.app')

@section('title', 'Dashboard Toko')
@section('page_title', 'Dashboard Ringkasan Toko')
@section('page_subtitle', 'Pemantauan omzet, laba kotor, pergerakan produk, dan transaksi real-time')

@section('topbar_actions')
<div class="flex items-center gap-2">
    <a href="{{ route('pos.index') }}" class="px-3 py-1.5 rounded-lg bg-[#42634F] hover:bg-[#354F3F] text-white text-xs font-medium flex items-center gap-1.5 shadow-sm transition-colors">
        <i data-lucide="calculator" class="w-3.5 h-3.5"></i>
        <span>Buka Kasir POS</span>
    </a>
</div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- Active Shift Status Bar -->
    @if($activeShift)
    <div class="bg-white rounded-lg border border-[#E3E6E0] p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-3">
            <div class="w-3 h-3 rounded-full bg-[#357653] animate-pulse"></div>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-[#303632]">Shift Kasir Aktif:</span>
                    <span class="text-xs font-mono font-medium text-[#42634F]">{{ $activeShift->shift_number }}</span>
                    <span class="text-[11px] px-2 py-0.5 rounded bg-[#E6F4EA] text-[#357653] font-medium">Buka Sejak {{ $activeShift->opened_at->format('H:i') }} WIB</span>
                </div>
                <p class="text-[11px] text-[#747B75] mt-0.5">Petugas: {{ $activeShift->user->name }} | Modal Awal Laci: <span class="font-medium text-[#303632]">Rp {{ number_format($activeShift->starting_cash, 0, ',', '.') }}</span></p>
            </div>
        </div>
        <div class="flex items-center gap-2 self-end sm:self-auto">
            <a href="{{ route('shifts.index') }}" class="px-2.5 py-1.5 rounded text-xs font-medium border border-[#E3E6E0] hover:bg-[#F0F1ED] text-[#303632] transition-colors">
                Kelola Shift
            </a>
            <a href="{{ route('pos.index') }}" class="px-2.5 py-1.5 rounded text-xs font-medium bg-[#42634F] hover:bg-[#354F3F] text-white transition-colors">
                Mulai Transaksi
            </a>
        </div>
    </div>
    @else
    <div class="bg-[#FEF7E0] rounded-lg border border-[#A66D24]/20 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-[#A66D24]">
        <div class="flex items-center gap-2.5">
            <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
            <span>Saat ini belum ada shift kasir yang dibuka. Buka shift kasir untuk memulai pencatatan uang tunai.</span>
        </div>
        <a href="{{ route('shifts.index') }}" class="px-3 py-1.5 rounded bg-[#A66D24] text-white font-medium hover:bg-[#8f5e1f] text-center self-start sm:self-auto transition-colors">
            Buka Shift Sekarang
        </a>
    </div>
    @endif

    <!-- Period Filter Bar -->
    <div class="bg-white rounded-lg border border-[#E3E6E0] p-3 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-1.5">
            <span class="text-xs font-medium text-[#747B75] mr-1">Periode:</span>
            <a href="{{ route('dashboard', ['period' => 'today']) }}" 
               class="px-2.5 py-1 rounded text-xs font-medium transition-colors {{ $period === 'today' ? 'bg-[#42634F] text-white' : 'hover:bg-[#F0F1ED] text-[#303632]' }}">
                Hari Ini
            </a>
            <a href="{{ route('dashboard', ['period' => '7days']) }}" 
               class="px-2.5 py-1 rounded text-xs font-medium transition-colors {{ $period === '7days' ? 'bg-[#42634F] text-white' : 'hover:bg-[#F0F1ED] text-[#303632]' }}">
                7 Hari Terakhir
            </a>
            <a href="{{ route('dashboard', ['period' => '30days']) }}" 
               class="px-2.5 py-1 rounded text-xs font-medium transition-colors {{ $period === '30days' ? 'bg-[#42634F] text-white' : 'hover:bg-[#F0F1ED] text-[#303632]' }}">
                30 Hari Terakhir
            </a>
        </div>

        <form action="{{ route('dashboard') }}" method="GET" class="flex items-center gap-2">
            <input type="hidden" name="period" value="custom">
            <div class="flex items-center gap-1.5 text-xs text-[#747B75]">
                <span>Rentang:</span>
                <input type="date" name="start_date" value="{{ $startDate ?? \Carbon\Carbon::today()->format('Y-m-d') }}" 
                       class="px-2 py-1 rounded border border-[#E3E6E0] text-xs text-[#303632] bg-[#FDFDFD]">
                <span>-</span>
                <input type="date" name="end_date" value="{{ $endDate ?? \Carbon\Carbon::today()->format('Y-m-d') }}" 
                       class="px-2 py-1 rounded border border-[#E3E6E0] text-xs text-[#303632] bg-[#FDFDFD]">
            </div>
            <button type="submit" class="px-2.5 py-1 rounded bg-[#F0F1ED] hover:bg-[#E3E6E0] text-xs font-medium text-[#303632]">
                Terapkan
            </button>
        </form>
    </div>

    <!-- 4 KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- 1. Omzet Penjualan -->
        <div class="bg-white rounded-lg border border-[#E3E6E0] p-4 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-[#747B75]">Total Omzet (Penjualan Neto)</span>
                <span class="w-7 h-7 rounded bg-[#DCE8DE] text-[#42634F] flex items-center justify-center">
                    <i data-lucide="wallet" class="w-3.5 h-3.5"></i>
                </span>
            </div>
            <div class="text-xl font-bold text-[#303632]">
                Rp {{ number_format($totalOmzet, 0, ',', '.') }}
            </div>
            <div class="mt-2 flex items-center gap-1.5 text-xs">
                @if($omzetGrowth !== null)
                    @if($omzetGrowth >= 0)
                        <span class="text-[#357653] font-medium flex items-center">
                            <i data-lucide="trending-up" class="w-3 h-3 mr-0.5"></i> +{{ $omzetGrowth }}%
                        </span>
                    @else
                        <span class="text-[#B84B4B] font-medium flex items-center">
                            <i data-lucide="trending-down" class="w-3 h-3 mr-0.5"></i> {{ $omzetGrowth }}%
                        </span>
                    @endif
                    <span class="text-[#747B75]">vs periode lalu</span>
                @else
                    <span class="text-[#747B75]">Data periode aktif</span>
                @endif
            </div>
        </div>

        <!-- 2. Transaksi Berhasil -->
        <div class="bg-white rounded-lg border border-[#E3E6E0] p-4 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-[#747B75]">Transaksi Berhasil</span>
                <span class="w-7 h-7 rounded bg-[#DCE8DE] text-[#42634F] flex items-center justify-center">
                    <i data-lucide="shopping-bag" class="w-3.5 h-3.5"></i>
                </span>
            </div>
            <div class="text-xl font-bold text-[#303632]">
                {{ $transactionCount }} <span class="text-xs font-normal text-[#747B75]">transaksi</span>
            </div>
            <div class="mt-2 flex items-center gap-1.5 text-xs">
                @if($countGrowth !== null)
                    @if($countGrowth >= 0)
                        <span class="text-[#357653] font-medium">+{{ $countGrowth }}%</span>
                    @else
                        <span class="text-[#B84B4B] font-medium">{{ $countGrowth }}%</span>
                    @endif
                    <span class="text-[#747B75]">vs periode lalu</span>
                @else
                    <span class="text-[#747B75]">Total transaksi lunas</span>
                @endif
            </div>
        </div>

        <!-- 3. Rata-Rata Transaksi (AOV) -->
        <div class="bg-white rounded-lg border border-[#E3E6E0] p-4 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-[#747B75]">Rata-Rata Transaksi (AOV)</span>
                <span class="w-7 h-7 rounded bg-[#DCE8DE] text-[#42634F] flex items-center justify-center">
                    <i data-lucide="receipt" class="w-3.5 h-3.5"></i>
                </span>
            </div>
            <div class="text-xl font-bold text-[#303632]">
                Rp {{ number_format($averageOrderValue, 0, ',', '.') }}
            </div>
            <div class="mt-2 text-xs text-[#747B75]">
                Nilai belanja rata-rata per struk
            </div>
        </div>

        <!-- 4. Estimasi Laba Kotor -->
        <div class="bg-white rounded-lg border border-[#E3E6E0] p-4 shadow-xs">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-[#747B75]">Estimasi Laba Kotor</span>
                <span class="w-7 h-7 rounded bg-[#DCE8DE] text-[#42634F] flex items-center justify-center">
                    <i data-lucide="circle-dollar-sign" class="w-3.5 h-3.5"></i>
                </span>
            </div>
            <div class="text-xl font-bold text-[#357653]">
                Rp {{ number_format($grossProfit, 0, ',', '.') }}
            </div>
            <div class="mt-2 text-xs text-[#747B75]">
                Omzet dikurangi HPP barang terjual
            </div>
        </div>

    </div>

    <!-- Charts & Analytics Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left 2 Cols: Hourly Sales Chart -->
        <div class="lg:col-span-2 bg-white rounded-lg border border-[#E3E6E0] p-5 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-semibold text-[#303632]">Distribusi Penjualan per Jam</h3>
                    <p class="text-xs text-[#747B75]">Pola penjualan operasional toko (08.00 - 22.00)</p>
                </div>
                @if($peakHour && $peakHourSales > 0)
                <div class="text-right">
                    <span class="text-[11px] font-semibold text-[#357653] bg-[#E6F4EA] px-2 py-0.5 rounded">
                        Jam Tersibuk: {{ $peakHour }}
                    </span>
                    <p class="text-[10px] text-[#747B75] mt-0.5">Omzet: Rp {{ number_format($peakHourSales, 0, ',', '.') }}</p>
                </div>
                @endif
            </div>

            <div class="relative h-64 w-full">
                <canvas id="hourlySalesChart"></canvas>
            </div>
        </div>

        <!-- Right 1 Col: Payment Methods & Smart Insights -->
        <div class="space-y-6">
            
            <!-- Payment Method Breakdown -->
            <div class="bg-white rounded-lg border border-[#E3E6E0] p-5 shadow-xs">
                <h3 class="text-sm font-semibold text-[#303632] mb-3">Metode Pembayaran</h3>
                
                @php
                    $sumPay = $cashSales + $qrisSales + $transferSales;
                    $cashPct = $sumPay > 0 ? round(($cashSales / $sumPay) * 100, 1) : 0;
                    $qrisPct = $sumPay > 0 ? round(($qrisSales / $sumPay) * 100, 1) : 0;
                    $transferPct = $sumPay > 0 ? round(($transferSales / $sumPay) * 100, 1) : 0;
                @endphp

                <div class="space-y-3">
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-medium text-[#303632] flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#42634F]"></span> Tunai (Cash)
                            </span>
                            <span class="font-medium text-[#303632]">Rp {{ number_format($cashSales, 0, ',', '.') }} ({{ $cashPct }}%)</span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-[#F0F1ED] overflow-hidden">
                            <div class="h-full bg-[#42634F]" style="width: {{ $cashPct }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-medium text-[#303632] flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#357653]"></span> QRIS
                            </span>
                            <span class="font-medium text-[#303632]">Rp {{ number_format($qrisSales, 0, ',', '.') }} ({{ $qrisPct }}%)</span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-[#F0F1ED] overflow-hidden">
                            <div class="h-full bg-[#357653]" style="width: {{ $qrisPct }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-medium text-[#303632] flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#747B75]"></span> Transfer / Debit
                            </span>
                            <span class="font-medium text-[#303632]">Rp {{ number_format($transferSales, 0, ',', '.') }} ({{ $transferPct }}%)</span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-[#F0F1ED] overflow-hidden">
                            <div class="h-full bg-[#747B75]" style="width: {{ $transferPct }}%"></div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-[#E3E6E0] text-[11px] text-[#747B75]">
                    Penerimaan uang kas fisik di laci: <span class="font-semibold text-[#303632]">Rp {{ number_format($cashSales, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Smart Insights Widget -->
            <div class="bg-[#F0F1ED] rounded-lg border border-[#E3E6E0] p-4">
                <div class="flex items-center gap-2 mb-2 text-[#42634F]">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                    <h4 class="text-xs font-semibold uppercase tracking-wider">Analisis Pintar Toko</h4>
                </div>
                <div class="space-y-2 text-xs text-[#303632]">
                    @if($peakHour)
                    <p class="leading-relaxed">
                        • <span class="font-medium">Waktu tersibuk:</span> Volume transaksi tertinggi tercatat pada <span class="font-semibold">{{ $peakHour }}</span>. Pastikan kasir siap dan uang kembalian memadai.
                    </p>
                    @endif

                    @if($lowStockProducts->isNotEmpty())
                    <p class="leading-relaxed">
                        • <span class="font-medium">Rekomendasi Restok:</span> Terdapat <span class="font-semibold text-[#B84B4B]">{{ $lowStockProducts->count() }} produk</span> yang berada di bawah stok minimum. Segera buat pesanan pengadaan.
                    </p>
                    @else
                    <p class="leading-relaxed">
                        • <span class="font-medium">Stok Aman:</span> Seluruh persediaan produk berada di atas batas minimum.
                    </p>
                    @endif

                    @if($omzetGrowth !== null)
                    <p class="leading-relaxed">
                        • <span class="font-medium">Performa Omzet:</span> {{ $omzetGrowth >= 0 ? 'Meningkat ' . $omzetGrowth . '%' : 'Turun ' . abs($omzetGrowth) . '%' }} dibandingkan periode sebelumnya yang setara.
                    </p>
                    @endif
                </div>
            </div>

        </div>

    </div>

    <!-- Tables Row: Top Products & Low Stock Alerts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Top Products -->
        <div class="bg-white rounded-lg border border-[#E3E6E0] shadow-xs overflow-hidden">
            <div class="p-4 border-b border-[#E3E6E0] flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-[#303632]">Produk Terlaris</h3>
                    <p class="text-xs text-[#747B75]">Berdasarkan volume unit terjual periode ini</p>
                </div>
                <a href="{{ route('reports.daily') }}" class="text-xs text-[#42634F] hover:underline font-medium">Lihat Laporan</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F0F1ED] text-[#747B75] uppercase text-[10px] font-semibold border-b border-[#E3E6E0]">
                        <tr>
                            <th class="px-4 py-2.5">Produk</th>
                            <th class="px-4 py-2.5 text-center">Terjual</th>
                            <th class="px-4 py-2.5 text-right">Total Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E3E6E0]">
                        @forelse($topProducts as $item)
                        <tr class="hover:bg-[#F7F7F4] transition-colors">
                            <td class="px-4 py-3">
                                <p class="font-medium text-[#303632]">{{ $item->product_name }}</p>
                                <p class="text-[10px] text-[#747B75] font-mono">{{ $item->product_sku }}</p>
                            </td>
                            <td class="px-4 py-3 text-center font-semibold text-[#303632]">
                                {{ $item->total_qty }} unit
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-[#303632]">
                                Rp {{ number_format($item->total_revenue, 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-xs text-[#747B75]">
                                Belum ada transaksi penjualan pada periode ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Low Stock Alerts -->
        <div class="bg-white rounded-lg border border-[#E3E6E0] shadow-xs overflow-hidden">
            <div class="p-4 border-b border-[#E3E6E0] flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-[#303632]">Peringatan Stok Menipis</h3>
                    <p class="text-xs text-[#747B75]">Produk yang mencapai atau berada di bawah batas minimum</p>
                </div>
                <a href="{{ route('inventory.index') }}" class="text-xs text-[#42634F] hover:underline font-medium">Kelola Stok</a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F0F1ED] text-[#747B75] uppercase text-[10px] font-semibold border-b border-[#E3E6E0]">
                        <tr>
                            <th class="px-4 py-2.5">Produk</th>
                            <th class="px-4 py-2.5 text-center">Sisa Stok</th>
                            <th class="px-4 py-2.5 text-center">Batas Min</th>
                            <th class="px-4 py-2.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E3E6E0]">
                        @forelse($lowStockProducts as $p)
                        <tr class="hover:bg-[#F7F7F4] transition-colors">
                            <td class="px-4 py-3">
                                <p class="font-medium text-[#303632]">{{ $p->name }}</p>
                                <p class="text-[10px] text-[#747B75]">{{ $p->category->name ?? '-' }}</p>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-[#FEF7E0] text-[#A66D24]">
                                    {{ $p->stock }} {{ $p->unit }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-[#747B75]">
                                {{ $p->min_stock }} {{ $p->unit }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('inventory.index', ['product_id' => $p->id]) }}" 
                                   class="px-2 py-1 rounded bg-[#F0F1ED] hover:bg-[#E3E6E0] text-[#303632] text-[11px] font-medium transition-colors">
                                    Restok
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-xs text-[#357653]">
                                <i data-lucide="check-circle" class="w-5 h-5 mx-auto mb-1 text-[#357653]"></i>
                                Semua stok produk saat ini dalam batas aman.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-lg border border-[#E3E6E0] shadow-xs overflow-hidden">
        <div class="p-4 border-b border-[#E3E6E0] flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-[#303632]">Transaksi Terbaru</h3>
                <p class="text-xs text-[#747B75]">Daftar aktivitas kasir terkini</p>
            </div>
            <a href="{{ route('transactions.index') }}" class="text-xs text-[#42634F] hover:underline font-medium">Lihat Semua Riwayat</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F0F1ED] text-[#747B75] uppercase text-[10px] font-semibold border-b border-[#E3E6E0]">
                    <tr>
                        <th class="px-4 py-2.5">No. Invoice</th>
                        <th class="px-4 py-2.5">Waktu</th>
                        <th class="px-4 py-2.5">Kasir</th>
                        <th class="px-4 py-2.5">Pelanggan</th>
                        <th class="px-4 py-2.5">Metode Bayar</th>
                        <th class="px-4 py-2.5 text-right">Total Belanja</th>
                        <th class="px-4 py-2.5 text-center">Status</th>
                        <th class="px-4 py-2.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E3E6E0]">
                    @forelse($recentTransactions as $trx)
                    <tr class="hover:bg-[#F7F7F4] transition-colors">
                        <td class="px-4 py-3 font-mono font-medium text-[#42634F]">
                            {{ $trx->invoice_number }}
                        </td>
                        <td class="px-4 py-3 text-[#747B75]">
                            {{ $trx->transaction_date->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-3 text-[#303632]">
                            {{ $trx->user->name ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-[#747B75]">
                            {{ $trx->customer->name ?? 'Pelanggan Umum' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="uppercase font-semibold text-[10px] px-1.5 py-0.5 rounded bg-[#F0F1ED] text-[#303632]">
                                {{ $trx->payment_method }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-[#303632]">
                            Rp {{ number_format($trx->total_amount, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($trx->payment_status === 'paid')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#E6F4EA] text-[#357653]">Lunas</span>
                            @elseif($trx->payment_status === 'void')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#FCE8E6] text-[#B84B4B]">Batal</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#FEF7E0] text-[#A66D24]">{{ $trx->payment_status }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('transactions.show', $trx->id) }}" class="text-[#42634F] hover:underline font-medium">
                                Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-xs text-[#747B75]">
                            Belum ada riwayat transaksi.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('hourlySalesChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($hourlyLabels) !!},
                    datasets: [{
                        label: 'Omzet (Rp)',
                        data: {!! json_encode($hourlyData) !!},
                        backgroundColor: '#42634F',
                        hoverBackgroundColor: '#354F3F',
                        borderRadius: 4,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Omzet: Rp ' + new Intl.NumberFormat('id-ID').format(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { family: 'Inter', size: 10 } }
                        },
                        y: {
                            grid: { color: '#E3E6E0' },
                            ticks: {
                                font: { family: 'Inter', size: 10 },
                                callback: function(value) {
                                    if (value >= 1000000) return (value / 1000000) + ' Jt';
                                    if (value >= 1000) return (value / 1000) + ' Rb';
                                    return value;
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
