@extends('layouts.app')

@section('title', 'Riwayat Transaksi')
@section('page_title', 'Riwayat Transaksi Penjualan')
@section('page_subtitle', 'Pencatatan seluruh invoice kasir, status pembayaran, dan cetak ulang bukti belanja')

@section('topbar_actions')
@if(auth()->user()->isAdmin())
<a href="{{ route('transactions.export', request()->all()) }}" class="px-3 py-1.5 rounded-lg border border-[#E3E6E0] hover:bg-[#F0F1ED] text-xs font-medium text-[#303632] flex items-center gap-1.5 transition-colors">
    <i data-lucide="download" class="w-3.5 h-3.5"></i>
    <span>Ekspor Riwayat (CSV)</span>
</a>
@endif
@endsection

@section('content')
<div class="space-y-6">

    <!-- Filters Bar -->
    <div class="bg-white rounded-lg border border-[#E3E6E0] p-4 shadow-xs">
        <form action="{{ route('transactions.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 text-xs">
            
            <!-- Invoice Search -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-medium text-[#747B75] mb-1">Nomor Invoice</label>
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 text-[#747B75] absolute left-3 top-2.5"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Contoh: TRX-20261028-0001" 
                           class="w-full pl-9 pr-3 py-1.5 rounded-lg border border-[#E3E6E0] text-xs text-[#303632]">
                </div>
            </div>

            <!-- Cashier Filter (Admin only) -->
            @if(auth()->user()->isAdmin())
            <div>
                <label class="block text-[11px] font-medium text-[#747B75] mb-1">Kasir</label>
                <select name="cashier_id" class="w-full px-2.5 py-1.5 rounded-lg border border-[#E3E6E0] text-xs text-[#303632]">
                    <option value="">Semua Kasir</option>
                    @foreach($cashiers as $c)
                    <option value="{{ $c->id }}" {{ $cashierId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <!-- Payment Method -->
            <div>
                <label class="block text-[11px] font-medium text-[#747B75] mb-1">Metode Bayar</label>
                <select name="payment_method" class="w-full px-2.5 py-1.5 rounded-lg border border-[#E3E6E0] text-xs text-[#303632]">
                    <option value="">Semua Metode</option>
                    <option value="cash" {{ $paymentMethod === 'cash' ? 'selected' : '' }}>Tunai</option>
                    <option value="qris" {{ $paymentMethod === 'qris' ? 'selected' : '' }}>QRIS</option>
                    <option value="transfer" {{ $paymentMethod === 'transfer' ? 'selected' : '' }}>Transfer</option>
                </select>
            </div>

            <!-- Payment Status -->
            <div>
                <label class="block text-[11px] font-medium text-[#747B75] mb-1">Status</label>
                <select name="status" class="w-full px-2.5 py-1.5 rounded-lg border border-[#E3E6E0] text-xs text-[#303632]">
                    <option value="">Semua Status</option>
                    <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Lunas (Paid)</option>
                    <option value="void" {{ $status === 'void' ? 'selected' : '' }}>Dibatalkan (Void)</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 py-1.5 px-3 rounded-lg bg-[#42634F] hover:bg-[#354F3F] text-white font-medium">Filter</button>
                <a href="{{ route('transactions.index') }}" class="py-1.5 px-3 rounded-lg border border-[#E3E6E0] hover:bg-[#F0F1ED] text-[#303632]">Reset</a>
            </div>

        </form>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-lg border border-[#E3E6E0] shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F0F1ED] text-[#747B75] uppercase text-[10px] font-semibold border-b border-[#E3E6E0]">
                    <tr>
                        <th class="px-4 py-3">No. Invoice</th>
                        <th class="px-4 py-3">Waktu Transaksi</th>
                        <th class="px-4 py-3">Kasir</th>
                        <th class="px-4 py-3">Pelanggan</th>
                        <th class="px-4 py-3 text-center">Item</th>
                        <th class="px-4 py-3">Metode Bayar</th>
                        <th class="px-4 py-3 text-right">Total Transaksi</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E3E6E0]">
                    @forelse($transactions as $t)
                    <tr class="hover:bg-[#F7F7F4] transition-colors {{ $t->isVoid() ? 'bg-red-50/30' : '' }}">
                        <td class="px-4 py-3 font-mono font-semibold text-[#42634F]">
                            {{ $t->invoice_number }}
                        </td>
                        <td class="px-4 py-3 text-[#747B75] whitespace-nowrap">
                            {{ $t->transaction_date->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-3 text-[#303632]">
                            {{ $t->user->name ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-[#747B75]">
                            {{ $t->customer->name ?? 'Pelanggan Umum' }}
                        </td>
                        <td class="px-4 py-3 text-center text-[#747B75]">
                            {{ $t->items->count() }} macam
                        </td>
                        <td class="px-4 py-3">
                            <span class="uppercase font-semibold text-[10px] px-1.5 py-0.5 rounded bg-[#F0F1ED] text-[#303632]">
                                {{ $t->payment_method }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-[#303632]">
                            Rp {{ number_format($t->total_amount, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($t->isPaid())
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#E6F4EA] text-[#357653]">Lunas</span>
                            @elseif($t->isVoid())
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#FCE8E6] text-[#B84B4B]">Void / Batal</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#FEF7E0] text-[#A66D24]">{{ $t->payment_status }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <a href="{{ route('transactions.show', $t->id) }}" class="text-[#42634F] hover:underline font-medium">
                                Detail
                            </a>
                            <a href="{{ route('pos.receipt', $t->id) }}" class="text-[#747B75] hover:text-[#303632] font-medium" title="Cetak Ulang Struk">
                                Cetak
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-xs text-[#747B75]">
                            Tidak ada data transaksi yang ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
        <div class="p-3 border-t border-[#E3E6E0] bg-[#FDFDFD]">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
