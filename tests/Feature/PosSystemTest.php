<?php

namespace Tests\Feature;

use App\Models\CashShift;
use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PosService;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cashier;
    protected Category $category;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->cashier = User::create([
            'name' => 'Cashier Test',
            'email' => 'cashier@test.com',
            'password' => bcrypt('password123'),
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'name' => 'Bahan Pokok',
            'slug' => 'bahan-pokok',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'category_id' => $this->category->id,
            'sku' => 'TEST-001',
            'barcode' => '8991234567890',
            'name' => 'Beras Premium 5kg',
            'cost_price' => 50000,
            'selling_price' => 60000,
            'stock' => 10,
            'min_stock' => 2,
            'unit' => 'karung',
            'is_active' => true,
        ]);
    }

    /**
     * Skenario 1: Login Admin dan Kasir
     */
    public function test_user_authentication(): void
    {
        // 1. Login Admin
        $response = $this->post(route('login.post'), [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);
        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->admin);

        // Logout
        $this->post(route('logout'));
        $this->assertGuest();

        // 2. Login Kasir diarahkan langsung ke POS
        $response = $this->post(route('login.post'), [
            'email' => 'cashier@test.com',
            'password' => 'password123',
        ]);
        $response->assertRedirect(route('pos.index'));
        $this->assertAuthenticatedAs($this->cashier);
    }

    /**
     * Skenario 2: Pembatasan Hak Akses (Kasir ditolak akses halaman admin)
     */
    public function test_role_authorization_protection(): void
    {
        // Login sebagai Kasir
        $this->actingAs($this->cashier);

        // Kasir mencoba akses dashboard admin -> 403 Forbidden
        $response = $this->get(route('dashboard'));
        $response->assertStatus(403);

        // Kasir mencoba akses pengaturan toko -> 403 Forbidden
        $response = $this->get(route('settings.index'));
        $response->assertStatus(403);

        // Kasir mencoba akses manajemen pengguna -> 403 Forbidden
        $response = $this->get(route('users.index'));
        $response->assertStatus(403);
    }

    /**
     * Skenario 3: Transaksi POS berhasil, stok berkurang secara atomik & tercatat kartu stok
     */
    public function test_pos_checkout_success_and_stock_deduction(): void
    {
        $this->actingAs($this->cashier);

        // Buka shift terlebih dahulu
        $shift = CashShift::create([
            'user_id' => $this->cashier->id,
            'shift_number' => 'SHIFT-TEST-001',
            'opened_at' => now(),
            'starting_cash' => 100000,
            'status' => 'open',
        ]);

        $initialStock = $this->product->stock; // 10
        $buyQty = 3;

        $posService = app(PosService::class);
        $transaction = $posService->processCheckout([
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => $buyQty]
            ],
            'payment_method' => 'cash',
            'cash_received' => 200000,
            'discount_amount' => 5000,
        ], $this->cashier);

        $this->assertNotNull($transaction);
        $this->assertEquals('paid', $transaction->payment_status);

        // Perhitungan subtotal & diskon: 3 * 60.000 = 180.000 - 5.000 = 175.000
        $this->assertEquals(180000, $transaction->subtotal);
        $this->assertEquals(5000, $transaction->discount_amount);
        $this->assertEquals(175000, $transaction->total_amount);
        $this->assertEquals(25000, $transaction->cash_change); // 200.000 - 175.000

        // Verifikasi stok berkurang
        $this->assertEquals($initialStock - $buyQty, $this->product->fresh()->stock);

        // Verifikasi kartu stok tercatat
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'type' => 'sale',
            'quantity' => -$buyQty,
            'after_stock' => $initialStock - $buyQty,
        ]);
    }

    /**
     * Skenario 4: Transaksi ditolak ketika stok tidak mencukupi
     */
    public function test_pos_checkout_rejected_when_stock_insufficient(): void
    {
        $this->actingAs($this->cashier);

        $posService = app(PosService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Stok untuk.*tidak mencukupi/i');

        // Minta kuantitas 100 padahal stok hanya 10
        $posService->processCheckout([
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 100]
            ],
            'payment_method' => 'cash',
            'cash_received' => 10000000,
        ], $this->cashier);
    }

    /**
     * Skenario 5: Buka dan Tutup Shift Kasir dengan Rekonsiliasi Kas Fisik
     */
    public function test_cash_shift_reconciliation(): void
    {
        $this->actingAs($this->cashier);

        // 1. Buka shift
        $response = $this->post(route('shifts.open'), [
            'starting_cash' => 200000,
            'notes' => 'Shift uji',
        ]);
        $response->assertSessionHas('success');

        $shift = CashShift::where('user_id', $this->cashier->id)->where('status', 'open')->first();
        $this->assertNotNull($shift);
        $this->assertEquals(200000, $shift->starting_cash);

        // 2. Tambah kas masuk (kembalian dari brankas)
        $this->post(route('shifts.movement', $shift->id), [
            'type' => 'cash_in',
            'amount' => 50000,
            'reason' => 'Uang receh pecahan 2rb',
        ]);

        // 3. Catat kas keluar (petty cash beli spidol)
        $this->post(route('shifts.movement', $shift->id), [
            'type' => 'cash_out',
            'amount' => 10000,
            'reason' => 'Beli spidol whiteboard',
        ]);

        // 4. Tutup shift: Ekspektasi = 200.000 + 50.000 - 10.000 = 240.000
        $actualCashCount = 240000;
        $this->post(route('shifts.close', $shift->id), [
            'actual_cash' => $actualCashCount,
            'closing_notes' => 'Hitungan pas',
        ]);

        $closedShift = $shift->fresh();
        $this->assertEquals('closed', $closedShift->status);
        $this->assertEquals(240000, $closedShift->expected_cash);
        $this->assertEquals(240000, $closedShift->actual_cash);
        $this->assertEquals(0, $closedShift->difference); // Selisih 0
    }

    /**
     * Skenario 6: Pembatalan (Void) Transaksi mengembalikan stok barang
     */
    public function test_void_transaction_restores_inventory(): void
    {
        $this->actingAs($this->admin);

        $posService = app(PosService::class);
        $transaction = $posService->processCheckout([
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2]
            ],
            'payment_method' => 'cash',
            'cash_received' => 150000,
        ], $this->admin);

        $stockAfterSale = $this->product->fresh()->stock; // 8

        // Void transaksi
        $posService->voidTransaction($transaction, 'Pelanggan membatalkan pesanan', $this->admin);

        $this->assertEquals('void', $transaction->fresh()->payment_status);
        $this->assertEquals(10, $this->product->fresh()->stock); // Stok kembali jadi 10

        // Mutasi stok bertipe 'return' tercatat
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'type' => 'return',
            'quantity' => 2,
        ]);
    }

    /**
     * Skenario 7: Pengujian Format Laporan Harian WhatsApp
     */
    public function test_whatsapp_daily_report_generation(): void
    {
        $whatsAppService = app(WhatsAppService::class);
        $result = $whatsAppService->sendDailyReport();

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('LAPORAN HARIAN — KASIR PINTAR', $result['content']);
        $this->assertStringContainsString('Total penjualan neto:', $result['content']);

        // Log tersimpan di database
        $this->assertDatabaseHas('whatsapp_message_logs', [
            'message_type' => 'daily_report',
        ]);
    }
}
