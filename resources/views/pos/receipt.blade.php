@extends('layouts.app')

@section('title', 'Struk Transaksi ' . $transaction->invoice_number)
@section('page_title', 'Struk Pembayaran')
@section('page_subtitle', 'Bukti transaksi resmi ' . $transaction->invoice_number)

@section('topbar_actions')
<div class="flex items-center gap-2">
    <a href="{{ route('pos.index') }}" class="px-3 py-1.5 rounded-lg bg-[#42634F] hover:bg-[#354F3F] text-white text-xs font-medium flex items-center gap-1.5 shadow-sm">
        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
        <span>Transaksi Baru</span>
    </a>
</div>
@endsection

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <!-- Action Bar (Hidden when printing) -->
    <div class="no-print bg-white rounded-lg border border-[#E3E6E0] p-4 flex flex-wrap items-center justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-[#357653]"></span>
            <span class="text-xs font-medium text-[#303632]">Transaksi Berhasil Diselesaikan!</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-3 py-2 rounded-lg bg-[#303632] hover:bg-black text-white text-xs font-medium flex items-center gap-1.5 shadow-sm transition-colors">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Cetak Struk (Thermal 80mm)</span>
            </button>
            <button @click="$dispatch('open-wa-modal')" class="px-3 py-2 rounded-lg bg-[#357653] hover:bg-[#2c5f43] text-white text-xs font-medium flex items-center gap-1.5 shadow-sm transition-colors">
                <i data-lucide="message-square" class="w-4 h-4"></i>
                <span>Kirim WhatsApp</span>
            </button>
            <a href="{{ route('transactions.show', $transaction->id) }}" class="px-3 py-2 rounded-lg border border-[#E3E6E0] hover:bg-[#F0F1ED] text-xs font-medium text-[#303632] transition-colors">
                Detail Transaksi
            </a>
        </div>
    </div>

    <!-- Thermal Receipt Card -->
    <div id="thermal-receipt" class="bg-white rounded-lg border border-[#E3E6E0] p-6 shadow-sm mx-auto max-w-[380px] font-mono text-xs text-[#303632] leading-tight">
        
        <!-- Header -->
        <div class="text-center pb-3 border-b border-dashed border-[#747B75]/40 space-y-1">
            <h2 class="text-sm font-bold uppercase tracking-wide">{{ $storeSettings['name'] }}</h2>
            @if($storeSettings['tagline'])
                <p class="text-[10px] text-[#747B75]">{{ $storeSettings['tagline'] }}</p>
            @endif
            <p class="text-[11px] whitespace-pre-line">{{ $storeSettings['address'] }}</p>
            @if($storeSettings['phone'])
                <p class="text-[11px]">Telp: {{ $storeSettings['phone'] }}</p>
            @endif
        </div>

        <!-- Meta -->
        <div class="py-2.5 border-b border-dashed border-[#747B75]/40 space-y-1 text-[11px]">
            <div class="flex justify-between">
                <span>No. Invoice:</span>
                <span class="font-bold">{{ $transaction->invoice_number }}</span>
            </div>
            <div class="flex justify-between">
                <span>Tanggal:</span>
                <span>{{ $transaction->transaction_date->format('d/m/Y H:i:s') }}</span>
            </div>
            <div class="flex justify-between">
                <span>Kasir:</span>
                <span>{{ $transaction->user->name ?? 'Kasir' }}</span>
            </div>
            @if($transaction->customer)
            <div class="flex justify-between">
                <span>Pelanggan:</span>
                <span>{{ $transaction->customer->name }}</span>
            </div>
            @endif
        </div>

        <!-- Items -->
        <div class="py-3 border-b border-dashed border-[#747B75]/40 space-y-2">
            @foreach($transaction->items as $item)
            <div>
                <div class="font-semibold">{{ $item->product_name }}</div>
                <div class="flex justify-between text-[11px] text-[#747B75]">
                    <span>{{ $item->quantity }} x Rp {{ number_format($item->unit_price, 0, ',', '.') }}</span>
                    <span class="font-bold text-[#303632]">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Financial Totals -->
        <div class="py-2.5 border-b border-dashed border-[#747B75]/40 space-y-1.5 text-[11px]">
            <div class="flex justify-between">
                <span>Subtotal:</span>
                <span>Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
            </div>
            @if($transaction->discount_amount > 0)
            <div class="flex justify-between text-[#B84B4B]">
                <span>Diskon:</span>
                <span>-Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
            </div>
            @endif
            @if($transaction->tax_amount > 0)
            <div class="flex justify-between">
                <span>Pajak:</span>
                <span>Rp {{ number_format($transaction->tax_amount, 0, ',', '.') }}</span>
            </div>
            @endif
            <div class="flex justify-between text-xs font-bold pt-1 border-t border-[#747B75]/20">
                <span>TOTAL:</span>
                <span>Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="py-2.5 border-b border-dashed border-[#747B75]/40 space-y-1 text-[11px]">
            <div class="flex justify-between">
                <span>Metode Bayar:</span>
                <span class="font-bold uppercase">{{ $transaction->payment_method }}</span>
            </div>
            <div class="flex justify-between">
                <span>Bayar:</span>
                <span>Rp {{ number_format($transaction->cash_received, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between">
                <span>Kembalian:</span>
                <span class="font-bold">Rp {{ number_format($transaction->cash_change, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="pt-3 text-center text-[10px] space-y-1 text-[#747B75]">
            <p class="whitespace-pre-line">{{ $storeSettings['footer'] }}</p>
            <p class="font-sans text-[9px] pt-1 text-[#747B75]/70">Kasir Pintar POS System</p>
        </div>

    </div>

    <!-- WhatsApp Modal -->
    <div x-data="{ open: false, phone: '{{ $transaction->customer->phone ?? '' }}', sending: false, statusMsg: '' }"
         x-show="open" 
         @open-wa-modal.window="open = true"
         x-cloak
         class="no-print fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-xl max-w-sm w-full p-5 shadow-xl border border-[#E3E6E0]" @click.away="open = false">
            <div class="flex items-center gap-2 text-[#357653] mb-3">
                <i data-lucide="message-square" class="w-5 h-5"></i>
                <h3 class="text-sm font-semibold text-[#303632]">Kirim Struk ke WhatsApp</h3>
            </div>
            <p class="text-xs text-[#747B75] mb-4">
                Masukkan nomor WhatsApp pelanggan (contoh: 08123456789 atau 628123456789).
            </p>
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-[#303632] mb-1">Nomor WhatsApp Pelanggan</label>
                    <input type="text" x-model="phone" placeholder="081234567890" 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632] focus:ring-2 focus:ring-[#357653]/30">
                </div>

                <div x-show="statusMsg" class="p-2.5 rounded bg-[#F0F1ED] text-xs text-[#303632]" x-text="statusMsg"></div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="open = false" class="px-3 py-1.5 rounded-lg text-xs font-medium text-[#747B75] hover:bg-[#F0F1ED]">
                        Tutup
                    </button>
                    <button type="button" 
                            @click="sendWhatsAppReceipt()"
                            :disabled="sending || !phone"
                            class="px-4 py-1.5 rounded-lg bg-[#357653] hover:bg-[#2c5f43] text-white text-xs font-medium transition-colors">
                        <span x-show="!sending">Kirim Sekarang</span>
                        <span x-show="sending">Mengirim...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function sendWhatsAppReceipt() {
        const modal = document.querySelector('[x-data*="open-wa-modal"]') || document.querySelector('[x-data*="statusMsg"]');
        const phoneInput = document.querySelector('input[x-model="phone"]');
        const phone = phoneInput ? phoneInput.value : '';

        if (!phone) {
            alert('Masukkan nomor WhatsApp terlebih dahulu.');
            return;
        }

        fetch("{{ route('pos.receipt.whatsapp', $transaction->id) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({ phone: phone })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (data.wa_url) {
                    if (confirm('Struk siap dikirim! Buka WhatsApp Web / Aplikasi sekarang?')) {
                        window.open(data.wa_url, '_blank');
                    }
                } else {
                    alert('Struk digital berhasil dikirim via WhatsApp!');
                }
            } else {
                alert('Gagal mengirim: ' + (data.error || 'Terjadi kesalahan'));
            }
        })
        .catch(err => alert('Error: ' + err.message));
    }
</script>
@endpush
