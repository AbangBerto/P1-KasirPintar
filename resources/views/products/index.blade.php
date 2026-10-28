@extends('layouts.app')

@section('title', 'Manajemen Produk')
@section('page_title', 'Katalog & Manajemen Produk')
@section('page_subtitle', 'Kelola data master barang, harga modal, harga jual, dan kontrol stok minimum')

@section('topbar_actions')
<div class="flex items-center gap-2">
    <a href="{{ route('products.export') }}" class="px-3 py-1.5 rounded-lg border border-[#E3E6E0] hover:bg-[#F0F1ED] text-xs font-medium text-[#303632] flex items-center gap-1.5 transition-colors">
        <i data-lucide="download" class="w-3.5 h-3.5"></i>
        <span>Ekspor CSV</span>
    </a>
    <a href="{{ route('products.create') }}" class="px-3 py-1.5 rounded-lg bg-[#42634F] hover:bg-[#354F3F] text-white text-xs font-medium flex items-center gap-1.5 shadow-sm transition-colors">
        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
        <span>Tambah Produk Baru</span>
    </a>
</div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- KPI / Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg border border-[#E3E6E0] p-4 shadow-xs">
            <span class="text-xs font-medium text-[#747B75]">Total Produk Terdaftar</span>
            <div class="text-xl font-bold text-[#303632] mt-1">{{ $totalProducts }} <span class="text-xs font-normal text-[#747B75]">item</span></div>
        </div>
        <div class="bg-white rounded-lg border border-[#E3E6E0] p-4 shadow-xs">
            <span class="text-xs font-medium text-[#A66D24]">Stok Menipis (<= Batas Min)</span>
            <div class="text-xl font-bold text-[#A66D24] mt-1">{{ $lowStockCount }} <span class="text-xs font-normal text-[#747B75]">produk</span></div>
        </div>
        <div class="bg-white rounded-lg border border-[#E3E6E0] p-4 shadow-xs">
            <span class="text-xs font-medium text-[#B84B4B]">Stok Habis (0)</span>
            <div class="text-xl font-bold text-[#B84B4B] mt-1">{{ $outOfStockCount }} <span class="text-xs font-normal text-[#747B75]">produk</span></div>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-white rounded-lg border border-[#E3E6E0] p-4 shadow-xs">
        <form action="{{ route('products.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            
            <!-- Search Keyword -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-medium text-[#747B75] mb-1">Cari Produk</label>
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 text-[#747B75] absolute left-3 top-2.5"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Nama produk, SKU, barcode..." 
                           class="w-full pl-9 pr-3 py-1.5 rounded-lg border border-[#E3E6E0] text-xs text-[#303632] focus:ring-2 focus:ring-[#42634F]">
                </div>
            </div>

            <!-- Category Filter -->
            <div>
                <label class="block text-[11px] font-medium text-[#747B75] mb-1">Kategori</label>
                <select name="category_id" class="w-full px-2.5 py-1.5 rounded-lg border border-[#E3E6E0] text-xs text-[#303632]">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Stock Status Filter -->
            <div>
                <label class="block text-[11px] font-medium text-[#747B75] mb-1">Kondisi Stok</label>
                <select name="stock_filter" class="w-full px-2.5 py-1.5 rounded-lg border border-[#E3E6E0] text-xs text-[#303632]">
                    <option value="">Semua Kondisi</option>
                    <option value="low" {{ $stockFilter === 'low' ? 'selected' : '' }}>Stok Menipis</option>
                    <option value="out" {{ $stockFilter === 'out' ? 'selected' : '' }}>Stok Habis</option>
                </select>
            </div>

            <!-- Actions / Filter button -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 py-1.5 px-3 rounded-lg bg-[#42634F] hover:bg-[#354F3F] text-white text-xs font-medium transition-colors">
                    Filter
                </button>
                <a href="{{ route('products.index') }}" class="py-1.5 px-3 rounded-lg border border-[#E3E6E0] hover:bg-[#F0F1ED] text-xs font-medium text-[#303632]">
                    Reset
                </a>
            </div>

        </form>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-lg border border-[#E3E6E0] shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F0F1ED] text-[#747B75] uppercase text-[10px] font-semibold border-b border-[#E3E6E0]">
                    <tr>
                        <th class="px-4 py-3">Produk & SKU</th>
                        <th class="px-4 py-3">Kategori</th>
                        @if(auth()->user()->isAdmin())
                        <th class="px-4 py-3 text-right">Harga Modal (HPP)</th>
                        @endif
                        <th class="px-4 py-3 text-right">Harga Jual</th>
                        <th class="px-4 py-3 text-center">Stok</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E3E6E0]">
                    @forelse($products as $p)
                    <tr class="hover:bg-[#F7F7F4] transition-colors">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-[#303632]">{{ $p->name }}</p>
                            <div class="flex items-center gap-2 text-[10px] text-[#747B75] font-mono mt-0.5">
                                <span>SKU: {{ $p->sku }}</span>
                                @if($p->barcode)
                                <span>| Barcode: {{ $p->barcode }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-[#747B75]">
                            {{ $p->category->name ?? '-' }}
                        </td>
                        @if(auth()->user()->isAdmin())
                        <td class="px-4 py-3 text-right text-[#747B75] font-mono">
                            Rp {{ number_format($p->cost_price, 0, ',', '.') }}
                        </td>
                        @endif
                        <td class="px-4 py-3 text-right font-semibold text-[#357653] font-mono">
                            Rp {{ number_format($p->selling_price, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($p->stock <= 0)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#FCE8E6] text-[#B84B4B]">
                                    Habis (0 {{ $p->unit }})
                                </span>
                            @elseif($p->stock <= $p->min_stock)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#FEF7E0] text-[#A66D24]">
                                    {{ $p->stock }} {{ $p->unit }} (Min: {{ $p->min_stock }})
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-[#E6F4EA] text-[#357653]">
                                    {{ $p->stock }} {{ $p->unit }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($p->is_active)
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-[#E6F4EA] text-[#357653] font-medium">Aktif</span>
                            @else
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-[#F0F1ED] text-[#747B75]">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right space-x-1.5">
                            <a href="{{ route('products.show', $p->id) }}" class="text-[#747B75] hover:text-[#303632] font-medium">
                                Detail
                            </a>
                            <a href="{{ route('products.edit', $p->id) }}" class="text-[#42634F] hover:underline font-medium">
                                Edit
                            </a>
                            <form action="{{ route('products.toggle', $p->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" onclick="return confirm('Ubah status aktif produk ini?')" class="text-xs text-[#747B75] hover:text-[#B84B4B]">
                                    {{ $p->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-xs text-[#747B75]">
                            Tidak ada produk yang sesuai dengan kriteria pencarian.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($products->hasPages())
        <div class="p-3 border-t border-[#E3E6E0] bg-[#FDFDFD]">
            {{ $products->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
