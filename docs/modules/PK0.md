# Modul PK0: Fondasi

Rilis P0. Cakupan dari PRD 14.1 dan kriteria selesai PRD 14.2.

## Cakupan

- Login, pembatasan laju login, sesi berakhir otomatis, RBAC 5 peran sesuai matriks PRD 2.
- `audit_logs` append-only + pola Action untuk semua perubahan data.
- `pos_anggaran` (kode `Lampiran/kode`, induk, path, tipe nilai, agregasi, sifat waktu, masa berlaku) + `pos_alias`.
- Seed pos baku dari seluruh kode Lampiran 2-7 RKAT 2026 (menunggu D1).
- Mesin impor Excel generik: `import_jobs`, `import_rows`, pratinjau, galat per baris, idempoten, antrean database.
- Layout: sidebar (Dashboard, PKP 4 submenu, Pengaturan khusus Admin), header pemilih tahun, format angka id-ID, zona WIB.

## Kriteria selesai (PRD 14.2)

1. Login dan peran sesuai matriks bagian 2.
2. Audit log berjalan.
3. Pos baku terisi dari seluruh kode Lampiran 2-7 RKAT 2026.
4. Mesin impor menyediakan pratinjau, galat per baris, dan impor ulang tidak menggandakan data.
5. Layout sidebar responsif.

## Uji wajib

- RBAC per peran (feature test, 5 peran x objek utama).
- Audit tercatat pada setiap Action.
- Impor idempoten: impor dua kali dengan berkas sama tidak menambah baris.
- Migrasi rollback bersih.

## Blocker

- D1: dokumen RKAT 2026. Tanpa itu poin 3 tidak dapat diselesaikan; sementara hanya struktur dan mekanisme.
