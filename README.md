# Kasir Pintar — Smart Point of Sale (POS) System

Sistem Point of Sale (POS) cerdas dan aplikasi manajemen retail/minimarket yang dibangun menggunakan **Laravel 12**, **Blade**, **Tailwind CSS**, **Alpine.js**, **Chart.js**, dan terintegrasi dengan **WhatsApp Business Platform Cloud API**.

Aplikasi dirancang dengan tampilan antarmuka bisnis modern berpalet warna netral dan aksen hijau lembut, alur kerja transaksi cepat, validasi stok atomik, kontrol pergantian shift kasir, audit kartu stok, serta analisis bisnis pintar.

---

## 1. Arsitektur & Prinsip Desain

### A. Prinsip UI/UX & Palet Warna
- **Background Utama:** `#F7F7F4`
- **Background Panel:** `#FFFFFF`
- **Sidebar:** `#F0F1ED`
- **Primary:** `#42634F`
- **Primary Hover:** `#354F3F`
- **Soft Accent:** `#DCE8DE`
- **Teks Utama:** `#303632`
- **Teks Sekunder:** `#747B75`
- **Border:** `#E3E6E0`
- **Success:** `#357653`
- **Warning:** `#A66D24`
- **Danger:** `#B84B4B`
- **Tipografi:** Sans-serif *Inter* yang bersih, mudah dibaca, dengan format Rupiah Indonesia standar (`Rp 15.000`).
- **Ikon:** Lucide icons yang konsisten (tanpa emoji pada menu navigasi).

### B. 12 Modul Navigasi Utama
1. **Dashboard** (`/dashboard`): Ringkasan omzet, laba kotor, grafik penjualan per jam, metode pembayaran, transaksi terbaru, produk terlaris, dan stok menipis.
2. **Transaksi Kasir (POS)** (`/pos`): Pencarian instan produk/SKU, dukungan pemindai barcode, keranjang belanja dinamis, pilihan pelanggan, diskon, pajak, tombol uang pas/pecahan cepat, kalkulasi kembalian, cetak struk thermal 80mm, dan bagikan struk via WhatsApp.
3. **Produk** (`/products`): Manajemen katalog barang, harga modal (HPP, khusus Admin), harga jual, batas minimum stok, satuan, filter kondisi stok, dan ekspor CSV.
4. **Kategori Produk** (`/categories`): Pengelompokan barang dan kalkulasi jumlah item terdaftar.
5. **Stok & Persediaan** (`/inventory`): Kartu stok lengkap (mutasi masuk/keluar/retur/rusak/penyesuaian manual), deteksi stok menipis, dan stok habis.
6. **Riwayat Transaksi** (`/transactions`): Daftar seluruh invoice kasir, detail transaksi, pembatalan (void) dengan pengembalian stok otomatis, dan ekspor CSV.
7. **Laporan Penjualan** (`/reports/daily` & `/reports/periodic`): Laporan harian, mingguan, bulanan, grafik tren omzet vs laba kotor, dan distribusi transaksi per jam.
8. **Shift Kasir** (`/shifts`): Buka shift (modal awal laci kasir), mutasi kas masuk/keluar (petty cash), tutup shift dengan rekonsiliasi kas fisik vs sistem, serta lembar tanda terima.
9. **Pelanggan** (`/customers`): Direktori pelanggan, nomor WhatsApp, akumulasi belanja, dan histori invoice.
10. **Integrasi WhatsApp** (`/whatsapp`): Konfigurasi WhatsApp Cloud API, trigger laporan harian otomatis, notifikasi stok menipis, mode simulasi demo, dan log pengiriman pesan dengan link direct `wa.me`.
11. **Pengguna & Hak Akses** (`/users`): Manajemen akun Administrator dan Kasir dengan perlindungan hak akses server-side.
12. **Pengaturan Toko** (`/settings`): Nama toko, jam operasional, jam batas hari bisnis (cut-off tengah malam), persentase PPN, dan header/footer struk thermal.

---

## 2. Struktur Database & Model

