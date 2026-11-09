# PRD v0.1 — Aplikasi PKP BAZNAS Kabupaten Cianjur

> Draft untuk review · 5 November 2026
> Aplikasi PKP (Perencanaan, Keuangan, Pelaporan) dikembangkan lebih dulu dan berdiri sendiri; PRD sistem ZIS lengkap ditahan sementara.
> **[TBC]** = menunggu jawaban atau file dari BAZNAS Cianjur · **[ASUMSI]** = default yang boleh dikoreksi

**Petunjuk membaca (untuk AI coding agent):** modul diberi kode PK0-PK5 (bagian 14). Kebutuhan fungsional memakai ID `FR-R-xx` (Rencana), `FR-L-xx` (Realisasi), dan `FR-K-xx` (Keuangan). Validasi RKAT memakai `VAL-x` dan aturan bisnis umum `BR-xx` (bagian 10). Hal bertanda [TBC] belum diputuskan: jangan ditebak, catat di `docs/open-questions.md`.

## 1. Ringkasan

Aplikasi web untuk Bidang PKP BAZNAS Kabupaten Cianjur. Fungsinya: menyimpan RKAT 2021 sampai sekarang, mencatat realisasi penerimaan, penyaluran, dan operasional, menghitung capaian terhadap RKAT, merekap kinerja antar tahun, dan menampilkan laporan keuangan. Tampilan utamanya adalah **dashboard** dengan menu **PKP** di sidebar yang memiliki empat submenu: **Rencana, Realisasi, Kinerja, Keuangan**.

### 1.1 Masalah yang diselesaikan

- RKAT 2021-2026 dan realisasinya tersimpan di berkas terpisah (Excel dan PDF); perbandingan rencana dengan realisasi disusun manual.
- Struktur pos tiap tahun dapat berbeda, sehingga perbandingan antar tahun sulit dan rawan salah [TBC: template 2021-2025].
- Pimpinan belum punya satu tampilan capaian anggaran.
- Laporan keuangan tersebar sebagai berkas dan tidak mudah dibandingkan antar periode.

### 1.2 Tujuan dan ukuran keberhasilan

| Tujuan | Ukuran keberhasilan [ASUMSI] |
|---|---|
| RKAT 2021-sekarang di satu tempat | Semua tahun tersedia dengan struktur pos baku sehingga dapat dibandingkan |
| Capaian tanpa kompilasi manual | Capaian terhadap RKAT per jenis dana, asnaf, bidang, dan belanja operasional tampil otomatis setelah realisasi bulanan diinput |
| Kinerja antar tahun | Perbandingan 2021-sekarang tersedia dalam satu halaman dan dapat diekspor |
| Laporan keuangan terakses | Laporan tiap periode dapat dibuka dalam format yang berlaku |
| Dashboard pimpinan | Terbuka di bawah 2 detik dan menampilkan data terbaru yang sudah diinput |

### 1.3 Di luar cakupan (v1)

- Transaksi muzakki/munfiq, data mustahik, UPZ, BSZ, kertas hijau, dan integrasi Midtrans atau Kantor Digital (ada di PRD induk).
- Pembukuan: jurnal, buku besar, dan penyusunan otomatis laporan keuangan dari transaksi [TBC: tergantung format laporan yang dikirim].
- Indikator Kinerja Kunci (IKK) dan Lampiran 1 RKAT dikeluarkan dari aplikasi ini.
- Approval berjenjang, notifikasi, dan aplikasi mobile native.

### 1.4 Hubungan dengan PRD induk

Aplikasi ini mencakup modul L1, L2, L4, L5, dan L6 PRD induk (RKAT, realisasi, dashboard, laporan, rekap) dan memakai kode pos, aturan, serta tabel yang sama. Saat modul Pengumpulan dan Pendistribusian dibangun nanti, keduanya dapat menyuplai realisasi otomatis tanpa merombak data: setiap nilai realisasi menyimpan **sumber** (manual, impor, sistem), dan tiap sumber memiliki **tanggal peralihan**.

## 2. Pengguna dan Hak Akses

| Peran | Tugas utama |
|---|---|
| Staf Perencanaan | Mengelola RKAT: input, impor, versi, pengajuan, catatan pengesahan |
| Staf Pelaporan | Input atau impor realisasi penerimaan dan penyaluran, jumlah orang; mengunci periode; ekspor laporan |
| Keuangan | Input realisasi operasional dan saldo awal; mengelola laporan keuangan |
| Pimpinan (Ketua, Wakil Ketua) | Melihat dashboard dan seluruh halaman PKP (hanya baca); ekspor |
| Admin | Pengguna, pos anggaran, konfigurasi, audit log, buka kunci periode |

