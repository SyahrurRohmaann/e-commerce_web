# E-commerce Admin Dashboard

## Problem
Admin dan pemilik toko tidak memiliki fitur untuk mengelola operasi toko secara memadai (nama, gambar, harga, stok produk, dll). Tanpa alat ini, operasional toko terhambat dan memaksa pengeditan manual ke dalam database.

## Evidence
- Assumption — needs validation via operational necessity (toko tidak bisa berjalan tanpa manajemen produk dasar).

## Users
- **Primary**: Admin toko dan pemilik toko, saat butuh mengatur katalog dan memproses pesanan.
- **Not for**: Pelanggan (customers).

## Hypothesis
We believe **a complete admin dashboard** will **allow full operational control** for **store admins and owners**.
We'll know we're right when **admin bisa kelola produk, pantau stok, dan cek status transaksi harian tanpa perlu buka database manual (phpMyAdmin/Tinker) sama sekali**.

## Success Metrics
| Metric | Target | How measured |
|---|---|---|
| Manual DB Edits | 0 / hari | Pantauan log operasional / tidak ada akses langsung ke DB |

## Scope
**MVP**
1. Login Admin (gerbang akses).
2. Category (CRUD).
3. Product (CRUD, termasuk manajemen stok).
4. Transaction (list + detail, read-only, status ter-update dari webhook).

**Out of scope**
- Discount/Promo & voucher — ditunda.
- Manajemen User/Customer (block/unblock) — ditunda.
- Shipping zone & ongkir dinamis — asumsikan flat/manual dulu.
- Review/rating produk — ditunda.
- Role & permission granular — cukup 2 role: admin & customer.
- Store settings (logo, banner, dll) — ditunda.
- Laporan/export penjualan — ditunda.
- Dashboard/Overview dengan grafik — ditunda.

## Delivery Milestones
<!-- Business outcomes, not engineering tasks. /plan turns each into a plan. -->
<!-- Status: pending | in-progress | complete -->

| # | Milestone | Outcome | Status | Plan |
|---|---|---|---|---|
| 1 | Admin Auth & Category | Admin dapat login dan mengelola struktur kategori | pending | — |
| 2 | Product Management | Admin dapat menambah dan mengedit produk beserta stok | pending | — |
| 3 | Transaction Monitoring | Admin dapat memantau pesanan masuk dan status otomatisnya | pending | — |

## Open Questions
- [ ] Kapan pindah Xendit dari sandbox ke live/production key?
- [ ] Apakah mekanisme lock/reservasi (race condition stok) wajib diimplementasikan sekarang untuk skripsi, atau cukup jadi catatan future work?

## Risks
| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Race condition stok | Medium | High | Putuskan status implementasi lock/reservasi produk |
| Webhook mismatch | Low | High | Pastikan struktur JSON Xendit sandbox sama dengan production |

## Key Files
- `laravel/app/Http/Controllers/CategoryController.php`
- `laravel/app/Http/Controllers/ProductController.php`
- `laravel/app/Http/Controllers/TransactionController.php`
- `laravel/app/Http/Controllers/AuthController.php`
- `react/src/pages/Dashboard.jsx`
- `react/src/layouts/AdminLayout.jsx`

## Implementation Steps
1. **Admin Auth**: Backend AuthController modifications for admin roles, Frontend AdminLayout and protected routes.
2. **Category CRUD**: Backend CategoryController, Frontend Category management UI.
3. **Product CRUD**: Backend ProductController (stock management), Frontend Product management UI.
4. **Transaction Monitoring**: Backend TransactionController, Frontend Transaction list/detail UI.

---
*Status: PLAN_GENERATED*
*SESSION_ID: session-1723233600*
