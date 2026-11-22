# AGENTS.md: Aplikasi SISFOZIS BAZNAS Kabupaten Cianjur

Sumber kebenaran:
- **Utama:** `docs/PRD.md` (PRD SISFOZIS v0.1).
- **Spesifikasi rinci RKAT pos L2-L7 (PK0/PK1 = PRC-01):** `docs/prd-pkp.md` dan `docs/rules.md`.
- **Rencana kerja lintas fase:** `TASK.md` (arsip rencana awal PKP: `docs/implementation-plan.md`).
- **Spesifikasi desain UI:** `docs/DESIGN.md` (di dalam aplikasi; hijau BAZNAS di launcher, login, dan halaman publik).
- **Standar UI front end:** Setiap pembuatan atau pengubahan UI front end wajib membaca `docs/DESIGN.md` sebagai arah desain dan memuat skill `antislop` (beserta sub-skill terkait seperti `antislop-ui`, `antislop-human`, `antislop-layoutmobile`, `antislop-copywriting`) sebagai filter kualitas.
- **Pertanyaan terbuka & keputusan:** `docs/open-questions.md`.
- **Bahasa UI, dokumen, dan balasan ke pengguna:** **Bahasa Indonesia**.

## Stack

- Laravel 12 + Inertia 2 + React 19 + TypeScript + Tailwind 4 (starter kit React, sidebar shadcn).
- PostgreSQL 17 (Docker `pkp-postgres`, port host 5433; DB `pkp` dan `pkp_test`).
- Pest 4 untuk uji; Pint untuk lint PHP; ESLint + Prettier + `tsc` untuk frontend.
- Library: `spatie/laravel-permission`, `maatwebsite/excel`, `barryvdh/laravel-dompdf`.
- Antrean: driver `database`. Zona waktu `Asia/Jakarta`, locale `id`, angka format id-ID.

## Lingkungan & Perintah (`E:\SIM BAZNAS\sim-baznas`)

- **Docker:** `docker compose up -d` menjalankan `pkp-app` (port 8000, image `sim-baznas-app:latest`, PHP 8.4.15 + ekstensi lengkap) dan `pkp-postgres` (PostgreSQL 17, port host 5433, DB `pkp` dan `pkp_test`).
- **PHP & Composer di Windows:** PHP Herd Lite di host (`C:\Users\ARSYAIRA\.config\herd-lite\bin\php.exe`) masih **PHP 8.1.31 tanpa `ext-gd`**, sedangkan `composer.lock` mensyaratkan PHP >= 8.4 dan `ext-gd`. Oleh karena itu perintah PHP 8.4 (`composer`, `pest`, `pint`, `artisan` migrasi uji) dijalankan via Docker menggunakan image `sim-baznas-app:latest` dengan mount workspace `E:/SIM BAZNAS/sim-baznas:/var/www/html` pada network `sim-baznas_pkp-network` (wajib sertakan `-e APP_ENV=testing` saat menjalankan Pest karena `Dockerfile` menyetel `ENV APP_ENV=production`). Untuk runtime container aktif gunakan `docker exec pkp-app php artisan ...`.
- **Node/npm di Windows:** `node node_modules/eslint/bin/eslint.js resources/js` dan `node node_modules/typescript/bin/tsc --noEmit` berjalan di Node host; bila `node_modules` berisi binding native Linux sehingga `vite build` di Windows gagal, bangun aset via container `node:22-alpine` atau salin hasil build dari `pkp-app` (`docker cp pkp-app:/var/www/html/public/build ./public/`).