K = kelola (buat/ubah), L = lihat, V = kunci atau memutuskan, – = tidak ada akses.

| Objek | Perencanaan | Pelaporan | Keuangan | Pimpinan | Admin |
|---|---|---|---|---|---|
| RKAT (Rencana) | K | L | L | L | K |
| Realisasi penerimaan, penyaluran, jumlah orang | L | K, V | L | L | K |
| Realisasi operasional dan saldo | L | L | K, V | L | K |
| Buka kunci periode | – | – | – | – | V |
| Kinerja | L | L | L | L | L |
| Laporan keuangan | L | L | K, V | L | K |
| Dashboard | L | L | L | L | L |
| Pengguna, pos anggaran, audit | – | – | – | – | K |

**Prinsip akses:** berbasis peran; semua perubahan data tercatat di audit log (siapa, kapan, nilai lama dan baru); tidak ada hard delete pada RKAT dan realisasi; sesi berakhir otomatis; 2FA opsional untuk Keuangan dan Admin.

## 3. Navigasi dan Halaman

**Sidebar:** Dashboard · PKP (Rencana, Realisasi, Kinerja, Keuangan) · Pengaturan (khusus Admin: Pengguna, Pos Anggaran, Impor dan Audit).
**Header:** pemilih tahun aktif (default tahun berjalan) dan menu pengguna.

| Menu | Halaman | Isi singkat |
|---|---|---|
| Dashboard | Dashboard PKP | Kartu ringkas, grafik kumulatif, peringatan (bagian 9) |
| PKP > Rencana | Daftar RKAT | Tabel tahun 2021-sekarang beserta versi aktif dan status |
| PKP > Rencana | Detail RKAT | Tab Lampiran 2-7, ringkasan validasi, riwayat versi |
| PKP > Rencana | Impor dan Bandingkan | Impor Excel; selisih antar versi atau antar tahun |
| PKP > Realisasi | Input Bulanan | Grid pohon pos: rencana tahunan, realisasi bulan, kumulatif, capaian |
| PKP > Realisasi | Capaian | Realisasi terhadap plotting anggaran per tahun, per kelompok |
| PKP > Realisasi | Impor | Impor Excel realisasi per kelompok |
| PKP > Kinerja | Antar Tahun | Perbandingan realisasi penerimaan, penyaluran, operasional per tahun |
| PKP > Kinerja | Capaian vs RKAT | Tren capaian terhadap RKAT tiap tahun |
| PKP > Keuangan | Laporan Keuangan | Laporan per jenis dan periode sesuai format [TBC] |
| Pengaturan | Pengguna, Pos Anggaran, Impor dan Audit | Administrasi sistem |

## 4. Konsep Data Kunci

- **Pos anggaran baku:** satu daftar pos dengan kode berbentuk `Lampiran/kode` (misalnya `L2/1.3`), nama, induk, tipe nilai (rupiah, orang, badan, jiwa, unit, persen), dan perilaku agregasi (jumlah anak, input langsung, atau hitung). Daftar yang sama dipakai semua tahun agar dapat dibandingkan. Kode dari RKAT tahun lain dipetakan ke kode baku lewat tabel alias saat impor; pos yang belum terpetakan ditandai dan tidak ikut perbandingan sampai dipetakan.
- **RKAT:** per tahun dan **versi** (Awal, Perubahan ke-n). Satu versi berstatus Disahkan menjadi acuan realisasi tahun itu. Metadata pengesahan: nomor dan tanggal surat BAZNAS Provinsi, lembar penetapan, dan catatan pengesahan.
- **Realisasi:** nilai per tahun, bulan, dan pos baku, dengan sumber nilai dan status periode (Draft atau Terkunci).
- **Capaian:** realisasi kumulatif dibagi rencana pada versi RKAT aktif.
- **Laporan keuangan:** laporan per jenis dan periode berdasarkan template [TBC].

## 5. Rencana (RKAT)

### 5.1 Daftar RKAT

Tabel tahun 2021 sampai sekarang: tahun, versi aktif, status, nomor surat pengesahan, total penerimaan, total penyaluran, total biaya operasional, dan aksi (lihat, impor, buat versi Perubahan).

