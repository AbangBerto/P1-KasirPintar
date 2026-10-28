@extends('layouts.app')

@section('title', 'Laporan Tren & Penjualan Berkala')
@section('page_title', 'Laporan Tren & Periode')
@section('page_subtitle', 'Analisis komparatif tren omzet mingguan/bulanan dan performa kategori produk')

@section('content')
<div class="space-y-6">

    <!-- Filter Buttons Bar -->
    <div class="bg-white rounded-lg border border-[#E3E6E0] p-4 shadow-xs flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-1.5">
            <span class="text-[#747B75] mr-1">Pilihan Periode:</span>
            <a href="{{ route('reports.periodic', ['period' => '7days']) }}" 
               class="px-3 py-1.5 rounded-lg font-medium transition-colors {{ $period === '7days' ? 'bg-[#42634F] text-white' : 'hover:bg-[#F0F1ED] text-[#303632]' }}">
                7 Hari Terakhir
            </a>
            <a href="{{ route('reports.periodic', ['period' => 'this_month']) }}" 
               class="px-3 py-1.5 rounded-lg font-medium transition-colors {{ $period === 'this_month' ? 'bg-[#42634F] text-white' : 'hover:bg-[#F0F1ED] text-[#303632]' }}">
                Bulan Ini
            </a>
            <a href="{{ route('reports.periodic', ['period' => 'last_month']) }}" 
               class="px-3 py-1.5 rounded-lg font-medium transition-colors {{ $period === 'last_month' ? 'bg-[#42634F] text-white' : 'hover:bg-[#F0F1ED] text-[#303632]' }}">
                Bulan Lalu
            </a>
        </div>

        <form action="{{ route('reports.periodic') }}" method="GET" class="flex items-center gap-2">
            <input type="hidden" name="period" value="custom">
            <div class="flex items-center gap-1 text-[#747B75]">
                <input type="date" name="start_date" value="{{ $startDate }}" class="px-2 py-1 rounded border border-[#E3E6E0] text-xs text-[#303632]">
                <span>-</span>
                <input type="date" name="end_date" value="{{ $endDate }}" class="px-2 py-1 rounded border border-[#E3E6E0] text-xs text-[#303632]">
            </div>
            <button type="submit" class="px-3 py-1 rounded bg-[#F0F1ED] hover:bg-[#E3E6E0] text-[#303632] font-medium">Terapkan</button>
        </form>
    </div>

    <!-- 4 KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg border border-[#E3E6E0] p-4 shadow-xs">
            <span class="text-xs font-medium text-[#747B75]">Total Omzet Periode Ini</span>
            <div class="text-xl font-bold text-[#303632] mt-1">Rp {{ number_format($totalSales, 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-lg border border-[#E3E6E0] p-4 shadow-xs">
            <span class="text-xs font-medium text-[#747B75]">Estimasi Laba Kotor</span>
            <div class="text-xl font-bold text-[#357653] mt-1">Rp {{ number_format($totalProfit, 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-lg border border-[#E3E6E0] p-4 shadow-xs">
            <span class="text-xs font-medium text-[#747B75]">Total Transaksi</span>
            <div class="text-xl font-bold text-[#303632] mt-1">{{ $totalCount }} <span class="text-xs font-normal text-[#747B75]">struk</span></div>
        </div>
        <div class="bg-white rounded-lg border border-[#E3E6E0] p-4 shadow-xs">
            <span class="text-xs font-medium text-[#747B75]">Rata-Rata Transaksi (AOV)</span>
            <div class="text-xl font-bold text-[#42634F] mt-1">Rp {{ number_format($aov, 0, ',', '.') }}</div>
        </div>
    </div>

    <!-- Trend Chart -->
    <div class="bg-white rounded-lg border border-[#E3E6E0] p-5 shadow-xs">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-sm font-semibold text-[#303632]">Tren Penjualan & Laba Harian</h3>
                <p class="text-xs text-[#747B75]">{{ $title }}</p>
            </div>
            <div class="flex items-center gap-3 text-xs">
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-[#42634F]"></span> Omzet Penjualan</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-[#357653]"></span> Laba Kotor</span>
            </div>
        </div>
        <div class="relative h-72 w-full">
            <canvas id="periodicTrendChart"></canvas>
        </div>
    </div>

    <!-- Category Contribution Table -->
    <div class="bg-white rounded-lg border border-[#E3E6E0] shadow-xs overflow-hidden">
        <div class="p-4 border-b border-[#E3E6E0]">
            <h3 class="text-sm font-semibold text-[#303632]">Kontribusi Penjualan per Kategori</h3>
            <p class="text-xs text-[#747B75]">Performa penjualan masing-masing kategori barang pada periode terpilih</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F0F1ED] text-[#747B75] uppercase text-[10px] font-semibold border-b border-[#E3E6E0]">
                    <tr>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3 text-center">Volume Barang Terjual</th>
                        <th class="px-4 py-3 text-right">Nilai Omzet</th>
                        <th class="px-4 py-3 text-right">Pangsa Penjualan (%)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E3E6E0]">
                    @forelse($topCategories as $cat)
                    @php
                        $pct = $totalSales > 0 ? round(($cat->total_revenue / $totalSales) * 100, 1) : 0;
                    @endphp
                    <tr class="hover:bg-[#F7F7F4]">
                        <td class="px-4 py-3 font-semibold text-[#303632]">{{ $cat->category_name }}</td>
                        <td class="px-4 py-3 text-center text-[#747B75]">{{ $cat->total_qty }} unit</td>
                        <td class="px-4 py-3 text-right font-bold text-[#303632]">Rp {{ number_format($cat->total_revenue, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-[#42634F]">{{ $pct }}%</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-xs text-[#747B75]">Belum ada penjualan pada periode ini.</td>
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
        const ctx = document.getElementById('periodicTrendChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($chartDates) !!},
                    datasets: [
                        {
                            label: 'Omzet (Rp)',
                            data: {!! json_encode($chartSales) !!},
                            borderColor: '#42634F',
                            backgroundColor: 'rgba(66, 99, 79, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3,
                        },
                        {
                            label: 'Laba Kotor (Rp)',
                            data: {!! json_encode($chartProfits) !!},
                            borderColor: '#357653',
                            borderDash: [5, 5],
                            borderWidth: 2,
                            fill: false,
                            tension: 0.3,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: {
                            grid: { color: '#E3E6E0' },
                            ticks: {
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
