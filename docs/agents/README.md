# README — Paket Eksekusi Otomatis: E-Commerce Migration

Paket ini dibuat supaya coding agent (opencode, Claude Code, atau agent lain) bisa mengeksekusi `PRD_MIGRATION.md` secara berurutan, terverifikasi, dan bisa di-resume kalau sesi terputus.

## Isi Paket
| File | Fungsi |
|---|---|
| `PRD_MIGRATION.md` | Dokumen requirement asli — sumber kebenaran scope & fitur. |
| `AGENTS.md` | Aturan main agent: protokol eksekusi, konvensi kode, batasan (apa yang tidak boleh dilakukan). |
| `TASKS.md` | Checklist granular per Step, lengkap dengan Acceptance Criteria untuk verifikasi tiap Step. |
| `STATE.json` | State progres machine-readable — sumber kebenaran "sedang di Step mana" dan status tiap Step. |

## Alur Kerja
1. Agent baca `AGENTS.md` dulu → paham aturan & batasan.
2. Agent baca `STATE.json` → cek `current_step`. Kalau bukan `step_1`, artinya melanjutkan sesi sebelumnya, jangan mulai dari nol.
3. Agent buka `TASKS.md`, kerjakan sub-task Step yang aktif secara berurutan, centang `[x]` sambil jalan.
4. Setelah semua sub-task selesai, jalankan Acceptance Criteria Step tersebut.
5. Lolos → set status Step `"done"` di `STATE.json`, isi `last_updated`, majukan `current_step` ke Step berikutnya.
6. Gagal → set status `"blocked"`, isi `notes` dengan alasan, jangan lanjut sebelum beres.

## Cara Menjalankan dengan Coding Agent
Contoh prompt awal ke agent:

> "Baca AGENTS.md, TASKS.md, dan STATE.json di root folder ini. Lanjutkan eksekusi PRD_MIGRATION.md dari step yang tercatat di STATE.json (`current_step`). Ikuti protokol eksekusi di AGENTS.md secara ketat, termasuk aturan verifikasi sebelum lanjut ke step berikutnya."

Kalau ingin mulai ulang dari awal, reset `STATE.json` (semua status jadi `"pending"`, `current_step` ke `"step_1"`, `last_updated` ke `null`).

## Yang Perlu Disiapkan Manual Sebelum Step 3
- Kredensial sandbox Xendit: `XENDIT_API_KEY` dan `XENDIT_CALLBACK_TOKEN`. Agent tidak diperbolehkan mengarang nilai ini sendiri (lihat `AGENTS.md` §7) — isi ke `.env` sebelum Step 3 dijalankan.

## Catatan
- `STATE.json` adalah satu-satunya sumber kebenaran progres antar sesi. Jangan dihapus kecuali memang mau mengulang dari awal.
- Kalau scope di PRD berubah di tengah jalan, update `PRD_MIGRATION.md` dan `TASKS.md` secara eksplisit, lalu catat perubahan di `notes` pada `STATE.json` supaya jejaknya jelas.