### 5.2 Detail RKAT: struktur Lampiran

| Lampiran | Isi | Kolom nilai |
|---|---|---|
| 2 | Penerimaan dalam neraca per jenis dana (kode 1-5); pencatatan luar neraca; saldo awal dana | Persen, jumlah |
| 3 | Penyaluran per asnaf dan per jenis dana; saldo akhir dana | Persen, jumlah |
| 4 | Pendistribusian (4 bidang) dan pendayagunaan (3 bidang); 10 Program Prioritas | Jumlah; jiwa, unit/desa |
| 5 | Penggalangan muzakki, munfiq, donatur; mustahik per bidang; pengentasan kemiskinan | Orang, badan |
| 6 | Penerimaan dan penggunaan dana operasional (8 kelompok belanja) | Persen, jumlah |
| 7 | Penerimaan dan penggunaan dana APBN/APBD | Persen, jumlah |

### 5.3 Kebutuhan fungsional

| ID | Kebutuhan |
|---|---|
| FR-R-01 | Membuat RKAT per tahun dan versi, dengan opsi menyalin dari versi atau tahun sebelumnya |
| FR-R-02 | Input nilai pada pos paling bawah; subtotal, total, dan persentase dihitung otomatis |
| FR-R-03 | Impor Excel dengan pemetaan kode ke pos baku, pratinjau, dan laporan galat per baris |
| FR-R-04 | Enam validasi silang (bagian 10) tampil sebagai daftar periksa; versi tidak dapat diajukan bila ada yang gagal |
| FR-R-05 | Status versi: Draft, Diajukan, Disahkan, Digantikan; metadata pengesahan dan unggah PDF surat pengesahan dan lembar penetapan |
| FR-R-06 | Catatan pengesahan tersimpan sebagai data: batas hak amil (zakat, infak, CSR, DSKL), target jumlah orang, target pengentasan kemiskinan |
| FR-R-07 | Membandingkan dua versi atau dua tahun (selisih dan persentase) per pos |
| FR-R-08 | Ekspor Excel dan PDF mengikuti tata letak Lampiran asli |
| FR-R-09 | Versi Disahkan terkunci; perubahan hanya lewat versi Perubahan baru |

## 6. Realisasi

### 6.1 Cakupan

| Kelompok | Pos RKAT | Periode input | Penginput |
|---|---|---|---|
| Penerimaan | L2: jenis dana kode 1-5; luar neraca | Bulanan | Staf Pelaporan |
| Penyaluran | L3: asnaf dan jenis dana; L4: bidang, program, Program Prioritas (jiwa, unit/desa, jumlah) | Bulanan | Staf Pelaporan |
| Muzakki dan mustahik | L5: orang, badan, per bidang, pengentasan kemiskinan | Bulanan, kumulatif tahun berjalan [ASUMSI] | Staf Pelaporan |
| Operasional | L6 dan L7: penerimaan dan penggunaan per kelompok belanja | Bulanan | Keuangan |
| Saldo dana | L2: saldo awal; saldo akhir dihitung | Awal tahunan; akhir bulanan | Keuangan |

Sumber data realisasi sekarang (Excel atau aplikasi lain) dan formatnya [TBC]; aplikasi menyediakan impor Excel agar tidak perlu mengetik ulang.

### 6.2 Kebutuhan fungsional

