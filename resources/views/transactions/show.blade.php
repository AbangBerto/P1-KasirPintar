@extends('layouts.app')

@section('title', 'Detail Transaksi ' . $transaction->invoice_number)
@section('page_title', 'Detail Transaksi')
@section('page_subtitle', 'Rincian lengkap transaksi penjualan ' . $transaction->invoice_number)

@section('topbar_actions')
<div class="flex items-center gap-2">
    <a href="{{ route('pos.receipt', $transaction->id) }}" class="px-3 py-1.5 rounded-lg border border-[#E3E6E0] hover:bg-[#F0F1ED] text-xs font-medium text-[#303632] flex items-center gap-1.5 transition-colors">
        <i data-lucide="printer" class="w-3.5 h-3.5"></i>
        <span>Lihat / Cetak Struk</span>
    </a>
    @if($transaction->isPaid() && auth()->user()->isAdmin())
    <button @click="$dispatch('open-void-modal')" class="px-3 py-1.5 rounded-lg bg-[#B84B4B] hover:bg-[#963c3c] text-white text-xs font-medium flex items-center gap-1.5 shadow-sm transition-colors">
        <i data-lucide="ban" class="w-3.5 h-3.5"></i>
        <span>Batalkan (Void) Transaksi</span>
    </button>
    @endif
</div>
@endsection

