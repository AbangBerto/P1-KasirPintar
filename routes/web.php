<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\StoreSettingController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhatsAppController;
use Illuminate\Support\Facades\Route;

// Redirect root to dashboard or POS based on role
Route::get('/', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }
    return auth()->user()->isCashier() ? redirect()->route('pos.index') : redirect()->route('dashboard');
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/quick-login', [AuthController::class, 'quickLogin'])->name('login.quick');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Cashier & Shared Routes (Accessible by both Cashier and Admin)
Route::middleware(['cashier'])->group(function () {
    // POS Transaksi Kasir
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
    Route::get('/pos/receipt/{id}', [PosController::class, 'receipt'])->name('pos.receipt');
    Route::post('/pos/receipt/{id}/whatsapp', [PosController::class, 'sendReceiptWhatsApp'])->name('pos.receipt.whatsapp');

    // Manajemen Shift Kasir
    Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
    Route::post('/shifts/open', [ShiftController::class, 'open'])->name('shifts.open');
    Route::post('/shifts/{id}/movement', [ShiftController::class, 'addMovement'])->name('shifts.movement');
    Route::post('/shifts/{id}/close', [ShiftController::class, 'close'])->name('shifts.close');
    Route::get('/shifts/{id}', [ShiftController::class, 'show'])->name('shifts.show');

    // Riwayat Transaksi (Lihat & Cetak Ulang Struk)
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{id}', [TransactionController::class, 'show'])->name('transactions.show');
});

// Admin-Only Routes
Route::middleware(['admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Manajemen Produk
    Route::get('/products/export/csv', [ProductController::class, 'exportCsv'])->name('products.export');
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{id}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{id}', [ProductController::class, 'update'])->name('products.update');
    Route::post('/products/{id}/toggle', [ProductController::class, 'toggleStatus'])->name('products.toggle');

    // Kategori Produk
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{id}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Stok dan Persediaan
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');

    // Riwayat Transaksi Lanjutan & Void
    Route::post('/transactions/{id}/void', [TransactionController::class, 'void'])->name('transactions.void');
    Route::get('/transactions/export/csv', [TransactionController::class, 'exportCsv'])->name('transactions.export');

    // Laporan Penjualan
    Route::get('/reports/daily', [ReportController::class, 'daily'])->name('reports.daily');
    Route::get('/reports/periodic', [ReportController::class, 'periodic'])->name('reports.periodic');
    Route::get('/reports/daily/export', [ReportController::class, 'exportDailyCsv'])->name('reports.daily.export');

    // Pelanggan
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{id}', [CustomerController::class, 'show'])->name('customers.show');
    Route::put('/customers/{id}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{id}', [CustomerController::class, 'destroy'])->name('customers.destroy');

    // Integrasi WhatsApp
    Route::get('/whatsapp', [WhatsAppController::class, 'index'])->name('whatsapp.index');
    Route::post('/whatsapp/settings', [WhatsAppController::class, 'updateSettings'])->name('whatsapp.settings.update');
    Route::post('/whatsapp/test', [WhatsAppController::class, 'sendTest'])->name('whatsapp.test');
    Route::post('/whatsapp/daily-report', [WhatsAppController::class, 'triggerDailyReport'])->name('whatsapp.daily_report');
    Route::post('/whatsapp/retry/{id}', [WhatsAppController::class, 'retryLog'])->name('whatsapp.retry');

    // Pengguna dan Hak Akses
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
    Route::post('/users/{id}/toggle', [UserController::class, 'toggleStatus'])->name('users.toggle');

    // Pengaturan Toko
    Route::get('/settings', [StoreSettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [StoreSettingController::class, 'update'])->name('settings.update');
});