| ID | Kebutuhan |
|---|---|
| FR-L-01 | Halaman Input Bulanan: pilih tahun, bulan, kelompok; grid pohon pos dengan kolom Rencana tahunan, Realisasi bulan ini (input), Kumulatif s.d. bulan (hitung), Capaian |
| FR-L-02 | Simpan sebagai Draft; Kunci periode per bulan dan kelompok oleh penginput; buka kunci hanya oleh Admin dengan alasan |
| FR-L-03 | Impor Excel per kelompok dari template yang dapat diunduh, dengan pratinjau dan galat per baris |
| FR-L-04 | Menyalin nilai bulan sebelumnya sebagai titik awal (opsional bagi pengguna) |
| FR-L-05 | Capaian = kumulatif dibagi rencana RKAT aktif; label Capaian untuk penerimaan dan Serapan untuk penyaluran dan operasional |
| FR-L-06 | Ambang warna capaian dapat dikonfigurasi (default pada FR-L-10) |
| FR-L-07 | Rasio hak amil per jenis dana (zakat, infak, CSR, DSKL) terhadap batas pada catatan pengesahan; peringatan di 90% batas dan saat melewati batas |
| FR-L-08 | Saldo akhir terhitung per jenis dana (saldo awal + penerimaan − penyaluran), informatif, dibandingkan dengan rencana saldo akhir |
| FR-L-09 | Pos di luar RKAT tetap dapat dicatat dan ditandai di luar rencana |
| FR-L-10 | Default ambang [ASUMSI]: penerimaan hijau bila capaian ≥ 100% dari proporsi waktu, kuning 80-99%, merah di bawah 80%; belanja hijau bila ≤ 100% proporsi waktu, kuning 100-110%, merah di atas 110%. Plotting bulanan atau triwulanan belum ada di v1 sehingga musim Ramadan dapat membuat warna menyesatkan |
| FR-L-11 | Riwayat perubahan per sel (siapa, kapan, nilai lama dan baru) |
| FR-L-12 | Indikator kelengkapan: jumlah pos yang belum terisi dan periode yang belum dikunci |

### 6.3 Halaman Capaian (realisasi terhadap plotting anggaran per tahun)

Tabel per Lampiran dengan kolom: Rencana tahun itu, Realisasi s.d. bulan terpilih, Selisih, Capaian %, dan penanda status. Filter: tahun, bulan akhir, kelompok. Grafik batang rencana lawan realisasi. Ekspor Excel dan PDF.

## 7. Kinerja

### 7.1 Antar Tahun

Pilih rentang tahun (default 2021 sampai tahun berjalan), kelompok (penerimaan, penyaluran, operasional), dan dimensi (total, per jenis dana, per asnaf, per bidang, langsung dibanding via UPZ). Keluaran: tabel dengan satu kolom per tahun, selisih, dan pertumbuhan tahunan (YoY) dalam persen; grafik garis atau batang; komposisi dalam persen. Dua mode: **periode sama** (s.d. bulan terpilih di tiap tahun) dan **tahun penuh**.

### 7.2 Capaian vs RKAT

Tren capaian realisasi terhadap RKAT Disahkan di tiap tahun, per kelompok.

### 7.3 Ekspor

Semua tabel dan grafik halaman Kinerja dapat diekspor ke Excel dan PDF.

## 8. Keuangan

Menu Keuangan menampilkan laporan keuangan per periode (bulanan, triwulanan, semester, atau tahunan) **sesuai format yang akan dikirim [TBC]**. Karena format belum diterima, v1 dirancang fleksibel dengan dua kemampuan:

1. **Laporan terstruktur berbasis template:** baris berkode, kolom periode, subtotal atau rumus, input atau impor Excel, tampilan dan ekspor mengikuti format yang dikirim. Memakai mesin tabel berpohon yang sama dengan RKAT.
2. **Dokumen laporan:** unggah PDF atau Excel resmi per periode, dengan metadata jenis laporan, periode, dan status (Draft, Final, Diaudit).

| ID | Kebutuhan |
|---|---|
| FR-K-01 | Daftar laporan per jenis, tahun, dan periode, dengan status |
| FR-K-02 | Template laporan didefinisikan dari format yang dikirim (baris, kolom, rumus) dan disimpan sebagai data |
| FR-K-03 | Input atau impor Excel nilai laporan per periode; subtotal otomatis |
| FR-K-04 | Status Draft, Final, Diaudit; periode Final terkunci dan hanya dapat dibuka Admin dengan alasan |
| FR-K-05 | Unggah dokumen laporan resmi (PDF, Excel) dan lihat riwayat versi |
| FR-K-06 | Bandingkan periode atau tahun; ekspor Excel dan PDF |
| FR-K-07 | Pemeriksaan konsistensi opsional antara angka laporan dan realisasi (misalnya total penerimaan) setelah format diterima [TBC] |

Batasan v1: tidak ada jurnal atau buku besar. Cakupan akhir (misalnya laporan posisi keuangan, perubahan dana, arus kas, dan catatan atas laporan keuangan) menunggu file format [TBC].

## 9. Dashboard

Halaman pertama setelah login. Filter: tahun dan bulan akhir. Data dibaca dari tabel ringkasan (rollup) yang diperbarui saat realisasi disimpan atau dikunci; label "diperbarui pada …" selalu tampil.

