# Product Requirements Document (PRD): E-Commerce Migration (Laravel + React + Xendit)

## 1. Overview
Migrasi aplikasi e-commerce dari Native PHP menjadi arsitektur modern (Headless). 
- **Backend:** Laravel 11 (API).
- **Frontend:** React (Vite).
- **Payment:** Xendit (Invoice & Webhooks).

## 2. Autonomous Execution Steps (Delegation Contract)

### Step 1: Backend Initialization & Database Schema
1. Generate project Laravel baru (folder pi).
2. Konfigurasi .env (Database: MySQL).
3. Buat Migrations untuk tabel: users (admin/customer), categories, products (dengan soft deletes), 	ransactions, 	ransaction_items.
4. Buat Models dengan relasi Eloquent (HasMany, BelongsTo).
5. Buat Database Seeders (Dummy Admin, 3 Kategori, 10 Produk).

### Step 2: API & Authentication (Laravel Sanctum)
1. Install & setup Laravel Sanctum.
2. Buat AuthController (Login, Register, Logout).
3. Buat Middleware Role (isAdmin).
4. Buat CRUD API Controllers untuk Admin (CategoryController, ProductController, TransactionController).
5. Buat Public API Controllers untuk Customer (Katalog Produk, Detail Produk).

### Step 3: Payment Gateway Integration (Xendit)
1. Setup Xendit PHP Client di Laravel.
2. Buat CheckoutController:
   - Validasi stok produk.
   - Insert data ke 	ransactions (Status: PENDING).
   - Tembak API Xendit Create Invoice.
   - Kembalikan invoice_url ke frontend.
3. Buat WebhookController:
   - Terima POST dari Xendit.
   - Validasi callback token.
   - Update status transaksi (PAID / EXPIRED).

### Step 4: Frontend Initialization (React)
1. Generate project React (Vite) di folder client.
2. Setup Axios instance dengan interceptors (attach Bearer Token Sanctum).
3. Setup Routing (React Router).
4. Buat layout terpisah: Public (Customer) dan Protected (Admin Dashboard).

### Step 5: Frontend Integration (Customer App)
1. Halaman Home: Fetch API Public Katalog Produk.
2. Halaman Cart: State management (Zustand/Context) untuk menyimpan item.
3. Halaman Checkout: Kirim data cart ke API Laravel -> Redirect user ke invoice_url Xendit.

### Step 6: Frontend Integration (Admin Dashboard)
1. Halaman Login Admin.
2. CRUD Kategori & Produk UI (Fetch dari API Admin).
3. List Transaksi UI (Read Only, status terupdate otomatis via Webhook).

## 3. Delegation Completion Contract (ECC)
Sebagai Autonomous Lead Engineer, saya (opencode) berkomitmen untuk mengeksekusi langkah-langkah di atas secara berurutan saat mode eksekusi diaktifkan. Setiap langkah (*Step*) akan diselesaikan, diuji (verify API response/UI render), dan dilaporkan statusnya sebelum melanjutkan ke langkah berikutnya.
