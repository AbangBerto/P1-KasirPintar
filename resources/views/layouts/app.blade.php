<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Kasir Pintar') — Smart Point of Sale System</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Lucide Icons CDN fallback & Vite -->
    <script src="https://unpkg.com/lucide@latest"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --bg-main: #F7F7F4;
            --bg-panel: #FFFFFF;
            --bg-sidebar: #F0F1ED;
            --primary: #42634F;
            --primary-hover: #354F3F;
            --accent-soft: #DCE8DE;
            --text-main: #303632;
            --text-secondary: #747B75;
            --border-color: #E3E6E0;
            --success: #357653;
            --warning: #A66D24;
            --danger: #B84B4B;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        .bg-sidebar { background-color: var(--bg-sidebar); }
        .bg-panel { background-color: var(--bg-panel); }
        .bg-primary-app { background-color: var(--primary); }
        .bg-primary-hover:hover { background-color: var(--primary-hover); }
        .bg-accent-soft { background-color: var(--accent-soft); }
        .text-primary-app { color: var(--primary); }
        .text-main { color: var(--text-main); }
        .text-secondary { color: var(--text-secondary); }
        .border-app { border-color: var(--border-color); }
        .badge-success { background-color: #E6F4EA; color: var(--success); }
        .badge-warning { background-color: #FEF7E0; color: var(--warning); }
        .badge-danger { background-color: #FCE8E6; color: var(--danger); }
        .badge-neutral { background-color: #EAECE7; color: var(--text-secondary); }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.625rem 0.875rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-secondary);
            transition: all 0.15s ease-in-out;
            text-decoration: none;
        }

        .nav-item:hover {
            background-color: #E6E8E2;
            color: var(--text-main);
        }

        .nav-item.active {
            background-color: var(--primary);
            color: #FFFFFF;
        }

        .nav-item.active i {
            color: #FFFFFF;
        }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen flex antialiased" x-data="{ sidebarOpen: false }">

    <!-- Mobile Sidebar Backdrop -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-200" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="transition-opacity ease-linear duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         @click="sidebarOpen = false" 
         class="fixed inset-0 bg-black/40 z-40 lg:hidden" 
         style="display: none;">
    </div>

    <!-- Left Sidebar -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed inset-y-0 left-0 z-50 w-64 bg-sidebar border-r border-app flex flex-col transition-transform duration-200 ease-in-out lg:static lg:inset-auto">
        
        <!-- Store / App Brand Header -->
        <div class="h-16 px-5 border-b border-app flex items-center justify-between">
            <a href="{{ auth()->user()->isCashier() ? route('pos.index') : route('dashboard') }}" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-primary-app text-white flex items-center justify-center font-bold text-sm shadow-sm">
                    KP
                </div>
                <div>
                    <h1 class="text-sm font-semibold text-main leading-tight">Kasir Pintar</h1>
                    <p class="text-[11px] text-secondary">Smart Retail POS</p>
                </div>
            </a>
            <button @click="sidebarOpen = false" class="lg:hidden text-secondary hover:text-main">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <!-- Navigation Menu (12 Modules) -->
        <nav class="flex-1 overflow-y-auto px-3 py-3 space-y-1">
            
            @if(auth()->user()->isAdmin())
            <!-- 1. Dashboard -->
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                <span>Dashboard</span>
            </a>
            @endif

            <!-- 2. Transaksi Kasir (POS) -->
            <a href="{{ route('pos.index') }}" class="nav-item {{ request()->routeIs('pos.*') ? 'active' : '' }}">
                <i data-lucide="calculator" class="w-4 h-4"></i>
                <span class="flex-1">Transaksi Kasir</span>
                <span class="text-[10px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-accent-soft text-primary-app">POS</span>
            </a>

            @if(auth()->user()->isAdmin())
            <!-- 3. Produk -->
            <a href="{{ route('products.index') }}" class="nav-item {{ request()->routeIs('products.*') ? 'active' : '' }}">
                <i data-lucide="package" class="w-4 h-4"></i>
                <span>Produk</span>
            </a>

            <!-- 4. Kategori Produk -->
            <a href="{{ route('categories.index') }}" class="nav-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                <i data-lucide="tags" class="w-4 h-4"></i>
                <span>Kategori Produk</span>
            </a>

            <!-- 5. Stok dan Persediaan -->
            <a href="{{ route('inventory.index') }}" class="nav-item {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                <i data-lucide="boxes" class="w-4 h-4"></i>
                <span>Stok & Persediaan</span>
            </a>
            @endif

            <!-- 6. Riwayat Transaksi -->
            <a href="{{ route('transactions.index') }}" class="nav-item {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                <i data-lucide="receipt" class="w-4 h-4"></i>
                <span>Riwayat Transaksi</span>
            </a>

            @if(auth()->user()->isAdmin())
            <!-- 7. Laporan Penjualan -->
            <div x-data="{ reportOpen: {{ request()->routeIs('reports.*') ? 'true' : 'false' }} }">
                <button @click="reportOpen = !reportOpen" 
                        class="nav-item w-full justify-between {{ request()->routeIs('reports.*') ? 'text-main font-semibold' : '' }}">
                    <div class="flex items-center gap-3">
                        <i data-lucide="bar-chart-3" class="w-4 h-4"></i>
                        <span>Laporan Penjualan</span>
                    </div>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform" :class="reportOpen ? 'rotate-180' : ''"></i>
                </button>
                <div x-show="reportOpen" class="pl-7 pr-2 py-1 space-y-1">
                    <a href="{{ route('reports.daily') }}" class="block text-xs py-1.5 px-2.5 rounded hover:bg-black/5 {{ request()->routeIs('reports.daily') ? 'font-semibold text-primary-app bg-accent-soft/40' : 'text-secondary' }}">
                        Laporan Harian
                    </a>
                    <a href="{{ route('reports.periodic') }}" class="block text-xs py-1.5 px-2.5 rounded hover:bg-black/5 {{ request()->routeIs('reports.periodic') ? 'font-semibold text-primary-app bg-accent-soft/40' : 'text-secondary' }}">
                        Tren & Periode
                    </a>
                </div>
            </div>
            @endif

            <!-- 8. Shift Kasir -->
            <a href="{{ route('shifts.index') }}" class="nav-item {{ request()->routeIs('shifts.*') ? 'active' : '' }}">
                <i data-lucide="clock" class="w-4 h-4"></i>
                <span>Shift Kasir</span>
            </a>

            @if(auth()->user()->isAdmin())
            <!-- 9. Pelanggan -->
            <a href="{{ route('customers.index') }}" class="nav-item {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                <i data-lucide="users" class="w-4 h-4"></i>
                <span>Pelanggan</span>
            </a>

            <!-- 10. Integrasi WhatsApp -->
            <a href="{{ route('whatsapp.index') }}" class="nav-item {{ request()->routeIs('whatsapp.*') ? 'active' : '' }}">
                <i data-lucide="message-square" class="w-4 h-4"></i>
                <span>Integrasi WhatsApp</span>
            </a>

            <!-- 11. Pengguna dan Hak Akses -->
            <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i data-lucide="shield-check" class="w-4 h-4"></i>
                <span>Pengguna & Akses</span>
            </a>

            <!-- 12. Pengaturan Toko -->
            <a href="{{ route('settings.index') }}" class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <i data-lucide="store" class="w-4 h-4"></i>
                <span>Pengaturan Toko</span>
            </a>
            @endif

        </nav>

        <!-- Current User Profile & Fast Switch -->
        <div class="p-3 border-t border-app">
            <div class="p-2.5 rounded-lg bg-panel border border-app">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2 overflow-hidden">
                        <div class="w-7 h-7 rounded-full bg-accent-soft text-primary-app flex items-center justify-center font-semibold text-xs shrink-0">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <div class="truncate">
                            <p class="text-xs font-medium text-main truncate">{{ auth()->user()->name }}</p>
                            <span class="inline-block px-1.5 py-0.2 text-[10px] font-semibold rounded {{ auth()->user()->isAdmin() ? 'bg-primary-app text-white' : 'badge-neutral' }}">
                                {{ strtoupper(auth()->user()->role) }}
                            </span>
                        </div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" title="Keluar / Logout" class="text-secondary hover:text-danger p-1 rounded hover:bg-black/5">
                            <i data-lucide="log-out" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>

                <!-- Quick Switcher Demo Pill for Tester/Reviewer -->
                <div class="pt-2 border-t border-app/60 flex items-center justify-between text-[11px] text-secondary">
                    <span>Uji Peran:</span>
                    <div class="flex gap-1.5">
                        <a href="{{ route('login.quick', ['role' => 'admin']) }}" class="px-1.5 py-0.5 rounded bg-black/5 hover:bg-black/10 text-[10px] {{ auth()->user()->isAdmin() ? 'font-bold text-primary-app' : '' }}">Admin</a>
                        <a href="{{ route('login.quick', ['role' => 'cashier']) }}" class="px-1.5 py-0.5 rounded bg-black/5 hover:bg-black/10 text-[10px] {{ auth()->user()->isCashier() ? 'font-bold text-primary-app' : '' }}">Kasir</a>
                    </div>
                </div>
            </div>
        </div>

    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
        
        <!-- Topbar -->
        <header class="h-16 bg-panel border-b border-app px-4 sm:px-6 flex items-center justify-between sticky top-0 z-30">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = true" class="lg:hidden text-secondary hover:text-main p-1.5 rounded-md hover:bg-black/5">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <div>
                    <h2 class="text-base sm:text-lg font-semibold text-main leading-tight">@yield('page_title', 'Kasir Pintar')</h2>
                    <p class="text-xs text-secondary hidden sm:block">@yield('page_subtitle', 'Sistem Manajemen Penjualan & Kasir Retail')</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <!-- Date / Time indicator -->
                <div class="text-right hidden sm:block">
                    <p class="text-xs font-medium text-main">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</p>
                    <p class="text-[11px] text-secondary font-mono" id="realtime-clock">{{ \Carbon\Carbon::now()->format('H:i') }} WIB</p>
                </div>

                @yield('topbar_actions')
            </div>
        </header>

        <!-- Flash Messages -->
        <div class="px-4 sm:px-6 pt-4">
            @if(session('success'))
            <div class="mb-4 p-3.5 rounded-lg bg-[#E6F4EA] border border-[#357653]/20 flex items-center justify-between text-sm text-[#357653]">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
                    <span>{{ session('success') }}</span>
                </div>
                @if(session('wa_direct_url'))
                <a href="{{ session('wa_direct_url') }}" target="_blank" class="px-2.5 py-1 rounded bg-[#357653] text-white text-xs font-medium hover:bg-[#2c5f43] flex items-center gap-1.5 shadow-sm">
                    <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                    <span>Kirim ke WhatsApp Sekarang</span>
                </a>
                @endif
            </div>
            @endif

            @if(session('error'))
            <div class="mb-4 p-3.5 rounded-lg bg-[#FCE8E6] border border-[#B84B4B]/20 flex items-center gap-2.5 text-sm text-[#B84B4B]">
                <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
            @endif

            @if($errors->any())
            <div class="mb-4 p-3.5 rounded-lg bg-[#FCE8E6] border border-[#B84B4B]/20 text-sm text-[#B84B4B]">
                <div class="flex items-center gap-2 mb-1 font-medium">
                    <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0"></i>
                    <span>Terdapat beberapa kesalahan input:</span>
                </div>
                <ul class="list-disc list-inside text-xs space-y-0.5 ml-6">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>

        <!-- Main Content -->
        <main class="flex-1 p-4 sm:p-6">
            @yield('content')
        </main>
    </div>

    <script>
        // Realtime Clock Updater
        function updateClock() {
            const clockEl = document.getElementById('realtime-clock');
            if (clockEl) {
                const now = new Date();
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                clockEl.textContent = `${hours}:${minutes}:${seconds} WIB`;
            }
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Ensure lucide icons initialize
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide && window.lucide.createIcons) {
                window.lucide.createIcons();
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