| Komponen | Isi |
|---|---|
| Kartu ringkas | Penerimaan, penyaluran, belanja operasional: realisasi, rencana, capaian; hak amil terhadap batas; jumlah muzakki dan mustahik |
| Grafik kumulatif | Realisasi kumulatif bulanan penerimaan dan penyaluran terhadap target tahunan, dengan garis tahun lalu sebagai pembanding |
| Capaian per jenis dana | Tabel ringkas zakat, infak, CSR, DSKL (langsung dan via UPZ) |
| Peringatan | Hak amil mendekati atau melewati batas; bulan belum diinput atau belum dikunci; RKAT belum lulus validasi; pos di luar rencana |
| Pintasan | Ke Input Bulanan, Capaian, dan Antar Tahun |

Pimpinan melihat dashboard tanpa tombol ubah.

## 10. Aturan Bisnis

### 10.1 Validasi silang RKAT (sekaligus data uji dari RKAT 2026)

| ID | Aturan | Nilai RKAT 2026 |
|---|---|---|
| VAL-1 | Total penerimaan Lampiran 2 sama dengan total penyaluran Lampiran 3 | Rp 22.015.000.000 |
| VAL-2 | Pendistribusian + pendayagunaan Lampiran 4 sama dengan penerimaan dikurangi hak amil | 12.898.442.400 + 5.591.321.600 = 18.489.764.000 |
| VAL-3 | Bagian amil pada penerimaan dana operasional (Lampiran 6) sama dengan jumlah penyaluran untuk amil di Lampiran 3 | 1.325.440.000 + 1.946.580.400 + 253.215.600 = 3.525.236.000 |
| VAL-4 | Dana operasional Lampiran 6 sama dengan hak amil + hibah operasional APBN/APBD (Lampiran 7) | 3.525.236.000 + 250.000.000 = 3.775.236.000 |
| VAL-5 | Total mustahik pada Lampiran 5 sama dengan jumlah per bidang; total muzakki + munfiq + donatur sama dengan jumlah rinciannya | 35.372 orang; 201.430 orang (176.434 + 24.846 + 150) |
| VAL-6 | Bagian amil per jenis dana tidak melebihi batas pada catatan pengesahan | Zakat 12,5%; infak 20%; CSR 10%; DSKL 0% |

Nilai kontrol lain RKAT 2026: luar neraca Rp 137.959.000.000; saldo awal dan saldo akhir dana masing-masing Rp 1.500.000.000; Program Prioritas Rp 1.835.000.000; badan 29.

### 10.2 Aturan umum

| ID | Aturan |
|---|---|
| BR-01 | Hanya satu versi RKAT berstatus Disahkan yang aktif per tahun |
| BR-02 | Versi Disahkan terkunci; perubahan lewat versi Perubahan baru |
| BR-03 | Subtotal, total, dan persentase selalu dihitung sistem, tidak diinput |
| BR-04 | Versi tidak dapat diajukan bila ada validasi VAL-1 sampai VAL-6 yang gagal |
| BR-05 | Realisasi disimpan per tahun, bulan, dan pos baku; nilai negatif hanya untuk koreksi dengan catatan |
| BR-06 | Periode Terkunci tidak dapat diubah; buka kunci hanya oleh Admin dengan alasan dan jejak audit |
| BR-07 | Kumulatif = jumlah realisasi bulan 1 sampai n; capaian = kumulatif dibagi rencana aktif; rencana nol menghasilkan n/a |
| BR-08 | Rasio hak amil per jenis dana dibandingkan batas; peringatan pada 90% batas [ASUMSI] |
| BR-09 | Realisasi pada pos yang tidak ada di RKAT ditandai di luar rencana |
| BR-10 | Perbandingan antar tahun memakai pos baku; pos belum terpetakan tidak ikut total pembanding sampai dipetakan |
| BR-11 | Tidak ada hard delete pada RKAT dan realisasi; data lama diarsipkan |
| BR-12 | Nominal rupiah bulat (BIGINT); persentase satu desimal dihitung dari nilai asli, bukan dari nilai yang sudah dibulatkan |
| BR-13 | Tahun anggaran 1 Januari sampai 31 Desember |
| BR-14 | Nilai realisasi menyimpan sumber (manual, impor, sistem); satu periode pos tidak boleh memakai dua sumber sekaligus |

## 11. Model Data Tingkat Tinggi

