# Rencana Pengerjaan Aplikasi PKP BAZNAS Cianjur (berdasar `Docs/PRD_PKP.md` v0.1)

Catatan konteks: berkas ada di `Docs/PRD_PKP.md` (bukan `DOC/`). Repo masih kosong (hanya `Docs/`), tanpa git. PRD merujuk `docs/` huruf kecil; putuskan satu (rekomendasi: `docs/`, pindahkan PRD ke `docs/prd-pkp.md` di P0). Toolchain terdeteksi: PHP + Composer (herd-lite), Node 23. PostgreSQL belum terverifikasi; periksa di P0.

Skala prioritas: **P-A** blocker/jalur kritis, **P-B** wajib untuk go-live rilis, **P-C** penting tapi bisa menyusul dalam rilis yang sama, **P-D** ditunda/opsional. Tidak ada estimasi waktu; urutan mengikuti ketergantungan data PRD: Fondasi → Rencana → Realisasi → Kinerja + Dashboard → Keuangan.

## 1. Hasil analisis PRD (temuan yang mengubah rencana)

Angka kontrol VAL-1..VAL-5 sudah saya hitung ulang dan konsisten (mis. 22.015.000.000 − 3.525.236.000 = 18.489.764.000; 176.434 + 24.846 + 150 = 201.430). Celah yang perlu keputusan sebelum/ketika coding:

1. **Dokumen sumber RKAT 2026 tidak ada di repo.** Seed pos baku (L2-L7), `fixtures/rkat-2026.json`, dan tata letak ekspor Lampiran semuanya bergantung padanya. Ini blocker PK0/PK1 (P-A).
2. **Ukuran ganda per pos.** L4 punya rupiah + jiwa + unit/desa; L5 punya orang + badan. Tabel `rkat_values` menyebut beberapa kolom, tetapi `realisasi_values` hanya "nilai". Keputusan: tiap ukuran = satu baris `pos_anggaran` sendiri dengan `tipe_nilai` (rupiah/orang/badan/jiwa/unit/persen). Satu kolom `nilai` BIGINT cukup, agregasi dan alias tetap seragam.
3. **BR-07 (kumulatif = jumlah bulan 1..n) bertabrakan dengan data stok.** Jumlah orang "kumulatif tahun berjalan" [ASUMSI] dan saldo adalah stok, bukan aliran. Tambah atribut `sifat_waktu` pada pos (`aliran` dijumlahkan, `stok` ambil nilai terakhir/hitung). Default konservatif: orang diinput **per bulan (aliran)** sehingga BR-07 berlaku; ubah bila jawaban Q6 berbeda.
4. **Pos baku tidak punya masa berlaku.** Struktur pos berubah antar tahun. Tambah `berlaku_dari_tahun`/`berlaku_sampai_tahun` (atau tandai nonaktif, tanpa hard delete).
5. **`pos_alias` hanya aman many-to-one** (banyak kode lama ke satu kode baku). Pemecahan satu pos lama menjadi beberapa tidak terwakili: tandai tak terpetakan (sesuai BR-10) dan catat di open-questions.
6. **Tanggal peralihan sumber (PRD 1.4) tidak ada di model data.** v1 hanya `manual` dan `impor`; nilai `sistem` dan tabel `source_cutover` ditunda (P-D). Kolom `sumber` tetap dibuat.
7. **BR-14 vs impor ulang:** definisikan: impor menimpa nilai Draft (tercatat audit, sumber menjadi `impor`), ditolak pada periode Terkunci. Satu baris per (tahun, bulan, pos) menjamin satu sumber.
8. **Siapa mengubah status Disahkan?** Matriks hak akses hanya memberi Perencanaan "K". Keputusan: Perencanaan mencatat Diajukan dan Disahkan (pengesahan terjadi di luar sistem, di BAZNAS Provinsi); Admin dapat membatalkan lewat versi Perubahan, bukan edit.
9. **Kunci per (tahun, bulan, kelompok)**, sedangkan saldo akhir (Keuangan) bergantung pada penerimaan dan penyaluran (Pelaporan). Saldo akhir dihitung hidup dan diberi tanda "belum final" bila kelompok sumbernya belum terkunci.
10. **VAL-6 untuk DSKL batas 0%:** rasio memerlukan pembagi nol. Aturan: batas 0 berarti setiap nilai amil > 0 = melewati batas; tampilkan n/a untuk rasio.
11. **Rollup menyertakan nilai Draft atau hanya Terkunci?** PRD bilang diperbarui saat simpan dan kunci. Rekomendasi: rollup memuat semua nilai tersimpan plus penanda `terkunci` per periode; Capaian dan Dashboard memakai rollup yang sama (uji konsistensi PK5).
12. **Dimensi "via UPZ" (Kinerja 7.1, Dashboard) bertentangan dengan UPZ di luar cakupan.** Hanya bisa dipenuhi bila RKAT 2026 L2 memang memisahkan langsung/UPZ. Verifikasi pada dokumen sumber; bila tidak ada, hapus dimensi itu (P-D).
13. **Kolom Dashboard per bidang untuk Wakil Ketua (Q8)** belum terdefinisi: v1 tampilkan dashboard yang sama untuk semua Pimpinan; filter bidang jadi P-D.
14. **Ekspor PDF "mengikuti tata letak Lampiran asli":** perlu berkas Lampiran asli. Pilih dompdf (tanpa dependensi Chromium) kecuali tata letak mustahil; spike kecil di P1.
15. **PK4 sepenuhnya bergantung pada Q1.** Mulai dengan jalur "Dokumen laporan" (unggah + metadata + status + kunci); mesin template baru setelah format diterima.
16. Jadwal PRD (40 hari, ±15 Feb 2027) tidak konsisten dengan asumsi 4 hari/minggu pada P1 dan P4 (kalender memberi lebih banyak hari dari kolom "hari dev"). Tidak dipakai di rencana ini; kalibrasi ulang setelah P0 sesuai PRD.

