@extends('layouts.app')

@section('title', 'Manajemen Shift Kasir')
@section('page_title', 'Manajemen Shift Kasir & Rekonsiliasi')
@section('page_subtitle', 'Buka dan tutup laci kasir, kontrol uang receh modal, kas masuk/keluar, dan selisih kas')

@section('topbar_actions')
@if(!$activeShift)
<button @click="$dispatch('open-start-shift-modal')" class="px-3 py-1.5 rounded-lg bg-[#42634F] hover:bg-[#354F3F] text-white text-xs font-medium flex items-center gap-1.5 shadow-sm transition-colors">
    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
    <span>Buka Shift Baru</span>
</button>
@endif
@endsection

@section('content')
<div x-data="{ showOpenModal: false, showCloseModal: false, showMovementModal: false, movementType: 'cash_in' }" class="space-y-6">

    <!-- Active Shift Panel (If any shift is currently open) -->
    @if($activeShift)
    <div class="bg-white rounded-xl border-2 border-[#42634F]/30 p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-[#E3E6E0]">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#357653] animate-pulse"></span>
                    <span class="text-xs font-bold uppercase tracking-wider text-[#357653]">Shift Kasir Sedang Berlangsung</span>
                    <span class="text-xs font-mono font-bold text-[#42634F] bg-[#DCE8DE] px-2 py-0.5 rounded">{{ $activeShift->shift_number }}</span>
                </div>
                <h3 class="text-base font-bold text-[#303632]">Petugas: {{ $activeShift->user->name }}</h3>
                <p class="text-xs text-[#747B75]">Dibuka pada: {{ $activeShift->opened_at->format('d/m/Y H:i:s') }}</p>
            </div>
            
            <div class="flex items-center gap-2">
                <button @click="movementType = 'cash_in'; showMovementModal = true" class="px-3 py-1.5 rounded-lg border border-[#E3E6E0] hover:bg-[#F0F1ED] text-xs font-medium text-[#303632] flex items-center gap-1">
                    <i data-lucide="arrow-down-left" class="w-3.5 h-3.5 text-[#357653]"></i>
                    <span>Kas Masuk</span>
                </button>
                <button @click="movementType = 'cash_out'; showMovementModal = true" class="px-3 py-1.5 rounded-lg border border-[#E3E6E0] hover:bg-[#F0F1ED] text-xs font-medium text-[#303632] flex items-center gap-1">
                    <i data-lucide="arrow-up-right" class="w-3.5 h-3.5 text-[#B84B4B]"></i>
                    <span>Kas Keluar (Petty Cash)</span>
                </button>
                <button @click="showCloseModal = true" class="px-3 py-1.5 rounded-lg bg-[#B84B4B] hover:bg-[#963c3c] text-white text-xs font-medium flex items-center gap-1 shadow-sm">
                    <i data-lucide="lock" class="w-3.5 h-3.5"></i>
                    <span>Tutup Shift & Rekonsiliasi</span>
                </button>
            </div>
        </div>

        <!-- Financial Realtime Flow Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 pt-4 text-xs">
            <div class="p-3 rounded-lg bg-[#F7F7F4] border border-[#E3E6E0]">
                <span class="text-[#747B75] block">1. Modal Awal Laci</span>
                <span class="font-bold text-sm text-[#303632]">Rp {{ number_format($activeShift->starting_cash, 0, ',', '.') }}</span>
            </div>
            <div class="p-3 rounded-lg bg-[#F7F7F4] border border-[#E3E6E0]">
                <span class="text-[#747B75] block">2. Penjualan Tunai</span>
                <span class="font-bold text-sm text-[#357653]">+Rp {{ number_format($activeShift->cash_sales, 0, ',', '.') }}</span>
            </div>
            <div class="p-3 rounded-lg bg-[#F7F7F4] border border-[#E3E6E0]">
                <span class="text-[#747B75] block">3. Kas Masuk (In)</span>
                <span class="font-bold text-sm text-[#357653]">+Rp {{ number_format($activeShift->cash_in, 0, ',', '.') }}</span>
            </div>
            <div class="p-3 rounded-lg bg-[#F7F7F4] border border-[#E3E6E0]">
                <span class="text-[#747B75] block">4. Kas Keluar (Out)</span>
                <span class="font-bold text-sm text-[#B84B4B]">-Rp {{ number_format($activeShift->cash_out, 0, ',', '.') }}</span>
            </div>
            <div class="p-3 rounded-lg bg-[#F7F7F4] border border-[#E3E6E0]">
                <span class="text-[#747B75] block">5. Refund Tunai</span>
                <span class="font-bold text-sm text-[#B84B4B]">-Rp {{ number_format($activeShift->cash_refunds, 0, ',', '.') }}</span>
            </div>
            <div class="p-3 rounded-lg bg-[#DCE8DE]/50 border border-[#42634F]/30">
                <span class="text-[#42634F] font-semibold block">Ekspektasi Uang di Laci</span>
                <span class="font-bold text-sm text-[#42634F]">Rp {{ number_format($activeShift->recalculateExpectedCash(), 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Cash Movements in Current Shift -->
        @if($activeShift->movements->isNotEmpty())
        <div class="mt-4 pt-4 border-t border-[#E3E6E0]">
            <h4 class="text-xs font-semibold text-[#303632] mb-2">Riwayat Kas Masuk / Keluar Shift Ini:</h4>
            <div class="space-y-1.5">
                @foreach($activeShift->movements as $cm)
                <div class="text-xs flex items-center justify-between p-2 rounded bg-[#F7F7F4] border border-[#E3E6E0]">
                    <div class="flex items-center gap-2">
                        <span class="px-1.5 py-0.2 rounded text-[10px] font-bold {{ $cm->type === 'cash_in' ? 'bg-[#E6F4EA] text-[#357653]' : 'bg-[#FCE8E6] text-[#B84B4B]' }}">
                            {{ $cm->type === 'cash_in' ? 'KAS MASUK' : 'KAS KELUAR' }}
                        </span>
                        <span class="text-[#303632]">{{ $cm->reason }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-bold font-mono {{ $cm->type === 'cash_in' ? 'text-[#357653]' : 'text-[#B84B4B]' }}">
                            {{ $cm->type === 'cash_in' ? '+' : '-' }}Rp {{ number_format($cm->amount, 0, ',', '.') }}
                        </span>
                        <span class="text-[11px] text-[#747B75]">({{ $cm->created_at->format('H:i') }})</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Shift History Table -->
    <div class="bg-white rounded-lg border border-[#E3E6E0] shadow-xs overflow-hidden">
        <div class="p-4 border-b border-[#E3E6E0] flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-[#303632]">Riwayat Shift Kasir Sebelumnya</h3>
                <p class="text-xs text-[#747B75]">Daftar sesi shift yang telah selesai beserta hasil rekonsiliasi selisih kas fisik</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F0F1ED] text-[#747B75] uppercase text-[10px] font-semibold border-b border-[#E3E6E0]">
                    <tr>
                        <th class="px-4 py-3">No. Shift</th>
                        <th class="px-4 py-3">Kasir</th>
                        <th class="px-4 py-3">Waktu Buka / Tutup</th>
                        <th class="px-4 py-3 text-right">Modal Awal</th>
                        <th class="px-4 py-3 text-right">Penjualan Tunai</th>
                        <th class="px-4 py-3 text-right">Saldo Ekspektasi</th>
                        <th class="px-4 py-3 text-right">Saldo Aktual</th>
                        <th class="px-4 py-3 text-center">Selisih</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E3E6E0]">
                    @forelse($shifts as $s)
                    <tr class="hover:bg-[#F7F7F4] transition-colors">
                        <td class="px-4 py-3 font-mono font-medium text-[#42634F]">
                            {{ $s->shift_number }}
                        </td>
                        <td class="px-4 py-3 text-[#303632]">
                            {{ $s->user->name ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-[#747B75] text-[11px]">
                            <div>Buka: {{ $s->opened_at->format('d/m/y H:i') }}</div>
                            @if($s->closed_at)
                            <div>Tutup: {{ $s->closed_at->format('d/m/y H:i') }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-[#747B75]">
                            Rp {{ number_format($s->starting_cash, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right font-medium text-[#357653]">
                            Rp {{ number_format($s->cash_sales, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right font-medium text-[#303632]">
                            Rp {{ number_format($s->expected_cash, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-[#303632]">
                            {{ $s->actual_cash !== null ? 'Rp ' . number_format($s->actual_cash, 0, ',', '.') : '-' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($s->difference === null)
                                <span class="text-[#747B75]">-</span>
                            @elseif($s->difference == 0)
                                <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-[#E6F4EA] text-[#357653]">Pas (Rp 0)</span>
                            @elseif($s->difference > 0)
                                <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-[#FEF7E0] text-[#A66D24]">Lebih +Rp {{ number_format($s->difference, 0, ',', '.') }}</span>
                            @else
                                <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-[#FCE8E6] text-[#B84B4B]">Kurang Rp {{ number_format(abs($s->difference), 0, ',', '.') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($s->isOpen())
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#E6F4EA] text-[#357653]">Buka</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-[#F0F1ED] text-[#747B75]">Ditutup</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('shifts.show', $s->id) }}" class="text-[#42634F] hover:underline font-medium">
                                Laporan Shift
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-4 py-8 text-center text-xs text-[#747B75]">
                            Belum ada riwayat shift kasir.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($shifts->hasPages())
        <div class="p-3 border-t border-[#E3E6E0] bg-[#FDFDFD]">
            {{ $shifts->links() }}
        </div>
        @endif
    </div>

    <!-- Modal 1: Open Shift Modal -->
    <div x-show="showOpenModal" 
         @open-start-shift-modal.window="showOpenModal = true"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-[#E3E6E0]" @click.away="showOpenModal = false">
            <h3 class="text-base font-semibold text-[#303632] mb-1">Buka Shift Kasir Baru</h3>
            <p class="text-xs text-[#747B75] mb-4">Masukkan saldo uang tunai awal di dalam laci kasir (uang receh kembalian).</p>

            <form action="{{ route('shifts.open') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-[#303632] mb-1">Saldo Awal Kas Laci (Rp) <span class="text-[#B84B4B]">*</span></label>
                    <input type="number" name="starting_cash" value="200000" min="0" step="1000" required 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632] font-semibold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#303632] mb-1">Catatan Shift (Opsional)</label>
                    <input type="text" name="notes" placeholder="Contoh: Shift pagi" 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#E3E6E0]">
                    <button type="button" @click="showOpenModal = false" class="px-3 py-1.5 rounded-lg text-xs font-medium text-[#747B75] hover:bg-[#F0F1ED]">Batal</button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-[#42634F] hover:bg-[#354F3F] text-white text-xs font-medium">Buka Shift</button>
                </div>
            </form>
        </div>
    </div>

    @if($activeShift)
    <!-- Modal 2: Cash In / Out (Petty Cash) Modal -->
    <div x-show="showMovementModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-[#E3E6E0]" @click.away="showMovementModal = false">
            <h3 class="text-base font-semibold text-[#303632] mb-1" x-text="movementType === 'cash_in' ? 'Catat Kas Masuk (Penambahan Modal)' : 'Catat Kas Keluar (Petty Cash)'"></h3>
            <p class="text-xs text-[#747B75] mb-4">Pencatatan mutasi kas langsung mempengaruhi saldo akhir uang di laci.</p>

            <form action="{{ route('shifts.movement', $activeShift->id) }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="type" :value="movementType">

                <div>
                    <label class="block text-xs font-semibold text-[#303632] mb-1">Nominal (Rp) <span class="text-[#B84B4B]">*</span></label>
                    <input type="number" name="amount" min="100" step="100" required placeholder="0" 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632] font-semibold">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#303632] mb-1">Alasan / Keterangan Keperluan <span class="text-[#B84B4B]">*</span></label>
                    <input type="text" name="reason" required placeholder="Contoh: Tambah uang receh kembalian, Beli kertas struk..." 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#E3E6E0]">
                    <button type="button" @click="showMovementModal = false" class="px-3 py-1.5 rounded-lg text-xs font-medium text-[#747B75] hover:bg-[#F0F1ED]">Batal</button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-[#42634F] hover:bg-[#354F3F] text-white text-xs font-medium">Simpan Mutasi Kas</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 3: Close Shift & Reconciliation Modal -->
    <div x-show="showCloseModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-[#E3E6E0]" @click.away="showCloseModal = false">
            <div class="flex items-center gap-2 text-[#B84B4B] mb-2">
                <i data-lucide="lock" class="w-5 h-5"></i>
                <h3 class="text-base font-semibold text-[#303632]">Tutup Shift & Rekonsiliasi Kasir</h3>
            </div>
            <p class="text-xs text-[#747B75] mb-4">
                Hitung seluruh uang fisik di laci kasir dan masukkan total aktual untuk membandingkannya dengan sistem.
            </p>

            <form action="{{ route('shifts.close', $activeShift->id) }}" method="POST" 
                  x-data="{ expected: {{ $activeShift->recalculateExpectedCash() }}, actual: {{ $activeShift->recalculateExpectedCash() }} }" 
                  class="space-y-4">
                @csrf

                <div class="p-3 rounded-lg bg-[#F0F1ED] space-y-1 text-xs">
                    <div class="flex justify-between text-[#747B75]">
                        <span>Saldo Kas Yang Seharusnya:</span>
                        <span class="font-bold text-[#303632]">Rp {{ number_format($activeShift->recalculateExpectedCash(), 0, ',', '.') }}</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#303632] mb-1">Total Uang Fisik Aktual di Laci (Rp) <span class="text-[#B84B4B]">*</span></label>
                    <input type="number" name="actual_cash" x-model.number="actual" min="0" step="100" required 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632] font-bold">
                </div>

                <!-- Difference calculation display -->
                <div class="p-3 rounded-lg border flex items-center justify-between text-xs" 
                     :class="(actual - expected) === 0 ? 'bg-[#E6F4EA] border-[#357653]/30 text-[#357653]' : ((actual - expected) > 0 ? 'bg-[#FEF7E0] border-[#A66D24]/30 text-[#A66D24]' : 'bg-[#FCE8E6] border-[#B84B4B]/30 text-[#B84B4B]')">
                    <span class="font-semibold">Selisih Kas:</span>
                    <span class="font-bold" x-text="(actual - expected) === 0 ? 'Pas (Tidak ada selisih)' : ((actual - expected) > 0 ? 'Lebih Rp ' + new Intl.NumberFormat('id-ID').format(actual - expected) : 'Kurang Rp ' + new Intl.NumberFormat('id-ID').format(Math.abs(actual - expected)))"></span>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#303632] mb-1">Catatan Penutupan Shift (Opsional)</label>
                    <textarea name="closing_notes" rows="2" placeholder="Catatan bila ada selisih uang atau kendala..." 
                              class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#E3E6E0]">
                    <button type="button" @click="showCloseModal = false" class="px-3 py-1.5 rounded-lg text-xs font-medium text-[#747B75] hover:bg-[#F0F1ED]">Batal</button>
                    <button type="submit" onclick="return confirm('Apakah Anda yakin data hitungan kasir sudah tepat dan ingin menutup shift ini?')" 
                            class="px-4 py-1.5 rounded-lg bg-[#B84B4B] hover:bg-[#963c3c] text-white text-xs font-medium">
                        Konfirmasi Tutup Shift
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