| Kelompok | Tabel | Atribut kunci |
|---|---|---|
| Akses | `users`, `roles`, `audit_logs` | peran; nilai sebelum dan sesudah |
| Pos baku | `pos_anggaran`, `pos_alias` | kode (Lampiran/kode), induk, tipe nilai, agregasi; alias per tahun ke kode baku |
| RKAT | `rkat_versions`, `rkat_values`, `rkat_notes`, `rkat_documents` | tahun, versi, status, nomor dan tanggal surat; nilai, jiwa, unit/desa; batas hak amil; berkas |
| Realisasi | `realisasi_values`, `realisasi_periods`, `saldo_awal` | tahun, bulan, pos, nilai, sumber; status periode dan pengunci; saldo awal per jenis dana |
| Keuangan | `fin_templates`, `fin_template_rows`, `fin_reports`, `fin_values`, `fin_documents` | jenis, periode, status; baris berkode; nilai; berkas [TBC] |
| Ringkasan | `rollup_realisasi` | tahun, bulan, pos, nilai bulan, nilai kumulatif |
| Operasional | `import_jobs`, `import_rows` | status; galat per baris |

**Keputusan model:** nominal BIGINT rupiah; pohon pos memakai induk dan path agar agregasi cepat; rollup diperbarui saat simpan dan kunci; pembatasan unik pada (tahun, bulan, pos); tidak ada hard delete.

## 12. Teknologi dan Persyaratan Non-Fungsional

- **Stack:** Laravel + Inertia.js + React, satu repositori dan satu deploy; PostgreSQL; antrean dengan driver database untuk impor (Redis ditambah bila diperlukan); ekspor Excel (Laravel Excel/PhpSpreadsheet) dan PDF (dompdf atau Browsershot, dipilih saat implementasi); penyimpanan berkas lokal atau S3-compatible.
- **Target performa [ASUMSI]:** halaman biasa di bawah 1 detik (p95); dashboard di bawah 2 detik; impor RKAT satu tahun di bawah 30 detik; ekspor di bawah 10 detik; sampai 15 pengguna bersamaan.
- **Keamanan:** RBAC (misalnya spatie/laravel-permission), audit log append-only, kata sandi kuat, HTTPS, pembatasan laju login, backup harian basis data dan berkas dengan uji pemulihan berkala. Data RKAT dan realisasi adalah data organisasi, bukan data pribadi.
- **Kompatibilitas:** Chrome, Edge, dan Firefox terbaru; desktop-first, nyaman di tablet, ponsel hanya untuk melihat. Bahasa Indonesia, format angka id-ID (titik ribuan), zona waktu WIB.
- **Hosting:** satu VPS pada awalnya [TBC: domain dan hosting].

## 13. Arah Tampilan (bahan Design System)

- **Tata letak:** sidebar kiri yang dapat dilipat (Dashboard, PKP dengan empat submenu, Pengaturan), header berisi pemilih tahun, dan area konten dengan breadcrumb.
- **Gaya:** hijau tua dari logo BAZNAS Cianjur sebagai warna utama, netral keabuan sebagai dasar, aksen emas dari lambang secara terbatas; kontras minimal AA; huruf sans-serif lugas dengan angka tabular.
- **Angka:** rata kanan, format Rp dengan titik ribuan; kartu memakai ringkasan (miliar, juta) dengan nilai penuh pada tooltip; satuan selalu terlihat.
- **Komponen kunci:** tabel pohon pos (buka-tutup, header dan kolom lekat), grid input yang mendukung keyboard (Tab, Enter, tempel dari Excel), kartu ringkasan, grafik garis dan batang, lencana status (Draft, Diajukan, Disahkan, Terkunci), wizard impor tiga langkah, tampilan selisih antar versi.
- **Status warna:** hijau sesuai atau di atas target, kuning mendekati batas, merah di bawah target atau melewati batas, abu untuk tidak ada data; makna tidak boleh hanya lewat warna (selalu disertai teks atau ikon).
- **Cetak:** tata letak cetak Lampiran 2-7 dan halaman Capaian.

Design System disusun sebagai dokumen terpisah setelah PRD ini disepakati.

## 14. Rencana Pengerjaan dan Jadwal

### 14.1 Urutan modul dan jadwal usulan

