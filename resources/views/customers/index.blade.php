@extends('layouts.app')

@section('title', 'Daftar Pelanggan')
@section('page_title', 'Manajemen Pelanggan')
@section('page_subtitle', 'Data kontak pelanggan untuk pengiriman struk WhatsApp dan riwayat belanja')

@section('topbar_actions')
<button @click="$dispatch('open-add-customer-modal')" class="px-3 py-1.5 rounded-lg bg-[#42634F] hover:bg-[#354F3F] text-white text-xs font-medium flex items-center gap-1.5 shadow-sm transition-colors">
    <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
    <span>Tambah Pelanggan</span>
</button>
@endsection

@section('content')
<div x-data="{ showModal: false, isEdit: false, modalTitle: 'Tambah Pelanggan Baru', actionUrl: '{{ route('customers.store') }}', cName: '', cPhone: '', cEmail: '', cAddress: '' }" class="space-y-6">

    <!-- Search Bar -->
    <div class="bg-white rounded-lg border border-[#E3E6E0] p-4 shadow-xs">
        <form action="{{ route('customers.index') }}" method="GET" class="flex items-center gap-3">
            <div class="relative flex-1">
                <i data-lucide="search" class="w-4 h-4 text-[#747B75] absolute left-3 top-2.5"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, nomor telepon WhatsApp, atau email..." 
                       class="w-full pl-9 pr-3 py-1.5 rounded-lg border border-[#E3E6E0] text-xs text-[#303632]">
            </div>
            <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#42634F] hover:bg-[#354F3F] text-white text-xs font-medium">Cari</button>
            <a href="{{ route('customers.index') }}" class="px-3 py-1.5 rounded-lg border border-[#E3E6E0] hover:bg-[#F0F1ED] text-[#303632] text-xs">Reset</a>
        </form>
    </div>

    <!-- Customer Table -->
    <div class="bg-white rounded-lg border border-[#E3E6E0] shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F0F1ED] text-[#747B75] uppercase text-[10px] font-semibold border-b border-[#E3E6E0]">
                    <tr>
                        <th class="px-4 py-3">Nama Pelanggan</th>
                        <th class="px-4 py-3">Nomor WhatsApp</th>
                        <th class="px-4 py-3">Alamat</th>
                        <th class="px-4 py-3 text-center">Jumlah Belanja</th>
                        <th class="px-4 py-3 text-right">Akumulasi Belanja</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E3E6E0]">
                    @forelse($customers as $c)
                    <tr class="hover:bg-[#F7F7F4] transition-colors">
                        <td class="px-4 py-3 font-semibold text-[#303632]">
                            {{ $c->name }}
                        </td>
                        <td class="px-4 py-3 text-[#303632] font-mono">
                            {{ $c->phone ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-[#747B75]">
                            {{ $c->address ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-center font-bold text-[#42634F]">
                            {{ $c->transactions_count }} kali
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-[#357653]">
                            Rp {{ number_format($c->total_spent, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <a href="{{ route('customers.show', $c->id) }}" class="text-[#747B75] hover:text-[#303632] font-medium">Riwayat</a>
                            <button type="button" 
                                    @click="
                                        isEdit = true;
                                        modalTitle = 'Edit Data: {{ addslashes($c->name) }}';
                                        actionUrl = '{{ route('customers.update', $c->id) }}';
                                        cName = '{{ addslashes($c->name) }}';
                                        cPhone = '{{ addslashes($c->phone ?? '') }}';
                                        cEmail = '{{ addslashes($c->email ?? '') }}';
                                        cAddress = '{{ addslashes($c->address ?? '') }}';
                                        showModal = true;
                                    " 
                                    class="text-[#42634F] hover:underline font-medium">
                                Edit
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-xs text-[#747B75]">
                            Tidak ada data pelanggan yang cocok.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
        <div class="p-3 border-t border-[#E3E6E0] bg-[#FDFDFD]">
            {{ $customers->links() }}
        </div>
        @endif
    </div>

    <!-- Customer Modal -->
    <div x-show="showModal" 
         @open-add-customer-modal.window="isEdit = false; modalTitle = 'Tambah Pelanggan Baru'; actionUrl = '{{ route('customers.store') }}'; cName = ''; cPhone = ''; cEmail = ''; cAddress = ''; showModal = true"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-[#E3E6E0]" @click.away="showModal = false">
            <h3 class="text-base font-semibold text-[#303632] mb-4" x-text="modalTitle"></h3>

            <form :action="actionUrl" method="POST" class="space-y-4 text-xs">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div>
                    <label class="block font-semibold text-[#303632] mb-1">Nama Lengkap <span class="text-[#B84B4B]">*</span></label>
                    <input type="text" name="name" x-model="cName" required placeholder="Contoh: Ibu Henny" 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                </div>

                <div>
                    <label class="block font-semibold text-[#303632] mb-1">Nomor Telepon / WhatsApp</label>
                    <input type="text" name="phone" x-model="cPhone" placeholder="081234567890" 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632] font-mono">
                    <p class="text-[11px] text-[#747B75] mt-0.5">Digunakan untuk pengiriman struk digital WhatsApp.</p>
                </div>

                <div>
                    <label class="block font-semibold text-[#303632] mb-1">Alamat Email (Opsional)</label>
                    <input type="email" name="email" x-model="cEmail" placeholder="pelanggan@email.com" 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                </div>

                <div>
                    <label class="block font-semibold text-[#303632] mb-1">Alamat Domisili (Opsional)</label>
                    <textarea name="address" x-model="cAddress" rows="2" placeholder="Nama jalan, perumahan, RT/RW..." 
                              class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#E3E6E0]">
                    <button type="button" @click="showModal = false" class="px-3 py-1.5 rounded-lg text-xs font-medium text-[#747B75] hover:bg-[#F0F1ED]">Batal</button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-[#42634F] hover:bg-[#354F3F] text-white text-xs font-medium">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
