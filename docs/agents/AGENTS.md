# AGENTS.md — E-Commerce Migration (Laravel 11 + React + Xendit)

Dokumen ini adalah **aturan main** untuk agent yang mengeksekusi `PRD_MIGRATION.md` secara otomatis. Baca file ini sebelum menyentuh kode apa pun.

## 1. Peran & Mode Operasi
- Kamu bertindak sebagai **Autonomous Lead Engineer** yang mengeksekusi PRD ini.
- Sumber kebenaran scope & fitur: `PRD_MIGRATION.md`.
- Sumber kebenaran urutan kerja & kriteria selesai: `TASKS.md`.
- Sumber kebenaran progres saat ini: `STATE.json`.
- Selalu baca `STATE.json` di awal sesi untuk tahu harus mulai/lanjut dari Step mana. Jangan mulai dari Step 1 kalau `current_step` sudah lebih jauh.

## 2. Protokol Eksekusi (Wajib Diikuti)
1. Set status Step terkait di `STATE.json` menjadi `"in_progress"` sebelum mulai.
2. Kerjakan sub-task di `TASKS.md` **berurutan**, jangan lompat.
3. Setelah semua sub-task Step selesai, jalankan **Acceptance Criteria** Step tersebut (lihat `TASKS.md`).
4. Jika lolos verifikasi → tandai `[x]` semua sub-task, set status Step `"done"` di `STATE.json`, isi `last_updated`, pindahkan `current_step` ke Step berikutnya.
5. Jika gagal verifikasi → set status `"blocked"`, tulis alasan singkat di field `notes`, **jangan lanjut** ke Step berikutnya sebelum ini beres.
6. Laporkan progres tiap kali satu Step selesai: nama Step, file yang berubah, hasil verifikasi (ringkas, bukan dump log panjang).
7. Jangan pernah menandai Step `"done"` tanpa benar-benar menjalankan verifikasinya.

## 3. Tech Stack & Struktur Folder
- Backend: Laravel 11, PHP 8.2+, MySQL, Laravel Sanctum untuk auth.
- Frontend: React 18 + Vite, React Router v6, Zustand/Context untuk state cart, Axios untuk HTTP client.
- Payment: Xendit Invoice API + Webhook.
- Struktur repo:
  ```
  /api      -> backend Laravel
  /client   -> frontend React (Vite)
  ```

## 4. Konvensi Kode
- Laravel: gunakan Form Request untuk validasi, API Resource untuk shape response, Policy/Middleware untuk otorisasi (`isAdmin`), `SoftDeletes` trait di model Product.
- Format response API konsisten, contoh: `{ "success": bool, "data": ..., "message": string }`.
- React: functional components + hooks saja, tidak ada class component.
- Semua kredensial (Xendit API Key, Callback Token, DB creds) wajib lewat `.env`, tidak pernah hardcode di kode.
- Sediakan `.env.example` dengan placeholder untuk setiap secret yang dipakai.

## 5. Git & Commit
- Satu commit per sub-task atau unit logis yang selesai.
- Format pesan commit: `[Step X.Y] deskripsi singkat`.
- Jangan commit `.env`, `vendor/`, `node_modules/`.

## 6. Error Handling & Rollback
- Migration gagal → `php artisan migrate:rollback` sebelum retry, jangan tumpuk migration baru di atas yang error.
- Webhook Xendit dengan signature/token tidak valid → tolak dengan HTTP 401, log percobaan, **jangan** update status transaksi.
- Update status transaksi dari webhook harus idempotent (cek status existing dulu sebelum overwrite) agar tidak double-process.
- Exception yang tidak tertangani saat eksekusi → berhenti, set status Step `"blocked"` di `STATE.json`, jelaskan masalahnya alih-alih menebak-nebak solusi secara diam-diam.

## 7. Batasan — Hal yang TIDAK Boleh Dilakukan Agent
- Tidak boleh mengarang API Key/Callback Token Xendit sendiri. Kalau kredensial sandbox belum tersedia di `.env`, berhenti dan minta itu dulu sebelum lanjut Step 3.
- Tidak boleh menandai Step selesai tanpa menjalankan Acceptance Criteria di `TASKS.md`.
- Tidak boleh mengubah scope di `PRD_MIGRATION.md` atau urutan di `TASKS.md` tanpa mencatatnya secara eksplisit di `notes` pada `STATE.json`.
- Tidak boleh melompati validasi stok produk saat checkout, atau melewati validasi token webhook, meskipun untuk mempercepat testing.