| Rilis | Modul | Hari dev | Pengembangan | Target go-live |
|---|---|---|---|---|
| P0 Fondasi | PK0: login, peran, layout sidebar, pos baku, mesin impor Excel, audit log, Design System v1 | 8 | 9-20 Nov 2026 | Internal |
| P1 Rencana | PK1: RKAT, versi, validasi, impor 2021-2026, bandingkan, ekspor | 9 | 23 Nov-11 Des 2026 | ±15 Des 2026 |
| P2 Realisasi | PK2: input bulanan, impor, kunci periode, capaian, hak amil, saldo | 9 | 14 Des 2026-8 Jan 2027 | ±12 Jan 2027 |
| P3 Kinerja dan Dashboard | PK3: antar tahun, capaian vs RKAT; PK5: dashboard | 8 | 11-22 Jan 2027 | ±27 Jan 2027 |
| P4 Keuangan | PK4: laporan keuangan sesuai format [TBC] | 6 | 25 Jan-5 Feb 2027 | ±15 Feb 2027 |

Total sekitar 40 hari pengembangan, selesai sekitar pertengahan Februari 2027. Asumsi: satu pengembang dengan AI coding agent, mulai Senin 9 November 2026, 4 hari kerja pengembangan efektif per minggu, libur nasional dihitung kasar; tiap hari yang hilang per minggu menambah durasi sekitar 25%. Jadwal dikalibrasi ulang setelah P0 [ASUMSI].

Rilis dibuat bertahap sehingga setiap submenu dapat dipakai begitu selesai. Urutannya mengikuti ketergantungan data: Rencana (acuan), Realisasi (diukur terhadap acuan), Kinerja dan Dashboard (membaca keduanya), Keuangan (bergantung pada format).

### 14.2 Kriteria selesai per modul

| Modul | Kriteria selesai |
|---|---|
| PK0 | Login dan peran sesuai matriks bagian 2; audit log berjalan; pos baku terisi dari seluruh kode Lampiran 2-7 RKAT 2026; mesin impor menyediakan pratinjau, galat per baris, dan impor ulang tidak menggandakan data; layout sidebar responsif |
| PK1 | RKAT 2026 dapat diinput dan diimpor dengan enam validasi lulus pada nilai kontrol bagian 10.1; status versi dan penguncian bekerja; RKAT 2021-2025 dapat diimpor dengan pemetaan alias dan pos tak terpetakan ditandai; bandingkan versi dan tahun; ekspor tata letak Lampiran |
| PK2 | Input bulanan dan impor Excel bekerja; kumulatif dan capaian benar (diuji data sintetis); kunci dan buka kunci tercatat di audit; rasio hak amil dan peringatan muncul; saldo akhir terhitung; riwayat perubahan per sel |
| PK3 | Perbandingan antar tahun mode periode sama dan tahun penuh benar beserta YoY; ekspor Excel dan PDF |
| PK5 | Dashboard terbuka di bawah 2 detik dengan data 6 tahun; angka kartu sama dengan halaman Capaian (uji konsistensi); peringatan muncul sesuai aturan |
| PK4 | Sesuai format yang dikirim: template, input atau impor, status dan penguncian, unggah dokumen, bandingkan periode, ekspor |

### 14.3 Gerbang rilis

- **P1:** RKAT 2026 lulus enam validasi dengan angka kontrol; Staf Perencanaan mengimpor minimal satu RKAT tahun lain tanpa galat yang tidak terjelaskan.
- **P2:** satu bulan realisasi nyata diinput atau diimpor; capaian dibandingkan dengan perhitungan manual Staf Pelaporan, selisih nol atau terjelaskan.
- **P3:** dua pimpinan memakai dashboard; angka perbandingan antar tahun cocok dengan rekap manual untuk periode yang sama.
- **P4:** satu laporan keuangan nyata ditampilkan dalam format yang berlaku dan disetujui Keuangan.

### 14.4 Risiko

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Template RKAT 2021-2025 berbeda dari 2026 | Pemetaan lama dan perbandingan antar tahun tertunda | Tabel alias pos; pos tak terpetakan ditandai; minta file lebih awal |
| Format laporan keuangan terlambat atau kompleks | PK4 mundur | Mesin laporan berbasis template; mulai dengan unggah dokumen |
| Sumber realisasi tidak rapi | Waktu input membengkak | Template impor Excel baku, validasi total, penguncian bulanan |
| Plotting bulanan dibutuhkan (musim Ramadan) | Warna capaian menyesatkan | Tampilkan proporsi waktu; siapkan plotting bulanan sebagai tahap berikut |
| Satu pengembang | Keberlanjutan | Modular, dokumentasi, uji otomatis, backup |

