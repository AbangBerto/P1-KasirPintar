@extends('layouts.app')

@section('title', 'Integrasi WhatsApp')
@section('page_title', 'Integrasi WhatsApp Business Platform')
@section('page_subtitle', 'Pengiriman otomatis laporan harian, notifikasi peringatan stok, dan struk digital kasir')

@section('content')
<div class="space-y-6">

    <!-- Status Banner -->
    <div class="p-4 rounded-xl border {{ $settings['demo_mode'] ? 'bg-[#FEF7E0] border-[#A66D24]/30' : 'bg-[#E6F4EA] border-[#357653]/30' }} flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-3">
            <span class="w-10 h-10 rounded-lg {{ $settings['demo_mode'] ? 'bg-[#A66D24] text-white' : 'bg-[#357653] text-white' }} flex items-center justify-center shrink-0">
                <i data-lucide="message-square" class="w-5 h-5"></i>
            </span>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-bold {{ $settings['demo_mode'] ? 'text-[#A66D24]' : 'text-[#357653]' }}">
                        {{ $settings['demo_mode'] ? 'Mode Demo & Tautan Langsung Aktif' : 'WhatsApp Cloud API Aktif' }}
                    </h3>
                    <span class="px-2 py-0.2 rounded text-[10px] font-bold {{ $settings['demo_mode'] ? 'bg-white/80 text-[#A66D24]' : 'bg-white/80 text-[#357653]' }}">
                        {{ $settings['demo_mode'] ? 'Simulator / Direct Link' : 'Connected to Meta' }}
                    </span>
                </div>
                <p class="text-[11px] text-[#747B75] mt-0.5">
                    @if($settings['demo_mode'])
                        Aplikasi dapat beroperasi penuh tanpa token Meta. Pesan tercatat di log dan dapat dikirim langsung via WhatsApp Web/App sekali klik.
                    @else
                        Mengirim pesan otomatis di latar belakang melalui Meta WhatsApp Business Platform Cloud API resmi.
                    @endif
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto">
            <form action="{{ route('whatsapp.daily_report') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#42634F] hover:bg-[#354F3F] text-white font-medium flex items-center gap-1.5 shadow-sm transition-colors">
                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                    <span>Kirim Laporan Harian Sekarang</span>
                </button>
            </form>
        </div>
    </div>

    <!-- 2 Column Layout: Settings Form & Quick Test -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left (8 Cols): WhatsApp Cloud API Settings -->
        <div class="lg:col-span-8 bg-white rounded-lg border border-[#E3E6E0] p-6 shadow-xs space-y-5">
            <div class="pb-3 border-b border-[#E3E6E0]">
                <h3 class="text-sm font-bold text-[#303632]">Konfigurasi WhatsApp Cloud API</h3>
                <p class="text-xs text-[#747B75]">Kredensial resmi dari Meta for Developers (WhatsApp Business Platform)</p>
            </div>

            <form action="{{ route('whatsapp.settings.update') }}" method="POST" class="space-y-4 text-xs">
                @csrf

                <!-- Mode Demo Toggle -->
                <div class="p-3.5 rounded-lg bg-[#F7F7F4] border border-[#E3E6E0]">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" name="demo_mode" value="1" {{ $settings['demo_mode'] ? 'checked' : '' }} 
                               class="mt-0.5 rounded border-[#E3E6E0] text-[#42634F] focus:ring-[#42634F]">
                        <div>
                            <span class="font-bold text-[#303632] block">Aktifkan Mode Demo & Tautan Direct WhatsApp</span>
                            <span class="text-[11px] text-[#747B75]">Jika dicentang, pesan disimpan ke log lokal dan menghasilkan tombol tautan <code>wa.me</code> tanpa memerlukan token Meta berbayar.</span>
                        </div>
                    </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- API Endpoint -->
                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-[#303632] mb-1">API Endpoint</label>
                        <input type="url" name="api_endpoint" value="{{ $settings['api_endpoint'] }}" required 
                               class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632] font-mono">
                    </div>

                    <!-- Phone Number ID -->
                    <div>
                        <label class="block font-semibold text-[#303632] mb-1">Phone Number ID Meta</label>
                        <input type="text" name="phone_number_id" value="{{ $settings['phone_number_id'] }}" placeholder="Contoh: 109876543210987" 
                               class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632] font-mono">
                    </div>

                    <!-- Recipient Number (Owner Phone) -->
                    <div>
                        <label class="block font-semibold text-[#303632] mb-1">Nomor WhatsApp Pemilik (Penerima Laporan) <span class="text-[#B84B4B]">*</span></label>
                        <input type="text" name="recipient_number" value="{{ $settings['recipient_number'] }}" required placeholder="6281234567890" 
                               class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632] font-mono">
                    </div>

                    <!-- Permanent Access Token -->
                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-[#303632] mb-1">System User Access Token (Meta Graph API)</label>
                        <input type="password" name="access_token" value="{{ $settings['access_token'] }}" placeholder="EAAG..." 
                               class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632] font-mono">
                        <p class="text-[11px] text-[#747B75] mt-0.5">Disimpan aman di database server dan tidak pernah diekspos ke publik.</p>
                    </div>

                    <!-- Daily Report Automation Schedule -->
                    <div>
                        <label class="block font-semibold text-[#303632] mb-1">Jam Pengiriman Laporan Harian</label>
                        <input type="time" name="daily_report_time" value="{{ $settings['daily_report_time'] }}" required 
                               class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                    </div>

                    <div class="space-y-2 pt-2">
                        <label class="flex items-center gap-2 cursor-pointer font-medium text-[#303632]">
                            <input type="checkbox" name="daily_report_enabled" value="1" {{ $settings['daily_report_enabled'] ? 'checked' : '' }} 
                                   class="rounded border-[#E3E6E0] text-[#42634F]">
                            <span>Otomatis kirim laporan harian tiap malam</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer font-medium text-[#303632]">
                            <input type="checkbox" name="stock_alert_enabled" value="1" {{ $settings['stock_alert_enabled'] ? 'checked' : '' }} 
                                   class="rounded border-[#E3E6E0] text-[#42634F]">
                            <span>Kirim notifikasi instan jika ada stok menipis</span>
                        </label>
                    </div>
                </div>

                <div class="pt-3 border-t border-[#E3E6E0] flex justify-end">
                    <button type="submit" class="px-5 py-2 rounded-lg bg-[#42634F] hover:bg-[#354F3F] text-white text-xs font-semibold shadow-sm transition-colors">
                        Simpan Pengaturan WhatsApp
                    </button>
                </div>
            </form>
        </div>

        <!-- Right (4 Cols): Test Send Tool -->
        <div class="lg:col-span-4 bg-white rounded-lg border border-[#E3E6E0] p-6 shadow-xs space-y-4">
            <div>
                <h3 class="text-sm font-bold text-[#303632]">Uji Kirim Pesan</h3>
                <p class="text-xs text-[#747B75]">Kirim pesan pengujian untuk memverifikasi jalur gateway</p>
            </div>

            <form action="{{ route('whatsapp.test') }}" method="POST" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-semibold text-[#303632] mb-1">Nomor Tujuan Uji</label>
                    <input type="text" name="recipient_number" value="{{ $settings['recipient_number'] }}" required 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632] font-mono">
                </div>

                <button type="submit" class="w-full py-2 px-3 rounded-lg bg-[#303632] hover:bg-black text-white text-xs font-semibold transition-colors flex items-center justify-center gap-1.5">
                    <i data-lucide="zap" class="w-3.5 h-3.5 text-[#FEF7E0]"></i>
                    <span>Jalankan Uji Koneksi</span>
                </button>
            </form>

            <div class="pt-3 border-t border-[#E3E6E0] text-[11px] text-[#747B75] space-y-1">
                <p class="font-semibold text-[#303632]">Format Pesan Laporan Harian:</p>
                <div class="p-2.5 rounded bg-[#F7F7F4] font-mono text-[10px] text-[#303632] whitespace-pre-wrap leading-tight border border-[#E3E6E0]">LAPORAN HARIAN — KASIR PINTAR
Tanggal: {{ date('d F Y') }}
Total penjualan neto: Rp ...
Jumlah transaksi: ...
Estimasi laba kotor: Rp ...
Metode: Tunai, QRIS, Transfer
Produk terlaris: ...
Stok menipis: ...</div>
            </div>
        </div>

    </div>

    <!-- Message Logs Table -->
    <div class="bg-white rounded-lg border border-[#E3E6E0] shadow-xs overflow-hidden">
        <div class="p-4 border-b border-[#E3E6E0] flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-[#303632]">Log Pengiriman Pesan WhatsApp</h3>
                <p class="text-xs text-[#747B75]">Audit histori seluruh notifikasi, laporan, dan struk yang telah diproses</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#F0F1ED] text-[#747B75] uppercase text-[10px] font-semibold border-b border-[#E3E6E0]">
                    <tr>
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">Tujuan</th>
                        <th class="px-4 py-3">Tipe Pesan</th>
                        <th class="px-4 py-3">Konten Pesan</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E3E6E0]">
                    @forelse($logs as $log)
                    @php
                        $payload = json_decode($log->response_payload, true);
                        $directUrl = $payload['wa_link'] ?? "https://wa.me/{$log->recipient_phone}?text=" . urlencode($log->content);
                    @endphp
                    <tr class="hover:bg-[#F7F7F4]">
                        <td class="px-4 py-3 text-[#747B75] whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="px-4 py-3 font-mono font-medium text-[#303632]">{{ $log->recipient_phone }}</td>
                        <td class="px-4 py-3">
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase bg-[#F0F1ED] text-[#303632]">
                                {{ str_replace('_', ' ', $log->message_type) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 max-w-xs">
                            <p class="text-[11px] text-[#303632] truncate font-mono">{{ $log->content }}</p>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($log->status === 'sent' || $log->status === 'delivered')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#E6F4EA] text-[#357653]">Terkirim</span>
                            @elseif($log->status === 'demo_dispatched')
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#FEF7E0] text-[#A66D24]">Siap Kirim (Demo)</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#FCE8E6] text-[#B84B4B]">Gagal</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <a href="{{ $directUrl }}" target="_blank" class="text-[#357653] hover:underline font-semibold" title="Buka di WhatsApp Web / App">
                                Buka WhatsApp
                            </a>
                            <form action="{{ route('whatsapp.retry', $log->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-xs text-[#747B75] hover:text-[#303632]">Ulang</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-xs text-[#747B75]">Belum ada riwayat pesan WhatsApp yang dikirim.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
        <div class="p-3 border-t border-[#E3E6E0] bg-[#FDFDFD]">
            {{ $logs->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
