@extends('layouts.app')

@section('title', 'Kategori Produk')
@section('page_title', 'Kategori Produk')
@section('page_subtitle', 'Pengelompokan barang untuk memudahkan pencarian dan pelaporan')

@section('content')
<div x-data="{ showModal: false, isEdit: false, modalTitle: 'Tambah Kategori', actionUrl: '{{ route('categories.store') }}', catName: '', catDesc: '', catActive: true }">
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        
        <!-- Categories Table (2 Cols) -->
        <div class="lg:col-span-2 bg-white rounded-lg border border-[#E3E6E0] shadow-xs overflow-hidden">
            <div class="p-4 border-b border-[#E3E6E0] flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-[#303632]">Daftar Kategori</h3>
                    <p class="text-xs text-[#747B75]">Total {{ count($categories) }} kategori terdaftar</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#F0F1ED] text-[#747B75] uppercase text-[10px] font-semibold border-b border-[#E3E6E0]">
                        <tr>
                            <th class="px-4 py-3">Nama Kategori</th>
                            <th class="px-4 py-3">Deskripsi</th>
                            <th class="px-4 py-3 text-center">Jumlah Produk</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E3E6E0]">
                        @forelse($categories as $cat)
                        <tr class="hover:bg-[#F7F7F4] transition-colors">
                            <td class="px-4 py-3 font-semibold text-[#303632]">
                                {{ $cat->name }}
                            </td>
                            <td class="px-4 py-3 text-[#747B75]">
                                {{ $cat->description ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-center font-bold text-[#42634F]">
                                {{ $cat->products_count }} item
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($cat->is_active)
                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-[#E6F4EA] text-[#357653] font-medium">Aktif</span>
                                @else
                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-[#F0F1ED] text-[#747B75]">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <button type="button" 
                                        @click="
                                            isEdit = true;
                                            modalTitle = 'Edit Kategori: {{ addslashes($cat->name) }}';
                                            actionUrl = '{{ route('categories.update', $cat->id) }}';
                                            catName = '{{ addslashes($cat->name) }}';
                                            catDesc = '{{ addslashes($cat->description ?? '') }}';
                                            catActive = {{ $cat->is_active ? 'true' : 'false' }};
                                            showModal = true;
                                        " 
                                        class="text-[#42634F] hover:underline font-medium">
                                    Edit
                                </button>

                                @if($cat->products_count == 0)
                                <form action="{{ route('categories.destroy', $cat->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Hapus kategori ini?')" class="text-[#B84B4B] hover:underline font-medium">
                                        Hapus
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-xs text-[#747B75]">
                                Belum ada kategori produk.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Category Form Card (1 Col) -->
        <div class="bg-white rounded-lg border border-[#E3E6E0] p-5 shadow-xs">
            <h3 class="text-sm font-semibold text-[#303632] mb-1">Tambah Kategori Baru</h3>
            <p class="text-xs text-[#747B75] mb-4">Tambahkan grup baru untuk produk toko Anda.</p>

            <form action="{{ route('categories.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-[#303632] mb-1">Nama Kategori <span class="text-[#B84B4B]">*</span></label>
                    <input type="text" name="name" required placeholder="Contoh: Bumbu Dapur" 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632] focus:ring-2 focus:ring-[#42634F]">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#303632] mb-1">Keterangan (Opsional)</label>
                    <textarea name="description" rows="3" placeholder="Keterangan singkat..." 
                              class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]"></textarea>
                </div>

                <button type="submit" class="w-full py-2 px-4 rounded-lg bg-[#42634F] hover:bg-[#354F3F] text-white text-xs font-medium shadow-sm transition-colors">
                    Simpan Kategori
                </button>
            </form>
        </div>

    </div>

    <!-- Edit Category Modal -->
    <div x-show="showModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-[#E3E6E0]" @click.away="showModal = false">
            <h3 class="text-base font-semibold text-[#303632] mb-4" x-text="modalTitle"></h3>
            
            <form :action="actionUrl" method="POST" class="space-y-4">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div>
                    <label class="block text-xs font-semibold text-[#303632] mb-1">Nama Kategori</label>
                    <input type="text" name="name" x-model="catName" required 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#303632] mb-1">Deskripsi</label>
                    <textarea name="description" x-model="catDesc" rows="3" 
                              class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]"></textarea>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" :checked="catActive" class="rounded border-[#E3E6E0] text-[#42634F]">
                    <label class="text-xs font-medium text-[#303632]">Status Kategori Aktif</label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#E3E6E0]">
                    <button type="button" @click="showModal = false" class="px-3 py-1.5 rounded-lg text-xs font-medium text-[#747B75] hover:bg-[#F0F1ED]">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-[#42634F] hover:bg-[#354F3F] text-white text-xs font-medium">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