## 2. Keputusan yang harus dikunci dahulu (berurutan)

| # | Keputusan | Default yang direkomendasikan | Blokir |
|---|---|---|---|
| D1 | Dokumen RKAT 2026 (Excel/PDF) tersedia untuk seed | **Wajib dari pengguna** | PK0 seed, PK1 |
| D2 | Model ukuran ganda = satu pos per ukuran (temuan 2) | Setuju | Skema `pos_anggaran` |
| D3 | `sifat_waktu` aliran/stok (temuan 3), orang per bulan | Aliran per bulan | PK2 |
| D4 | Siapa Disahkan (temuan 8) | Staf Perencanaan | PK1 |
| D5 | Login email atau username; daftar pengguna awal (Q8, Q9) | Email | PK0 |
| D6 | RKAT 2021-2025 dikirim (Q2) | Minta sekarang | PK1 impor lama |
| D7 | Library: spatie/laravel-permission, Laravel Excel, dompdf | Setuju | PK0 |

Hal [TBC] lain (Q3-Q7 PRD) dicatat di `docs/open-questions.md`; pakai asumsi PRD yang konservatif, jangan ditebak.

## 3. Rencana kerja berurutan

### Tahap 0: Persiapan repo (P-A)
1. Inisialisasi git dan proyek Laravel + Inertia + React + PostgreSQL; verifikasi PHP, ekstensi `pdo_pgsql`, `gd`, `zip`, dan Postgres lokal.
2. Buat `AGENTS.md` (stack, konvensi, perintah uji/lint, aturan wajib PRD 15), `docs/prd-pkp.md`, `docs/rules.md` (FR/VAL/BR), `docs/modules/PK0..PK5.md`, `docs/open-questions.md` (isi Q1-Q9 + temuan 1-16).
3. Setup CI lokal/skrip: test (Pest/PHPUnit), lint (Pint, ESLint, tsc), migrasi rollback.
4. Konvensi uji: tiap VAL/BR minimal satu uji yang namanya memuat ID.

### P0 Fondasi, modul PK0
Prioritas A:
- Auth (login, pembatasan laju, sesi kedaluwarsa), tabel `users`/`roles`, RBAC 5 peran sesuai matriks bagian 2.
- `audit_logs` append-only + pola **Action** (semua perubahan lewat Action yang menulis audit).
- `pos_anggaran` (induk, path, tipe_nilai, agregasi, sifat_waktu, masa berlaku) dan seed dari seluruh kode L2-L7 RKAT 2026; `pos_alias`.
- Mesin impor Excel generik: `import_jobs`/`import_rows`, pratinjau, galat per baris, **idempoten** (upsert pada kunci alami), antrean driver database.
- Layout: sidebar (Dashboard, PKP>4 submenu, Pengaturan hanya Admin), header pemilih tahun, format angka id-ID, zona WIB.

Prioritas B: Pengaturan (CRUD pengguna, Pos Anggaran, layar Impor dan Audit), Design System v1 (token warna hijau BAZNAS, tabel pohon, badge status, wizard impor 3 langkah).
Prioritas C: 2FA opsional Keuangan/Admin, sidebar responsif lanjutan.
**Selesai bila:** kriteria PK0 PRD 14.2 terpenuhi; uji RBAC per peran; impor ulang tidak menggandakan data.

### P1 Rencana, modul PK1
Prioritas A (jalur kritis):
- `rkat_versions`, `rkat_values`, `rkat_notes`, `rkat_documents`; BR-01/02/03/11 (satu Disahkan aktif, terkunci, hitung server, tanpa hard delete).
- Grid input pohon (hanya daun input; subtotal dan persen dihitung server).
- Enam validasi VAL-1..VAL-6 sebagai service murni + daftar periksa UI; BR-04 blokir pengajuan. Uji dengan `fixtures/rkat-2026.json` pada angka kontrol (termasuk 137.959.000.000, saldo 1.500.000.000, 1.835.000.000, badan 29).
- Status Draft→Diajukan→Disahkan→Digantikan; metadata pengesahan; unggah PDF.
- Impor Excel RKAT 2026 dan pemetaan alias; impor RKAT 2021-2025 dengan penandaan pos tak terpetakan (BR-10).
Prioritas B: Catatan pengesahan terstruktur (batas hak amil, target orang, pengentasan), salin dari versi/tahun sebelumnya, bandingkan versi/tahun, daftar RKAT, ekspor Excel.
Prioritas C: Ekspor PDF tata letak Lampiran (spike dompdf), cetak L2-L7.
**Gerbang P1:** RKAT 2026 lulus 6 validasi; satu RKAT tahun lain terimpor tanpa galat tak terjelaskan.

