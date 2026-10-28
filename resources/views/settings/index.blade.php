@extends('layouts.app')

@section('title', 'Pengaturan Toko')
@section('page_title', 'Pengaturan Toko & Jam Operasional')
@section('page_subtitle', 'Identitas minimarket, jam kerja operasional, batas hari bisnis, dan format struk')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg border border-[#E3E6E0] p-6 shadow-xs">
        
        <form action="{{ route('settings.update') }}" method="POST" class="space-y-6 text-xs">
            @csrf

            <!-- Section 1: Profil Toko -->
            <div>
                <h3 class="text-sm font-bold text-[#303632] pb-2 border-b border-[#E3E6E0] mb-4">Profil & Identitas Usaha</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-[#303632] mb-1">Nama Toko / Minimarket <span class="text-[#B84B4B]">*</span></label>
                        <input type="text" name="store_name" value="{{ old('store_name', $settings['store_name']) }}" required 
                               class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                    </div>

                    <div>
                        <label class="block font-semibold text-[#303632] mb-1">Slogan / Tagline Toko</label>
                        <input type="text" name="store_tagline" value="{{ old('store_tagline', $settings['store_tagline']) }}" 
                               class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-[#303632] mb-1">Alamat Lengkap Toko <span class="text-[#B84B4B]">*</span></label>
                        <input type="text" name="store_address" value="{{ old('store_address', $settings['store_address']) }}" required 
                               class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                    </div>

                    <div>
                        <label class="block font-semibold text-[#303632] mb-1">Nomor Telepon / CS Toko <span class="text-[#B84B4B]">*</span></label>
                        <input type="text" name="store_phone" value="{{ old('store_phone', $settings['store_phone']) }}" required 
                               class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                    </div>

                    <div>
                        <label class="block font-semibold text-[#303632] mb-1">Alamat Email Toko</label>
                        <input type="email" name="store_email" value="{{ old('store_email', $settings['store_email']) }}" 
                               class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                    </div>
                </div>
            </div>

            <!-- Section 2: Jam Operasional & Batas Hari Bisnis -->
            <div>
                <h3 class="text-sm font-bold text-[#303632] pb-2 border-b border-[#E3E6E0] mb-4">Jam Operasional & Zona Waktu</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-[#303632] mb-1">Jam Buka Toko <span class="text-[#B84B4B]">*</span></label>
                        <input type="time" name="opening_hour" value="{{ old('opening_hour', $settings['opening_hour']) }}" required 
                               class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                    </div>

                    <div>
                        <label class="block font-semibold text-[#303632] mb-1">Jam Tutup Toko <span class="text-[#B84B4B]">*</span></label>
                        <input type="time" name="closing_hour" value="{{ old('closing_hour', $settings['closing_hour']) }}" required 
                               class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                    </div>

                    <div>
                        <label class="block font-semibold text-[#303632] mb-1">Batas Pergantian Hari Bisnis (Cut-off) <span class="text-[#B84B4B]">*</span></label>
                        <input type="time" name="business_day_cutoff" value="{{ old('business_day_cutoff', $settings['business_day_cutoff']) }}" required 
                               class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                        <p class="text-[11px] text-[#747B75] mt-0.5">Transaksi sebelum jam ini dihitung ke tanggal bisnis hari sebelumnya (berguna jika toko buka melewati tengah malam).</p>
                    </div>

                    <div>
                        <label class="block font-semibold text-[#303632] mb-1">Zona Waktu Sistem <span class="text-[#B84B4B]">*</span></label>
                        <select name="timezone" class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                            <option value="Asia/Jakarta" {{ $settings['timezone'] === 'Asia/Jakarta' ? 'selected' : '' }}>WIB (Asia/Jakarta)</option>
                            <option value="Asia/Makassar" {{ $settings['timezone'] === 'Asia/Makassar' ? 'selected' : '' }}>WITA (Asia/Makassar)</option>
                            <option value="Asia/Jayapura" {{ $settings['timezone'] === 'Asia/Jayapura' ? 'selected' : '' }}>WIT (Asia/Jayapura)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 3: Pajak & Struk Pembayaran -->
            <div>
                <h3 class="text-sm font-bold text-[#303632] pb-2 border-b border-[#E3E6E0] mb-4">Pajak Penjualan & Format Struk Kasir</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-[#303632] mb-1">Persentase Pajak Penjualan (%) <span class="text-[#B84B4B]">*</span></label>
                        <input type="number" name="tax_percentage" value="{{ old('tax_percentage', $settings['tax_percentage']) }}" min="0" max="100" step="0.5" required 
                               class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                        <p class="text-[11px] text-[#747B75] mt-0.5">Isi 0 jika toko tidak mengenakan PPN tambahan ke pembeli.</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-[#303632] mb-1">Header Tambahan Struk (Thermal 80mm)</label>
                        <textarea name="receipt_header" rows="2" placeholder="Teks tambahan di atas struk..." 
                                  class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632] font-mono">{{ old('receipt_header', $settings['receipt_header']) }}</textarea>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-[#303632] mb-1">Catatan Kaki Struk (Footer)</label>
                        <textarea name="receipt_footer" rows="2" placeholder="Pesan terima kasih, ketentuan retur..." 
                                  class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">{{ old('receipt_footer', $settings['receipt_footer']) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="pt-4 border-t border-[#E3E6E0] flex justify-end">
                <button type="submit" class="px-5 py-2.5 rounded-lg bg-[#42634F] hover:bg-[#354F3F] text-white text-xs font-semibold shadow-sm transition-colors">
                    Simpan Pengaturan Toko
                </button>
            </div>

        </form>

    </div>
</div>
@endsection
