@extends('layouts.app')

@section('title', 'Laporan Penjualan Harian')
@section('page_title', 'Laporan Penjualan Harian')
@section('page_subtitle', 'Audit omzet harian, laba kotor, rincian pembayaran, dan distribusi transaksi per jam')

@section('topbar_actions')
<div class="flex items-center gap-2">
    <a href="{{ route('reports.daily.export', ['date' => $date]) }}" class="px-3 py-1.5 rounded-lg border border-[#E3E6E0] hover:bg-[#F0F1ED] text-xs font-medium text-[#303632] flex items-center gap-1.5 transition-colors">
        <i data-lucide="download" class="w-3.5 h-3.5"></i>
        <span>Ekspor Laporan (CSV)</span>
    </a>
    <form action="{{ route('whatsapp.daily_report') }}" method="POST" class="inline">
        @csrf
        <input type="hidden" name="date" value="{{ $date }}">
        <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#357653] hover:bg-[#2c5f43] text-white text-xs font-medium flex items-center gap-1.5 shadow-sm transition-colors">
            <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
            <span>Kirim Ringkasan ke WA Pemilik</span>
        </button>
    </form>
</div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- Date & Cashier Filter Bar -->
    <div class="bg-white rounded-lg border border-[#E3E6E0] p-4 shadow-xs">
        <form action="{{ route('reports.daily') }}" method="GET" class="flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5">
                    <label class="font-medium text-[#747B75]">Pilih Tanggal:</label>
                    <input type="date" name="date" value="{{ $date }}" 
                           class="px-2.5 py-1.5 rounded-lg border border-[#E3E6E0] text-xs text-[#303632] bg-[#FDFDFD]">
                </div>

                <div class="flex items-center gap-1.5">
                    <label class="font-medium text-[#747B75]">Kasir:</label>
                    <select name="cashier_id" class="px-2.5 py-1.5 rounded-lg border border-[#E3E6E0] text-xs text-[#303632]">
                        <option value="">Semua Kasir</option>
                        @foreach($cashiers as $c)
                        <option value="{{ $c->id }}" {{ $cashierId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#42634F] hover:bg-[#354F3F] text-white font-medium">
                    Tampilkan Laporan
                </button>
            </div>

            <div class="text-xs text-[#747B75]">
                Laporan untuk: <span class="font-bold text-[#303632]">{{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}</span>
            </div>
        </form>
    </div>

    <!-- 4 KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <div class="bg-white rounded-lg border border-[#E3E6E0] p-4 shadow-xs">
            <span class="text-xs font-medium text-[#747B75]">Total Penjualan Neto (Omzet)</span>
            <div class="text-xl font-bold text-[#303632] mt-1">
                Rp {{ number_format($netSales, 0, ',', '.') }}
            </div>
            <p class="text-[11px] text-[#747B75] mt-1">Bruto: Rp {{ number_format($grossSales, 0, ',', '.') }} | Diskon: Rp {{ number_format($discounts, 0, ',', '.') }}</p>
        </div>

        <div class="bg-white rounded-lg border border-[#E3E6E0] p-4 shadow-xs">
            <span class="text-xs font-medium text-[#747B75]">Estimasi Laba Kotor</span>
            <div class="text-xl font-bold text-[#357653] mt-1">
                Rp {{ number_format($grossProfit, 0, ',', '.') }}
            </div>
            <p class="text-[11px] text-[#747B75] mt-1">HPP Modal: Rp {{ number_format($totalCost, 0, ',', '.') }}</p>
        </div>

        <div class="bg-white rounded-lg border border-[#E3E6E0] p-4 shadow-xs">
            <span class="text-xs font-medium text-[#747B75]">Jumlah Transaksi Berhasil</span>
            <div class="text-xl font-bold text-[#303632] mt-1">
                {{ $transactionCount }} <span class="text-xs font-normal text-[#747B75]">struk</span>
            </div>
            <p class="text-[11px] text-[#747B75] mt-1">Rata-rata: Rp {{ number_format($aov, 0, ',', '.') }}</p>
        </div>

        <div class="bg-white rounded-lg border border-[#E3E6E0] p-4 shadow-xs">
            <span class="text-xs font-medium text-[#747B75]">Uang Kas Fisik di Laci</span>
            <div class="text-xl font-bold text-[#42634F] mt-1">
                Rp {{ number_format($cashSales, 0, ',', '.') }}
            </div>
            <p class="text-[11px] text-[#747B75] mt-1">Non-tunai (QRIS/Bank): Rp {{ number_format($qrisSales + $transferSales, 0, ',', '.') }}</p>
        </div>

    </div>

    <!-- Payment Method Distribution & Top Products -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Payment Breakdown Card -->
        <div class="bg-white rounded-lg border border-[#E3E6E0] p-5 shadow-xs">
            <h3 class="text-sm font-semibold text-[#303632] mb-3">Rincian Metode Pembayaran</h3>
            <div class="space-y-3 text-xs">
                <div class="p-3 rounded-lg bg-[#F7F7F4] border border-[#E3E6E0] flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="banknote" class="w-4 h-4 text-[#42634F]"></i>
                        <span class="font-medium text-[#303632]">Tunai (Cash)</span>
                    </div>
                    <span class="font-bold text-[#303632]">Rp {{ number_format($cashSales, 0, ',', '.') }}</span>
                </div>

                <div class="p-3 rounded-lg bg-[#F7F7F4] border border-[#E3E6E0] flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="qr-code" class="w-4 h-4 text-[#357653]"></i>
                        <span class="font-medium text-[#303632]">QRIS</span>
                    </div>
                    <span class="font-bold text-[#303632]">Rp {{ number_format($qrisSales, 0, ',', '.') }}</span>
                </div>

                <div class="p-3 rounded-lg bg-[#F7F7F4] border border-[#E3E6E0] flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="credit-card" class="w-4 h-4 text-[#747B75]"></i>
                        <span class="font-medium text-[#303632]">Transfer / Debit</span>
                    </div>
                    <span class="font-bold text-[#303632]">Rp {{ number_format($transferSales, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Top Selling Products Table (2 Cols) -->
        <div class="lg:col-span-2 bg-white rounded-lg border border-[#E3E6E0] shadow-xs overflow-hidden">
            <div class="p-4 border-b border-[#E3E6E0]">
                <h3 class="text-sm font-semibold text-[#303632]">Produk Terlaris Hari Ini</h3>
                <p class="text-xs text-[#747B75]">Daftar barang dengan volume penjualan tertinggi</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F0F1ED] text-[#747B75] uppercase text-[10px] font-semibold border-b border-[#E3E6E0]">
                        <tr>
                            <th class="px-4 py-2.5">Produk</th>
                            <th class="px-4 py-2.5 text-center">Volume</th>
                            <th class="px-4 py-2.5 text-right">Nilai Omzet</th>
                            <th class="px-4 py-2.5 text-right">Estimasi Laba</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E3E6E0]">
                        @forelse($topProducts as $item)
                        <tr class="hover:bg-[#F7F7F4]">
                            <td class="px-4 py-2.5">
                                <span class="font-medium text-[#303632]">{{ $item->product_name }}</span>
                                <span class="block text-[10px] font-mono text-[#747B75]">{{ $item->product_sku }}</span>
                            </td>
                            <td class="px-4 py-2.5 text-center font-bold text-[#303632]">{{ $item->total_qty }} unit</td>
                            <td class="px-4 py-2.5 text-right font-semibold text-[#303632]">Rp {{ number_format($item->total_revenue, 0, ',', '.') }}</td>
                            <td class="px-4 py-2.5 text-right font-semibold text-[#357653]">Rp {{ number_format($item->total_profit, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-xs text-[#747B75]">Belum ada data produk terjual hari ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Hourly Distribution Table & Chart -->
    <div class="bg-white rounded-lg border border-[#E3E6E0] p-5 shadow-xs">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-sm font-semibold text-[#303632]">Ringkasan Penjualan per Jam Operasional</h3>
                <p class="text-xs text-[#747B75]">Identifikasi waktu transaksi tersibuk pada toko</p>
            </div>
            @if($peakHour && $peakSales > 0)
            <div class="text-right">
                <span class="text-xs font-bold text-[#357653] bg-[#E6F4EA] px-2.5 py-1 rounded">
                    Jam Tersibuk: {{ $peakHour }} (Rp {{ number_format($peakSales, 0, ',', '.') }})
                </span>
            </div>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F0F1ED] text-[#747B75] uppercase text-[10px] font-semibold border-b border-[#E3E6E0]">
                    <tr>
                        <th class="px-4 py-2.5">Rentang Jam</th>
                        <th class="px-4 py-2.5 text-center">Jumlah Transaksi</th>
                        <th class="px-4 py-2.5 text-right">Nilai Omzet</th>
                        <th class="px-4 py-2.5">Visualisasi Beban</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E3E6E0]">
                    @foreach($hourlyReport as $hr)
                    @php
                        $barPct = $peakSales > 0 ? round(($hr['sales'] / $peakSales) * 100) : 0;
                    @endphp
                    <tr class="hover:bg-[#F7F7F4]">
                        <td class="px-4 py-2 font-mono text-[#303632]">{{ $hr['hour'] }}</td>
                        <td class="px-4 py-2 text-center font-medium text-[#303632]">{{ $hr['count'] }}</td>
                        <td class="px-4 py-2 text-right font-semibold text-[#303632]">Rp {{ number_format($hr['sales'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2">
                            <div class="w-full h-2 rounded-full bg-[#F0F1ED] overflow-hidden max-w-[200px]">
                                <div class="h-full {{ $hr['sales'] == $peakSales && $peakSales > 0 ? 'bg-[#357653]' : 'bg-[#42634F]' }}" style="width: {{ $barPct }}%"></div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