| Tujuan | Perintah |
|---|---|
| Nyalakan layanan Docker | `docker compose up -d` (atau `docker start pkp-postgres pkp-app`) |
| Pasang dependensi PHP | `docker run --rm -e COMPOSER_MAX_PARALLEL_HTTP=4 -v "E:/SIM BAZNAS/sim-baznas:/var/www/html" -w /var/www/html sim-baznas-app:latest composer install --no-interaction` |
| Migrasi (container app) | `docker exec pkp-app php artisan migrate` |
| Uji (Pest) | `docker run --rm --network sim-baznas_pkp-network -v "E:/SIM BAZNAS/sim-baznas:/var/www/html" -w /var/www/html -e APP_ENV=testing -e DB_HOST=pkp-postgres -e DB_PORT=5432 sim-baznas-app:latest php vendor/bin/pest` |
| Lint PHP (Pint) | `docker run --rm -v "E:/SIM BAZNAS/sim-baznas:/var/www/html" -w /var/www/html sim-baznas-app:latest php vendor/bin/pint --test` (perbaiki: tanpa `--test`) |
| Lint JS & TS | `node node_modules/eslint/bin/eslint.js resources/js` dan `node node_modules/typescript/bin/tsc --noEmit` |
| Build aset frontend | `docker run --rm -v "E:/SIM BAZNAS/sim-baznas:/var/www/html" -w /var/www/html node:22-alpine npm run build` (atau `docker cp pkp-app:/var/www/html/public/build ./public/`) |
| Rollback uji (`pkp_test`) | `docker run --rm --network sim-baznas_pkp-network -v "E:/SIM BAZNAS/sim-baznas:/var/www/html" -w /var/www/html -e APP_ENV=testing -e DB_HOST=pkp-postgres -e DB_PORT=5432 -e DB_DATABASE=pkp_test sim-baznas-app:latest sh -c "php artisan migrate:fresh && php artisan migrate:rollback"` |

## Keputusan Arsitektur Terkunci (K1-K15)

1. **K1 (Sumber & Jembatan RKAT):** `docs/PRD.md` sumber utama. RKAT pos L2-L7 (`rkat_*`) dipertahankan sebagai `PRC-01`/`PRC-02` tanpa refaktor tabel `rkat_*`, dan dijembatani ke `bidang`, `jenis_dana`, `sumber_dana`, dan `program` pada `PRC-T02`.
2. **K2 (Uang BIGINT):** Nominal rupiah = **BIGINT rupiah utuh** di seluruh tabel (termasuk tabel baru SISFOZIS: `penerimaan_item`, `penyaluran_item`, `aset`, `cash_opname`, `jurnal`), bukan `numeric(18,2)`. Catat sebagai penyimpangan PRD Bagian 10 di `docs/open-questions.md`.
3. **K3, K7, K12 (RBAC 18 Peran Tetap):** RBAC memakai **18 peran tetap per bidang** (+ `admin`) dengan `spatie/laravel-permission`, ditegakkan lewat middleware `peran:` dan pengecekan ulang di Action (tidak memakai izin granular `modul.sumberdaya.aksi`). Semua pengguna internal otomatis memiliki peran dasar `amil`. Peran `pimpinan` lama dipecah menjadi `ketua` dan `waka_1..4`; peran `perencanaan`, `keuangan`, `admin` dipertahankan. Peran `upz` adalah akun pengguna eksternal pengurus UPZ untuk memantau setoran dan status SK UPZ sendiri. Kabag dapat melihat dashboard bidang lain (view only) dan kelola penuh di bidangnya.
4. **K4 (Desain Gabungan):** Token desain `docs/DESIGN.md` diterapkan manual di `resources/css/app.css` tanpa menjalankan CLI preset shadcn `b3Z3SCCVl2`. Di dalam aplikasi memakai palet `DESIGN.md`; hijau BAZNAS digunakan di launcher, login, dan halaman publik.
5. **K6 (Deployment Fase 0):** Fase 0 memakai Docker (`Dockerfile` + `docker-compose.yml`); sinkronisasi VPS dan server lokal di luar MVP (Fase 4).
6. **K8 (Rute Permohonan 7 Langkah):** Pelayanan Mustahik (input/verifikasi) -> Disposisi Ketua -> Kabag Pendis Pendag (hasil asesmen) -> Wakil Ketua II (ajuan besaran bantuan) -> Ketua (ACC besaran bantuan) -> Staf Pendis/Pendag (meminta uang ke bagian keuangan) -> Pelayanan Mustahik (bantuan diterima mustahik). Penugasan Staf Pendis vs Pendag fleksibel.
7. **K9 (Rute Surat Masuk):** Admin Arsip -> Ketua -> Wakil Ketua I/II/III/IV (bisa multi Waka) -> instruksi tindak lanjut ke bawahan.
8. **K10, K11, K14 (Master Dana, Asnaf, Program):** 4 Jenis Dana (`Zakat`, `Infak`, `DSKL`, `CSR`), 9 Sumber Dana (`ZF`, `ZM`, `IFTT`, `IFT`, `FDY`, `KRB`, `KA`, `KFT`, `CSR`), 8 Asnaf (khusus Zakat; dana lain tanpa asnaf), serta program Pendistribusian dan Pendayagunaan bersumber dari `docs/Jenis Dana_Sumber Dana_Asnaf_Program.md`. Peringatan "saldo tidak cukup" dihitung per **Kelompok Dana**.
9. **K13 (Master Wilayah):** Master wilayah Kabupaten Cianjur bersumber dari `docs/Daerah Kabupaten Cianjur.csv` (360 desa, kecamatan lengkap di Kab. Cianjur).
10. **K15 (Penomoran Dokumen):** Penomoran berbasis template konfigurasi (`config/penomoran.php` dengan token seperti `{jenis}`, `{urut:4}`, `{romawi_bulan}`, `{tahun}`) di atas tabel `nomor_sequence` dengan row-locking, reset per tahun.