### P2 Realisasi, modul PK2
Prioritas A:
- `realisasi_values` (unik tahun, bulan, pos; sumber), `realisasi_periods` (kunci per tahun/bulan/kelompok), `saldo_awal`, `rollup_realisasi` (diperbarui saat simpan dan kunci, diuji vs penjumlahan sumber).
- Input Bulanan (FR-L-01/02/05): grid, Draft, Kunci, buka kunci Admin dengan alasan; **penguncian ditegakkan di server**. Hak input per kelompok sesuai matriks.
- Capaian vs Serapan, BR-07 (rencana nol = n/a), BR-05 (negatif butuh catatan), BR-09 (di luar rencana), BR-12 (BIGINT, persen dari nilai asli).
Prioritas B: impor Excel per kelompok dengan template unduhan (FR-L-03), hak amil per jenis dana + peringatan 90% (FR-L-07, BR-08, temuan 10), saldo akhir terhitung (FR-L-08), riwayat per sel (FR-L-11), halaman Capaian + ekspor, indikator kelengkapan (FR-L-12).
Prioritas C: salin bulan lalu (FR-L-04), ambang warna terkonfigurasi (FR-L-06, default FR-L-10 + tampilkan proporsi waktu).
**Gerbang P2:** satu bulan realisasi nyata dibandingkan dengan hitung manual Pelaporan; selisih nol/terjelaskan.

### P3 Kinerja dan Dashboard, modul PK3 + PK5
Prioritas A: Antar Tahun (mode periode sama dan tahun penuh, YoY, komposisi) dari pos baku, memakai rollup; Dashboard (kartu, grafik kumulatif + tahun lalu, peringatan, label "diperbarui pada") dengan uji konsistensi angka dibanding Capaian.
Prioritas B: Capaian vs RKAT (tren), ekspor Excel/PDF Kinerja, performa dashboard < 2 detik dengan data 6 tahun (seed uji).
Prioritas C: tabel capaian per jenis dana; pintasan.
Prioritas D: dimensi "via UPZ" (temuan 12), filter bidang Wakil Ketua (temuan 13).
**Gerbang P3:** dua pimpinan memakai dashboard; angka antar tahun cocok dengan rekap manual.

### P4 Keuangan, modul PK4
Prioritas A (bisa mulai tanpa format): `fin_reports` + `fin_documents` (unggah PDF/Excel, metadata jenis/periode, status Draft/Final/Diaudit, kunci Final, buka kunci Admin, riwayat versi).
Prioritas B (setelah Q1 terjawab): `fin_templates`/`fin_template_rows`/`fin_values`, input/impor, subtotal/rumus (gunakan ulang mesin tabel pohon), bandingkan periode, ekspor.
Prioritas D: FR-K-07 konsistensi laporan vs realisasi.
**Gerbang P4:** satu laporan keuangan nyata tampil sesuai format dan disetujui Keuangan.

## 4. Lintas modul (selalu aktif)
- Tiap modul = satu cabang + PR kecil; urutan kerja per modul: spesifikasi → rencana implementasi (berhenti untuk review) → migrasi+model → Action+uji aturan → halaman → seed → uji+lint → demo.
- Migrasi harus dapat di-rollback; tanpa hard delete (kolom `arsip`/status).
- Backup harian DB dan berkas + uji pemulihan; HTTPS; hosting/domain (Q9) diputuskan sebelum go-live P1.
- Risiko utama dan mitigasi: lihat PRD 14.4; tambahan: dokumen RKAT 2026 belum ada (D1), PK4 menunggu Q1.

## 5. Validasi
- Unit: VAL-1..6 dan BR-xx dengan angka kontrol 2026; rollup vs penjumlahan; kumulatif/capaian data sintetis (termasuk rencana nol, nilai negatif, DSKL 0%).
- Fitur: RBAC per peran dan per kelompok, penguncian server-side (coba ubah via request langsung), impor idempoten, audit tercatat.
- Konsistensi: Dashboard = Capaian = Kinerja untuk periode sama.
- Performa: dashboard < 2 s, impor RKAT < 30 s, ekspor < 10 s (data uji 6 tahun).

## 6. Pertanyaan terbuka yang tersisa
Lihat D1-D7 (blocker) dan Q1-Q9 PRD. Pertanyaan paling kritis saat ini: **D1, kirim dokumen RKAT 2026 (dan 2021-2025 bila ada)**.
