# Rencana Implementasi PK1 (Rencana / RKAT)

Status: **diimplementasikan 9 November 2026**, menunggu review. Sumber: `docs/modules/PK1.md`, PRD 5, 10, 14.2, `docs/rules.md`, `docs/open-questions.md`, kode PK0.

## Hasil implementasi (9 November 2026)

- Migrasi `rkat_versions` (indeks unik parsial satu Disahkan per tahun), `rkat_values`, `rkat_notes`, `rkat_documents`; rollback bersih. Model menolak hard delete (BR-11).
- Service: `HitungRkat`, `ValidasiRkat`, `ParserRkatExcel`, `PemetaanPos`, `BandingkanRkat`, `BarisLampiran`.
- Action (`app/Actions/Rkat/`): `BuatVersiRkat`, `SimpanNilaiRkat`, `SimpanCatatanRkat`, `AjukanRkat`, `KembalikanRkat`, `SahkanRkat`, `ArsipkanRkat`, `UnggahDokumenRkat`, `PratinjauImporRkat`, `TerapkanImporRkat`, `PetakanAliasRkat`; semuanya menegakkan peran (perencanaan, admin) dan status, serta menulis audit.
- Halaman `resources/js/pages/pkp/rencana/`: `index`, `show` (grid pohon L2-L7, VAL, status, catatan, dokumen, riwayat), `impor`, `impor-pratinjau`, `bandingkan`. Ekspor Excel (satu sheet per lampiran) dan PDF (dompdf) per versi.
- `RkatSeeder`: RKAT 2026 Awal berstatus Draft dengan batas hak amil PRD (tidak disahkan; nomor surat tidak diketahui).
- Perbaikan PK0 yang ditemukan: seed pos salah induk `L5/1.2` (open-questions 25). Uji PK0 disesuaikan (akar 28 menjadi 27).
- Uji: `tests/Feature/Pk1/` (VAL-1..6, BR-01..04, BR-10..12, impor 2022-2026 dari berkas sumber, HTTP, bandingkan, ekspor, seed).
- Keterbatasan: tata letak PDF belum dibandingkan secara visual dengan berkas sumber; tabel panjang dapat pecah halaman. Flow impor/alias belum diuji di browser.

## 1. Temuan yang mengubah rencana

1. **Berkas sumber memuat 2022-2026 dalam satu template** (kolom `Tahun 2022`..`Tahun 2031`, kode `No` sama dengan baku). RKAT 2022-2025 bisa diimpor sekarang dengan kode identik; alias hanya diuji dengan berkas sintetis. 2021 tidak ada (D6 sisa).
2. **Hasil hitung VAL pada leaf berkas sumber** (probe sekali pakai, sudah dihapus):

| Tahun | VAL-1 (L2−L3) | VAL-2 (L4 − (L2−amil)) | VAL-3 | VAL-4 (L6 − amil − hibah) |
|---|---|---|---|---|
| 2022 | −2.410.000.001 gagal | +1.560.000.001 gagal | 0 | 0 |
| 2023 | −1.627.745.640 gagal | +677.745.640 gagal | 0 | +950.000.000 gagal (Infak Operasional `L6/1.5`) |
| 2024 | −1.000.000.000 gagal | +1.962.875.000 gagal | 0 | 0 |
| 2025 | 0 | 0 | 0 | 0 |
| 2026 | 0 | 0 | 0 | 0 |

   Akibat BR-04: 2022-2024 tidak dapat diajukan lewat jalur biasa. Angka sumber sudah diverifikasi pengguna, jadi ini bukan galat data. Keputusan pengguna (O-1): impor historis boleh diajukan dan disahkan, VAL tampil sebagai peringatan.
3. **Kode baku tidak sama dengan PRD 5.2.** Di baku: L6 = penerimaan dana amil termasuk hibah (`L6/1.6-1.8`), L7 = penggunaan dana operasional. PRD VAL-4 menyebut hibah "(L7)". VAL-4 memakai kode baku.
4. **VAL-5 secara struktur tautologi** bila induk dihitung dari daun (BR-03). Tetap diimplementasi (hitung ulang terpisah dari daun, orang saja; badan terpisah, temuan 21). Galat nyata hanya muncul bila impor membawa subtotal yang berbeda; itu dilaporkan sebagai peringatan per baris.
5. **Program Prioritas (1.835.000.000) dan saldo awal dana (1.500.000.000) tidak ada di pos baku** (temuan 18-19). Uji kontrol hanya menegaskan nilai yang ada; dua angka itu tidak diuji sampai D1 dilengkapi.
6. `MulaiImpor` (PK0) tidak menerapkan data ke tabel bisnis, hanya mengisi `import_rows` (kunci unik `(jenis, kunci)` global). PK1 memakainya untuk pratinjau dan galat per baris dengan kunci `{tahun}/{versi_id}/{kode}/{tipe}` agar tidak bentrok antar tahun, lalu Action baru menerapkan ke `rkat_values`. `MulaiImpor` tidak diubah.

