<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\Customer;
use App\Models\CashShift;
use App\Models\CashMovement;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\InventoryMovement;
use App\Models\StoreSetting;
use App\Models\WhatsappSetting;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with realistic retail minimarket data.
     */
    public function run(): void
    {
        // 1. Store Settings
        $storeSettings = [
            'store_name' => 'Minimarket Berkah Jaya',
            'store_tagline' => 'Belanja Hemat & Lengkap Setiap Hari',
            'store_address' => 'Jl. Merdeka No. 45, Kebayoran Baru, Jakarta Selatan',
            'store_phone' => '0812-3456-7890',
            'store_email' => 'toko@berkahjaya.com',
            'opening_hour' => '08:00',
            'closing_hour' => '22:00',
            'business_day_cutoff' => '04:00',
            'timezone' => 'Asia/Jakarta',
            'tax_percentage' => '0',
            'receipt_header' => "MINIMARKET BERKAH JAYA\nJl. Merdeka No. 45 Jakarta Selatan\nTelp: 0812-3456-7890",
            'receipt_footer' => "Terima kasih atas kunjungan Anda!\nBarang yang sudah dibeli tidak dapat ditukar/dikembalikan kecuali ada perjanjian.",
        ];

        foreach ($storeSettings as $k => $v) {
            StoreSetting::set($k, $v);
        }

        // 2. WhatsApp Settings
        $whatsappSettings = [
            'api_endpoint' => 'https://graph.facebook.com/v19.0',
            'phone_number_id' => '109876543210987',
            'access_token' => '',
            'recipient_number' => '6281234567890',
            'daily_report_enabled' => '1',
            'daily_report_time' => '22:05',
            'stock_alert_enabled' => '1',
            'min_stock_threshold' => '5',
            'demo_mode' => '1',
        ];

        foreach ($whatsappSettings as $k => $v) {
            WhatsappSetting::set($k, $v);
        }

        // 3. Users
        $admin = User::create([
            'name' => 'Berto Admin',
            'email' => 'admin@kasirpintar.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        $cashier = User::create([
            'name' => 'Siti Rahmawati',
            'email' => 'kasir@kasirpintar.com',
            'password' => Hash::make('password123'),
            'role' => 'cashier',
            'phone' => '081398765432',
            'is_active' => true,
        ]);

        // 4. Categories
        $categoriesData = [
            ['name' => 'Sembako & Bahan Pokok', 'description' => 'Minyak, beras, gula, telur dan kebutuhan pokok'],
            ['name' => 'Makanan & Snack', 'description' => 'Mie instan, biskuit, roti, dan camilan'],
            ['name' => 'Minuman & Susu', 'description' => 'Air mineral, teh kemasan, kopi, dan susu'],
            ['name' => 'Kebutuhan Rumah Tangga', 'description' => 'Sabun cuci piring, deterjen, dan pembersih'],
            ['name' => 'Perawatan Pribadi', 'description' => 'Sabun mandi, pasta gigi, dan sampo'],
        ];

        $categories = [];
        foreach ($categoriesData as $c) {
            $categories[$c['name']] = Category::create($c);
        }

        // 5. Products
        $productsData = [
            // Sembako
            [
                'cat' => 'Sembako & Bahan Pokok',
                'sku' => 'SMB-001',
                'barcode' => '8992753123456',
                'name' => 'Minyak Goreng Bimoli 2L',
                'cost_price' => 32000,
                'selling_price' => 37500,
                'stock' => 24,
                'min_stock' => 6,
                'unit' => 'pcs',
            ],
            [
                'cat' => 'Sembako & Bahan Pokok',
                'sku' => 'SMB-002',
                'barcode' => '8993456789012',
                'name' => 'Beras Rojolele Premium 5kg',
                'cost_price' => 68000,
                'selling_price' => 76000,
                'stock' => 15,
                'min_stock' => 4,
                'unit' => 'karung',
            ],
            [
                'cat' => 'Sembako & Bahan Pokok',
                'sku' => 'SMB-003',
                'barcode' => '8991234567890',
                'name' => 'Gula Pasir Gulaku Premium 1kg',
                'cost_price' => 14500,
                'selling_price' => 17500,
                'stock' => 4, // Menipis
                'min_stock' => 10,
                'unit' => 'pcs',
            ],
            [
                'cat' => 'Sembako & Bahan Pokok',
                'sku' => 'SMB-004',
                'barcode' => '8995678901234',
                'name' => 'Telur Ayam Negeri Fresh 1kg',
                'cost_price' => 25000,
                'selling_price' => 29000,
                'stock' => 22,
                'min_stock' => 10,
                'unit' => 'kg',
            ],

            // Makanan & Snack
            [
                'cat' => 'Makanan & Snack',
                'sku' => 'MKN-001',
                'barcode' => '8998866200112',
                'name' => 'Indomie Goreng Spesial 85g',
                'cost_price' => 2700,
                'selling_price' => 3500,
                'stock' => 120,
                'min_stock' => 24,
                'unit' => 'bungkus',
            ],
            [
                'cat' => 'Makanan & Snack',
                'sku' => 'MKN-002',
                'barcode' => '8998866200223',
                'name' => 'Indomie Rasa Soto Mie 70g',
                'cost_price' => 2600,
                'selling_price' => 3500,
                'stock' => 96,
                'min_stock' => 24,
                'unit' => 'bungkus',
            ],
            [
                'cat' => 'Makanan & Snack',
                'sku' => 'MKN-003',
                'barcode' => '8992345678123',
                'name' => 'Roma Kelapa Biskuit 300g',
                'cost_price' => 9500,
                'selling_price' => 12000,
                'stock' => 28,
                'min_stock' => 8,
                'unit' => 'pack',
            ],
            [
                'cat' => 'Makanan & Snack',
                'sku' => 'MKN-004',
                'barcode' => '8994567890234',
                'name' => 'Roti Tawar Sari Roti Spesial',
                'cost_price' => 12000,
                'selling_price' => 15000,
                'stock' => 8,
                'min_stock' => 5,
                'unit' => 'pack',
            ],

            // Minuman & Susu
            [
                'cat' => 'Minuman & Susu',
                'sku' => 'MNM-001',
                'barcode' => '8991001100220',
                'name' => 'Aqua Air Mineral 600ml',
                'cost_price' => 2800,
                'selling_price' => 4000,
                'stock' => 60,
                'min_stock' => 24,
                'unit' => 'botol',
            ],
            [
                'cat' => 'Minuman & Susu',
                'sku' => 'MNM-002',
                'barcode' => '8991001500330',
                'name' => 'Teh Botol Sosro Kotak 250ml',
                'cost_price' => 3000,
                'selling_price' => 4500,
                'stock' => 48,
                'min_stock' => 15,
                'unit' => 'kotak',
            ],
            [
                'cat' => 'Minuman & Susu',
                'sku' => 'MNM-003',
                'barcode' => '8992345112233',
                'name' => 'Susu UHT Ultra Milk Cokelat 1L',
                'cost_price' => 17000,
                'selling_price' => 21000,
                'stock' => 3, // Menipis
                'min_stock' => 8,
                'unit' => 'kotak',
            ],
            [
                'cat' => 'Minuman & Susu',
                'sku' => 'MNM-004',
                'barcode' => '8993456223344',
                'name' => 'Kopi Kapal Api Spesial Mix 10x24g',
                'cost_price' => 13000,
                'selling_price' => 16000,
                'stock' => 20,
                'min_stock' => 5,
                'unit' => 'renceng',
            ],

            // Kebutuhan Rumah Tangga
            [
                'cat' => 'Kebutuhan Rumah Tangga',
                'sku' => 'RMT-001',
                'barcode' => '8999999123456',
                'name' => 'Sunlight Pencuci Piring Jeruk Nipis 750ml',
                'cost_price' => 13500,
                'selling_price' => 16500,
                'stock' => 18,
                'min_stock' => 6,
                'unit' => 'pouch',
            ],
            [
                'cat' => 'Kebutuhan Rumah Tangga',
                'sku' => 'RMT-002',
                'barcode' => '8999999654321',
                'name' => 'Rinso Antinoda Deterjen Bubuk 800g',
                'cost_price' => 18000,
                'selling_price' => 22000,
                'stock' => 14,
                'min_stock' => 5,
                'unit' => 'pack',
            ],

            // Perawatan Pribadi
            [
                'cat' => 'Perawatan Pribadi',
                'sku' => 'PRW-001',
                'barcode' => '8998888123456',
                'name' => 'Lifebuoy Sabun Mandi Total 10 110g',
                'cost_price' => 4200,
                'selling_price' => 5500,
                'stock' => 45,
                'min_stock' => 10,
                'unit' => 'pcs',
            ],
            [
                'cat' => 'Perawatan Pribadi',
                'sku' => 'PRW-002',
                'barcode' => '8998888654321',
                'name' => 'Pepsodent Pencegah Gigi Berlubang 190g',
                'cost_price' => 11500,
                'selling_price' => 14500,
                'stock' => 2, // Menipis
                'min_stock' => 6,
                'unit' => 'tube',
            ],
        ];

        $products = [];
        foreach ($productsData as $p) {
            $cat = $categories[$p['cat']];
            $product = Product::create([
                'category_id' => $cat->id,
                'sku' => $p['sku'],
                'barcode' => $p['barcode'],
                'name' => $p['name'],
                'cost_price' => $p['cost_price'],
                'selling_price' => $p['selling_price'],
                'stock' => $p['stock'],
                'min_stock' => $p['min_stock'],
                'unit' => $p['unit'],
                'is_active' => true,
            ]);

            // Initial inventory movement
            InventoryMovement::create([
                'product_id' => $product->id,
                'user_id' => $admin->id,
                'type' => 'initial',
                'quantity' => $p['stock'],
                'before_stock' => 0,
                'after_stock' => $p['stock'],
                'reason' => 'Stok awal sistem minimarket',
                'created_at' => Carbon::now()->subDays(5),
            ]);

            $products[$p['sku']] = $product;
        }

        // 6. Customers
        $customersData = [
            ['name' => 'Pelanggan Umum (Walk-in)', 'phone' => null, 'address' => null],
            ['name' => 'Ibu Henny', 'phone' => '081234567890', 'address' => 'Jl. Tebet Barat No. 12'],
            ['name' => 'Pak Joko', 'phone' => '081398765432', 'address' => 'Komplek Pertamina Blok C-4'],
            ['name' => 'Warung Bu Siti', 'phone' => '085712345678', 'address' => 'Jl. Kebayoran Lama No. 8'],
        ];

        $customers = [];
        foreach ($customersData as $c) {
            $customers[] = Customer::create(array_merge($c, ['is_active' => true]));
        }

        // 7. Cash Shift for Cashier
        $shift = CashShift::create([
            'user_id' => $cashier->id,
            'shift_number' => 'SHIFT-' . date('Ymd') . '-001',
            'opened_at' => Carbon::today()->setHour(8)->setMinute(0),
            'starting_cash' => 200000,
            'cash_sales' => 0,
            'non_cash_sales' => 0,
            'cash_in' => 50000, // Tambah uang kembalian pecahan kecil
            'cash_out' => 15000, // Beli kertas struk cadangan
            'cash_refunds' => 0,
            'expected_cash' => 235000,
            'status' => 'open',
            'notes' => 'Shift pagi kasir Siti Rahmawati',
        ]);

        // Cash movements for the shift
        CashMovement::create([
            'cash_shift_id' => $shift->id,
            'user_id' => $cashier->id,
            'type' => 'cash_in',
            'amount' => 50000,
            'reason' => 'Penambahan uang receh/kembalian dari brankas',
            'created_at' => Carbon::today()->setHour(8)->setMinute(15),
        ]);

        CashMovement::create([
            'cash_shift_id' => $shift->id,
            'user_id' => $cashier->id,
            'type' => 'cash_out',
            'amount' => 15000,
            'reason' => 'Beli cadangan kertas thermal struk kasir',
            'created_at' => Carbon::today()->setHour(10)->setMinute(30),
        ]);

        // 8. Realistic Transactions for Today & Past Days
        $sampleSales = [
            // Today's Sales
            [
                'time' => Carbon::today()->setHour(8)->setMinute(45),
                'method' => 'cash',
                'customer' => $customers[1],
                'items' => [
                    ['sku' => 'SMB-001', 'qty' => 1], // Minyak 37.500
                    ['sku' => 'MKN-001', 'qty' => 5], // Indomie Goreng 5x3500 = 17.500
                    ['sku' => 'MNM-001', 'qty' => 2], // Aqua 2x4000 = 8.000
                ],
                'cash_received' => 70000,
            ],
            [
                'time' => Carbon::today()->setHour(9)->setMinute(30),
                'method' => 'qris',
                'customer' => $customers[0],
                'items' => [
                    ['sku' => 'MNM-004', 'qty' => 1], // Kopi Kapal Api 16.000
                    ['sku' => 'MKN-003', 'qty' => 1], // Biskuit Roma 12.000
                ],
                'cash_received' => 28000,
            ],
            [
                'time' => Carbon::today()->setHour(11)->setMinute(15),
                'method' => 'cash',
                'customer' => $customers[2],
                'items' => [
                    ['sku' => 'SMB-002', 'qty' => 1], // Beras 76.000
                    ['sku' => 'SMB-004', 'qty' => 1], // Telur 29.000
                    ['sku' => 'RMT-001', 'qty' => 1], // Sunlight 16.500
                ],
                'cash_received' => 130000,
            ],
            [
                'time' => Carbon::today()->setHour(14)->setMinute(20),
                'method' => 'transfer',
                'customer' => $customers[3],
                'items' => [
                    ['sku' => 'MKN-001', 'qty' => 20], // Indomie Goreng 20x3500 = 70.000
                    ['sku' => 'MKN-002', 'qty' => 20], // Indomie Soto 20x3500 = 70.000
                    ['sku' => 'MNM-001', 'qty' => 12], // Aqua 12x4000 = 48.000
                ],
                'cash_received' => 188000,
            ],
            [
                'time' => Carbon::today()->setHour(16)->setMinute(50),
                'method' => 'qris',
                'customer' => $customers[0],
                'items' => [
                    ['sku' => 'PRW-001', 'qty' => 2], // Lifebuoy 2x5500 = 11.000
                    ['sku' => 'PRW-002', 'qty' => 1], // Pepsodent 14.500
                    ['sku' => 'MKN-004', 'qty' => 1], // Sari Roti 15.000
                ],
                'cash_received' => 40500,
            ],
        ];

        $todayCashTotal = 0;
        $todayNonCashTotal = 0;
        $trxCount = 1;

        foreach ($sampleSales as $sale) {
            $invNumber = 'TRX-' . Carbon::today()->format('Ymd') . '-' . str_pad($trxCount++, 4, '0', STR_PAD_LEFT);
            $subtotal = 0;
            $totalCost = 0;

            $itemsToCreate = [];
            foreach ($sale['items'] as $itemData) {
                $p = $products[$itemData['sku']];
                $itemSubtotal = $p->selling_price * $itemData['qty'];
                $itemCost = $p->cost_price * $itemData['qty'];
                $itemProfit = $itemSubtotal - $itemCost;

                $subtotal += $itemSubtotal;
                $totalCost += $itemCost;

                $itemsToCreate[] = [
                    'product_id' => $p->id,
                    'product_name' => $p->name,
                    'product_sku' => $p->sku,
                    'cost_price' => $p->cost_price,
                    'unit_price' => $p->selling_price,
                    'quantity' => $itemData['qty'],
                    'subtotal' => $itemSubtotal,
                    'profit' => $itemProfit,
                ];
            }

            $totalAmount = $subtotal;
            $grossProfit = $totalAmount - $totalCost;
            $cashReceived = $sale['cash_received'];
            $cashChange = max(0, $cashReceived - $totalAmount);

            $trx = Transaction::create([
                'invoice_number' => $invNumber,
                'user_id' => $cashier->id,
                'cash_shift_id' => $shift->id,
                'customer_id' => $sale['customer']?->id,
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => $totalAmount,
                'total_cost' => $totalCost,
                'gross_profit' => $grossProfit,
                'payment_method' => $sale['method'],
                'payment_status' => 'paid',
                'cash_received' => $cashReceived,
                'cash_change' => $cashChange,
                'transaction_date' => $sale['time'],
                'created_at' => $sale['time'],
            ]);

            foreach ($itemsToCreate as $it) {
                $trx->items()->create($it);
            }

            if ($sale['method'] === 'cash') {
                $todayCashTotal += $totalAmount;
            } else {
                $todayNonCashTotal += $totalAmount;
            }
        }

        // Update shift totals
        $shift->update([
            'cash_sales' => $todayCashTotal,
            'non_cash_sales' => $todayNonCashTotal,
            'expected_cash' => $shift->starting_cash + $todayCashTotal + $shift->cash_in - $shift->cash_out - $shift->cash_refunds,
        ]);
    }
}
