@extends('layouts.app')

@section('title', 'Stok dan Persediaan')
@section('page_title', 'Stok & Manajemen Persediaan')
@section('page_subtitle', 'Audit kartu stok barang, deteksi stok menipis, dan penyesuaian inventaris')

@section('topbar_actions')
<button @click="$dispatch('open-adjust-modal')" class="px-3 py-1.5 rounded-lg bg-[#42634F] hover:bg-[#354F3F] text-white text-xs font-medium flex items-center gap-1.5 shadow-sm transition-colors">
    <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
    <span>Penyesuaian Stok Manual</span>
</button>
@endsection

@section('content')
<div x-data="{ activeTab: 'movements', showAdjustModal: false, selectedProductId: '{{ $productId ?? '' }}' }">

    <!-- Stock Condition Alert Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        
        <!-- Low Stock Banner -->
        <div class="bg-white rounded-lg border border-[#E3E6E0] p-4 flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-lg bg-[#FEF7E0] text-[#A66D24] flex items-center justify-center shrink-0">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                </span>
                <div>
                    <h4 class="text-xs font-semibold text-[#303632]">Stok Menipis (< Batas Min)</h4>
                    <p class="text-[11px] text-[#747B75] mt-0.5">{{ count($lowStockProducts) }} produk membutuhkan pengadaan segera</p>
                </div>
            </div>
            <button @click="activeTab = 'low_stock'" class="text-xs text-[#A66D24] hover:underline font-semibold">
                Lihat Daftar
            </button>
        </div>

        <!-- Out of Stock Banner -->
        <div class="bg-white rounded-lg border border-[#E3E6E0] p-4 flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-lg bg-[#FCE8E6] text-[#B84B4B] flex items-center justify-center shrink-0">
                    <i data-lucide="x-circle" class="w-5 h-5"></i>
                </span>
                <div>
                    <h4 class="text-xs font-semibold text-[#303632]">Stok Habis (0)</h4>
                    <p class="text-[11px] text-[#747B75] mt-0.5">{{ count($outOfStockProducts) }} produk kosong dan tidak dapat dijual</p>
                </div>
            </div>
            <button @click="activeTab = 'out_of_stock'" class="text-xs text-[#B84B4B] hover:underline font-semibold">
                Lihat Daftar
            </button>
        </div>

    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-[#E3E6E0] mb-6 flex items-center gap-4 text-xs font-medium">
        <button @click="activeTab = 'movements'" 
                :class="activeTab === 'movements' ? 'border-[#42634F] text-[#42634F] border-b-2 font-semibold' : 'text-[#747B75] hover:text-[#303632]'"
                class="pb-2.5 transition-colors">
            Riwayat Kartu Stok (Audit Mutasi)
        </button>
        <button @click="activeTab = 'low_stock'" 
                :class="activeTab === 'low_stock' ? 'border-[#42634F] text-[#42634F] border-b-2 font-semibold' : 'text-[#747B75] hover:text-[#303632]'"
                class="pb-2.5 transition-colors flex items-center gap-1.5">
            <span>Daftar Stok Menipis</span>
            <span class="px-1.5 py-0.2 rounded-full bg-[#FEF7E0] text-[#A66D24] text-[10px] font-bold">{{ count($lowStockProducts) }}</span>
        </button>
        <button @click="activeTab = 'out_of_stock'" 
                :class="activeTab === 'out_of_stock' ? 'border-[#42634F] text-[#42634F] border-b-2 font-semibold' : 'text-[#747B75] hover:text-[#303632]'"
                class="pb-2.5 transition-colors flex items-center gap-1.5">
            <span>Daftar Stok Habis</span>
            <span class="px-1.5 py-0.2 rounded-full bg-[#FCE8E6] text-[#B84B4B] text-[10px] font-bold">{{ count($outOfStockProducts) }}</span>
        </button>
    </div>

    <!-- Tab 1: Stock Movements Table -->
    <div x-show="activeTab === 'movements'" class="space-y-4">
        
        <!-- Filters -->
        <div class="bg-white rounded-lg border border-[#E3E6E0] p-4 shadow-xs">
            <form action="{{ route('inventory.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                <div>
                    <label class="block text-[11px] font-medium text-[#747B75] mb-1">Filter Produk</label>
                    <select name="product_id" class="w-full px-2.5 py-1.5 rounded-lg border border-[#E3E6E0] text-xs text-[#303632]">
                        <option value="">Semua Produk</option>
                        @foreach($allProducts as $p)
                        <option value="{{ $p->id }}" {{ $productId == $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ $p->sku }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-[#747B75] mb-1">Jenis Mutasi</label>
                    <select name="type" class="w-full px-2.5 py-1.5 rounded-lg border border-[#E3E6E0] text-xs text-[#303632]">
                        <option value="">Semua Jenis</option>
                        <option value="initial" {{ $type === 'initial' ? 'selected' : '' }}>Stok Awal</option>
                        <option value="restock" {{ $type === 'restock' ? 'selected' : '' }}>Restok / Pembelian</option>
                        <option value="sale" {{ $type === 'sale' ? 'selected' : '' }}>Penjualan Kasir</option>
                        <option value="damaged" {{ $type === 'damaged' ? 'selected' : '' }}>Barang Rusak</option>
                        <option value="return" {{ $type === 'return' ? 'selected' : '' }}>Retur</option>
                        <option value="adjustment" {{ $type === 'adjustment' ? 'selected' : '' }}>Penyesuaian Manual</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-[#747B75] mb-1">Rentang Tanggal</label>
                    <div class="flex items-center gap-1">
                        <input type="date" name="start_date" value="{{ $startDate }}" class="w-full px-2 py-1.5 rounded border border-[#E3E6E0] text-xs">
                        <span>-</span>
                        <input type="date" name="end_date" value="{{ $endDate }}" class="w-full px-2 py-1.5 rounded border border-[#E3E6E0] text-xs">
                    </div>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 py-1.5 px-3 rounded-lg bg-[#42634F] hover:bg-[#354F3F] text-white font-medium">Filter</button>
                    <a href="{{ route('inventory.index') }}" class="py-1.5 px-3 rounded-lg border border-[#E3E6E0] hover:bg-[#F0F1ED] text-[#303632]">Reset</a>
                </div>
            </form>
        </div>

        <!-- Movements Table -->
        <div class="bg-white rounded-lg border border-[#E3E6E0] shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F0F1ED] text-[#747B75] uppercase text-[10px] font-semibold border-b border-[#E3E6E0]">
                        <tr>
                            <th class="px-4 py-3">Waktu</th>
                            <th class="px-4 py-3">Produk</th>
                            <th class="px-4 py-3">Jenis Mutasi</th>
                            <th class="px-4 py-3 text-center">Mutasi</th>
                            <th class="px-4 py-3 text-center">Sebelum</th>
                            <th class="px-4 py-3 text-center">Sesudah</th>
                            <th class="px-4 py-3">Alasan / Catatan</th>
                            <th class="px-4 py-3">Petugas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E3E6E0]">
                        @forelse($movements as $m)
                        <tr class="hover:bg-[#F7F7F4] transition-colors">
                            <td class="px-4 py-3 text-[#747B75] whitespace-nowrap">
                                {{ $m->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3 font-medium text-[#303632]">
                                {{ $m->product->name ?? 'Produk Dihapus' }}
                                <span class="block text-[10px] font-mono text-[#747B75]">{{ $m->product->sku ?? '' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold 
                                    {{ $m->quantity > 0 ? 'bg-[#E6F4EA] text-[#357653]' : 'bg-[#FCE8E6] text-[#B84B4B]' }}">
                                    {{ $m->type_text }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center font-bold {{ $m->quantity > 0 ? 'text-[#357653]' : 'text-[#B84B4B]' }}">
                                {{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}
                            </td>
                            <td class="px-4 py-3 text-center text-[#747B75]">
                                {{ $m->before_stock }}
                            </td>
                            <td class="px-4 py-3 text-center font-bold text-[#303632]">
                                {{ $m->after_stock }}
                            </td>
                            <td class="px-4 py-3 text-[#303632]">
                                {{ $m->reason ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-[#747B75]">
                                {{ $m->user->name ?? '-' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-xs text-[#747B75]">
                                Belum ada riwayat mutasi stok.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($movements->hasPages())
            <div class="p-3 border-t border-[#E3E6E0] bg-[#FDFDFD]">
                {{ $movements->links() }}
            </div>
            @endif
        </div>

    </div>

    <!-- Tab 2: Low Stock List -->
    <div x-show="activeTab === 'low_stock'" style="display: none;">
        <div class="bg-white rounded-lg border border-[#E3E6E0] shadow-xs overflow-hidden">
            <div class="p-4 border-b border-[#E3E6E0]">
                <h3 class="text-sm font-semibold text-[#303632]">Daftar Produk Stok Menipis</h3>
                <p class="text-xs text-[#747B75]">Produk dengan persediaan berada di bawah batas minimum pengadaan</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F0F1ED] text-[#747B75] uppercase text-[10px] font-semibold border-b border-[#E3E6E0]">
                        <tr>
                            <th class="px-4 py-3">Produk</th>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3 text-center">Sisa Stok</th>
                            <th class="px-4 py-3 text-center">Batas Minimum</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E3E6E0]">
                        @forelse($lowStockProducts as $lp)
                        <tr class="hover:bg-[#F7F7F4] transition-colors">
                            <td class="px-4 py-3 font-semibold text-[#303632]">
                                {{ $lp->name }}
                                <span class="block text-[10px] font-mono text-[#747B75]">{{ $lp->sku }}</span>
                            </td>
                            <td class="px-4 py-3 text-[#747B75]">{{ $lp->category->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-[#FEF7E0] text-[#A66D24]">
                                    {{ $lp->stock }} {{ $lp->unit }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-[#747B75]">{{ $lp->min_stock }} {{ $lp->unit }}</td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" 
                                        @click="selectedProductId = '{{ $lp->id }}'; showAdjustModal = true"
                                        class="px-2.5 py-1 rounded bg-[#42634F] hover:bg-[#354F3F] text-white text-xs font-medium">
                                    Tambah Stok
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-xs text-[#357653]">
                                Tidak ada produk dengan stok menipis saat ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 3: Out of Stock List -->
    <div x-show="activeTab === 'out_of_stock'" style="display: none;">
        <div class="bg-white rounded-lg border border-[#E3E6E0] shadow-xs overflow-hidden">
            <div class="p-4 border-b border-[#E3E6E0]">
                <h3 class="text-sm font-semibold text-[#303632]">Daftar Produk Stok Habis</h3>
                <p class="text-xs text-[#747B75]">Produk dengan stok 0 yang otomatis tidak dapat ditransaksikan di kasir</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F0F1ED] text-[#747B75] uppercase text-[10px] font-semibold border-b border-[#E3E6E0]">
                        <tr>
                            <th class="px-4 py-3">Produk</th>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3 text-center">Sisa Stok</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E3E6E0]">
                        @forelse($outOfStockProducts as $op)
                        <tr class="hover:bg-[#F7F7F4] transition-colors">
                            <td class="px-4 py-3 font-semibold text-[#303632]">
                                {{ $op->name }}
                                <span class="block text-[10px] font-mono text-[#747B75]">{{ $op->sku }}</span>
                            </td>
                            <td class="px-4 py-3 text-[#747B75]">{{ $op->category->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-[#FCE8E6] text-[#B84B4B]">
                                    0 {{ $op->unit }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" 
                                        @click="selectedProductId = '{{ $op->id }}'; showAdjustModal = true"
                                        class="px-2.5 py-1 rounded bg-[#42634F] hover:bg-[#354F3F] text-white text-xs font-medium">
                                    Restok Sekarang
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-xs text-[#357653]">
                                Tidak ada produk yang kehabisan stok.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Stock Adjustment Modal -->
    <div x-show="showAdjustModal" 
         @open-adjust-modal.window="showAdjustModal = true"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-xl max-w-lg w-full p-6 shadow-xl border border-[#E3E6E0]" @click.away="showAdjustModal = false">
            <div class="flex items-center gap-2 mb-3 text-[#42634F]">
                <i data-lucide="boxes" class="w-5 h-5"></i>
                <h3 class="text-base font-semibold text-[#303632]">Penyesuaian Stok Barang</h3>
            </div>
            <p class="text-xs text-[#747B75] mb-4">
                Setiap perubahan stok akan dicatat dalam kartu stok secara permanen demi akurasi audit inventaris.
            </p>

            <form action="{{ route('inventory.adjust') }}" method="POST" class="space-y-4">
                @csrf
                
                <!-- Product Selector -->
                <div>
                    <label class="block text-xs font-semibold text-[#303632] mb-1">Pilih Produk <span class="text-[#B84B4B]">*</span></label>
                    <select name="product_id" x-model="selectedProductId" required class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                        <option value="">-- Pilih Barang --</option>
                        @foreach($allProducts as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} (Stok saat ini: {{ $p->stock }} {{ $p->unit }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Type -->
                <div class="grid grid-cols-3 gap-2 text-xs">
                    <label class="cursor-pointer border border-[#E3E6E0] rounded-lg p-2.5 text-center has-[:checked]:border-[#42634F] has-[:checked]:bg-[#DCE8DE]/40">
                        <input type="radio" name="action" value="add" checked class="hidden">
                        <span class="font-bold text-[#357653] block text-sm">+ Masuk</span>
                        <span class="text-[10px] text-[#747B75]">Tambah stok</span>
                    </label>
                    <label class="cursor-pointer border border-[#E3E6E0] rounded-lg p-2.5 text-center has-[:checked]:border-[#42634F] has-[:checked]:bg-[#DCE8DE]/40">
                        <input type="radio" name="action" value="subtract" class="hidden">
                        <span class="font-bold text-[#B84B4B] block text-sm">- Keluar</span>
                        <span class="text-[10px] text-[#747B75]">Kurang stok</span>
                    </label>
                    <label class="cursor-pointer border border-[#E3E6E0] rounded-lg p-2.5 text-center has-[:checked]:border-[#42634F] has-[:checked]:bg-[#DCE8DE]/40">
                        <input type="radio" name="action" value="set" class="hidden">
                        <span class="font-bold text-[#303632] block text-sm">= Tetapkan</span>
                        <span class="text-[10px] text-[#747B75]">Koreksi fisik</span>
                    </label>
                </div>

                <!-- Quantity -->
                <div>
                    <label class="block text-xs font-semibold text-[#303632] mb-1">Jumlah Unit <span class="text-[#B84B4B]">*</span></label>
                    <input type="number" name="quantity" min="1" required placeholder="Contoh: 10" 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                </div>

                <!-- Reason Category -->
                <div>
                    <label class="block text-xs font-semibold text-[#303632] mb-1">Alasan Penyesuaian <span class="text-[#B84B4B]">*</span></label>
                    <select name="reason_type" required class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                        <option value="restock">Pemasukan / Restok Barang Baru</option>
                        <option value="damaged">Barang Rusak / Kadaluarsa</option>
                        <option value="return">Retur dari Pelanggan / Supplier</option>
                        <option value="adjustment">Koreksi Stock Opname Fisik</option>
                    </select>
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-xs font-semibold text-[#303632] mb-1">Keterangan / Nomor Surat Jalan <span class="text-[#B84B4B]">*</span></label>
                    <input type="text" name="notes" required placeholder="Contoh: Restok dari Supplier Maju Jaya PO-991" 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#E3E6E0]">
                    <button type="button" @click="showAdjustModal = false" class="px-4 py-2 rounded-lg text-xs font-medium text-[#747B75] hover:bg-[#F0F1ED]">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-lg bg-[#42634F] hover:bg-[#354F3F] text-white text-xs font-medium">
                        Simpan Penyesuaian
                    </button>
                </div>

            </form>
        </div>
    </div>

</div>
@endsection
