@extends('layouts.app')

@section('title', 'Riwayat Pelanggan ' . $customer->name)
@section('page_title', 'Profil & Riwayat Belanja Pelanggan')
@section('page_subtitle', 'Data kontak dan histori invoice transaksi ' . $customer->name)

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">

    <!-- Customer Card -->
    <div class="bg-white rounded-lg border border-[#E3E6E0] p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <h3 class="text-lg font-bold text-[#303632]">{{ $customer->name }}</h3>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#E6F4EA] text-[#357653]">Aktif</span>
            </div>
            <p class="text-xs text-[#747B75]">
                WhatsApp: <span class="font-mono font-medium text-[#303632]">{{ $customer->phone ?? 'Belum ada nomor' }}</span>
                @if($customer->email) | Email: {{ $customer->email }} @endif
            </p>
            @if($customer->address)
            <p class="text-xs text-[#747B75] mt-1">Alamat: {{ $customer->address }}</p>
            @endif
        </div>

        <div class="p-3 rounded-lg bg-[#F7F7F4] border border-[#E3E6E0] text-right">
            <span class="text-xs text-[#747B75] block">Total Akumulasi Belanja</span>
            <span class="text-lg font-bold text-[#357653]">Rp {{ number_format($customer->total_spent, 0, ',', '.') }}</span>
            <span class="text-[11px] text-[#747B75] block mt-0.5">{{ $customer->transactions->count() }} transaksi lunas</span>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="bg-white rounded-lg border border-[#E3E6E0] shadow-xs overflow-hidden">
        <div class="p-4 border-b border-[#E3E6E0]">
            <h4 class="text-sm font-semibold text-[#303632]">Daftar Invoice Belanja</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F0F1ED] text-[#747B75] uppercase text-[10px] font-semibold border-b border-[#E3E6E0]">
                    <tr>
                        <th class="px-4 py-3">No. Invoice</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3 text-center">Banyak Item</th>
                        <th class="px-4 py-3">Metode Bayar</th>
                        <th class="px-4 py-3 text-right">Total Transaksi</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E3E6E0]">
                    @forelse($customer->transactions as $t)
                    <tr class="hover:bg-[#F7F7F4]">
                        <td class="px-4 py-3 font-mono font-bold text-[#42634F]">{{ $t->invoice_number }}</td>
                        <td class="px-4 py-3 text-[#747B75]">{{ $t->transaction_date->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-center text-[#747B75]">{{ $t->items->count() }} item</td>
                        <td class="px-4 py-3 uppercase text-[10px] font-semibold text-[#747B75]">{{ $t->payment_method }}</td>
                        <td class="px-4 py-3 text-right font-bold text-[#303632]">Rp {{ number_format($t->total_amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($t->isPaid())
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#E6F4EA] text-[#357653]">Lunas</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#FCE8E6] text-[#B84B4B]">{{ $t->payment_status }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('transactions.show', $t->id) }}" class="text-[#42634F] hover:underline font-medium">Detail</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-xs text-[#747B75]">Pelanggan ini belum memiliki riwayat transaksi.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
