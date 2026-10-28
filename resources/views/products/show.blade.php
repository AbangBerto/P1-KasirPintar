@extends('layouts.app')

@section('title', 'Detail Produk ' . $product->name)
@section('page_title', 'Detail Produk')
@section('page_subtitle', 'Informasi spesifikasi barang dan riwayat kartu stok')

@section('topbar_actions')
<div class="flex items-center gap-2">
    <a href="{{ route('products.edit', $product->id) }}" class="px-3 py-1.5 rounded-lg border border-[#E3E6E0] hover:bg-[#F0F1ED] text-xs font-medium text-[#303632] flex items-center gap-1.5 transition-colors">
        <i data-lucide="edit" class="w-3.5 h-3.5"></i>
        <span>Edit Produk</span>
    </a>
    <a href="{{ route('inventory.index', ['product_id' => $product->id]) }}" class="px-3 py-1.5 rounded-lg bg-[#42634F] hover:bg-[#354F3F] text-white text-xs font-medium flex items-center gap-1.5 shadow-sm transition-colors">
        <i data-lucide="boxes" class="w-3.5 h-3.5"></i>
        <span>Sesuaikan Stok</span>
    </a>
</div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- Top Card: Product Specs -->
    <div class="bg-white rounded-lg border border-[#E3E6E0] p-6 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-mono font-medium text-[#747B75] bg-[#F0F1ED] px-2 py-0.5 rounded">SKU: {{ $product->sku }}</span>
                    @if($product->barcode)
                    <span class="text-xs font-mono font-medium text-[#747B75] bg-[#F0F1ED] px-2 py-0.5 rounded">Barcode: {{ $product->barcode }}</span>
                    @endif
                    <span class="text-xs font-medium px-2 py-0.5 rounded {{ $product->is_active ? 'bg-[#E6F4EA] text-[#357653]' : 'bg-[#F0F1ED] text-[#747B75]' }}">
                        {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
                <h3 class="text-lg font-bold text-[#303632]">{{ $product->name }}</h3>
                <p class="text-xs text-[#747B75] mt-1">Kategori: <span class="font-medium text-[#303632]">{{ $product->category->name ?? '-' }}</span> | Satuan: <span class="font-medium text-[#303632]">{{ $product->unit }}</span></p>
                @if($product->description)
                <p class="text-xs text-[#747B75] mt-2 italic bg-[#F7F7F4] p-2.5 rounded border border-[#E3E6E0]">{{ $product->description }}</p>
                @endif
            </div>

            <!-- Financial & Stock Summary Box -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 self-stretch md:self-auto">
                <div class="p-3 rounded-lg bg-[#F7F7F4] border border-[#E3E6E0]">
                    <span class="text-[11px] text-[#747B75] block">Harga Jual</span>
                    <span class="text-sm font-bold text-[#357653]">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                </div>
                @if(auth()->user()->isAdmin())
                <div class="p-3 rounded-lg bg-[#F7F7F4] border border-[#E3E6E0]">
                    <span class="text-[11px] text-[#747B75] block">Harga Modal (HPP)</span>
                    <span class="text-sm font-bold text-[#303632]">Rp {{ number_format($product->cost_price, 0, ',', '.') }}</span>
                    <span class="text-[10px] text-[#357653] font-medium block mt-0.5">Margin: {{ $product->margin_percentage }}%</span>
                </div>
                @endif
                <div class="p-3 rounded-lg bg-[#F7F7F4] border border-[#E3E6E0]">
                    <span class="text-[11px] text-[#747B75] block">Stok Tersedia</span>
                    <span class="text-sm font-bold {{ $product->stock <= $product->min_stock ? 'text-[#A66D24]' : 'text-[#303632]' }}">
                        {{ $product->stock }} {{ $product->unit }}
                    </span>
                    <span class="text-[10px] text-[#747B75] block mt-0.5">Min: {{ $product->min_stock }} {{ $product->unit }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Inventory Movement Audit History (Kartu Stok Produk) -->
    <div class="bg-white rounded-lg border border-[#E3E6E0] shadow-xs overflow-hidden">
        <div class="p-4 border-b border-[#E3E6E0] flex items-center justify-between">
            <div>
                <h4 class="text-sm font-semibold text-[#303632]">Kartu Stok & Jejak Pergerakan Barang</h4>
                <p class="text-xs text-[#747B75]">Riwayat perubahan stok masuk, keluar, dan transaksi</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F0F1ED] text-[#747B75] uppercase text-[10px] font-semibold border-b border-[#E3E6E0]">
                    <tr>
                        <th class="px-4 py-3">Tanggal & Waktu</th>
                        <th class="px-4 py-3">Jenis Mutasi</th>
                        <th class="px-4 py-3 text-center">Jumlah</th>
                        <th class="px-4 py-3 text-center">Stok Sebelum</th>
                        <th class="px-4 py-3 text-center">Stok Sesudah</th>
                        <th class="px-4 py-3">Alasan / Referensi</th>
                        <th class="px-4 py-3">Petugas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E3E6E0]">
                    @forelse($product->inventoryMovements as $mov)
                    <tr class="hover:bg-[#F7F7F4] transition-colors">
                        <td class="px-4 py-2.5 text-[#747B75]">
                            {{ $mov->created_at->format('d/m/Y H:i:s') }}
                        </td>
                        <td class="px-4 py-2.5">
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold 
                                {{ $mov->quantity > 0 ? 'bg-[#E6F4EA] text-[#357653]' : 'bg-[#FCE8E6] text-[#B84B4B]' }}">
                                {{ $mov->type_text }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-center font-bold {{ $mov->quantity > 0 ? 'text-[#357653]' : 'text-[#B84B4B]' }}">
                            {{ $mov->quantity > 0 ? '+' : '' }}{{ $mov->quantity }}
                        </td>
                        <td class="px-4 py-2.5 text-center text-[#747B75]">
                            {{ $mov->before_stock }}
                        </td>
                        <td class="px-4 py-2.5 text-center font-semibold text-[#303632]">
                            {{ $mov->after_stock }}
                        </td>
                        <td class="px-4 py-2.5 text-[#303632]">
                            {{ $mov->reason ?? '-' }}
                        </td>
                        <td class="px-4 py-2.5 text-[#747B75]">
                            {{ $mov->user->name ?? '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-xs text-[#747B75]">
                            Belum ada pergerakan stok tercatat untuk produk ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
