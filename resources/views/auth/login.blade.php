<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Kasir Pintar (Smart POS)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --bg-main: #F7F7F4;
            --bg-panel: #FFFFFF;
            --primary: #42634F;
            --primary-hover: #354F3F;
            --accent-soft: #DCE8DE;
            --text-main: #303632;
            --text-secondary: #747B75;
            --border-color: #E3E6E0;
        }
        body {
            background-color: var(--bg-main);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 antialiased">
    <div class="w-full max-w-md">
        <!-- Brand Header -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-[#42634F] text-white font-bold text-lg mb-3 shadow-sm">
                KP
            </div>
            <h1 class="text-xl font-bold text-[#303632]">Kasir Pintar</h1>
            <p class="text-sm text-[#747B75]">Smart Point of Sale & Retail Management</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-xl border border-[#E3E6E0] shadow-sm p-6 sm:p-8">
            <h2 class="text-base font-semibold text-[#303632] mb-1">Masuk ke Sistem</h2>
            <p class="text-xs text-[#747B75] mb-6">Masukkan kredensial akun Anda untuk mengakses sistem kasir.</p>

            @if(session('error'))
            <div class="mb-4 p-3 rounded-lg bg-[#FCE8E6] border border-[#B84B4B]/20 flex items-center gap-2 text-xs text-[#B84B4B]">
                <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
            @endif

            @if(session('success'))
            <div class="mb-4 p-3 rounded-lg bg-[#E6F4EA] border border-[#357653]/20 flex items-center gap-2 text-xs text-[#357653]">
                <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-medium text-[#303632] mb-1">Alamat Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', 'admin@kasirpintar.com') }}" required 
                           class="w-full px-3.5 py-2.5 rounded-lg border border-[#E3E6E0] text-sm text-[#303632] focus:outline-none focus:ring-2 focus:ring-[#42634F]/30 focus:border-[#42634F] transition-all bg-[#FDFDFD]">
                    @error('email')
                        <p class="mt-1 text-xs text-[#B84B4B]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="password" class="block text-xs font-medium text-[#303632]">Password</label>
                    </div>
                    <input type="password" id="password" name="password" value="password123" required 
                           class="w-full px-3.5 py-2.5 rounded-lg border border-[#E3E6E0] text-sm text-[#303632] focus:outline-none focus:ring-2 focus:ring-[#42634F]/30 focus:border-[#42634F] transition-all bg-[#FDFDFD]">
                    @error('password')
                        <p class="mt-1 text-xs text-[#B84B4B]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 text-[#747B75] cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-[#E3E6E0] text-[#42634F] focus:ring-[#42634F]">
                        <span>Ingat saya di perangkat ini</span>
                    </label>
                </div>

                <button type="submit" 
                        class="w-full py-2.5 px-4 rounded-lg bg-[#42634F] hover:bg-[#354F3F] text-white text-sm font-medium transition-colors shadow-sm mt-2">
                    Masuk Sekarang
                </button>
            </form>

            <!-- 1-Click Demo Login Shortcuts -->
            <div class="mt-6 pt-5 border-t border-[#E3E6E0]">
                <p class="text-[11px] font-semibold text-[#747B75] uppercase tracking-wider mb-2.5 text-center">Demo Quick Login</p>
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('login.quick', ['role' => 'admin']) }}" 
                       class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg border border-[#E3E6E0] hover:bg-[#F0F1ED] text-xs font-medium text-[#303632] transition-colors text-center">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-[#42634F]"></i>
                        <span>Login Admin</span>
                    </a>
                    <a href="{{ route('login.quick', ['role' => 'cashier']) }}" 
                       class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg border border-[#E3E6E0] hover:bg-[#F0F1ED] text-xs font-medium text-[#303632] transition-colors text-center">
                        <i data-lucide="calculator" class="w-3.5 h-3.5 text-[#42634F]"></i>
                        <span>Login Kasir</span>
                    </a>
                </div>
                <div class="mt-3 p-2.5 rounded bg-[#F0F1ED]/60 text-[11px] text-[#747B75] text-center">
                    Default password: <span class="font-mono font-medium text-[#303632]">password123</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <p class="text-center text-xs text-[#747B75] mt-6">
            Kasir Pintar POS &copy; {{ date('Y') }}. Sistem POS terintegrasi WhatsApp.
        </p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) window.lucide.createIcons();
        });
    </script>
</body>
</html>
