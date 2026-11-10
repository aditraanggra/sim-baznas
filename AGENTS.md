# AGENTS.md: Aplikasi PKP BAZNAS Kabupaten Cianjur

Sumber kebenaran: `docs/prd-pkp.md` (PRD v0.1). Rencana kerja: `docs/implementation-plan.md`. Aturan: `docs/rules.md`. Pertanyaan terbuka: `docs/open-questions.md`. Bahasa UI, dokumen, dan balasan ke pengguna: **Bahasa Indonesia**.

## Stack

- Laravel 12 + Inertia 2 + React 19 + TypeScript + Tailwind 4 (starter kit React, sidebar shadcn).
- PostgreSQL 17 (docker `pkp-postgres`, port host 5433; DB `pkp` dan `pkp_test`).
- Pest 4 untuk uji; Pint untuk lint PHP; ESLint + Prettier + `tsc` untuk frontend.
- Library: `spatie/laravel-permission`, `maatwebsite/excel`, `barryvdh/laravel-dompdf`.
- Antrean: driver `database`. Zona waktu `Asia/Jakarta`, locale `id`, angka format id-ID.

## Perintah

PHP dan Composer hanya ada di WSL Ubuntu-22.04 (`/home/sa00/.config/herd-lite/bin/`). Jalankan: `wsl.exe -d Ubuntu-22.04 -- bash -lc "cd /mnt/d/IT/sim-baznas && ~/.config/herd-lite/bin/php artisan ..."`. Node/npm di Windows; bila `vite`/`eslint`/`tsc` tidak dikenali, panggil `node node_modules/<paket>/bin/<berkas>.js`.

| Tujuan | Perintah |
|---|---|
| Nyalakan DB | `docker start pkp-postgres` |
| Migrasi | `php artisan migrate` |
| Uji | `php vendor/bin/pest` |
| Lint PHP | `php vendor/bin/pint --test` (perbaiki: tanpa `--test`) |
| Lint JS | `npm run lint` dan `npm run types` |
| Build | `npm run build` (uji fitur butuh `public/build/manifest.json`) |
| Dev | `composer dev` |
| Rollback uji | `php artisan migrate:fresh` lalu `php artisan migrate:rollback` harus bersih |

## Aturan wajib (PRD bagian 15)

1. Nominal rupiah = BIGINT. Persen dihitung dari nilai asli, dibulatkan hanya saat tampil (BR-12).
2. Setiap perubahan data lewat **Action** (`app/Actions/...`) yang menulis `audit_logs` (siapa, kapan, nilai lama dan baru). Controller tidak menulis langsung ke model bisnis.
3. Tiap VAL-x dan BR-xx punya minimal satu uji yang **namanya memuat ID** (contoh: `it('VAL-1: total L2 sama dengan total L3')`).
4. Impor idempoten (upsert pada kunci alami). Impor ulang tidak menggandakan data.
5. Penguncian periode dan status ditegakkan di **server**; UI hanya cermin.
6. Rollup diuji terhadap penjumlahan data sumber.
7. Migrasi harus dapat di-rollback. Tanpa hard delete pada RKAT dan realisasi (status/arsip).
8. **Jangan mengarang aturan bisnis.** Hal bertanda [TBC] atau tidak ada di PRD: catat di `docs/open-questions.md`, pakai asumsi paling konservatif, jangan ditebak.
9. Satu modul = satu cabang = satu PR kecil. Jangan mengubah modul lain.

## Alur per modul

1. Baca `docs/modules/PKx.md` dan aturan terkait.
2. Tulis rencana implementasi modul, **berhenti untuk review**.
3. Setelah disetujui: migrasi + model, lalu Action + uji aturan, lalu halaman, lalu seed.
4. Jalankan uji dan lint; periksa kriteria selesai di dokumen modul.
5. Review manual dan merge.

## Konvensi

- Struktur: `app/Actions/<Modul>/`, `app/Models`, `app/Services` (logika murni, mis. validasi VAL-x), `app/Http/Controllers`, `resources/js/pages/<modul>/`.
- Kode pos: `Lampiran/kode` (mis. `L2/1.3`).
- Konvensi nama tabel mengikuti PRD bagian 11.
- Peran (spatie): `perencanaan`, `pelaporan`, `keuangan`, `pimpinan`, `admin`. Matriks hak di PRD bagian 2.
- Fixture uji: `docs/fixtures/rkat-2026.json` (belum ada; lihat open-questions D1).
- Jangan commit `.env`. Tanpa komentar yang menjelaskan hal jelas.