@section('content')
<div x-data="{ showVoidModal: false }" class="max-w-4xl mx-auto space-y-6">

    <!-- Void Notice if Already Voided -->
    @if($transaction->isVoid())
    <div class="bg-[#FCE8E6] border border-[#B84B4B]/30 rounded-lg p-4 text-xs text-[#B84B4B]">
        <div class="flex items-center gap-2 font-bold text-sm mb-1">
            <i data-lucide="alert-octagon" class="w-4 h-4"></i>
            <span>Transaksi Ini Telah Dibatalkan (Void)</span>
        </div>
        <p>Alasan: <span class="font-semibold text-[#303632]">{{ $transaction->void_reason }}</span></p>
        <p class="text-[11px] text-[#747B75] mt-1">
            Dibatalkan oleh: {{ $transaction->voidedByUser->name ?? 'Admin' }} pada {{ $transaction->voided_at?->format('d/m/Y H:i:s') }}.
            Seluruh kuantitas barang telah dikembalikan ke stok fisik.
        </p>
    </div>
    @endif

    <!-- Invoice Header Card -->
    <div class="bg-white rounded-lg border border-[#E3E6E0] p-6 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-[#E3E6E0]">
            <div>
                <span class="text-[11px] text-[#747B75] uppercase font-semibold">Nomor Invoice</span>
                <h3 class="text-xl font-bold font-mono text-[#42634F]">{{ $transaction->invoice_number }}</h3>
                <p class="text-xs text-[#747B75] mt-0.5">Waktu Transaksi: {{ $transaction->transaction_date->format('l, d F Y H:i:s') }}</p>
            </div>
            <div>
                @if($transaction->isPaid())
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-[#E6F4EA] text-[#357653] inline-flex items-center gap-1">
                        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i> Lunas (Paid)
                    </span>
                @elseif($transaction->isVoid())
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-[#FCE8E6] text-[#B84B4B] inline-flex items-center gap-1">
                        <i data-lucide="x-circle" class="w-3.5 h-3.5"></i> Dibatalkan (Void)
                    </span>
                @endif
            </div>
        </div>

        <!-- Meta Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 text-xs">
            <div>
                <span class="text-[#747B75] block">Kasir Bertugas:</span>
                <span class="font-semibold text-[#303632]">{{ $transaction->user->name ?? '-' }}</span>
            </div>
            <div>
                <span class="text-[#747B75] block">Shift Kasir:</span>
                <span class="font-semibold text-[#303632]">{{ $transaction->cashShift->shift_number ?? 'Non-shift' }}</span>
            </div>
            <div>
                <span class="text-[#747B75] block">Pelanggan:</span>
                <span class="font-semibold text-[#303632]">{{ $transaction->customer->name ?? 'Pelanggan Umum (Walk-in)' }}</span>
            </div>
            <div>
                <span class="text-[#747B75] block">Metode Pembayaran:</span>
                <span class="font-semibold uppercase text-[#303632]">{{ $transaction->payment_method }}</span>
            </div>
        </div>
    </div>

    <!-- Items Purchased Table -->
    <div class="bg-white rounded-lg border border-[#E3E6E0] shadow-xs overflow-hidden">
        <div class="p-4 border-b border-[#E3E6E0]">
            <h4 class="text-sm font-semibold text-[#303632]">Rincian Barang yang Dibeli</h4>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F0F1ED] text-[#747B75] uppercase text-[10px] font-semibold border-b border-[#E3E6E0]">
                    <tr>
                        <th class="px-4 py-3">Produk</th>
                        <th class="px-4 py-3 text-right">Harga Satuan</th>
                        <th class="px-4 py-3 text-center">Kuantitas</th>
                        <th class="px-4 py-3 text-right">Subtotal</th>
                        @if(auth()->user()->isAdmin())
                        <th class="px-4 py-3 text-right">HPP (Modal)</th>
                        <th class="px-4 py-3 text-right">Laba Kotor</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E3E6E0]">
                    @foreach($transaction->items as $item)
                    <tr class="hover:bg-[#F7F7F4] transition-colors">
                        <td class="px-4 py-3">
                            <p class="font-medium text-[#303632]">{{ $item->product_name }}</p>
                            <span class="text-[10px] font-mono text-[#747B75]">SKU: {{ $item->product_sku }}</span>
                        </td>
                        <td class="px-4 py-3 text-right text-[#747B75]">
                            Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-center font-bold text-[#303632]">
                            {{ $item->quantity }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-[#303632]">
                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                        </td>
                        @if(auth()->user()->isAdmin())
                        <td class="px-4 py-3 text-right text-[#747B75] font-mono">
                            Rp {{ number_format($item->cost_price * $item->quantity, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-[#357653] font-mono">
                            Rp {{ number_format($item->profit, 0, ',', '.') }}
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Financial Summary -->
        <div class="p-4 bg-[#FDFDFD] border-t border-[#E3E6E0] flex flex-col sm:flex-row justify-between gap-4 text-xs">
            <div class="text-[#747B75] max-w-sm">
                @if($transaction->notes)
                <p><span class="font-semibold text-[#303632]">Catatan:</span> {{ $transaction->notes }}</p>
                @endif
            </div>

            <div class="space-y-1.5 w-full sm:w-64">
                <div class="flex justify-between text-[#747B75]">
                    <span>Subtotal:</span>
                    <span class="font-medium text-[#303632]">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
                </div>
                @if($transaction->discount_amount > 0)
                <div class="flex justify-between text-[#B84B4B]">
                    <span>Diskon:</span>
                    <span>-Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
                </div>
                @endif
                @if($transaction->tax_amount > 0)
                <div class="flex justify-between text-[#747B75]">
                    <span>Pajak:</span>
                    <span>Rp {{ number_format($transaction->tax_amount, 0, ',', '.') }}</span>
                </div>
                @endif
                <div class="pt-2 border-t border-[#E3E6E0] flex justify-between font-bold text-sm">
                    <span class="text-[#303632]">Total Belanja:</span>
                    <span class="text-[#357653]">Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-[#747B75] pt-1 border-t border-dashed border-[#E3E6E0]">
                    <span>Uang Diterima:</span>
                    <span>Rp {{ number_format($transaction->cash_received, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-[#747B75]">
                    <span>Kembalian:</span>
                    <span class="font-semibold text-[#303632]">Rp {{ number_format($transaction->cash_change, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Void Confirmation Modal -->
    <div x-show="showVoidModal" 
         @open-void-modal.window="showVoidModal = true"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-[#E3E6E0]" @click.away="showVoidModal = false">
            <div class="flex items-center gap-2 text-[#B84B4B] mb-3">
                <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                <h3 class="text-base font-semibold text-[#303632]">Batalkan (Void) Transaksi</h3>
            </div>
            <p class="text-xs text-[#747B75] mb-4">
                Tindakan ini akan mengembalikan stok seluruh barang ke inventaris dan mencatat jejak audit pembatalan. Tindakan tidak dapat diurungkan.
            </p>

            <form action="{{ route('transactions.void', $transaction->id) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-[#303632] mb-1">Alasan Pembatalan <span class="text-[#B84B4B]">*</span></label>
                    <textarea name="void_reason" rows="3" required placeholder="Contoh: Pelanggan salah memilih varian, kasir keliru input item..." 
                              class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632] focus:ring-2 focus:ring-[#B84B4B]"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#E3E6E0]">
                    <button type="button" @click="showVoidModal = false" class="px-3 py-1.5 rounded-lg text-xs font-medium text-[#747B75] hover:bg-[#F0F1ED]">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-[#B84B4B] hover:bg-[#963c3c] text-white text-xs font-medium">
                        Konfirmasi Void Transaksi
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
