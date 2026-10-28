<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Categories
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Products
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('cost_price', 14, 2); // Harga modal (restricted to admin)
            $table->decimal('selling_price', 14, 2); // Harga jual
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(5);
            $table->string('unit')->default('pcs'); // pcs, pack, botol, kg, box
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Customers
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable()->index();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->decimal('total_spent', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Cash Shifts (Manajemen Shift Kasir)
        Schema::create('cash_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('shift_number')->unique();
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->decimal('starting_cash', 14, 2); // Saldo modal awal kasir di laci
            $table->decimal('cash_sales', 14, 2)->default(0); // Total penjualan tunai
            $table->decimal('non_cash_sales', 14, 2)->default(0); // Total penjualan QRIS/Transfer
            $table->decimal('cash_in', 14, 2)->default(0); // Penambahan kas / modal tambahan
            $table->decimal('cash_out', 14, 2)->default(0); // Pengeluaran operasional kecil (petty cash)
            $table->decimal('cash_refunds', 14, 2)->default(0); // Refund tunai
            $table->decimal('expected_cash', 14, 2)->default(0); // starting_cash + cash_sales + cash_in - cash_out - cash_refunds
            $table->decimal('actual_cash', 14, 2)->nullable(); // Hasil hitung fisik kasir saat tutup
            $table->decimal('difference', 14, 2)->nullable(); // actual_cash - expected_cash
            $table->string('status')->default('open'); // 'open' or 'closed'
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 5. Cash Movements (Detail Kas Masuk / Kas Keluar Selama Shift)
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_shift_id')->constrained('cash_shifts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['cash_in', 'cash_out']);
            $table->decimal('amount', 14, 2);
            $table->string('reason');
            $table->timestamps();
        });

        // 6. Transactions (Header Transaksi)
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('user_id')->constrained('users'); // Kasir bertugas
            $table->foreignId('cash_shift_id')->nullable()->constrained('cash_shifts')->onDelete('set null');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->decimal('subtotal', 14, 2);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2); // Net penjualan yang harus dibayar
            $table->decimal('total_cost', 14, 2)->default(0); // HPP total saat transaksi
            $table->decimal('gross_profit', 14, 2)->default(0); // total_amount - total_cost
            $table->enum('payment_method', ['cash', 'qris', 'transfer'])->default('cash');
            $table->enum('payment_status', ['paid', 'void', 'refunded'])->default('paid');
            $table->decimal('cash_received', 14, 2)->default(0);
            $table->decimal('cash_change', 14, 2)->default(0);
            $table->dateTime('transaction_date');
            $table->text('notes')->nullable();
            $table->string('void_reason')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('voided_at')->nullable();
            $table->timestamps();
        });

        // 7. Transaction Items (Snapshot Detail Barang yang Terjual)
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->string('product_name');
            $table->string('product_sku');
            $table->decimal('cost_price', 14, 2); // HPP modal saat transaksi
            $table->decimal('unit_price', 14, 2); // Harga jual saat transaksi
            $table->integer('quantity');
            $table->decimal('subtotal', 14, 2); // quantity * unit_price
            $table->decimal('profit', 14, 2); // (unit_price - cost_price) * quantity
            $table->timestamps();
        });

        // 8. Inventory Movements (Kartu Stok & Audit Log Perubahan Barang)
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('set null');
            $table->enum('type', ['initial', 'restock', 'sale', 'return', 'damaged', 'adjustment']);
            $table->integer('quantity'); // Positif bertambah, negatif berkurang
            $table->integer('before_stock');
            $table->integer('after_stock');
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        // 9. Store Settings (Pengaturan Toko, Jam Buka/Tutup, Jam Laporan, dll)
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // 10. WhatsApp Settings
        Schema::create('whatsapp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // 11. WhatsApp Message Logs
        Schema::create('whatsapp_message_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_phone');
            $table->string('message_type'); // 'daily_report', 'stock_alert', 'receipt', 'test'
            $table->text('content');
            $table->enum('status', ['sent', 'delivered', 'failed', 'demo_dispatched'])->default('sent');
            $table->text('response_payload')->nullable();
            $table->timestamps();
        });

        // 12. Audit Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action'); // e.g. 'product_created', 'price_updated', 'stock_adjusted', 'transaction_voided', 'shift_closed'
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('whatsapp_message_logs');
        Schema::dropIfExists('whatsapp_settings');
        Schema::dropIfExists('store_settings');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('transaction_items');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('cash_shifts');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
