# TASKS.md — Checklist Eksekusi Granular

Setiap Step di bawah adalah turunan langsung dari `PRD_MIGRATION.md`, dipecah jadi sub-task yang bisa dicentang, plus **Acceptance Criteria** sebagai syarat sebelum lanjut ke Step berikutnya. Update status paralel di `STATE.json`.

---

## Step 1: Backend Initialization & Database Schema
- [x] 1.1 Generate project Laravel 11 baru di folder `api/`
- [x] 1.2 Konfigurasi `.env` (DB_CONNECTION=mysql, dst) + buat `.env.example` dengan placeholder
- [x] 1.3 Migration `users` (tambah kolom `role` enum: admin/customer)
- [x] 1.4 Migration `categories`
- [x] 1.5 Migration `products` (pakai `softDeletes()`)
- [x] 1.6 Migration `transactions`
- [x] 1.7 Migration `transaction_items`
- [x] 1.8 Jalankan `php artisan migrate` — pastikan tidak ada error
- [x] 1.9 Buat Model + relasi Eloquent: User, Category (hasMany Product), Product (belongsTo Category, SoftDeletes), Transaction (hasMany TransactionItem), TransactionItem (belongsTo Transaction, belongsTo Product)
- [x] 1.10 Seeder: AdminSeeder (1 admin dummy), CategorySeeder (3 kategori), ProductSeeder (10 produk)
- [x] 1.11 Jalankan `php artisan db:seed` — pastikan data masuk tanpa error

**Acceptance Criteria Step 1**
- `php artisan migrate:fresh --seed` sukses dari kondisi bersih tanpa error.
- Semua tabel sesuai schema PRD.
- Relasi Eloquent bisa dipanggil via Tinker tanpa error (mis. `Product::first()->category`).

---

## Step 2: API & Authentication (Laravel Sanctum)
- [x] 2.1 Install `laravel/sanctum`, publish config, migrate
- [x] 2.2 Setup Sanctum (stateful/token sesuai kebutuhan SPA)
- [x] 2.3 `AuthController`: `register()`, `login()`, `logout()`
- [x] 2.4 Middleware `IsAdmin` (cek `role === 'admin'`), register di Kernel
- [x] 2.5 `CategoryController` (Admin, CRUD lengkap, protected `auth:sanctum` + `isAdmin`)
- [x] 2.6 `ProductController` (Admin, CRUD lengkap, protected)
- [x] 2.7 `TransactionController` (Admin, read-only list + detail, protected)
- [x] 2.8 `CatalogController` (publik, list produk + filter kategori) & `ProductDetailController` (publik)
- [x] 2.9 Daftarkan semua route di `routes/api.php` (group prefix `/admin` untuk protected, publik tanpa prefix)

**Acceptance Criteria Step 2**
- Register & login mengembalikan token Sanctum valid.
- Endpoint admin mengembalikan 403 jika diakses tanpa role admin.
- Endpoint katalog publik bisa diakses tanpa token.
- Semua endpoint sudah dicoba manual (Postman/Thunder Client) atau via `php artisan test`.

---

## Step 3: Payment Gateway Integration (Xendit)
- [x] 3.1 Install `xendit/xendit-php`
- [x] 3.2 Tambahkan `XENDIT_API_KEY` & `XENDIT_CALLBACK_TOKEN` ke `.env` + `.env.example`
- [x] 3.3 `CheckoutController@store`: validasi stok tiap item vs `products.stock`
- [x] 3.4 Insert `transactions` (status `PENDING`) + `transaction_items`
- [x] 3.5 Panggil Xendit Create Invoice API, simpan `xendit_invoice_id`
- [x] 3.6 Return `invoice_url` ke frontend
- [x] 3.7 `WebhookController@handle`: validasi header `x-callback-token` vs `XENDIT_CALLBACK_TOKEN`
- [x] 3.8 Update status transaksi (`PAID`/`EXPIRED`) dari payload webhook, idempotent (cek status dulu sebelum overwrite)

**Acceptance Criteria Step 3**
- Checkout dengan stok cukup → transaksi `PENDING` + `invoice_url` valid dari Xendit sandbox.
- Checkout dengan stok kurang → ditolak dengan pesan error jelas, tidak ada transaksi terbuat.
- Webhook dengan token salah → HTTP 401, status transaksi tidak berubah.
- Webhook dengan token benar & payload `PAID` → status transaksi jadi `PAID`, stok produk berkurang sesuai item.

---

## Step 4: Frontend Initialization (React)
- [x] 4.1 Generate project Vite + React di folder `client/`
- [x] 4.2 Axios instance (`src/lib/axios.js`) dengan interceptor attach Bearer Token
- [x] 4.3 Setup React Router dengan struktur route publik & admin terpisah
- [x] 4.4 `PublicLayout` & `AdminLayout` (admin layout pakai protected route guard)

**Acceptance Criteria Step 4**
- `npm run dev` berjalan tanpa error.
- Navigasi publik & admin berfungsi; akses route admin tanpa login → redirect ke halaman login.

---

## Step 5: Frontend Integration (Customer App)
- [x] 5.1 Halaman Home: fetch katalog publik, render grid produk
- [x] 5.2 State cart (Zustand/Context): add/remove/update qty
- [x] 5.3 Halaman Cart: list item, total harga, tombol checkout
- [x] 5.4 Halaman Checkout: kirim payload cart ke `CheckoutController`, redirect ke `invoice_url`

**Acceptance Criteria Step 5**
- Produk di Home sesuai data seeder dari API.
- State cart bertahan saat navigasi antar halaman.
- Checkout sukses redirect ke halaman invoice Xendit sandbox.

---

## Step 6: Frontend Integration (Admin Dashboard)
- [x] 6.1 Halaman Login Admin (submit ke `AuthController@login`, simpan token)
- [x] 6.2 CRUD UI Kategori (list, create, edit, delete)
- [x] 6.3 CRUD UI Produk (list, create, edit, delete, termasuk field stok)
- [x] 6.4 List Transaksi UI (read-only), status reflect update dari webhook

**Acceptance Criteria Step 6**
- Login admin sukses, token tersimpan, dashboard bisa diakses.
- CRUD kategori & produk ter-reflect ke database (verifikasi lewat API/DB).
- List transaksi menampilkan status `PAID` setelah webhook diterima (uji manual trigger webhook dari dashboard sandbox Xendit atau simulasi payload).

---

## Catatan Umum
- Jangan tandai sub-task `[x]` sebelum benar-benar dikerjakan dan diverifikasi.
- Jika satu Acceptance Criteria gagal, perbaiki dulu sebelum melangkah ke Step berikutnya — jangan skip.
