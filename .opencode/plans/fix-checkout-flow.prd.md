# Fix Checkout Flow — Login, Register & Guest

## Problem
Customer e-commerce tidak bisa checkout dengan benar karena checkout page menggunakan hardcoded mock cart (bukan data cart sebenarnya), cart tidak persist saat refresh, dan ada dua entry point checkout yang saling bertabrakan. Ini memblokir launch produk — tidak ada customer yang bisa menyelesaikan pembelian secara reliable.

## Evidence
- `Checkout.jsx` line 30: hardcoded `[{ product_id: 1, quantity: 1, name: 'Product 1', price: 92000 }]` — cart asli dari Zustand store tidak pernah dibaca.
- `Cart.jsx` punya `handleCheckout` yang langsung hit `/checkout` API tanpa melewati shipping form — entry point kedua yang bypass flow utama.
- Zustand cart store (`react/src/store/cart.js`) tidak pakai `persist` middleware — refresh halaman = cart kosong.
- Cart hanya support `addItem` (+1) dan `removeItem` (hapus total) — tidak ada update quantity.
- Guest checkout flow ada di backend tapi frontend flow belum terintegrasi dengan cart store yang benar.

## Users
- **Primary**: Customer e-commerce yang mau beli barang — baik yang sudah punya akun maupun guest yang tidak mau register.
- **Not for**: Admin toko (admin punya dashboard terpisah), wholesale buyer, B2B customer.

## Hypothesis
We believe **memperbaiki checkout flow (koneksi cart → checkout, tiga jalur auth: login/register/guest, payment via Xendit, dan order tracking)** will **menghilangkan checkout yang gagal dan memungkinkan customer menyelesaikan pembelian end-to-end** for **customer e-commerce**.
We'll know we're right when **customer bisa checkout sampai bayar tanpa error, order tercatat benar di sistem (PENDING → PAID), dan order bisa dilacak (via akun untuk login user, via tracking link untuk guest)**.

## Success Metrics
| Metric | Target | How measured |
|---|---|---|
| Checkout completion rate | > 0% → functional (currently broken) | Manual end-to-end test: cart → checkout → payment → order recorded |
| Cart persistence | Cart survives page refresh | Manual test: add item, refresh, cart still there |
| Guest checkout success | Guest can complete order and track it | Manual test: guest flow end-to-end + tracking link works |
| Webhook idempotency | No double stock decrement on duplicate webhook | Test: send same webhook twice, verify stock decremented once |

## Scope
**MVP** — Yang harus diperbaiki/dibangun:

1. **Cart store fix**: Zustand cart persist ke localStorage, tambah update quantity action.
2. **Satu entry point checkout**: Dari Cart page → Checkout page. Hapus direct API call di Cart.jsx.
3. **Checkout.jsx baca dari Zustand store**: Buang hardcoded mock cart.
4. **Tiga jalur auth di checkout**:
   - Sistem deteksi token Sanctum aktif → jika sudah login, lanjut langsung.
   - Jika belum login → tampilkan 3 pilihan: **Register** (form register baru) | **Login** | **Lanjut sebagai Guest**.
   - Register: form register → otomatis login → lanjut checkout.
   - Login: form login → lanjut checkout dengan data prefill.
   - Guest: form manual (nama, alamat, kota, no HP, postal code).
5. **Konfirmasi data**: Setelah isi shipping form (semua jalur), tampilkan layar konfirmasi data sebelum lanjut ke payment.
6. **Tambah kolom `guest_email`** di transactions table untuk data email guest.
7. **Backend checkout flow**: Validasi stok, create transaction (PENDING), create Xendit invoice, return invoice_url.
8. **Webhook handling**: Validasi x-callback-token, update status PAID, decrement stock, idempotent (skip jika sudah PAID).
9. **Post-payment**:
   - Login user → order muncul di riwayat (query by user_id).
   - Guest → tracking via `domain.com/track/{tracking_token}`, tracking token disimpan di localStorage.
10. **Checkout success page**: Tampilkan info order + link tracking.

**Out of scope**
- Dynamic shipping cost (berdasarkan lokasi/berat) — deferred, pakai flat Rp 25.000 dulu
- Notifikasi email — deferred ke fase berikutnya
- Kupon/diskon system
- Multiple saved addresses management
- Metode pembayaran UI (handled by Xendit hosted page)

## Delivery Milestones

| # | Milestone | Outcome | Status | Plan |
|---|---|---|---|---|
| 1 | Cart yang reliable | Customer bisa add/update/remove item, cart survive refresh | complete | — |
| 2 | Auth gate di checkout | Customer melihat pilihan Register/Login/Guest saat belum login, langsung lanjut saat sudah login | complete | — |
| 3 | Shipping form + konfirmasi | Customer isi data pengiriman (prefill untuk login, manual untuk guest) dan konfirmasi sebelum bayar | complete | — |
| 4 | Payment flow end-to-end | Transaction tercreate, Xendit invoice tergenerate, customer redirect ke halaman bayar | complete | — |
| 5 | Webhook + stock management | Status PAID terupdate via webhook, stok terkurangi, idempotent | complete | — |
| 6 | Order tracking | Login user lihat riwayat order, guest akses via tracking link | complete | — |

## Open Questions
- [ ] Guest email field: tambah migrasi kolom `guest_email` di transactions — sudah decided, perlu implementasi
- [ ] Apakah guest tracking link perlu dikirim ke email sebagai fallback? (deferred — email notifikasi out of scope MVP)
- [ ] Shipping cost flat Rp 25.000 apakah sudah final untuk MVP, atau perlu configurable dari admin?
- [ ] Apakah perlu rate limiting di checkout endpoint untuk mencegah abuse?

## Risks
| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Race condition pada stock check — dua customer checkout barang terakhir bersamaan | Medium | High — overselling | DB transaction dengan pessimistic lock atau check-and-decrement atomic |
| Xendit webhook gagal/telat | Low | Medium — status stuck PENDING | Xendit retry mechanism built-in; tambah manual status check endpoint jika perlu |
| Guest kehilangan tracking token (clear cache) | Medium | Medium — tidak bisa akses order | Informasikan di checkout success page untuk simpan link; email fallback di fase berikutnya |
| SSL verify disabled di production | High (sudah ada di code) | High — security vulnerability | Hapus `'verify' => false` dari Guzzle client, pastikan SSL cert valid di production |

---
*Status: DRAFT — requirements only. Implementation planning pending via /plan.*