## 15. Panduan untuk AI Coding Agent

- **Dokumen repositori:** `AGENTS.md` (stack, konvensi, perintah uji dan lint, aturan wajib); `docs/prd-pkp.md` (salinan PRD ini); `docs/modules/PK1.md` dan seterusnya (cakupan dan kriteria selesai dari 14.2); `docs/rules.md` (FR, VAL, BR); `docs/fixtures/rkat-2026.json` (dari dokumen RKAT 2026; data organisasi, aman dipakai sebagai data uji); `docs/open-questions.md`.
- **Aturan wajib:** nominal BIGINT; setiap perubahan lewat Action yang menulis audit log; tiap VAL dan BR punya minimal satu uji yang namanya memuat ID; impor idempoten; penguncian periode ditegakkan di server, bukan hanya tampilan; rollup diuji terhadap penjumlahan data sumber; migrasi dapat di-rollback; agen tidak mengarang aturan bisnis, melainkan mencatatnya di open-questions dan memakai asumsi paling konservatif.
- **Alur per modul:** (1) siapkan spesifikasi modul; (2) agen menulis rencana implementasi dan berhenti untuk direview; (3) implementasi bertahap: migrasi dan model, action dan uji aturan, halaman, seed; (4) jalankan uji dan lint, periksa kriteria selesai; (5) review manual dan merge; (6) demo ke pengguna kunci. Satu modul sama dengan satu cabang dan satu PR kecil.
- **Contoh perintah awal:** Modul PK1 Rencana. Baca AGENTS.md, docs/modules/PK1.md, dan aturan VAL-1 sampai VAL-6 serta BR-01 sampai BR-04. Tulis rencana implementasi lalu berhenti untuk review. Setelah disetujui, kerjakan bertahap, gunakan fixture rkat-2026.json untuk uji, dan jangan mengubah modul lain.

## 16. Pertanyaan Terbuka [TBC]

| No | Pertanyaan | Dibutuhkan untuk | Jawaban |
|---|---|---|---|
| 1 | Format laporan keuangan (kirim contoh file): jenis laporan, periode, dan apakah mencakup neraca, perubahan dana, arus kas, catatan | PK4, sebelum 25 Jan 2027 | |
| 2 | RKAT 2021-2025: apakah template sama dengan 2026, adakah RKAT Perubahan; kirim filenya | PK1, sebelum 23 Nov 2026 | |
| 3 | Sumber data realisasi 2021-2026 (Excel atau aplikasi lain), formatnya, dan siapa yang menginput tiap bulan | PK2, sebelum 14 Des 2026 | |
| 4 | Target pengentasan kemiskinan resmi (660 atau 5.659/3.962) dan cara mengukurnya | Lampiran 5 | |
| 5 | Perlukah plotting bulanan atau triwulanan; ambang warna capaian yang dipakai | FR-L-06, FR-L-10 | |
| 6 | Jumlah orang (muzakki, mustahik): diinput kumulatif tahunan atau per bulan | L5 | |
| 7 | Sumber dan bentuk data luar neraca (Rp 137,96 M) | L2 | |
| 8 | Daftar pengguna awal dan peran; pembagian bidang tiap Wakil Ketua untuk dashboard | Bagian 2, 9 | |
| 9 | Domain, hosting, dan cara login (email atau username) | PK0 | |

## 17. Log Keputusan

- PKP dikembangkan lebih dulu sebagai aplikasi mandiri; PRD induk sistem ZIS ditahan sementara dan tetap menjadi acuan jangka panjang.
- Menu PKP di sidebar dengan submenu Rencana, Realisasi, Kinerja, Keuangan; halaman awal berupa dashboard.
- RKAT 2021-sekarang disimpan per tahun dan versi dengan pos baku agar dapat dibandingkan.
- Realisasi penerimaan, penyaluran, dan operasional diinput atau diimpor per bulan; capaian dibandingkan dengan RKAT tahunan.
- Laporan keuangan mengikuti format yang akan dikirim; mesin laporan dibuat fleksibel sampai format diterima.
- Stack Laravel + Inertia + React dan PostgreSQL; pengembangan dengan AI coding agent. IKK (Lampiran 1 RKAT dan rasio efektivitas) dikeluarkan dari aplikasi PKP; Lampiran 2-7 tetap.