1. `users`: Akun pengguna, peran (`admin` / `cashier`), nomor telepon, status aktif.
2. `categories`: Kategori barang dan slug unik.
3. `products`: SKU, barcode, nama, harga modal (HPP), harga jual, stok fisik, batas stok minimum, satuan.
4. `customers`: Nama, kontak WhatsApp, email, alamat domisili, total belanja.
5. `cash_shifts`: Nomor shift, kasir bertugas, modal awal, penjualan tunai, non-tunai, kas masuk, kas keluar, ekspektasi kas laci, hitungan aktual, dan selisih rekonsiliasi.
6. `cash_movements`: Log mutasi kas laci (kas masuk atau petty cash operasional).
7. `transactions`: Invoice unik, kasir, shift, pelanggan, subtotal, diskon, pajak, total neto, HPP modal snapshot, laba kotor, metode pembayaran, uang diterima, kembalian, status (`paid`/`void`), alasan void.
8. `transaction_items`: Snapshot nama produk, SKU, harga modal saat transaksi, harga jual saat transaksi, kuantitas, subtotal, dan laba kotor.
9. `inventory_movements`: Kartu stok audit perubahan (`initial`, `restock`, `sale`, `return`, `damaged`, `adjustment`), stok sebelum & sesudah, alasan, dan petugas.
10. `store_settings`: Konfigurasi jam buka/tutup, batas cut-off hari bisnis, zona waktu, pajak, header/footer struk.
11. `whatsapp_settings`: API endpoint, Phone Number ID, Access Token, nomor pemilik toko, jadwal pengiriman laporan, mode demo.
12. `whatsapp_message_logs`: Rekam jejak pengiriman notifikasi, tipe pesan, status (`sent`, `demo_dispatched`, `failed`), dan respon gateway.
13. `audit_logs`: Audit jejak tindakan penting (perubahan harga, void transaksi, penutupan shift).

---

## 3. Akun Pengguna Bawaan (Demo Seed)

Setelah menjalankan seeder database, tersedia 2 akun demo untuk pengujian:

| Peran | Alamat Email | Password | Hak Akses |
|---|---|---|---|
| **Administrator** | `admin@kasirpintar.com` | `password123` | Akses penuh (Dashboard, Laba Kotor, HPP, Laporan, Pengaturan, User, WhatsApp) |
| **Kasir** | `kasir@kasirpintar.com` | `password123` | POS Kasir, Shift Kasir, Riwayat Transaksi miliknya (HPP dan laba disembunyikan) |

*Tersedia tombol **Demo Quick Login** di halaman login untuk beralih peran hanya dengan 1 klik.*

---

## 4. Petunjuk Instalasi & Menjalankan Aplikasi

### Persyaratan Lingkungan
- PHP >= 8.2 (dengan ekstensi `pdo_sqlite` atau `pdo_mysql`, `mbstring`, `openssl`, `curl`)
- Composer
- Node.js & NPM

### Langkah-langkah Menjalankan:

1. **Clone / Buka direktori proyek:**
   ```bash
   cd "e:\1.Berto Tugas\Semester 7\UIUX\BelajarCape"
   ```

2. **Salin file konfigurasi lingkungan:**
   ```bash
   cp .env.example .env
   ```

3. **Install dependensi PHP & JavaScript:**
   ```bash
   composer install
   npm install
   ```

4. **Generate Application Key:**
   ```bash
   php artisan key:generate
   ```

5. **Jalankan Migrasi & Database Seeder:**
   ```bash
   php artisan migrate:fresh --seed
   ```
   *(Secara bawaan database menggunakan SQLite lokal tanpa perlu instalasi server database terpisah. Jika ingin menggunakan MySQL, cukup ubah `DB_CONNECTION=mysql` pada file `.env`)*

6. **Kompilasi Aset Frontend (Tailwind CSS, Alpine.js, Chart.js, Lucide):**
   ```bash
   npm run build
   ```

7. **Jalankan Development Server:**
   ```bash
   php artisan serve --port=8000
   ```
   Buka peramban pada tautan: `http://127.0.0.1:8000`

---

## 5. Pengujian Otomatis (Automated Testing)

Aplikasi dilengkapi unit dan feature testing lengkap mencakup autentikasi, otorisasi peran, validasi stok atomik, rekonsiliasi shift, void invoice, dan integrasi WhatsApp.

Jalankan test suite menggunakan perintah:
```bash
php artisan test
```

Hasil pengujian: **9 Passed (41 assertions), 0 Failed**.

---

## 6. Panduan Konfigurasi WhatsApp

1. Masuk sebagai **Administrator** dan buka menu **Integrasi WhatsApp** (`/whatsapp`).
2. Masukkan **Phone Number ID** dan **System User Access Token** dari portal Meta for Developers.
3. Masukkan nomor WhatsApp pemilik toko (contoh: `6281234567890`).
4. Klik **Simpan Pengaturan** lalu klik **Jalankan Uji Koneksi**.
5. **Mode Demo:** Jika token belum tersedia atau dalam tahap pengetesan lokal, biarkan opsi **Mode Demo** aktif. Sistem akan mencatat pesan laporan dan struk di log, serta menyediakan tombol tautan langsung (`https://wa.me/`) untuk membuka pesan yang sudah tersusun di WhatsApp Web/Aplikasi dengan 1 klik.
