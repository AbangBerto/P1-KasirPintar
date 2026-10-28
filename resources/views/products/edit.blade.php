@extends('layouts.app')

@section('title', 'Edit Produk ' . $product->name)
@section('page_title', 'Edit Data Produk')
@section('page_subtitle', 'Perbarui informasi produk, harga jual, dan batas minimum stok')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg border border-[#E3E6E0] p-6 shadow-xs">
        
        <form action="{{ route('products.update', $product->id) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                
                <!-- Product Name -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-[#303632] mb-1">Nama Produk <span class="text-[#B84B4B]">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $product->name) }}" required 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632] focus:ring-2 focus:ring-[#42634F]">
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-xs font-semibold text-[#303632] mb-1">Kategori <span class="text-[#B84B4B]">*</span></label>
                    <select name="category_id" required class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ (old('category_id', $product->category_id) == $cat->id) ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Unit -->
                <div>
                    <label class="block text-xs font-semibold text-[#303632] mb-1">Satuan Barang <span class="text-[#B84B4B]">*</span></label>
                    <input type="text" name="unit" value="{{ old('unit', $product->unit) }}" required 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                </div>

                <!-- SKU -->
                <div>
                    <label class="block text-xs font-semibold text-[#303632] mb-1">Kode SKU Produk <span class="text-[#B84B4B]">*</span></label>
                    <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" required 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632] font-mono">
                </div>

                <!-- Barcode -->
                <div>
                    <label class="block text-xs font-semibold text-[#303632] mb-1">Barcode Pemindai</label>
                    <input type="text" name="barcode" value="{{ old('barcode', $product->barcode) }}" 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632] font-mono">
                </div>

                <!-- Cost Price (Harga Modal) -->
                <div>
                    <label class="block text-xs font-semibold text-[#303632] mb-1">Harga Modal / Beli (HPP) <span class="text-[#B84B4B]">*</span></label>
                    <input type="number" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}" min="0" step="100" required 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                </div>

                <!-- Selling Price (Harga Jual) -->
                <div>
                    <label class="block text-xs font-semibold text-[#303632] mb-1">Harga Jual Kasir <span class="text-[#B84B4B]">*</span></label>
                    <input type="number" name="selling_price" value="{{ old('selling_price', $product->selling_price) }}" min="0" step="100" required 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632] font-semibold text-[#357653]">
                </div>

                <!-- Current Stock Notice & Adjustment Link -->
                <div class="p-3 rounded-lg bg-[#F0F1ED] border border-[#E3E6E0] flex items-center justify-between">
                    <div>
                        <span class="block text-[11px] font-semibold text-[#747B75] uppercase">Stok Saat Ini</span>
                        <span class="text-sm font-bold text-[#303632]">{{ $product->stock }} {{ $product->unit }}</span>
                    </div>
                    <a href="{{ route('inventory.index', ['product_id' => $product->id]) }}" 
                       class="px-2.5 py-1 rounded bg-white border border-[#E3E6E0] text-xs font-medium text-[#42634F] hover:bg-[#FDFDFD]">
                        Penyesuaian Stok
                    </a>
                </div>

                <!-- Min Stock Threshold -->
                <div>
                    <label class="block text-xs font-semibold text-[#303632] mb-1">Batas Minimum Peringatan Stok <span class="text-[#B84B4B]">*</span></label>
                    <input type="number" name="min_stock" value="{{ old('min_stock', $product->min_stock) }}" min="0" required 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                </div>

                <!-- Description -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-[#303632] mb-1">Keterangan / Catatan Tambahan</label>
                    <textarea name="description" rows="2" 
                              class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">{{ old('description', $product->description) }}</textarea>
                </div>

                <!-- Active Status -->
                <div class="sm:col-span-2 pt-2">
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-medium text-[#303632]">
                        <input type="checkbox" name="is_active" value="1" {{ $product->is_active ? 'checked' : '' }} 
                               class="rounded border-[#E3E6E0] text-[#42634F] focus:ring-[#42634F]">
                        <span>Produk aktif dan dapat dipilih di kasir (POS)</span>
                    </label>
                </div>

            </div>

            <!-- Action buttons -->
            <div class="pt-4 border-t border-[#E3E6E0] flex items-center justify-end gap-3">
                <a href="{{ route('products.index') }}" class="px-4 py-2 rounded-lg border border-[#E3E6E0] hover:bg-[#F0F1ED] text-xs font-medium text-[#303632]">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2 rounded-lg bg-[#42634F] hover:bg-[#354F3F] text-white text-xs font-medium shadow-sm transition-colors">
                    Simpan Perubahan
                </button>
            </div>

        </form>

    </div>
</div>
@endsection
