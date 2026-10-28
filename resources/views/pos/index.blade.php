@extends('layouts.app')

@section('title', 'Transaksi Kasir (POS)')
@section('page_title', 'Transaksi Kasir (Point of Sale)')
@section('page_subtitle', 'Pencatatan penjualan cepat dengan barcode, keranjang instan, dan multi pembayaran')

@section('topbar_actions')
<div class="flex items-center gap-2">
    @if($activeShift)
    <div class="hidden sm:flex items-center gap-1.5 px-2.5 py-1 rounded bg-[#E6F4EA] border border-[#357653]/20 text-[11px] text-[#357653] font-medium">
        <span class="w-2 h-2 rounded-full bg-[#357653]"></span>
        <span>Shift Aktif: {{ $activeShift->shift_number }}</span>
    </div>
    @else
    <button @click="$dispatch('open-shift-modal')" class="px-2.5 py-1 rounded bg-[#FEF7E0] border border-[#A66D24]/30 text-[11px] text-[#A66D24] font-medium hover:bg-[#A66D24] hover:text-white transition-colors">
        ⚠️ Buka Shift Kasir
    </button>
    @endif
</div>
@endsection

@section('content')
<div x-data="posApp()" class="h-full">

    <!-- Open Shift Required Modal (If no active shift) -->
    <div x-show="showOpenShiftModal" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4"
         style="display: none;">
        <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-[#E3E6E0]" @click.away="showOpenShiftModal = false">
            <div class="flex items-center gap-2.5 text-[#A66D24] mb-3">
                <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                <h3 class="text-base font-semibold text-[#303632]">Buka Shift Kasir Terlebih Dahulu</h3>
            </div>
            <p class="text-xs text-[#747B75] mb-4">
                Untuk menjaga akurasi rekonsiliasi kas di laci kasir, masukkan saldo awal uang tunai modal sebelum melayani transaksi penjualan.
            </p>
            <form action="{{ route('shifts.open') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-[#303632] mb-1">Saldo Awal Kas Laci (Rp)</label>
                    <input type="number" name="starting_cash" value="200000" min="0" step="1000" required 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632] focus:ring-2 focus:ring-[#42634F]/30 focus:border-[#42634F]">
                    <p class="text-[11px] text-[#747B75] mt-1">Contoh: Rp 200.000 uang pecahan kembalian.</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-[#303632] mb-1">Catatan Shift (Opsional)</label>
                    <input type="text" name="notes" placeholder="Contoh: Shift pagi Siti" 
                           class="w-full px-3 py-2 rounded-lg border border-[#E3E6E0] text-sm text-[#303632]">
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="showOpenShiftModal = false" 
                            class="px-3 py-2 rounded-lg text-xs font-medium text-[#747B75] hover:bg-[#F0F1ED]">
                        Lanjut Tanpa Shift
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 rounded-lg bg-[#42634F] hover:bg-[#354F3F] text-white text-xs font-medium transition-colors">
                        Buka Shift & Lanjutkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Main 2-Column POS Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left Side (7 Cols): Product Catalog & Scanner -->
        <div class="lg:col-span-7 space-y-4">
            
            <!-- Search & Barcode Bar -->
            <div class="bg-white rounded-lg border border-[#E3E6E0] p-3 shadow-xs space-y-2">
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 text-[#747B75] absolute left-3 top-3"></i>
                    <input type="text" 
                           x-model="searchQuery" 
                           @keydown.enter.prevent="handleBarcodeOrSearchEnter()"
                           placeholder="Scan barcode scanner atau ketik nama produk / SKU (tekan Enter)..." 
                           class="w-full pl-9 pr-24 py-2.5 rounded-lg border border-[#E3E6E0] text-sm text-[#303632] focus:outline-none focus:ring-2 focus:ring-[#42634F]/30 focus:border-[#42634F] bg-[#FDFDFD]">
                    <div class="absolute right-2.5 top-2 flex items-center gap-1">
                        <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-[#F0F1ED] text-[#747B75] border border-[#E3E6E0]">Enter</span>
                    </div>
                </div>

                <!-- Category Chips -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none text-xs">
                    <button @click="selectedCategory = null" 
                            :class="selectedCategory === null ? 'bg-[#42634F] text-white' : 'bg-[#F0F1ED] hover:bg-[#E3E6E0] text-[#303632]'"
                            class="px-3 py-1.5 rounded-md font-medium shrink-0 transition-colors">
                        Semua Kategori ({{ count($products) }})
                    </button>
                    @foreach($categories as $cat)
                    <button @click="selectedCategory = {{ $cat->id }}" 
                            :class="selectedCategory === {{ $cat->id }} ? 'bg-[#42634F] text-white' : 'bg-[#F0F1ED] hover:bg-[#E3E6E0] text-[#303632]'"
                            class="px-3 py-1.5 rounded-md font-medium shrink-0 transition-colors">
                        {{ $cat->name }}
                    </button>
                    @endforeach
                </div>
            </div>

            <!-- Product Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <template x-for="product in filteredProducts" :key="product.id">
                    <div @click="addToCart(product)" 
                         class="bg-white rounded-lg border border-[#E3E6E0] p-3.5 shadow-xs hover:border-[#42634F] cursor-pointer transition-all flex flex-col justify-between group select-none">
                        <div>
                            <div class="flex items-start justify-between gap-1 mb-1.5">
                                <span class="text-[10px] font-mono font-medium text-[#747B75]" x-text="product.sku"></span>
                                <span :class="product.stock <= 5 ? 'text-[#B84B4B] bg-[#FCE8E6]' : 'text-[#747B75] bg-[#F0F1ED]'"
                                      class="text-[10px] px-1.5 py-0.2 rounded font-semibold" 
                                      x-text="'Stok: ' + product.stock + ' ' + product.unit"></span>
                            </div>
                            <h4 class="text-xs font-semibold text-[#303632] line-clamp-2 leading-snug group-hover:text-[#42634F] transition-colors" x-text="product.name"></h4>
                        </div>
                        <div class="mt-3 pt-2 border-t border-[#E3E6E0] flex items-center justify-between">
                            <span class="text-xs font-bold text-[#357653]" x-text="formatRupiah(product.selling_price)"></span>
                            <span class="w-6 h-6 rounded bg-[#F0F1ED] group-hover:bg-[#42634F] group-hover:text-white flex items-center justify-center text-xs transition-colors">
                                +
                            </span>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Empty Search State -->
            <div x-show="filteredProducts.length === 0" class="bg-white rounded-lg border border-[#E3E6E0] p-8 text-center" style="display: none;">
                <i data-lucide="package-search" class="w-8 h-8 mx-auto text-[#747B75] mb-2"></i>
                <p class="text-sm font-medium text-[#303632]">Produk tidak ditemukan</p>
                <p class="text-xs text-[#747B75] mt-1">Coba gunakan kata kunci nama lain atau bersihkan filter pencarian.</p>
                <button @click="searchQuery = ''; selectedCategory = null" class="mt-3 px-3 py-1.5 rounded bg-[#F0F1ED] hover:bg-[#E3E6E0] text-xs font-medium text-[#303632]">
                    Reset Pencarian
                </button>
            </div>

        </div>

        <!-- Right Side (5 Cols): Cart, Totals, Payment & Checkout -->
        <div class="lg:col-span-5 bg-white rounded-xl border border-[#E3E6E0] shadow-sm flex flex-col sticky top-20">
            
            <!-- Cart Header -->
            <div class="p-4 border-b border-[#E3E6E0] flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="shopping-cart" class="w-4 h-4 text-[#42634F]"></i>
                    <h3 class="text-sm font-semibold text-[#303632]">Keranjang Belanja</h3>
                    <span class="px-2 py-0.5 rounded-full bg-[#DCE8DE] text-[#42634F] font-bold text-xs" x-text="cartTotalItems"></span>
                </div>
                <button x-show="cart.length > 0" @click="clearCart()" class="text-xs text-[#B84B4B] hover:underline font-medium">
                    Kosongkan
                </button>
            </div>

            <!-- Customer Picker -->
            <div class="px-4 py-2.5 bg-[#F7F7F4] border-b border-[#E3E6E0] flex items-center gap-2">
                <i data-lucide="user" class="w-3.5 h-3.5 text-[#747B75]"></i>
                <select x-model="selectedCustomer" class="flex-1 bg-transparent text-xs text-[#303632] focus:outline-none border-none py-0">
                    <option value="">Pelanggan Umum (Walk-in)</option>
                    @foreach($customers as $c)
                    <option value="{{ $c->id }}">{{ $c->name }} {{ $c->phone ? "({$c->phone})" : '' }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Cart Items List -->
            <div class="p-4 overflow-y-auto max-h-[300px] divide-y divide-[#E3E6E0]">
                <template x-for="(item, index) in cart" :key="item.product.id">
                    <div class="py-2.5 first:pt-0 last:pb-0 flex items-center justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-[#303632] truncate" x-text="item.product.name"></p>
                            <p class="text-[11px] text-[#747B75]" x-text="formatRupiah(item.product.selling_price) + ' x ' + item.quantity"></p>
                        </div>
                        
                        <!-- Qty Controls -->
                        <div class="flex items-center gap-1.5">
                            <button @click="decrementQty(index)" class="w-6 h-6 rounded bg-[#F0F1ED] hover:bg-[#E3E6E0] flex items-center justify-center text-xs font-bold text-[#303632]">-</button>
                            <input type="number" 
                                   x-model.number="item.quantity" 
                                   @change="validateQty(index)" 
                                   class="w-10 text-center text-xs font-semibold border border-[#E3E6E0] rounded py-0.5">
                            <button @click="incrementQty(index)" class="w-6 h-6 rounded bg-[#F0F1ED] hover:bg-[#E3E6E0] flex items-center justify-center text-xs font-bold text-[#303632]">+</button>
                        </div>

                        <!-- Line Subtotal & Remove -->
                        <div class="text-right">
                            <p class="text-xs font-bold text-[#303632]" x-text="formatRupiah(item.product.selling_price * item.quantity)"></p>
                            <button @click="removeItem(index)" class="text-[10px] text-[#B84B4B] hover:underline">Hapus</button>
                        </div>
                    </div>
                </template>

                <div x-show="cart.length === 0" class="py-8 text-center text-xs text-[#747B75]">
                    <i data-lucide="shopping-bag" class="w-8 h-8 mx-auto text-[#C4C9C1] mb-2"></i>
                    Keranjang masih kosong.<br>Klik produk atau scan barcode untuk menambahkan.
                </div>
            </div>

            <!-- Financial Summary & Calculations -->
            <div class="p-4 border-t border-[#E3E6E0] bg-[#FDFDFD] space-y-2 text-xs">
                <div class="flex justify-between text-[#747B75]">
                    <span>Subtotal:</span>
                    <span class="font-medium text-[#303632]" x-text="formatRupiah(cartSubtotal)"></span>
                </div>

                <!-- Discount Input -->
                <div class="flex items-center justify-between text-[#747B75]">
                    <span>Diskon (Rp):</span>
                    <input type="number" 
                           x-model.number="discountAmount" 
                           min="0" 
                           placeholder="0"
                           class="w-28 text-right px-2 py-0.5 rounded border border-[#E3E6E0] text-xs text-[#303632]">
                </div>

                @if($taxPercentage > 0)
                <div class="flex justify-between text-[#747B75]">
                    <span>Pajak ({{ $taxPercentage }}%):</span>
                    <span class="font-medium text-[#303632]" x-text="formatRupiah(cartTax)"></span>
                </div>
                @endif

                <div class="pt-2 border-t border-[#E3E6E0] flex justify-between items-baseline">
                    <span class="text-sm font-bold text-[#303632]">Total Tagihan:</span>
                    <span class="text-lg font-bold text-[#357653]" x-text="formatRupiah(cartGrandTotal)"></span>
                </div>
            </div>

            <!-- Payment Methods & Cash Processing -->
            <div class="p-4 border-t border-[#E3E6E0] space-y-3 bg-white">
                
                <!-- Payment Method Tabs -->
                <div>
                    <label class="block text-[11px] font-semibold text-[#747B75] uppercase mb-1.5">Metode Pembayaran</label>
                    <div class="grid grid-cols-3 gap-1.5">
                        <button type="button" 
                                @click="paymentMethod = 'cash'; syncCashReceived()" 
                                :class="paymentMethod === 'cash' ? 'bg-[#42634F] text-white font-semibold' : 'bg-[#F0F1ED] text-[#303632] hover:bg-[#E3E6E0]'"
                                class="py-2 rounded-md text-xs transition-colors flex items-center justify-center gap-1">
                            <i data-lucide="banknote" class="w-3.5 h-3.5"></i>
                            <span>Tunai</span>
                        </button>
                        <button type="button" 
                                @click="paymentMethod = 'qris'; cashReceived = cartGrandTotal" 
                                :class="paymentMethod === 'qris' ? 'bg-[#42634F] text-white font-semibold' : 'bg-[#F0F1ED] text-[#303632] hover:bg-[#E3E6E0]'"
                                class="py-2 rounded-md text-xs transition-colors flex items-center justify-center gap-1">
                            <i data-lucide="qr-code" class="w-3.5 h-3.5"></i>
                            <span>QRIS</span>
                        </button>
                        <button type="button" 
                                @click="paymentMethod = 'transfer'; cashReceived = cartGrandTotal" 
                                :class="paymentMethod === 'transfer' ? 'bg-[#42634F] text-white font-semibold' : 'bg-[#F0F1ED] text-[#303632] hover:bg-[#E3E6E0]'"
                                class="py-2 rounded-md text-xs transition-colors flex items-center justify-center gap-1">
                            <i data-lucide="credit-card" class="w-3.5 h-3.5"></i>
                            <span>Transfer</span>
                        </button>
                    </div>
                </div>

                <!-- Cash Tendered & Quick Buttons (When Cash is selected) -->
                <div x-show="paymentMethod === 'cash'" class="space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <label class="font-medium text-[#303632]">Uang Diterima:</label>
                        <input type="number" 
                               x-model.number="cashReceived" 
                               step="500" 
                               class="w-32 px-2.5 py-1 text-right font-bold text-sm text-[#303632] border border-[#E3E6E0] rounded-md focus:ring-2 focus:ring-[#42634F]">
                    </div>

                    <!-- Quick Denominations -->
                    <div class="grid grid-cols-3 gap-1 text-[11px]">
                        <button type="button" @click="cashReceived = cartGrandTotal" class="py-1 px-1.5 rounded bg-[#F0F1ED] hover:bg-[#E3E6E0] text-[#303632] font-medium">Uang Pas</button>
                        <button type="button" @click="cashReceived = 10000" class="py-1 px-1.5 rounded bg-[#F0F1ED] hover:bg-[#E3E6E0] text-[#303632]">10.000</button>
                        <button type="button" @click="cashReceived = 20000" class="py-1 px-1.5 rounded bg-[#F0F1ED] hover:bg-[#E3E6E0] text-[#303632]">20.000</button>
                        <button type="button" @click="cashReceived = 50000" class="py-1 px-1.5 rounded bg-[#F0F1ED] hover:bg-[#E3E6E0] text-[#303632]">50.000</button>
                        <button type="button" @click="cashReceived = 100000" class="py-1 px-1.5 rounded bg-[#F0F1ED] hover:bg-[#E3E6E0] text-[#303632]">100.000</button>
                        <button type="button" @click="cashReceived = 200000" class="py-1 px-1.5 rounded bg-[#F0F1ED] hover:bg-[#E3E6E0] text-[#303632]">200.000</button>
                    </div>

                    <!-- Change Calculation Box -->
                    <div class="p-2.5 rounded-lg bg-[#F7F7F4] border border-[#E3E6E0] flex items-center justify-between">
                        <span class="text-xs font-medium text-[#747B75]">Kembalian:</span>
                        <span :class="cashChange >= 0 ? 'text-[#357653]' : 'text-[#B84B4B]'" 
                              class="text-sm font-bold" 
                              x-text="formatRupiah(cashChange)"></span>
                    </div>
                </div>

                <!-- Checkout Button -->
                <button type="button" 
                        @click="processCheckout()" 
                        :disabled="!canCheckout || isProcessing"
                        :class="(!canCheckout || isProcessing) ? 'opacity-50 cursor-not-allowed bg-[#747B75]' : 'bg-[#42634F] hover:bg-[#354F3F]'"
                        class="w-full py-3 rounded-lg text-white font-semibold text-sm transition-all shadow-sm flex items-center justify-center gap-2">
                    <span x-show="!isProcessing" class="flex items-center gap-2">
                        <i data-lucide="check-check" class="w-4 h-4"></i>
                        <span>Selesaikan Transaksi & Bayar</span>
                    </span>
                    <span x-show="isProcessing" class="flex items-center gap-2" style="display: none;">
                        <svg class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Memproses Transaksi...</span>
                    </span>
                </button>

            </div>

        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
    function posApp() {
        return {
            products: {!! json_encode($products) !!},
            taxPercentage: {{ $taxPercentage }},
            searchQuery: '',
            selectedCategory: null,
            cart: [],
            selectedCustomer: '',
            discountAmount: 0,
            paymentMethod: 'cash',
            cashReceived: 0,
            isProcessing: false,
            showOpenShiftModal: {{ $activeShift ? 'false' : 'true' }},

            init() {
                this.$watch('cart', () => {
                    this.syncCashReceived();
                });
                this.$watch('discountAmount', () => {
                    this.syncCashReceived();
                });
                window.addEventListener('open-shift-modal', () => {
                    this.showOpenShiftModal = true;
                });
            },

            get filteredProducts() {
                return this.products.filter(p => {
                    const matchCategory = this.selectedCategory === null || p.category_id === this.selectedCategory;
                    const query = this.searchQuery.toLowerCase().trim();
                    const matchQuery = !query 
                        || p.name.toLowerCase().includes(query)
                        || p.sku.toLowerCase().includes(query)
                        || (p.barcode && p.barcode.toLowerCase().includes(query));
                    return matchCategory && matchQuery;
                });
            },

            get cartTotalItems() {
                return this.cart.reduce((sum, item) => sum + item.quantity, 0);
            },

            get cartSubtotal() {
                return this.cart.reduce((sum, item) => sum + (item.product.selling_price * item.quantity), 0);
            },

            get cartTax() {
                if (this.taxPercentage <= 0) return 0;
                const netAfterDisc = Math.max(0, this.cartSubtotal - (this.discountAmount || 0));
                return Math.round(netAfterDisc * (this.taxPercentage / 100));
            },

            get cartGrandTotal() {
                const sub = this.cartSubtotal;
                const disc = Math.max(0, Number(this.discountAmount) || 0);
                const tax = this.cartTax;
                return Math.max(0, sub - disc + tax);
            },

            get cashChange() {
                if (this.paymentMethod !== 'cash') return 0;
                return (Number(this.cashReceived) || 0) - this.cartGrandTotal;
            },

            get canCheckout() {
                if (this.cart.length === 0) return false;
                if (this.paymentMethod === 'cash') {
                    return this.cashReceived >= this.cartGrandTotal;
                }
                return true;
            },

            syncCashReceived() {
                if (this.paymentMethod === 'cash') {
                    if (this.cashReceived < this.cartGrandTotal) {
                        this.cashReceived = this.cartGrandTotal;
                    }
                } else {
                    this.cashReceived = this.cartGrandTotal;
                }
            },

            addToCart(product) {
                if (product.stock <= 0) {
                    alert('Produk ' + product.name + ' kehabisan stok!');
                    return;
                }

                const existing = this.cart.find(item => item.product.id === product.id);
                if (existing) {
                    if (existing.quantity + 1 > product.stock) {
                        alert('Kuantitas melebihi stok yang tersedia (' + product.stock + ' ' + product.unit + ')');
                        return;
                    }
                    existing.quantity++;
                } else {
                    this.cart.push({
                        product: product,
                        quantity: 1,
                    });
                }
            },

            handleBarcodeOrSearchEnter() {
                const query = this.searchQuery.trim().toLowerCase();
                if (!query) return;

                // Cek exact match barcode atau SKU terlebih dahulu
                const exactMatch = this.products.find(p => 
                    (p.barcode && p.barcode.toLowerCase() === query) || 
                    p.sku.toLowerCase() === query
                );

                if (exactMatch) {
                    this.addToCart(exactMatch);
                    this.searchQuery = '';
                    return;
                }

                // Jika hanya ada 1 hasil pencarian
                const matches = this.filteredProducts;
                if (matches.length === 1) {
                    this.addToCart(matches[0]);
                    this.searchQuery = '';
                }
            },

            incrementQty(index) {
                const item = this.cart[index];
                if (item.quantity + 1 > item.product.stock) {
                    alert('Kuantitas tidak dapat melebihi sisa stok (' + item.product.stock + ' ' + item.product.unit + ')');
                    return;
                }
                item.quantity++;
            },

            decrementQty(index) {
                const item = this.cart[index];
                if (item.quantity > 1) {
                    item.quantity--;
                } else {
                    this.removeItem(index);
                }
            },

            validateQty(index) {
                const item = this.cart[index];
                if (item.quantity < 1) item.quantity = 1;
                if (item.quantity > item.product.stock) {
                    alert('Kuantitas disesuaikan ke stok maksimal yang ada (' + item.product.stock + ')');
                    item.quantity = item.product.stock;
                }
            },

            removeItem(index) {
                this.cart.splice(index, 1);
            },

            clearCart() {
                if (confirm('Yakin ingin mengosongkan seluruh keranjang belanja?')) {
                    this.cart = [];
                    this.discountAmount = 0;
                    this.cashReceived = 0;
                }
            },

            formatRupiah(amount) {
                return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount || 0);
            },

            async processCheckout() {
                if (!this.canCheckout || this.isProcessing) return;

                this.isProcessing = true;

                const payload = {
                    items: this.cart.map(item => ({
                        product_id: item.product.id,
                        quantity: item.quantity,
                    })),
                    customer_id: this.selectedCustomer || null,
                    payment_method: this.paymentMethod,
                    cash_received: this.paymentMethod === 'cash' ? this.cashReceived : this.cartGrandTotal,
                    discount_amount: this.discountAmount || 0,
                };

                try {
                    const response = await fetch("{{ route('pos.checkout') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        // Redirect to printable & digital receipt view
                        window.location.href = data.receipt_url;
                    } else {
                        alert('Transaksi Gagal: ' + (data.message || 'Terjadi kesalahan sistem.'));
                        this.isProcessing = false;
                    }
                } catch (error) {
                    alert('Gagal menghubungi server: ' + error.message);
                    this.isProcessing = false;
                }
            }
        };
    }
</script>
@endpush