## 2. Keputusan desain

### Skema (migrasi rollback-able, tanpa hard delete)

| Tabel | Kolom kunci |
|---|---|
| `rkat_versions` | `tahun`, `jenis` (awal/perubahan), `nomor_perubahan` (0 untuk awal), `status` (draft/diajukan/disahkan/digantikan), `nomor_surat`, `tanggal_surat`, `disahkan_pada`, `disahkan_oleh`, `dibuat_oleh`, `disalin_dari_id`, `sumber` (manual/impor; `impor` melewati BR-04, lihat bagian status), `arsip` bool. Unik `(tahun, jenis, nomor_perubahan)`. **Indeks unik parsial `(tahun) WHERE status='disahkan'`** menegakkan BR-01 di DB. |
| `rkat_values` | `rkat_version_id`, `pos_anggaran_id` **nullable** (null = tak terpetakan, BR-10), `kode_asal`, `nama_asal`, `tipe_nilai`, `nilai` BIGINT. Unik `(rkat_version_id, kode_asal, tipe_nilai)` = kunci upsert impor. Hanya daun (`input_langsung`) disimpan; subtotal dan persen dihitung (BR-03). |
| `rkat_notes` | `rkat_version_id` unik; `batas_amil_zakat_bp`, `_infak_bp`, `_csr_bp`, `_dskl_bp` (basis poin, 1250 = 12,5%, bilangan bulat agar tanpa float); `target_orang`; `target_pengentasan_ekstrem`, `target_pengentasan_garis` (Q4 terbuka); `catatan`. |
| `rkat_documents` | `rkat_version_id`, `jenis` (surat_pengesahan/lembar_penetapan), `nama_berkas`, `path` (disk lokal privat), `mime`, `ukuran`, `diunggah_oleh`, `diarsipkan_pada`. Ganti berkas = arsipkan lama (BR-11). PDF saja, maks 10 MB. |

### Service murni (`app/Services/Rkat/`)

- `HitungRkat`: pohon pos dimuat sekali (tanpa N+1; `PohonPos::nilaiAgregat` lama tidak dipakai), agregasi bawah-ke-atas, persen dari nilai asli, dibulatkan hanya saat tampil (BR-12). Persen = nilai pos / total puncak lampiran (asumsi, O-4).
- `ValidasiRkat`: masukan = nilai agregat per kode + batas; keluaran enam hasil `{id, lulus, kiri, kanan, selisih, rincian}`.

| VAL | Definisi atas kode baku |
|---|---|
| 1 | jumlah `L2/1..5` (tanpa `L2/LN`) = jumlah `L3/1..9` |
| 2 | `L4/1 + L4/2` = total L2 − hak amil |
| 3 | `L6/1.1..1.4` = hak amil L3 |
| 4 | `L6/1` = `L6/1.1..1.4` + `L6/1.6..1.8` (Infak Operasional `L6/1.5` tidak termasuk, harfiah PRD) |
| 5 | `L5/2` = jumlah `L5/2.1..2.5`; orang `L5/1` (tanpa badan) = `1.1+1.3+1.4+1.5` |
| 6 | amil per jenis dana ÷ penerimaan jenis dana ≤ batas: zakat `L3/1.3 ÷ L2/1`; infak `(L3/2.2+L3/3.2) ÷ L2/2`; CSR `L3/4.2 ÷ L2/3`; DSKL `L3/8.2 ÷ L2/5`. Perbandingan aritmetika bilangan bulat. Batas 0: nilai amil > 0 gagal, rasio n/a (temuan 10). Batas belum diisi = tidak dapat dievaluasi = gagal. |

Hak amil = `L3/1.3 + 2.2 + 3.2 + 4.2 + 8.2` (2026 = 3.525.236.000). Kurban `L3/6.2` dan Fidyah `L3/7.2` dikecualikan (tidak ada baris L6 dan batas); 0 di 2022-2026 (O-3).

### Status dan hak

