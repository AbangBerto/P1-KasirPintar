@extends('layouts.app')

@section('title', 'Laporan Shift ' . $shift->shift_number)
@section('page_title', 'Laporan Rekonsiliasi Shift Kasir')
@section('page_subtitle', 'Bukti serah terima dan rekonsiliasi kas ' . $shift->shift_number)

@section('topbar_actions')
<div class="flex items-center gap-2">
    <button onclick="window.print()" class="no-print px-3 py-1.5 rounded-lg bg-[#303632] hover:bg-black text-white text-xs font-medium flex items-center gap-1.5 shadow-sm transition-colors">
        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
        <span>Cetak Laporan Shift</span>
    </button>
</div>
@endsection

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <!-- Shift Header Summary Card -->
    <div class="bg-white rounded-lg border border-[#E3E6E0] p-6 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-[#E3E6E0]">
            <div>
                <span class="text-[10px] uppercase font-bold tracking-wider text-[#747B75]">{{ $storeName }}</span>
                <h3 class="text-xl font-bold font-mono text-[#42634F]">{{ $shift->shift_number }}</h3>
                <p class="text-xs text-[#747B75] mt-0.5">Petugas Kasir: <span class="font-bold text-[#303632]">{{ $shift->user->name }}</span></p>
            </div>
            <div>
                @if($shift->isOpen())
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-[#E6F4EA] text-[#357653]">Shift Masih Buka</span>
                @else
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-[#F0F1ED] text-[#747B75]">Shift Selesai Ditutup</span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 text-xs">
            <div>
                <span class="text-[#747B75] block">Waktu Buka:</span>
                <span class="font-semibold text-[#303632]">{{ $shift->opened_at->format('d/m/Y H:i:s') }}</span>
            </div>
            <div>
                <span class="text-[#747B75] block">Waktu Tutup:</span>
                <span class="font-semibold text-[#303632]">{{ $shift->closed_at ? $shift->closed_at->format('d/m/Y H:i:s') : 'Masih Berjalan' }}</span>
            </div>
            <div>
                <span class="text-[#747B75] block">Jumlah Transaksi:</span>
                <span class="font-semibold text-[#303632]">{{ $shift->transactions->count() }} struk</span>
            </div>
            <div>
                <span class="text-[#747B75] block">Penjualan Non-Tunai:</span>
                <span class="font-semibold text-[#303632]">Rp {{ number_format($shift->non_cash_sales, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- Reconciliation Formula Table -->
    <div class="bg-white rounded-lg border border-[#E3E6E0] p-6 shadow-xs space-y-4">
        <h4 class="text-sm font-bold text-[#303632] pb-2 border-b border-[#E3E6E0]">Rincian Arus Kas Fisik & Rekonsiliasi</h4>

        <div class="space-y-2 text-xs">
            <div class="flex justify-between py-1.5 border-b border-[#E3E6E0]/60">
                <span class="text-[#747B75]">1. Saldo Modal Awal Laci:</span>
                <span class="font-semibold text-[#303632]">Rp {{ number_format($shift->starting_cash, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-[#E3E6E0]/60">
                <span class="text-[#747B75]">2. Total Penjualan Tunai:</span>
                <span class="font-semibold text-[#357653]">+Rp {{ number_format($shift->cash_sales, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-[#E3E6E0]/60">
                <span class="text-[#747B75]">3. Kas Masuk (Penambahan Uang Modal):</span>
                <span class="font-semibold text-[#357653]">+Rp {{ number_format($shift->cash_in, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-[#E3E6E0]/60">
                <span class="text-[#747B75]">4. Kas Keluar (Petty Cash Operasional):</span>
                <span class="font-semibold text-[#B84B4B]">-Rp {{ number_format($shift->cash_out, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-[#E3E6E0]/60">
                <span class="text-[#747B75]">5. Refund Penjualan Tunai:</span>
                <span class="font-semibold text-[#B84B4B]">-Rp {{ number_format($shift->cash_refunds, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between py-2 text-sm font-bold bg-[#F0F1ED] px-3 rounded-lg">
                <span class="text-[#303632]">Saldo Kas Yang Seharusnya (Ekspektasi):</span>
                <span class="text-[#42634F]">Rp {{ number_format($shift->expected_cash, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between py-2 text-sm font-bold bg-[#F7F7F4] px-3 rounded-lg border border-[#E3E6E0]">
                <span class="text-[#303632]">Saldo Kas Fisik Hasil Hitungan Kasir:</span>
                <span class="text-[#303632]">{{ $shift->actual_cash !== null ? 'Rp ' . number_format($shift->actual_cash, 0, ',', '.') : '-' }}</span>
            </div>
            @if($shift->difference !== null)
            <div class="flex justify-between py-2 text-sm font-bold px-3 rounded-lg 
                {{ $shift->difference == 0 ? 'bg-[#E6F4EA] text-[#357653]' : ($shift->difference > 0 ? 'bg-[#FEF7E0] text-[#A66D24]' : 'bg-[#FCE8E6] text-[#B84B4B]') }}">
                <span>Selisih Kas Rekonsiliasi:</span>
                <span>
                    @if($shift->difference == 0)
                        Rp 0 (Sempurna / Pas)
                    @elseif($shift->difference > 0)
                        Lebih +Rp {{ number_format($shift->difference, 0, ',', '.') }}
                    @else
                        Kurang -Rp {{ number_format(abs($shift->difference), 0, ',', '.') }}
                    @endif
                </span>
            </div>
            @endif
        </div>

        @if($shift->notes)
        <div class="pt-2 text-xs text-[#747B75] bg-[#F7F7F4] p-3 rounded border border-[#E3E6E0]">
            <span class="font-bold text-[#303632]">Catatan Petugas:</span> {{ $shift->notes }}
        </div>
        @endif
    </div>

    <!-- Signatures for Physical Accountability -->
    <div class="bg-white rounded-lg border border-[#E3E6E0] p-6 shadow-xs">
        <h4 class="text-xs font-bold uppercase tracking-wider text-[#747B75] mb-8 text-center">Lembar Tanda Tangan Serah Terima Kasir</h4>
        <div class="grid grid-cols-2 gap-8 text-center text-xs">
            <div>
                <div class="h-16 border-b border-dashed border-[#747B75]/60 mb-2"></div>
                <p class="font-semibold text-[#303632]">{{ $shift->user->name }}</p>
                <p class="text-[11px] text-[#747B75]">Kasir Bertugas</p>
            </div>
            <div>
                <div class="h-16 border-b border-dashed border-[#747B75]/60 mb-2"></div>
                <p class="font-semibold text-[#303632]">Supervisor / Admin Toko</p>
                <p class="text-[11px] text-[#747B75]">Pemeriksa Rekonsiliasi</p>
            </div>
        </div>
    </div>

</div>
@endsection
