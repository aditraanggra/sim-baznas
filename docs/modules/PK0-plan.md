# Rencana Implementasi PK0 (Fondasi)

Status: **menunggu review**. Belum ada kode modul yang ditulis. Semua yang di bawah ini adalah usulan.

Update 6 November 2026: D1 sebagian terjawab — berkas sumber RKAT diterima dan fixture `docs/fixtures/rkat-2026.json` (112 pos) sudah dibuat serta lolos semua nilai kontrol PRD 10.1. Celah tersisa (Program Prioritas, jiwa/unit/desa, saldo awal) tidak menghalangi seed pos baku; lihat `docs/open-questions.md` temuan 17-24. Pertanyaan reviewer no. 4 tidak lagi relevan: seed langsung dari fixture.

## 1. Ruang lingkup yang dikerjakan

Urutan sesuai PRD 15: migrasi + model -> Action + uji -> halaman -> seed.

1. RBAC 5 peran (spatie) + pembatasan pendaftaran mandiri.
2. `audit_logs` append-only + base Action.
3. `pos_anggaran` + `pos_alias` + seed struktur (menunggu D1 untuk isi kode).
4. `import_jobs` + `import_rows` + mesin impor generik idempoten.
5. Layout sidebar/header, format angka id-ID, zona WIB.
6. Pengaturan: pengguna, pos anggaran, impor, audit.

## 2. Skema (migrasi pertama, sulit diubah nanti)

### `pos_anggaran`

| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint PK | |
| kode | varchar(32) unik | format `L2/1.3` |
| nama | varchar(255) | |
| induk_id | FK self, null | akar Lampiran punya induk null |
| path | varchar(255) indeks | mis. `L2/1/1.3`, untuk agregasi cepat |
| lampiran | varchar(2) | `L2`..`L7` |
| tingkat | smallint | 2..7 |
| tipe_nilai | varchar(16) | `rupiah`,`orang`,`badan`,`jiwa`,`unit`,`persen` |
| agregasi | varchar(16) | `jumlah_anak`,`input_langsung`,`hitung` |
| sifat_waktu | varchar(8) | `aliran`,`stok` (default `aliran`) |
| rumus | varchar(255) null | hanya untuk `hitung` |
| berlaku_dari_tahun | smallint | |
| berlaku_sampai_tahun | smallint null | null = berlaku terus |
| aktif | boolean default true | pengganti hard delete |
| urutan | integer | urutan tampil |
| timestamps | | |

Unik: `(kode, tipe_nilai)` karena satu pos logis dapat punya beberapa ukuran. Bila ternyata kode sudah membedakan ukuran, tetap aman.

### `pos_alias`

`id`, `pos_anggaran_id` FK, `tahun` smallint, `kode_alias` varchar(64), `nama_alias` varchar(255) null, timestamps. Unik `(tahun, kode_alias)`. Many-to-one saja (temuan 5).

### `audit_logs`

`id`, `user_id` FK null, `aksi` varchar(32), `entitas_tipe` varchar(64) indeks, `entitas_id` bigint indeks, `nilai_lama` jsonb null, `nilai_baru` jsonb null, `alasan` text null, `ip` varchar(45) null, `user_agent` varchar(255) null, `created_at`. Tanpa `updated_at`; model melarang update/delete.

### `import_jobs` / `import_rows`

- jobs: `id`, `user_id`, `jenis` (`rkat`,`realisasi`,`pos`), `nama_berkas`, `path`, `status` (`menunggu`,`proses`,`selesai`,`gagal`), `total_baris`, `baris_sukses`, `baris_gagal`, `ringkasan` jsonb, `mulai_pada`, `selesai_pada`, timestamps.
- rows: `id`, `import_job_id` FK cascade, `nomor_baris`, `kunci` varchar(128), `status` (`valid`,`galat`,`dilewati`), `galat` jsonb null, `data` jsonb, timestamps. Unik `(import_job_id, nomor_baris)`.

Kunci alami impor: `(jenis, tahun, kode_pos, tipe_nilai)` untuk RKAT; `(jenis, tahun, bulan, kode_pos, tipe_nilai)` untuk realisasi. Upsert, bukan insert.

## 3. Action dan uji

- `App\Actions\Audit\CatatAudit` (dipanggil semua Action; menyimpan nilai lama/baru).
- `App\Actions\Pos\SimpanPos`, `NonaktifkanPos`, `ImporAlias`.
- `App\Actions\Impor\MulaiImpor`, `PratinjauImpor`, `ProsesBarisImpor` (antrean `database`).
- Uji wajib:
  - RBAC: 5 peran x objek matriks PRD 2 (feature test).
  - Audit: setiap Action menghasilkan satu baris audit dengan nilai lama/baru.
  - Impor idempoten: impor berkas sama dua kali -> jumlah baris tetap.
  - Audit append-only: update/delete ditolak.
  - Rollback migrasi bersih (`composer fresh`).

## 4. Halaman

- Layout aplikasi: sidebar (Dashboard, PKP > Rencana/Realisasi/Kinerja/Keuangan, Pengaturan khusus Admin), header pemilih tahun, breadcrumb.
- Dashboard placeholder (isi sesungguhnya PK5).
- Pengaturan: Pengguna (CRUD + peran), Pos Anggaran (tabel pohon + filter lampiran), Impor (wizard 3 langkah: unggah -> pratinjau -> hasil), Audit (daftar + filter).
- Utilitas format: `formatRupiah` (Rp + titik ribuan), `formatAngka`, `formatPersen` 1 desimal dari nilai asli.

## 5. Keputusan yang dipakai (dari open-questions)

- D2 satu baris `pos_anggaran` per ukuran; satu kolom `nilai` BIGINT di tabel nilai.
- D3 `sifat_waktu` default `aliran`.
- D4 Perencanaan mencatat Diajukan/Disahkan.
- D5 login email.
- D7 library sudah terpasang.
- Pendaftaran mandiri **dinonaktifkan**; Admin membuat pengguna (PRD 2: Admin mengelola pengguna). Dicatat sebagai asumsi.
- Sesi: `SESSION_LIFETIME=120`, `SESSION_EXPIRE_ON_CLOSE=true` (sesi berakhir otomatis).

## 6. Yang tidak dikerjakan di PK0

- Isi kode L2-L7 RKAT 2026 (butuh D1).
- Tabel RKAT, realisasi, keuangan (modul masing-masing).
- 2FA (P-C).

## 7. Pertanyaan untuk reviewer

1. Setujui skema `pos_anggaran` di atas, khususnya unik `(kode, tipe_nilai)`?
2. Setujui pendaftaran mandiri dinonaktifkan?
3. Setujui `SESSION_LIFETIME=120` menit + expire on close?
4. ~~Untuk seed sementara tanpa D1~~: terjawab — seed memakai `docs/fixtures/rkat-2026.json` (112 pos dari berkas sumber).