- Alur: `draft → diajukan → disahkan → digantikan`. `diajukan → draft` (kembalikan) boleh. `digantikan` hanya otomatis saat Perubahan disahkan, dalam satu transaksi (BR-01).
- Ajukan: jalankan VAL-1..6. Versi `sumber='impor'`: VAL gagal hanya peringatan, pengajuan lolos (O-1). Versi `manual` dan semua versi Perubahan: tolak bila ada gagal (BR-04). `SimpanNilaiRkat` mengubah `sumber` menjadi `manual` bila nilai diedit, agar angka yang tidak lagi sama dengan berkas terverifikasi kembali kena BR-04. Tak terpetakan **tidak** memblokir pengajuan, hanya peringatan.
- Sahkan: wajib `nomor_surat` + `tanggal_surat`; PDF opsional.
- Terkunci (BR-02): nilai, catatan, batas pada status selain draft. Dokumen boleh ditambah setelah Disahkan (lama diarsipkan, tercatat audit).
- Perubahan: hanya bila tahun itu punya versi Disahkan; menyalin nilai dari versi tersebut.
- Tulis: peran `perencanaan` dan `admin`; lihat: semua peran (matriks PRD 2). Penegakan **di Action** (status + peran), middleware hanya lapis pertama (aturan 5).
- Setiap Action menulis `audit_logs`; simpan grid = satu audit berisi diff sel yang berubah.

### Action (`app/Actions/Rkat/`)

`BuatVersiRkat` (opsi salin versi/tahun), `SimpanNilaiRkat`, `SimpanCatatanRkat`, `AjukanRkat`, `KembalikanRkat`, `SahkanRkat`, `UnggahDokumenRkat`, `ArsipkanRkat`, `TerapkanImporRkat`, `PetakanAliasRkat` (buat `pos_alias` dan petakan ulang nilai tak terpetakan).

### Impor

- `ParserRkatExcel`: template "Rencana 2022-2031"; sheet `A1..A7` ke `L2`, `L2/LN`, `L3..L7` (sama dengan fixture); kolom dicari dari header `Tahun YYYY`, bukan posisi. Sel rumus = subtotal (dilewati, hanya dibanding sebagai peringatan). Nilai pada pos bukan daun ditolak (BR-03).
- Pemetaan: kode+tipe langsung → `pos_alias(tahun)` → tak terpetakan (BR-10).
- Wizard 3 langkah: unggah (pilih tahun) → pratinjau (valid, galat, tak terpetakan) → terapkan. Hasil: versi Draft sumber `impor`. Impor ulang ke versi Draft yang sama menimpa lewat upsert `(versi, kode_asal, tipe)`, ditolak bila bukan Draft. Idempoten.
- Template selain "Rencana 2022-2031" (mis. 2021) menunggu D6; tidak ditebak.

### Halaman (`resources/js/pages/pkp/rencana/`)

`index` (daftar 5.1), `show` (tab L2-L7 grid pohon, hanya daun bisa diedit, daftar periksa VAL, panel status, catatan, dokumen, riwayat versi), `impor`, `bandingkan`. Route `pkp.rencana` menggantikan placeholder di `routes/web.php`; rute baru di `routes/rencana.php`.

### Bandingkan

`BandingkanRkat`: versi vs versi atau tahun (versi Disahkan, jika belum ada: draft terbaru ditandai) vs tahun, per pos baku: nilai A, nilai B, selisih, persen `(B−A)/A` satu desimal, n/a bila A = 0. Tak terpetakan keluar dari total dan dihitung sebagai peringatan (BR-10).

### Ekspor

- Excel (Laravel Excel): satu sheet per lampiran, kolom `No | Uraian | Persen | Jumlah`, indentasi pohon, mengikuti tata letak berkas sumber.
- PDF (dompdf): spike dulu pada L2 dan L7; bila tata letak tidak layak, laporkan batasnya (temuan 14). Berkas Lampiran asli tidak tersedia, jadi acuan = berkas sumber.

## 3. Tahapan kerja (berhenti per tahap bila ada galat)

| # | Tahap | Keluaran | Verifikasi |
|---|---|---|---|
| 1 | Migrasi + model | 4 tabel, model, relasi | `migrate:fresh` lalu `migrate:rollback` bersih |
| 2 | Service `HitungRkat`, `ValidasiRkat` | enam VAL dari fixture | uji VAL-1..6 (angka kontrol 2026 + kasus gagal) |
| 3 | Action + hak + audit | status, kunci, Perubahan, dokumen | uji BR-01..04, BR-11, RBAC, ubah via request langsung |
| 4 | Parser + impor + alias | pratinjau, terapkan, petakan | uji BR-10, idempoten, impor 2022-2026 dari berkas sumber |
| 5 | Halaman daftar, detail, grid, impor | UI | `tsc`, ESLint, build; cek manual di browser |
| 6 | Bandingkan | halaman + service | uji selisih, n/a, tak terpetakan keluar |
| 7 | Ekspor Excel + spike PDF | berkas unduhan | uji isi sheet; unduh PDF dibuka |
| 8 | Seed, dokumen, penutup | `RkatSeeder` 2026 (Draft, batas 12,5/20/10/0 dari PRD, bukan Disahkan karena nomor surat tak diketahui); update `open-questions.md` dan status `PK1.md` | `pest`, `pint --test`, `npm run lint`, `npm run types`, `npm run build` |

