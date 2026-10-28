@extends('layouts.app')

@section('title', 'Pengguna & Hak Akses')
@section('page_title', 'Pengguna & Manajemen Hak Akses')
@section('page_subtitle', 'Pengelolaan akun kasir dan administrator sistem kasir')

@section('topbar_actions')
<button @click="$dispatch('open-add-user-modal')" class="px-3 py-1.5 rounded-lg bg-[#42634F] hover:bg-[#354F3F] text-white text-xs font-medium flex items-center gap-1.5 shadow-sm transition-colors">
    <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
    <span>Tambah Pengguna Baru</span>
</button>
@endsection

@section('content')
<div x-data="{ showModal: false, isEdit: false, modalTitle: 'Tambah Pengguna', actionUrl: '{{ route('users.store') }}', uName: '', uEmail: '', uRole: 'cashier', uPhone: '', uActive: true }">
    
    <div class="bg-white rounded-lg border border-[#E3E6E0] shadow-xs overflow-hidden">
        <div class="p-4 border-b border-[#E3E6E0] flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-[#303632]">Daftar Akun Pengguna</h3>
                <p class="text-xs text-[#747B75]">Hak akses terbagi menjadi Administrator (Penuh) dan Kasir (Hanya Transaksi & Shift)</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F0F1ED] text-[#747B75] uppercase text-[10px] font-semibold border-b border-[#E3E6E0]">
                    <tr>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Email & Kontak</th>
                        <th class="px-4 py-3 text-center">Peran (Role)</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3">Dibuat Pada</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E3E6E0]">
                    @foreach($users as $u)
                    <tr class="hover:bg-[#F7F7F4] transition-colors">
                        <td class="px-4 py-3 font-semibold text-[#303632]">
                            {{ $u->name }}
                            @if($u->id === auth()->id())
                                <span class="text-[10px] text-[#42634F] font-normal">(Anda)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-[#747B75]">
                            <p class="text-[#303632]">{{ $u->email }}</p>
                            <p class="text-[11px] font-mono">{{ $u->phone ?? '-' }}</p>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($u->isAdmin())
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#42634F] text-white">Administrator</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#DCE8DE] text-[#42634F]">Kasir</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($u->is_active)
                                <span class="text-[10px] px-2 py-0.5 rounded bg-[#E6F4EA] text-[#357653] font-medium">Aktif</span>
                            @else
                                <span class="text-[10px] px-2 py-0.5 rounded bg-[#FCE8E6] text-[#B84B4B] font-medium">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-[#747B75]">
                            {{ $u->created_at->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <button type="button" 
                                    @click="
                                        isEdit = true;
                                        modalTitle = 'Edit Pengguna: {{ addslashes($u->name) }}';
                                        actionUrl = '{{ route('users.update', $u->id) }}';
                                        uName = '{{ addslashes($u->name) }}';
                                        uEmail = '{{ addslashes($u->email) }}';
                                        uRole = '{{ $u->role }}';
                                        uPhone = '{{ addslashes($u->phone ?? '') }}';
                                        uActive = {{ $u->is_active ? 'true' : 'false' }};
                                        showModal = true;
                                    "
                                    class="text-[#42634F] hover:underline font-medium">
                                Edit
                            </button>
                            @if($u->id !== auth()->id())
                            <form action="{{ route('users.toggle', $u->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" onclick="return confirm('Ubah status pengguna ini?')" class="text-xs text-[#747B75] hover:text-[#B84B4B]">
                                    {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- User Modal (Add/Edit) -->
    <div x-show="showModal" 
         @open-add-user-modal.window="isEdit = false; modalTitle = 'Tambah Pengguna Baru'; actionUrl = '{{ route('users.store') }}'; uName = ''; uEmail = ''; uRole = 'cashier'; uPhone = ''; uActive = true; showModal = true"
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
                    <input type="text" name="name" x-model="uName" required placeholder="Contoh: Budi Santoso" 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                </div>

                <div>
                    <label class="block font-semibold text-[#303632] mb-1">Alamat Email Login <span class="text-[#B84B4B]">*</span></label>
                    <input type="email" name="email" x-model="uEmail" required placeholder="kasir@toko.com" 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                </div>

                <div>
                    <label class="block font-semibold text-[#303632] mb-1">Peran (Hak Akses) <span class="text-[#B84B4B]">*</span></label>
                    <select name="role" x-model="uRole" required class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                        <option value="cashier">Kasir (Hanya POS & Shift Kasir)</option>
                        <option value="admin">Administrator (Akses Lengkap & Laba/HPP)</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-[#303632] mb-1">Nomor Telepon / WhatsApp</label>
                    <input type="text" name="phone" x-model="uPhone" placeholder="08123456789" 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                </div>

                <div>
                    <label class="block font-semibold text-[#303632] mb-1">
                        <span x-text="isEdit ? 'Password Baru (Kosongkan bila tidak diubah)' : 'Password Akun *'"></span>
                    </label>
                    <input type="password" name="password" :required="!isEdit" placeholder="Minimal 6 karakter" 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-[#E3E6E0]">
                    <button type="button" @click="showModal = false" class="px-3 py-1.5 rounded-lg text-xs font-medium text-[#747B75] hover:bg-[#F0F1ED]">Batal</button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-[#42634F] hover:bg-[#354F3F] text-white text-xs font-medium">Simpan Data Pengguna</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