## Aturan wajib (PRD bagian 15)

1. Nominal rupiah = BIGINT. Persen dihitung dari nilai asli, dibulatkan hanya saat tampil (BR-12).
2. Setiap perubahan data lewat **Action** (`app/Actions/...`) yang menulis `audit_logs` (siapa, kapan, nilai lama dan baru). Controller tidak menulis langsung ke model bisnis.
3. Tiap VAL-x dan BR-xx punya minimal satu uji yang **namanya memuat ID** (contoh: `it('VAL-1: total L2 sama dengan total L3')`).
4. Impor idempoten (upsert pada kunci alami). Impor ulang tidak menggandakan data.
5. Penguncian periode dan status ditegakkan di **server**; UI hanya cermin.
6. Rollup diuji terhadap penjumlahan data sumber.
7. Migrasi harus dapat di-rollback. Tanpa hard delete pada RKAT, realisasi, dan transaksi keuangan/penyaluran (pakai status/arsip).
8. **Jangan mengarang aturan bisnis.** Hal bertanda [TBC] atau tidak ada di PRD: catat di `docs/open-questions.md`, pakai asumsi paling konservatif, jangan ditebak.
9. Satu modul = satu cabang = satu PR kecil. Jangan mengubah modul lain.

## Alur per modul

1. Baca dokumen tugas terkait (`TASK.md`, `docs/PRD.md`, atau `docs/modules/PKx.md`) dan aturan terkait.
2. Tulis rencana implementasi modul, **berhenti untuk review** (wajib untuk tugas L/XL; tugas S boleh langsung dikerjakan sesuai `TASK.md`).
3. Setelah disetujui: migrasi + model, lalu Action + uji aturan, lalu halaman, lalu seed.
4. Jalankan uji dan lint; periksa kriteria selesai di dokumen modul/`TASK.md`.
5. Review manual dan merge.

## Konvensi

- Struktur: `app/Actions/<Modul>/`, `app/Models`, `app/Services` (logika murni, mis. validasi VAL-x), `app/Http/Controllers`, `resources/js/pages/<modul>/`.
- Kode pos: `Lampiran/kode` (mis. `L2/1.3`).
- Konvensi nama tabel mengikuti `docs/PRD.md` bagian 10 dan `docs/prd-pkp.md` bagian 11 (khusus `rkat_*`).
- Peran (spatie): 18 peran tetap per bidang (`pelayanan_muzakki`, `pelayanan_mustahik`, `admin_arsip`, `perencanaan`, `keuangan`, `kabag_sau`, `kabag_pengumpulan`, `kabag_pendis_pendag`, `kabag_pkp`, `staf_pendistribusian`, `staf_pendayagunaan`, `amil`, `waka_1`, `waka_2`, `waka_3`, `waka_4`, `ketua`, `upz`) + `admin` (lihat `TASK.md` bagian 1.1; peran `perencanaan`, `keuangan`, `admin` dipertahankan dari PK0/PK1).
- Fixture uji RKAT: `docs/fixtures/rkat-2026.json` dan `docs/fixtures/rkat-2022-2031.xlsx`.
- Jangan commit `.env`. Tanpa komentar yang menjelaskan hal jelas.
- Pembuatan dan pengubahan UI front end (`resources/js/pages/`, `resources/js/components/`, `resources/js/layouts/`) wajib menggunakan skill `/antislop` dengan `docs/DESIGN.md` sebagai arah desain.