## 4. Peta uji (nama memuat ID, `tests/Feature/Pk1/`)

- `VAL-1`..`VAL-6`: masing-masing lulus pada angka kontrol 2026 dan gagal pada nilai diubah (VAL-6: DSKL batas 0 dengan amil > 0; batas kosong).
- `BR-01` satu Disahkan per tahun (juga lewat insert langsung, indeks parsial). `BR-02` nilai versi Disahkan ditolak diubah, Perubahan menyalin. `BR-03` induk tidak bisa diinput, subtotal dihitung server. `BR-04` versi manual dan Perubahan ditolak diajukan bila satu VAL gagal (setiap VAL); versi impor lolos dengan peringatan; edit versi impor mengembalikan ke manual. `BR-10` tak terpetakan keluar dari perbandingan, alias memetakan. `BR-11` tidak ada hard delete, arsip. `BR-12` persen dari nilai asli.
- Impor: idempoten; 2025 lulus enam VAL; 2022-2024 terimpor tanpa galat, VAL gagal sesuai tabel temuan 2 tetapi dapat diajukan dan disahkan; berkas sintetis dengan kode alias.
- Hak: pelaporan/keuangan/pimpinan tidak bisa menulis; semua peran bisa melihat.

## 5. Keputusan default (koreksi bila tidak setuju)

| # | Hal | Default |
|---|---|---|
| **O-1** | RKAT historis 2022-2024 gagal VAL-1/2 (2023 juga VAL-4) | **Diputuskan pengguna**: versi hasil impor boleh diajukan dan disahkan, VAL jadi peringatan. Input manual dan Perubahan tetap kena BR-04. Menyimpang dari BR-04 untuk jalur impor; catat di `open-questions.md` |
| O-2 | VAL-4 dan Infak Operasional (`L6/1.5`, 950 jt di 2023) | Harfiah PRD: tidak ikut rumus, jadi gagal |
| O-3 | Himpunan hak amil dan Kurban/Fidyah | Lihat bagian 2 |
| O-4 | Persen terhadap apa (kolom persen tidak ada di sumber, temuan 20) | Total puncak lampiran; L5 tanpa persen |
| O-5 | PRD 5.2 vs baku (L6/L7) | Ikuti kode baku |
| O-6 | Dokumen setelah Disahkan | Boleh ditambah, lama diarsipkan |
| O-7 | UI alias | Hanya di langkah pratinjau impor (petakan kode tak terpetakan); tanpa halaman alias terpisah |
| O-8 | Target pengentasan (Q4) | Dua kolom nullable (ekstrem, garis kemiskinan); arti 660 tetap terbuka |

Ditambahkan ke `docs/open-questions.md` saat tahap 8 sebagai temuan 25-30.

## 6. Di luar cakupan PK1

Realisasi, rollup, capaian (PK2); Kinerja (PK3); dashboard (PK5); halaman Pos Anggaran (PK0, tidak diubah). Template RKAT 2021 dan versi Perubahan nyata menunggu D6/Q2.

## 7. Format belum dikirim atau belum jelas

Angka sumber dianggap terverifikasi pengguna; aplikasi hanya menyediakan field input. Yang menunggu pengguna:

| # | Hal | Default sementara |
|---|---|---|
| F1 | RKAT 2021 tidak ada (D6) | Dilewati |
| F2 | Kolom 2022-2025 di berkas "Rencana 2022-2031": disahkan atau rencana? Ada versi Perubahan? (Q2) | Impor sebagai versi Awal; status mengikuti O-1 |
| F3 | Lampiran 2-7 asli untuk tata letak ekspor | Tata letak berkas sumber |
| F4 | Persen Lampiran 2, 3, 6, 7: terhadap apa | Total puncak lampiran |
| F5 | Contoh catatan pengesahan; batas hak amil 2022-2025; arti target orang | Hanya 2026 (PRD); tahun lain kosong |
| F6 | Target pengentasan: 660 atau 3.962/5.659 (Q4) | Dua field nullable |
| F7 | Program Prioritas (10 program, 1,835 M), jiwa/unit/desa L4, saldo awal dana 1,5 M | Tidak dibuat sampai daftar dikirim |
| F8 | Format nomor surat pengesahan dan lembar penetapan | Teks bebas |
| F9 | Hibah APBN/APBD: PRD di L7, berkas sumber di L6 | Ikut berkas sumber |
