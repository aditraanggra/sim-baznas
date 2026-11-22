# PRD — SISFOZIS BAZNAS Kabupaten Cianjur

Versi 0.1 (Draft) · 14 November 2026 · Domain: sim.baznaskabcianjur.com

> Catatan: halaman launcher di Bagian 6 mengikuti desain SISFOZIS yang Anda kirim (tangkapan layar). Butir bertanda \[ASUMSI\] perlu dikonfirmasi; daftar pertanyaan ada di Bagian 13.

## 1. Ringkasan Eksekutif

SISFOZIS (Sistem Informasi Zakat Infak Sedekah) adalah sistem terpadu (semacam ERP ringan) untuk mengelola seluruh siklus dana ZIS BAZNAS Kabupaten Cianjur: perencanaan → penghimpunan → pendistribusian dan pendayagunaan → keuangan dan akuntansi → pelaporan, ditambah dukungan SDM, persuratan, aset, dan jaringan UPZ. Satu login, satu basis data, hak akses berbasis RBAC. SiMBA menjadi rujukan fungsional, tetapi sistem ini dirancang mengikuti alur kerja dan struktur empat bidang di BAZNAS Kabupaten Cianjur.

Prinsip produk:

- Satu kali input, banyak output: satu transaksi penerimaan menghasilkan kwitansi, laporan, berita acara, dan (Fase 2) jurnal tanpa input ulang.
- Transparan ke luar: mustahik dapat melacak permohonan dan masyarakat dapat memverifikasi SK UPZ tanpa login.
- Aman dan dapat diaudit: tidak ada penghapusan transaksi, semua perubahan tercatat.
- Data bersih: Postgres, normalisasi 3NF, tanpa duplikasi data.

## 2. Masalah dan Tujuan

Kondisi saat ini: pencatatan penerimaan dan penyaluran tersebar di Excel/kertas, kwitansi dan berita acara dibuat manual, laporan periodik disusun ulang setiap kali, status permohonan mustahik sulit dilacak, dokumen perencanaan dan SOP tidak terpusat, dan masa berlaku SK UPZ dipantau manual.

Tujuan:

1. Mendigitalkan operasional harian empat bidang dalam satu sistem.
2. Menghasilkan dokumen resmi (kwitansi, berita acara, lembar disposisi, surat) secara otomatis.
3. Menyediakan laporan periodik akurat tanpa kompilasi manual.
4. Meningkatkan akuntabilitas dan kepercayaan publik lewat tracking dan halaman transparansi.
5. Menyiapkan fondasi akuntansi sesuai PSAK 109.

Metrik keberhasilan (target usulan, baseline diukur saat discovery):

| Metrik | Target |
| --- | --- |
| Waktu input satu penerimaan sampai kwitansi tercetak | < 2 menit |
| Penerimaan dan penyaluran yang tercatat di sistem | 100% sejak go-live |
| Waktu menyusun laporan bulanan penerimaan/penyaluran | < 5 menit (dari hitungan hari) |
| Permohonan yang punya status dapat dilacak publik | 100% |
| SK UPZ yang diperingatkan sebelum habis masa berlaku | 100%, minimal 60 hari sebelumnya |
| Pengguna aktif mingguan dari seluruh amil yang punya akun | ≥ 90% setelah 3 bulan |

## 3. Pengguna dan Peran

| Peran | Kebutuhan utama |
| --- | --- |
| Administrator | Kelola pengguna, peran, master data; melihat semua bidang |
| Pimpinan (Ketua, Wakil Ketua) | Memantau kinerja, menyetujui disposisi, mengakses arsip dokumen disahkan |
| Kepala Pelaksana / Kabid / Kabag | Verifikasi, persetujuan berjenjang, laporan bidang |
| Staf Pengumpulan | Input muzakki dan penerimaan, kwitansi, berita acara |
| Staf Pendistribusian | Input mustahik, permohonan, penyaluran, disposisi |
| Staf Perencanaan dan Pelaporan | RKAT, RENJA, RENSTRA, laporan kinerja, arsip dokumen |
| Staf Keuangan | BKU, jurnal, cash opname, laporan keuangan |
| Staf SDM, Administrasi, Umum, UPZ | Data amil dan absensi, surat, aset, database UPZ |
| Mustahik / Masyarakat (publik) | Melacak status permohonan, memverifikasi SK UPZ |
| Pimpinan via link khusus | Melihat dashboard kinerja dan arsip tanpa login |

Peran bersifat berbasis izin, jadi satu pengguna dapat memegang beberapa peran lintas bidang.

## 4. Lingkup dan Fase

Dalam lingkup: seluruh modul pada dokumen kebutuhan per bidang, RBAC, halaman launcher, halaman publik, dan dokumen cetak/PDF otomatis.

Di luar lingkup versi ini : pembayaran donasi online/payment gateway, aplikasi muzakki, rekonsiliasi bank otomatis, payroll, tanda tangan digital tersertifikasi.

Dokumen Anda menandai tujuh area sebagai MVP, itu cakupan yang luas untuk satu rilis. Saya usulkan MVP dibangun berurutan agar nilai terasa lebih cepat:

| Fase | Isi |
| --- | --- |
| 0. Fondasi | Login, RBAC, launcher, master data (wilayah, jenis/sumber dana, asnaf, program), audit log, pembuat dokumen PDF dan penomoran |
| 1A. MVP inti | Pengumpulan, UPZ, Pendistribusian dan Pendayagunaan (termasuk tracking publik) |
| 1B. MVP pendukung | Perencanaan dan Laporan Kinerja, Administrasi (surat), Umum (aset) |
| 2. Keuangan dan Arsip | BKU, cash opname, jurnal, laporan PSAK 109, arsip dokumen PKP, halaman kinerja untuk pimpinan |
| 3. SDM | Data amil, absensi, kanban tugas, laporan kehadiran, KPI |

Catatan: meski Keuangan baru di Fase 2, penerimaan dan penyaluran sejak Fase 1A harus sudah memuat pemetaan akun (dana zakat, infak/sedekah, amil, nonhalal) agar jurnal otomatis bisa dibangun tanpa migrasi ulang.

## 5. Autentikasi dan RBAC

Alur: pengguna membuka sim.baznaskabcianjur.com → halaman login → launcher sesuai izin.

Model RBAC:

- Pengguna dapat memiliki banyak peran; peran memiliki banyak izin (many-to-many di kedua sisi).
- Format izin: modul.sumberdaya.aksi, misalnya pengumpulan.penerimaan.create. Aksi standar: view, create, update, delete, approve, print, export.
- Deny by default. Penegakan wajib di backend (API), bukan hanya menyembunyikan menu di UI.
- Peran lintas bidang dibuat dengan menggabungkan izin, misalnya Staf Keuangan + Pelaporan.
- Pemisahan tugas (maker-checker): pembuat transaksi tidak boleh menjadi penyetuju transaksi yang sama (penyaluran, pembatalan penerimaan, posting jurnal).
- Cakupan data opsional: izin dapat dibatasi per bidang atau per UPZ.
- Keamanan akun: kata sandi kuat, pembatasan percobaan login, sesi dengan batas waktu, 2FA wajib untuk Administrator dan Keuangan, riwayat login.
- Administrator mengelola pengguna, peran, izin, dan reset kata sandi; semua perubahan hak akses masuk audit log.

## 6. Halaman Launcher (Setelah Login)

Desain mengikuti tangkapan layar SISFOZIS: header dengan logo BAZNAS Kabupaten Cianjur dan judul Sistem Informasi Zakat Infak Sedekah (SISFOZIS), banner Selamat Datang di SISFOZIS, empat kartu berwarna dalam grid 2×2, dan footer hak cipta.

| Kartu | Warna | Membuka | Rujukan |
| --- | --- | --- | --- |
| Pengumpulan | Hijau | Bidang Pengumpulan | 7.1 |
| Pendistribusian dan Pendayagunaan | Kuning | Bidang Pendistribusian dan Pendayagunaan | 7.2 |
| SDM, Administrasi dan Umum | Biru | Bidang SDM, Administrasi dan Umum, termasuk UPZ \[ASUMSI\] | 7.4 |
| Perencanaan, Keuangan dan Pelaporan | Ungu | Bidang Perencanaan, Keuangan dan Pelaporan, termasuk arsip dokumen | 7.3 |

Perilaku halaman:

- Kartu muncul bila pengguna punya minimal satu izin view di modul itu; kartu tanpa izin tidak ditampilkan dan grid menyesuaikan. Administrator melihat keempat kartu.
- Peran lintas bidang menampilkan beberapa kartu sekaligus.
- Klik kartu (panah di pojok kanan bawah) membuka modul bidang; sidebar di dalamnya hanya memuat menu sesuai izin.
- Desain saat ini tanpa angka ringkasan. Ringkasan di kartu (misalnya penerimaan hari ini, permohonan menunggu disposisi) tidak masuk MVP kecuali Anda menginginkannya.
- Belum ada di desain tetapi dibutuhkan: menu profil dan logout, ikon notifikasi (SK UPZ segera habis, disposisi masuk), dan menu Administrasi Sistem di header khusus Administrator untuk pengguna, peran, dan master data (bukan kartu kelima).
- Responsif: kartu menjadi satu kolom di ponsel.

## 7. Kebutuhan Fungsional per Modul

### 7.1 Bidang Pengumpulan (MVP)

| ID | Kebutuhan |
| --- | --- |
| PGM-01 | Data Muzakki perorangan dan lembaga; riwayat setoran; peringatan kemungkinan duplikat (NIK/telepon) |
| PGM-02 | Form penerimaan: pilih muzakki (atau tambah cepat), jenis dana (zakat, infak, DSKL) lalu sumber dana (zakat fitrah, zakat mal, infak terikat, infak tidak terikat, dst), nominal, metode bayar, rekening, UPZ perantara (opsional); mendukung beberapa item dalam satu transaksi |
| PGM-03 | Kwitansi otomatis: nomor berurut per tahun, PDF siap cetak, QR untuk verifikasi keaslian, cetak ulang tercatat |
| PGM-04 | Laporan penerimaan harian, bulanan, triwulan, semester, tahunan; filter jenis dana, sumber dana, UPZ, metode bayar; ekspor PDF dan Excel |
| PGM-05 | Berita acara penerimaan dana ZIS harian dibuat otomatis dari total hari itu; status draft lalu final (terkunci) |
| PGM-06 | Koreksi lewat pembatalan dengan alasan dan persetujuan; transaksi tidak pernah dihapus |

### 7.2 Bidang Pendistribusian dan Pendayagunaan (MVP)

| ID | Kebutuhan |
| --- | --- |
| PDP-01 | Data Mustahik: asnaf, perorangan/lembaga, wilayah (kecamatan, desa), status verifikasi, dokumen pendukung |
| PDP-02 | Permohonan: input dokumen permohonan dan lampiran, kode tracking otomatis |
| PDP-03 | Form penyaluran: program, asnaf, mustahik, nominal, sumber dana, bentuk bantuan (uang/barang); validasi kesesuaian sumber dana dengan asnaf dan peringatan saldo tidak cukup |
| PDP-04 | Lembar disposisi otomatis (PDF) dengan rute persetujuan berjenjang Pelayanan Mustahik → Disposisi Ketua → Kepala Bagian (Hasil Asessment ) → Wakil Ketua II (Ajuan besaran bantuan ) → Ketua (ACC Besaran bantuan) → Staf Pendis (meminta uang ke bagian keuangan) → Bantuan diterima Mustahik |
| PDP-05 | Dokumen tracking: linimasa status (Diajukan, Disposisi, Verifikasi, Acc Pimpinan, Disetujui, Dicairkan, Ditolak) lengkap dengan pelaku dan waktu |
| PDP-06 | Laporan pendistribusian dan pendayagunaan per asnaf, program, wilayah, sumber dana; harian sampai tahunan |
| PDP-07 | Halaman publik tracking status permohonan (Bagian 8) |
| PDP-08 | Berita acara penyaluran dana ZIS harian otomatis |

### 7.3 Bidang Perencanaan, Keuangan dan Pelaporan

Perencanaan dan Laporan (MVP)

| ID | Kebutuhan |
| --- | --- |
| PRC-01 | RKAT per tahun anggaran, dengan versi revisi |
| PRC-02 | Perbandingan RKAT dengan realisasi per tahun anggaran, otomatis dari data transaksi \[ASUMSI: itu maksud 'perbandingan RKAT'\] |
| PRC-03 | RENJA per tahun: program, kegiatan, indikator, target, bidang penanggung jawab |
| PRC-04 | RENSTRA per 5 tahun: tujuan, sasaran, indikator, target tahunan |
| PRC-05 | Laporan Kinerja per tahun dan per 5 tahun: capaian vs target, realisasi anggaran, narasi; cetak PDF |
| PRC-06 | tabel kegiatan tiap bidang yang menjadi dasar RKAT (Contoh: Nama Kolom => value, Nama Kegiatan => Zakat Fitrah Perorangan Langsung; Bidang => Pengumpulan; Jenis Dana => Zakat dst), sepertinya ada di tabel rkat\_item&#32; |

Keuangan (Fase 2)

| ID | Kebutuhan |
| --- | --- |
| KEU-01 | BKU Kas Masuk, terisi otomatis dari penerimaan |
| KEU-02 | BKU Kas Keluar dan cetak kwitansi, dari penyaluran dan belanja operasional |
| KEU-03 | Cash opname: hitung per pecahan uang, bandingkan saldo buku vs fisik, catat selisih, cetak berita acara |
| KEU-04 | Jurnal: otomatis dari transaksi lewat template pemetaan akun, jurnal manual, posting, penguncian periode; koreksi lewat jurnal balik |
| KEU-05 | Laporan keuangan sesuai PSAK 109: posisi keuangan, perubahan dana, arus kas, CaLK; dana dipisah (zakat, infak/sedekah, amil, nonhalal). Dokumen Anda menulis PSAK 409; saya asumsikan maksudnya PSAK 109 |

Arsip Dokumen PKP dan Halaman Pimpinan (Fase 2)

| ID | Kebutuhan |
| --- | --- |
| ARS-01 | Unggah PDF dokumen yang sudah disahkan: RKAT, RENJA, RENSTRA, Laporan Kinerja tahunan dan 5 tahunan, Laporan Keuangan Audited dan Unaudited, SOP per bidang; metadata tahun, nomor, tanggal pengesahan, versi |
| ARS-02 | Hanya dokumen berstatus Disahkan yang tampil; akses lewat link khusus pimpinan dan kepala bagian, bisa dilihat, dicetak, diunduh, dengan gaya situs PPID |
| ARS-03 | Log akses dan unduhan dokumen |
| LKP-01 | Halaman Laporan Kinerja Pengumpulan dan Penyaluran lewat link khusus tanpa login (aturan keamanan di Bagian 8) |

### 7.4 Bidang SDM, Administrasi dan Umum

SDM (Fase 3)

| ID | Kebutuhan |
| --- | --- |
| SDM-01 | Data amil: jabatan, status, SK, dokumen |
| SDM-02 | Absensi harian (masuk/pulang), izin, cuti, sakit; web, dengan opsi aplikasi Flutter dan geofence \[ASUMSI\] |
| SDM-03 | Pencatatan tugas harian format Kanban (To do, Doing, Review, Done), prioritas, tenggat, per amil dan per bidang |
| SDM-04 | Laporan kehadiran bulanan |
| SDM-05 | KPI amil: indikator dan bobot per jabatan, skor dari data sistem (kehadiran, tugas selesai) ditambah penilaian atasan |

Administrasi (MVP)

| ID | Kebutuhan |
| --- | --- |
| ADM-01 | Input surat masuk, disposisi, dan dokumen tracking |
| ADM-02 | Surat keluar otomatis: template menyesuaikan jenis surat, nomor otomatis sesuai format lembaga, kop, PDF, persetujuan |
| ADM-03 | Arsip dan pencarian surat masuk/keluar |

Umum (MVP)

| ID | Kebutuhan |
| --- | --- |
| UMM-01 | Database aset: kategori, kondisi, lokasi, penanggung jawab, tanggal dan nilai perolehan |
| UMM-02 | Nomor aset otomatis dan cetak label berkode QR |
| UMM-03 | Penyusutan otomatis: metode garis lurus, umur ekonomis dan nilai residu per kategori \[ASUMSI\], jadwal bulanan, nilai buku |

UPZ (MVP)

| ID | Kebutuhan |
| --- | --- |
| UPZ-01 | Database UPZ: DKM, Desa, DIBALEKA, Perusahaan, Sekolah, Yayasan, dll; wilayah, penanggung jawab, kontak |
| UPZ-02 | Pencatatan SK UPZ: nomor, tanggal, masa berlaku, berkas |
| UPZ-03 | Notifikasi SK segera habis (usulan H-90, H-60, H-30) di dalam aplikasi dan email/WhatsApp \[ASUMSI\] |
| UPZ-04 | Halaman publik SK UPZ tracking untuk membangun kepercayaan masyarakat |
| UPZ-05 | Laporan setoran per UPZ, terhubung ke data penerimaan |

## 8. Halaman Publik dan Tautan Khusus

| Halaman | Akses | Isi | Pengamanan |
| --- | --- | --- | --- |
| Tracking permohonan | Publik | Status dan linimasa permohonan | Kode tracking acak ditambah verifikasi tambahan (misalnya 4 digit akhir telepon); batas permintaan, captcha; tanpa data pribadi lengkap |
| Verifikasi SK UPZ | Publik | Nama UPZ, nomor SK, status aktif/habis, masa berlaku | Hanya data non-sensitif |
| Verifikasi kwitansi (QR) | Publik | Keabsahan nomor kwitansi, tanggal, nominal | Tanpa data muzakki |
| Laporan Kinerja Pengumpulan dan Penyaluran | Link khusus, tanpa login | Dashboard agregat read-only | Lihat aturan tautan khusus di bawah |
| Arsip Dokumen PKP | Link khusus pimpinan dan kabag | Daftar PDF disahkan, cetak dan unduh | Aturan tautan khusus di bawah |

Aturan tautan khusus: token acak panjang yang disimpan sebagai hash, dapat dicabut dan diganti oleh Administrator, masa berlaku opsional, hanya baca, hanya data agregat tanpa data pribadi mustahik/muzakki, ada log akses, diberi header noindex agar tidak muncul di mesin pencari. Risiko yang diterima: siapa pun yang memegang tautan dapat membuka halaman, jadi tautan harus dibagikan terbatas.

## 9. Alur Lintas Modul

1. Penerimaan: input → kwitansi → masuk laporan dan berita acara harian → (Fase 2) BKU Kas Masuk dan jurnal.
2. Penyaluran: permohonan → verifikasi → disposisi berjenjang → persetujuan → pencairan → laporan dan berita acara harian → (Fase 2) BKU Kas Keluar dan jurnal.
3. Perencanaan: RKAT dan RENJA → realisasi dihitung otomatis dari transaksi → Laporan Kinerja → arsip dokumen disahkan.
4. Surat: surat masuk → disposisi → tindak lanjut; surat keluar memakai template dan nomor otomatis.
5. Aset: perolehan → nomor aset → penyusutan bulanan → (Fase 2) jurnal penyusutan.
6. UPZ: SK dan masa berlaku → notifikasi → setoran UPZ tercatat di penerimaan.

Setiap dokumen resmi (kwitansi, berita acara, disposisi, surat) memiliki status draft → final. Dokumen final terkunci; perubahan lewat pembatalan atau revisi bernomor.

## 10. Desain Data (PostgreSQL, 3NF)

Aturan umum:

- Setiap atribut non-kunci bergantung pada kunci, seluruh kunci, dan hanya kunci. Data referensi (jenis dana, sumber dana, asnaf, wilayah, status, jenis surat, dll) berada di tabel master dan direferensikan lewat foreign key.
- Nilai turunan (total penerimaan, saldo, realisasi, nilai buku) tidak disimpan; dihitung lewat view atau materialized view. Pengecualian: dokumen final disimpan sebagai berkas PDF, bukan menyalin kolom.
- Uang memakai numeric(18,2). Hubungan banyak-ke-banyak memakai tabel penghubung. Header dan detail dipisah (misalnya penerimaan dan penerimaan\_item).
- Kolom standar: id, created\_at, updated\_at, created\_by, deleted\_at (soft delete hanya untuk master data; transaksi memakai status batal).
- Penomoran dokumen memakai tabel sequence per jenis dan tahun, dengan unique constraint.
- Audit log terpisah untuk perubahan data penting dan akses dokumen.

Kelompok entitas:

| Domain | Entitas utama |
| --- | --- |
| Akses | users, roles, permissions, role\_permissions, user\_roles, modules, audit\_logs, login\_history, tautan\_akses |
| Master | bidang, provinsi/kabupaten/kecamatan/desa, jenis\_dana, sumber\_dana, asnaf, program, metode\_bayar, rekening\_bank, status\_dokumen |
| Pengumpulan | muzakki, penerimaan, penerimaan\_item, kwitansi, berita\_acara\_penerimaan |
| Pendistribusian | mustahik, mustahik\_asnaf, permohonan, permohonan\_dokumen, permohonan\_status\_log, disposisi, penyaluran, penyaluran\_item, berita\_acara\_penyaluran |
| Perencanaan | tahun\_anggaran, rkat, rkat\_item, renja, renja\_kegiatan, renstra, renstra\_sasaran, indikator, laporan\_kinerja |
| Keuangan | coa, jurnal, jurnal\_detail, template\_jurnal, periode\_akuntansi, bku, cash\_opname, cash\_opname\_detail |
| Arsip | dokumen\_arsip, kategori\_dokumen, akses\_dokumen\_log |
| SDM | amil, jabatan, absensi, jenis\_izin, papan\_tugas, tugas, kpi\_indikator, kpi\_penilaian |
| Administrasi | surat\_masuk, surat\_keluar, jenis\_surat, template\_surat, disposisi\_surat, nomor\_sequence |
| Umum | aset, kategori\_aset, penyusutan\_aset |
| UPZ | upz, jenis\_upz, sk\_upz, notifikasi |

Relasi kunci: penerimaan → muzakki, upz (opsional), rekening; penerimaan\_item → sumber\_dana → jenis\_dana; sumber\_dana dan program → dana\_kelompok (zakat, infak/sedekah, amil, nonhalal) untuk pemetaan akun; penyaluran → permohonan → mustahik; program → asnaf; sk\_upz → upz; renja dan rkat → program dan bidang.

### 10.1 Diagram Relasi (ERD)

Diagram berikut memperlihatkan relasi antar tabel per domain. Panah menunjuk dari tabel anak (pemegang foreign key) ke tabel induk dengan relasi satu-ke-banyak; relasi banyak-ke-banyak dipecah menjadi tabel penghubung. Kotak berwarna tipis adalah tabel master atau referensi, kotak bergaris tebal adalah tabel pusat tiap diagram, dan kotak polos adalah tabel transaksi atau turunan.

**A. Akses dan RBAC**

&#91;embedded content: ERD akses dan RBAC · 6 tabel\]

Pengguna tidak memegang izin langsung: izin melekat ke peran lewat role\_permissions, dan peran melekat ke pengguna lewat user\_roles.

**B. Pengumpulan**

&#91;embedded content: ERD pengumpulan · 11 tabel\]

Satu penerimaan memiliki banyak item dan satu kwitansi, lalu dikelompokkan ke berita acara harian. Jenis dana dan kelompok dana (PSAK 109) melekat pada sumber dana.

**C. Pendistribusian dan Pendayagunaan**

&#91;embedded content: ERD pendistribusian · 13 tabel\]

Permohonan menjadi induk bagi status, dokumen, disposisi, dan penyaluran; mustahik dan program tidak disalin ke penyaluran.

**D. Perencanaan dan Laporan Kinerja**

&#91;embedded content: ERD perencanaan · 13 tabel\]

Program, bidang, dan indikator dipakai bersama oleh RKAT, RENJA, dan RENSTRA, sehingga realisasi dan capaian dapat dibandingkan per program.

**E. Keuangan (Fase 2)**

&#91;embedded content: ERD keuangan · 15 tabel\]

Penyaluran dan RKAT berasal dari domain lain; pengeluaran\_kas menghubungkannya dengan akun dan rekening bank.

**F. Wilayah, UPZ, Muzakki, dan Mustahik**

&#91;embedded content: ERD wilayah dan UPZ · 9 tabel\]

Kecamatan, kabupaten, dan provinsi diturunkan lewat rantai relasi dari desa, sehingga alamat tidak disimpan berulang di tabel lain. Satu UPZ dapat memiliki beberapa SK dari waktu ke waktu.

Domain SDM, Administrasi, dan Umum hanya memiliki relasi satu tingkat, sehingga cukup dibaca dari kolom FK pada Bagian 10.2.

### 10.2 Spesifikasi Tabel

Setiap tabel memiliki id (bigint identity) sebagai PK serta kolom standar created\_at, updated\_at, dan created\_by yang tidak ditulis ulang di bawah. Notasi: U = unik, N = boleh kosong, tanda panah = foreign key ke tabel induk. Uang memakai numeric(18,2). Nilai turunan (total, saldo, realisasi, nilai buku, BKU) berupa view dan tidak disimpan.

**Akses dan RBAC**

| Tabel | Kolom | Relasi (FK) |
| --- | --- | --- |
| users | nama, email (U), username (U), password\_hash, status, last\_login\_at | amil\_id → amil (N) |
| roles | kode (U), nama, keterangan | - |
| modules | kode (U), nama, urutan (sub-modul/menu di dalam satu bidang) | bidang\_id → bidang |
| permissions | kode (U, format modul.sumberdaya.aksi), resource, aksi | modul\_id → modules |
| role\_permissions | PK komposit | role\_id → roles; permission\_id → permissions |
| user\_roles | PK komposit | user\_id → users; role\_id → roles |
| login\_history | waktu, ip\_address, hasil | user\_id → users |
| audit\_logs | tabel, record\_id, aksi, perubahan (jsonb), waktu | user\_id → users |
| tautan\_akses | jenis (laporan kinerja/arsip), token\_hash (U), kadaluarsa\_at (N), dicabut\_at (N) | dibuat\_oleh → users |

**Master dan referensi**

| Tabel | Kolom | Relasi (FK) |
| --- | --- | --- |
| bidang | kode (U), nama, warna, urutan (satu bidang = satu kartu launcher) | - |
| provinsi | kode (U), nama | - |
| kabupaten | kode (U), nama | provinsi\_id → provinsi |
| kecamatan | kode (U), nama | kabupaten\_id → kabupaten |
| desa | kode (U), nama | kecamatan\_id → kecamatan |
| jenis\_dana | nama (Zakat, Infak/Sedekah, DSKL) | - |
| dana\_kelompok | nama (Zakat, Infak/Sedekah, Amil, Nonhalal; sesuai PSAK 109) | - |
| sumber\_dana | nama (zakat fitrah, zakat mal, infak terikat, infak tidak terikat, dst), terikat (bool) | jenis\_dana\_id → jenis\_dana; dana\_kelompok\_id → dana\_kelompok |
| asnaf | nama | - |
| program | kode (U), nama, aktif | asnaf\_id → asnaf (N); parent\_id → program (N, sub-program) |
| metode\_bayar | nama (tunai, transfer, dst) | - |
| rekening\_bank | bank, no\_rekening (U), atas\_nama | coa\_id → coa (N, Fase 2) |
| jenis\_dokumen | nama (KTP, KK, SKTM, proposal, dst) | - |
| status\_dokumen | kelompok, kode, nama, urutan; U(kelompok, kode) | - |
| bentuk\_bantuan | nama (uang, barang) | - |
| nomor\_sequence | jenis\_nomor, tahun, nilai\_terakhir; U(jenis\_nomor, tahun) | - |

**Pengumpulan**

| Tabel | Kolom | Relasi (FK) |
| --- | --- | --- |
| muzakki | jenis (perorangan/lembaga), nama, nik (U, N), npwp (N), telepon, email, alamat | desa\_id → desa\
kecamatan\_id  →  kecamatan |
| penerimaan | nomor (U), tanggal, status (draft/final/batal) | muzakki\_id → muzakki; upz\_id → upz (N); metode\_bayar\_id → metode\_bayar; rekening\_id → rekening\_bank (N); berita\_acara\_id → berita\_acara\_penerimaan (N); petugas\_id → users |
| penerimaan\_item | jumlah, keterangan | penerimaan\_id → penerimaan; sumber\_dana\_id → sumber\_dana |
| kwitansi | nomor (U), tanggal\_terbit | penerimaan\_id → penerimaan (U, satu kwitansi per penerimaan) |
| kwitansi\_cetak\_log | waktu | kwitansi\_id → kwitansi; user\_id → users |
| berita\_acara\_penerimaan | nomor (U), tanggal (U), status (draft/final) | penanggung\_jawab\_id → amil; dibuat\_oleh → users |
| pembatalan\_penerimaan | alasan, waktu\_diminta, waktu\_disetujui (N) | penerimaan\_id → penerimaan (U); diminta\_oleh → users; disetujui\_oleh → users (N) |

**Pendistribusian dan Pendayagunaan**

| Tabel | Kolom | Relasi (FK) |
| --- | --- | --- |
| mustahik | jenis (perorangan/lembaga), nama, nik (U), telepon, alamat, status\_verifikasi | desa\_id → desa |
| mustahik\_asnaf | PK komposit | mustahik\_id → mustahik; asnaf\_id → asnaf |
| mustahik\_dokumen | file\_path | mustahik\_id → mustahik; jenis\_dokumen\_id → jenis\_dokumen |
| permohonan | uuid (U, untuk halaman publik), kode\_tracking (U), tanggal, nominal\_diajukan, uraian | mustahik\_id → mustahik; program\_id → program; status\_id → status\_dokumen; dibuat\_oleh → users |
| permohonan\_dokumen | file\_path | permohonan\_id → permohonan; jenis\_dokumen\_id → jenis\_dokumen |
| permohonan\_status\_log | waktu, catatan, tampil\_publik (bool) | permohonan\_id → permohonan; status\_id → status\_dokumen; user\_id → users |
| disposisi | urutan, instruksi, keputusan, waktu | permohonan\_id → permohonan; dari\_user\_id → users; kepada\_user\_id → users |
| penyaluran | nomor (U), tanggal, status (draft/disetujui/dicairkan/batal) | permohonan\_id → permohonan; bentuk\_bantuan\_id → bentuk\_bantuan; berita\_acara\_id → berita\_acara\_penyaluran (N); disetujui\_oleh → users (N) |
| penyaluran\_item | jumlah, keterangan | penyaluran\_id → penyaluran; sumber\_dana\_id → sumber\_dana |
| berita\_acara\_penyaluran | nomor (U), tanggal (U), status (draft/final) | penanggung\_jawab\_id → amil |

Catatan: mustahik dan program pada penyaluran diambil lewat permohonan, sehingga tidak disimpan dua kali. Penyaluran langsung tetap dicatat sebagai permohonan terlebih dahulu. Halaman publik tracking hanya membaca permohonan\_status\_log yang bertanda tampil\_publik.

**UPZ**

| Tabel | Kolom | Relasi (FK) |
| --- | --- | --- |
| jenis\_upz | nama (DKM, Desa, DIBALEKA, Perusahaan, Sekolah, Yayasan, dst) | - |
| upz | nama, ketua, sekretaris, bendahara, alamat, pic\_nama, pic\_telepon, aktif | jenis\_upz\_id → jenis\_upz; desa\_id → desa |
| sk\_upz | nomor (U), tanggal\_sk, berlaku\_mulai, berlaku\_sampai, file\_path | upz\_id → upz |
| notifikasi | jenis, ref\_tabel, ref\_id, pesan, dibaca\_at (N) | user\_id → users |

Status SK aktif atau habis dihitung dari berlaku\_sampai lewat view; tidak disimpan sebagai kolom.

**Arsip dokumen**

| Tabel | Kolom | Relasi (FK) |
| --- | --- | --- |
| kategori\_dokumen | nama (RKAT, RENJA, RENSTRA, Laporan Kinerja, Laporan Keuangan Audited/Unaudited, SOP) | - |
| dokumen\_arsip | tahun, nomor, judul, versi, tanggal\_pengesahan, file\_path, status (draft/disahkan/dicabut) | kategori\_dokumen\_id → kategori\_dokumen; bidang\_id → bidang (N, untuk SOP); diunggah\_oleh → users |
| akses\_dokumen\_log | aksi (lihat/cetak/unduh), waktu, ip\_address | dokumen\_arsip\_id → dokumen\_arsip; tautan\_akses\_id → tautan\_akses (N); user\_id → users (N) |

**Perencanaan dan Laporan Kinerja**

| Tabel | Kolom | Relasi (FK) |
| --- | --- | --- |
| tahun\_anggaran | tahun (U), status (draft/berjalan/tutup) | - |
| rkat | versi, status, tanggal\_pengesahan; U(tahun\_anggaran\_id, versi) | tahun\_anggaran\_id → tahun\_anggaran |
| rkat\_item | uraian, nilai\_anggaran | rkat\_id → rkat; program\_id → program; bidang\_id → bidang; coa\_id → coa (N) |
| indikator | kode (U), nama, satuan | - |
| renstra | periode\_mulai, periode\_selesai, visi, misi, status | - |
| renstra\_sasaran | tujuan, sasaran | renstra\_id → renstra |
| renstra\_target | tahun, target; U(renstra\_sasaran\_id, indikator\_id, tahun) | renstra\_sasaran\_id → renstra\_sasaran; indikator\_id → indikator |
| renja | status | tahun\_anggaran\_id → tahun\_anggaran; renstra\_id → renstra (N) |
| renja\_kegiatan | uraian | renja\_id → renja; program\_id → program; bidang\_id → bidang |
| renja\_target | target; PK komposit | renja\_kegiatan\_id → renja\_kegiatan; indikator\_id → indikator |
| laporan\_kinerja | jenis (tahunan/lima tahunan), narasi, status | tahun\_anggaran\_id → tahun\_anggaran (N); renstra\_id → renstra (N); dokumen\_arsip\_id → dokumen\_arsip (N) |

View turunan: v\_realisasi\_rkat (realisasi per item RKAT dari penerimaan, penyaluran, dan pengeluaran\_kas) dan v\_capaian\_kinerja (realisasi vs target RENJA/RENSTRA).

**Keuangan (Fase 2)**

| Tabel | Kolom | Relasi (FK) |
| --- | --- | --- |
| coa | kode (U), nama, tipe (aset/liabilitas/dana/pendapatan/beban), posisi\_normal | parent\_id → coa (N); dana\_kelompok\_id → dana\_kelompok (N) |
| periode\_akuntansi | tahun, bulan, status (terbuka/tutup); U(tahun, bulan) | - |
| template\_jurnal | jenis\_transaksi (penerimaan/penyaluran/pengeluaran/penyusutan), keterangan | dana\_kelompok\_id → dana\_kelompok (N) |
| template\_jurnal\_baris | posisi (debit/kredit) | template\_jurnal\_id → template\_jurnal; coa\_id → coa |
| jurnal | nomor (U), tanggal, sumber (otomatis/manual), ref\_tabel, ref\_id (N), keterangan, status (draft/posted) | periode\_id → periode\_akuntansi; dibuat\_oleh → users; diposting\_oleh → users (N) |
| jurnal\_detail | debit, kredit | jurnal\_id → jurnal; coa\_id → coa |
| pengeluaran\_kas | nomor (U), tanggal, uraian, penerima, jumlah, status | rekening\_id → rekening\_bank; penyaluran\_id → penyaluran (N); rkat\_item\_id → rkat\_item (N); coa\_id → coa; dibuat\_oleh → users |
| cash\_opname | tanggal, saldo\_buku (snapshot saat opname, agar tetap utuh sebagai bukti), status | petugas\_id → amil; saksi\_id → amil |
| cash\_opname\_detail | pecahan, jumlah\_lembar | cash\_opname\_id → cash\_opname |

BKU Kas Masuk adalah view dari penerimaan\_item, dan BKU Kas Keluar adalah view dari pengeluaran\_kas; keduanya tidak disimpan sebagai tabel. Kwitansi pengeluaran dicetak dari pengeluaran\_kas. Laporan PSAK 109 dibangun dari view atas jurnal\_detail dan coa.

**SDM**

| Tabel | Kolom | Relasi (FK) |
| --- | --- | --- |
| jabatan | nama | bidang\_id → bidang (N) |
| amil | nik (U), nama, status\_kepegawaian, tanggal\_masuk, sk\_nomor, telepon, email | jabatan\_id → jabatan; bidang\_id → bidang |
| jenis\_kehadiran | nama (hadir, izin, sakit, cuti, dinas luar) | - |
| absensi | tanggal, jam\_masuk, jam\_pulang, latitude (N), longitude (N); U(amil\_id, tanggal) | amil\_id → amil; jenis\_kehadiran\_id → jenis\_kehadiran |
| papan\_tugas | nama | bidang\_id → bidang |
| kolom\_tugas | nama, urutan | papan\_tugas\_id → papan\_tugas |
| tugas | judul, deskripsi, prioritas, tenggat, selesai\_at (N) | kolom\_tugas\_id → kolom\_tugas; penanggung\_jawab\_id → amil |
| kpi\_indikator | nama, bobot, sumber (otomatis/manual) | jabatan\_id → jabatan |
| kpi\_penilaian | periode, skor; U(amil\_id, kpi\_indikator\_id, periode) | amil\_id → amil; kpi\_indikator\_id → kpi\_indikator; penilai\_id → users (N) |

Laporan kehadiran adalah view atas absensi.

**Administrasi (surat)**

| Tabel | Kolom | Relasi (FK) |
| --- | --- | --- |
| jenis\_surat | kode (U), nama, format\_nomor | - |
| template\_surat | isi, aktif | jenis\_surat\_id → jenis\_surat |
| surat\_masuk | nomor\_agenda (U), nomor\_surat, tanggal\_surat, tanggal\_terima, pengirim, perihal, file\_path | status\_id → status\_dokumen; dicatat\_oleh → users |
| surat\_masuk\_status\_log | waktu, catatan | surat\_masuk\_id → surat\_masuk; status\_id → status\_dokumen; user\_id → users |
| disposisi\_surat | instruksi, tenggat, keputusan, waktu | surat\_masuk\_id → surat\_masuk; dari\_user\_id → users; kepada\_user\_id → users |
| surat\_keluar | nomor (U), tanggal, tujuan, perihal, isi, file\_path | jenis\_surat\_id → jenis\_surat; status\_id → status\_dokumen; dibuat\_oleh → users; disetujui\_oleh → users (N) |

**Umum (aset)**

| Tabel | Kolom | Relasi (FK) |
| --- | --- | --- |
| kondisi\_aset | nama (baik, rusak ringan, rusak berat) | - |
| kategori\_aset | kode (U), nama, umur\_ekonomis\_bulan, nilai\_residu\_persen | coa\_id → coa (N, Fase 2) |
| aset | nomor\_aset (U), nama, tanggal\_perolehan, nilai\_perolehan, lokasi, status | kategori\_aset\_id → kategori\_aset; kondisi\_id → kondisi\_aset; penanggung\_jawab\_id → amil |
| penyusutan\_aset | jumlah\_susut; U(aset\_id, periode\_id) | aset\_id → aset; periode\_id → periode\_akuntansi |

Nilai buku aset adalah view (nilai\_perolehan dikurangi akumulasi penyusutan). Tabel periode\_akuntansi sudah dibuat sejak Fase 1B agar penyusutan bisa berjalan sebelum modul Keuangan.

## 11. Kebutuhan Non-Fungsional

- Keamanan dan privasi: data mustahik dan muzakki adalah data pribadi (UU PDP); enkripsi saat transit (HTTPS) dan data sensitif saat disimpan, pembatasan akses berdasarkan peran, masking NIK, penyimpanan berkas privat dengan tautan bertanda tangan.
- Audit: setiap pembuatan, ubahan, persetujuan, pembatalan, cetak ulang, dan unduh tercatat siapa, kapan, apa.
- Kinerja: halaman utama < 2 detik, laporan bulanan < 5 detik pada data 5 tahun.
- Ketersediaan dan pemulihan: ketersediaan ≥ 99% pada jam kerja; backup harian otomatis, uji pemulihan berkala; RPO ≤ 24 jam, RTO ≤ 4 jam \[ASUMSI\].
- Kegunaan: antarmuka Bahasa Indonesia, responsif, dapat dipakai pengguna non-teknis; kontras dan ukuran huruf memadai.
- Dokumen cetak: PDF presisi untuk A4/kop surat BAZNAS, termasuk format kwitansi dan berita acara.
- Ekspor: PDF dan Excel untuk semua laporan.
- Pemeliharaan: migrasi basis data berversi, lingkungan staging, pengujian otomatis untuk aturan keuangan dan RBAC.

## 12. Arsitektur dan Roadmap

Usulan teknis (dapat disesuaikan):

- Basis data: PostgreSQL.
- Backend: Laravel sebagai API (paket spatie/laravel-permission untuk RBAC, antrean untuk notifikasi dan PDF, scheduler untuk notifikasi SK UPZ dan penyusutan). Node.js hanya bila ada kebutuhan layanan khusus.
- Frontend: React (SPA) via Inertia untuk launcher dan seluruh modul, ditambah halaman publik yang ringan.
- Deployment : Menggunakan Docker dengan konfigurasi pada Dockerfile + FrankenPHP pada VPS untuk akses utama, lalu bisa syncronize data dengan server lokal, jadi jika vps tidak bisa diakses aplikasi bisa diakses menggunakan server local, ketika vps kembali online maka data terbaru akan otomatis update ke vps
- Mobile: Flutter untuk absensi dan tugas harian pada Fase 3 bila diperlukan; sebelum itu cukup web responsif.
- Penyimpanan berkas: object storage privat; PDF dibuat di server dari template.
- Notifikasi: in-app, email, dan WhatsApp \[ASUMSI\].

Roadmap (estimasi durasi menunggu konfirmasi ukuran tim):

| Fase | Hasil yang dapat dipakai | Kriteria selesai |
| --- | --- | --- |
| 0 Fondasi | Login, RBAC, launcher, master data | Peran lintas bidang berfungsi, audit log aktif |
| 1A | Penerimaan, kwitansi, berita acara; mustahik, permohonan, disposisi, tracking publik; UPZ | Operasional harian dua bidang berjalan di sistem; laporan periodik keluar otomatis |
| 1B | RKAT, RENJA, RENSTRA, Laporan Kinerja; surat; aset | Laporan Kinerja dihasilkan dari data transaksi |
| 2 | BKU, jurnal, cash opname, PSAK 109, arsip PKP, halaman pimpinan | Laporan keuangan tutup periode tanpa input ulang |
| 3 | Data amil, absensi, kanban, KPI | Laporan kehadiran bulanan otomatis |

## 13. Risiko, Asumsi dan Pertanyaan Terbuka

Risiko utama:

| Risiko | Mitigasi |
| --- | --- |
| Cakupan MVP terlalu besar | Pecah menjadi fase 1A dan 1B; kunci kebutuhan sebelum pengembangan |
| Data lama (Excel/kertas) berantakan | Rencana migrasi, template impor, validasi dan pembersihan sebelum go-live |
| Adopsi rendah oleh amil | Pelatihan per peran, pendampingan awal, antarmuka sederhana |
| Aturan akuntansi PSAK 109 keliru | Libatkan akuntan/auditor sejak desain pemetaan akun |
| Tautan khusus tanpa login bocor | Token acak, dapat dicabut, data agregat saja, log akses |
| Satu kesalahan data publik merusak kepercayaan | Hanya tampilkan data final dan terverifikasi |

Pertanyaan terbuka:

1. Apakah maksud 'PSAK 409' adalah PSAK 109? Apakah perlu pelaporan ke BAZNAS Provinsi/Pusat atau ekspor ke SiMBA?
2. Kartu mana yang memuat UPZ: saat ini diasumsikan SDM, Administrasi dan Umum (sesuai dokumen kebutuhan), padahal UPZ juga terkait erat dengan Pengumpulan.
3. Format nomor kwitansi, nomor surat, nomor aset, dan templat dokumen yang berlaku saat ini.
4. Alur persetujuan penyaluran: siapa saja dan batas nominalnya.
5. Jumlah pengguna, jumlah UPZ, volume transaksi per bulan, dan data lama yang harus dimigrasi.
6. Apakah penerimaan online (QRIS/transfer terverifikasi) perlu masuk roadmap?
7. Hosting dan anggaran: server sendiri atau cloud; kanal notifikasi yang tersedia.
8. Metode penyusutan dan kebijakan akuntansi aset yang dipakai.
9. Arti 'DIBALEKA' sebagai jenis UPZ (untuk nama master data).

## 14. Spesifikasi Desain UI

Bagian ini adalah brief desain untuk agen AI dan desainer yang akan membuat antarmuka SISFOZIS. Semua butir mengikat, kecuali yang ditandai \[USULAN\] (boleh dioptimalkan dengan alasan tertulis). Bagian 6 (launcher), 7 (fungsional), dan 8 (halaman publik) adalah sumber kebutuhan layar; bagian ini mengatur tampilan, pola, dan perilakunya.

### 14.1 Arah Desain

Karakter yang dituju: tenang, tepercaya, rapi, dan efisien. Aplikasi ini dipakai amil berjam-jam untuk input berulang dan mengelola dana amanah umat, sehingga kejelasan dan ketelitian lebih penting daripada kesan mewah.

Konteks pemakaian:

- Staf kantor: laptop atau desktop, mouse dan keyboard, input berulang, layar penuh tabel dan form.
- Pimpinan dan kepala bagian: membaca laporan dan menyetujui disposisi, sering dari ponsel atau tablet.
- Publik (mustahik, masyarakat): ponsel, koneksi bervariasi, pengguna non-teknis, sebagian berusia lanjut.

Prinsip:

1. Kejelasan di atas dekorasi: setiap elemen punya fungsi; hierarki dibangun dari ukuran huruf, bobot, dan jarak, bukan dari warna dan bayangan.
2. Padat tetapi nyaman: tampilkan banyak informasi per layar dengan jarak yang konsisten; hindari ruang kosong berlebihan di halaman data.
3. Konsistensi pola: tugas yang sama selalu tampil dengan pola yang sama (daftar, form, detail, laporan).
4. Aman bagi transaksi: status dokumen, penguncian, konfirmasi, dan jejak perubahan selalu terlihat.
5. Identitas BAZNAS terkendali: hijau dan kuning tampil kuat di launcher, login, dan halaman publik; di dalam aplikasi warna dipakai hemat.
6. Inklusif: terbaca, dapat dioperasikan dengan keyboard, dan nyaman di ponsel untuk tugas yang memang dikerjakan di ponsel.

Tingkat kerapihan yang dijadikan rujukan (bukan untuk ditiru tampilannya): dasbor Stripe, Linear, GitHub Primer, dan IBM Carbon Design System.

### 14.2 Yang Harus Dihindari

Ciri tampilan generik hasil AI berikut tidak boleh muncul:

- Gradasi ungu-biru atau gradasi mencolok pada latar halaman, tombol, atau teks; efek kaca (glassmorphism), glow, dan neon.
- Emoji sebagai ikon atau hiasan; ilustrasi 3D atau bentuk blob acak; ikon dalam lingkaran berwarna di setiap baris.
- Halaman dalam yang dipenuhi kartu seragam berisi angka acak; dasbor dengan belasan kartu KPI tanpa makna.
- Sudut terlalu bulat (radius di atas 12 px) di luar kartu launcher; bayangan tebal pada setiap kartu; kartu di dalam kartu.
- Semua teks rata tengah; teks abu-abu kecil berkontras rendah; lebih dari tiga bobot huruf.
- Badge dan chip warna-warni yang tidak membawa makna status.
- Data tiruan yang tidak masuk akal (lorem ipsum, angka bulat acak); gunakan data realistis konteks BAZNAS.
- Sapaan basa-basi, tanda seru, dan microcopy berbunga-bunga.
- Beberapa set ikon atau beberapa gaya tombol untuk aksi yang sama.
- Animasi dekoratif (memantul, berkedip, parallax); spinner dan skeleton di mana-mana tanpa perlu.
- Grafik hiasan tanpa data nyata; diagram pai dengan banyak irisan; efek 3D.

Pengganti yang diharapkan: tipografi tegas, jarak konsisten, permukaan netral, satu warna aksi, data nyata, dan detail presisi (garis 1 px, angka rata kanan, status yang jelas).

### 14.3 Identitas Visual dan Tema

Acuan identitas adalah desain launcher SISFOZIS yang sudah ada: hijau BAZNAS, kuning emas, gelombang di banner dan footer, dan kartu empat warna per bidang. Ambil nilai warna yang persis dari desain itu; nilai di bawah adalah usulan awal token dan, karena UI memakai preset shadcn/ui (lihat 14.15), berfungsi sebagai panduan maksud. Semua pasangan teks dan latar wajib lulus WCAG 2.2 AA (4,5:1 untuk teks, 3:1 untuk komponen UI) dan diverifikasi oleh agen.

| Kelompok | Token | Usulan | Pemakaian |
| --- | --- | --- | --- |
| Brand | brand-600 | #0A7A3C | Tombol utama, tautan, fokus, item terpilih |
| Brand | brand-700 | #07602F | Hover/pressed, judul utama |
| Brand | brand-50 | #EAF5EE | Latar item terpilih, badge lembut |
| Brand | brand-900 | #064E2B | Footer dan header gelap halaman publik |
| Aksen identitas | gold-500 | #F7B500 | Hanya elemen identitas: garis aksen, gelombang, kartu Pendistribusian |
| Bidang | bidang-pengumpulan, -distribusi, -umum, -pkp | #0A7A3C, #F7B500, #1D4ED8, #5B21B6 | Chip bidang di top bar, penanda aktif sidebar, kartu launcher; bukan latar halaman |
| Netral | bg-app | #F5F6F8 | Latar aplikasi |
| Netral | surface | #FFFFFF | Panel, tabel, form |
| Netral | border, border-strong | #E2E5EA, #C9CED6 | Garis pemisah, batas input |
| Netral | text-primary | #151A21 | Teks utama |
| Netral | text-secondary, text-muted | #4A5361, #6B7480 | Teks pendukung, placeholder, keterangan |
| Status | success, warning, danger, info | #187A47, #9A5B00, #B42318, #1D5FD1 | Teks dan ikon status; latar lembut #E8F5EE, #FFF4DB, #FDECEA, #E8F0FD |
| Data | dana-zakat, dana-infak, dana-dskl, lainnya | #0A7A3C, #E0A100, #2563EB, #6B7480 | Grafik dan legenda; pemetaan sama di seluruh aplikasi |

Aturan pemakaian warna:

- Satu warna aksi utama per layar (primary dari preset di dalam aplikasi; hijau BAZNAS di launcher, login, dan halaman publik); tombol sekunder berupa outline netral.
- Warna bidang hanya penanda identitas modul, bukan dekorasi halaman. Kartu kuning memakai teks gelap agar kontras terpenuhi.
- Warna semantik hanya untuk status, dan status selalu disertai label teks (serta ikon bila perlu), tidak bergantung pada warna saja.
- Latar halaman di dalam aplikasi selalu netral; warna jenuh hanya di launcher, login, dan halaman publik.
- Mode gelap tidak masuk MVP, tetapi token disusun sebagai variabel semantik agar mudah ditambahkan.

### 14.4 Tipografi, Ikon, Bentuk

Typeface: judul dan merek memakai sans geometris yang sama dengan launcher (misalnya Plus Jakarta Sans); isi antarmuka, tabel, dan angka memakai Inter atau pasangan setara dengan tabular-nums. Maksimal dua keluarga huruf, hanya bobot 400, 500, dan 600, dengan fallback font sistem.

| Gaya | Ukuran / tinggi baris | Bobot | Pemakaian |
| --- | --- | --- | --- |
| Display | 32 / 40 | 600 | Judul launcher dan login saja |
| H1 | 24 / 32 | 600 | Judul halaman |
| H2 | 18 / 28 | 600 | Judul panel dan seksi |
| H3 | 15 / 24 | 600 | Sub-judul, judul kartu |
| Body | 14 / 20 | 400 | Teks dan form default |
| Body kecil | 13 / 18 | 400 | Sel tabel mode padat |
| Caption | 12 / 16 | 400 atau 500 | Label bantu, metadata, breadcrumb |

Angka: tabular-nums; nominal rata kanan; format Indonesia (Rp 1.250.000, 15 Nov 2026, 14.30 WIB).

Ikon: satu set garis (misalnya Lucide), 20 px (16 px di dalam teks), tebal garis 1,5 px, warna mengikuti teks, tanpa emoji. Ikon watermark besar hanya pada kartu launcher.

Bentuk dan jarak:

- Skala jarak berbasis 4 px: 4, 8, 12, 16, 24, 32, 48.
- Radius: 6 px (input, tombol), 8 px (panel, tabel), 12 px (dialog, popover), 20 px hanya untuk kartu launcher.
- Panel memakai garis 1 px tanpa bayangan; bayangan ringan hanya untuk popover, menu, dan dialog.
- Tinggi kontrol: 36 px (default), 32 px (padat), 44 px (ponsel).

### 14.5 Kerangka Layout

Ada tiga kerangka halaman:

1. Launcher: mengikuti desain terlampir (header berlogo, banner sambutan, grid 2×2, footer). Tambahan: chip pengguna (nama dan peran) dengan menu profil di kanan header; kartu naik 2 px saat hover dan panah bergeser; kartu dapat difokuskan dengan keyboard; bila hanya satu kartu yang diizinkan, tampilkan satu kartu rata kiri dengan lebar yang sama.
2. Aplikasi (kerangka modul): top bar, sidebar, dan area konten, seperti pada diagram di bawah.
3. Publik (tanpa login): header berlogo dengan garis hijau, konten satu kolom maksimal 720 px (dasbor kinerja 1120 px), footer hijau tua selaras dengan launcher.

Kerangka aplikasi memiliki tiga area tetap dan satu area yang berganti.

&#91;embedded content: wireframe kerangka aplikasi · top bar, sidebar, konten\]

Wireframe ini hanya mengatur pembagian area dan urutan elemen, bukan gaya visual akhir; warna, tipografi, dan komponen mengikuti 14.3 sampai 14.7.

Spesifikasi kerangka aplikasi:

- Top bar 56 px, putih, garis bawah 1 px. Kiri: tombol ikon grid (kembali ke launcher), logo kecil, nama modul dengan penanda warna bidang. Tengah: kolom pencarian global (Ctrl+K). Kanan: notifikasi (badge angka) dan profil.
- Sidebar 248 px (terlipat 64 px), putih, garis kanan 1 px. Item setinggi 40 px: ikon 20 px dan label 14 px. Item aktif: latar brand-50, teks brand-700, bar 3 px berwarna bidang di tepi kiri. Grup menu bertajuk caption. Maksimal dua tingkat.
- Area konten: latar bg-app, padding 24 px, lebar penuh dengan batas 1440 px; panel putih di atasnya.
- Header halaman: breadcrumb (caption), H1, deskripsi satu baris opsional, aksi di kanan dengan satu tombol utama.
- Breakpoint: 1280 px ke atas adalah acuan utama; 1024 sampai 1279 px sidebar terlipat otomatis; 768 sampai 1023 px sidebar menjadi panel geser; di bawah 768 px mode ponsel.

### 14.6 Navigasi

| Modul (kartu) | Item sidebar |
| --- | --- |
| Pengumpulan | Dasbor, Muzakki, Penerimaan, Berita Acara Harian, Laporan |
| Pendistribusian dan Pendayagunaan | Dasbor, Mustahik, Permohonan, Penyaluran, Disposisi Saya, Berita Acara Harian, Laporan |
| SDM, Administrasi dan Umum | SDM (Amil, Absensi, Tugas, KPI); Administrasi (Surat Masuk, Surat Keluar); Umum (Aset, Penyusutan); UPZ (Data UPZ, SK UPZ) |
| Perencanaan, Keuangan dan Pelaporan | Perencanaan (RKAT, RENJA, RENSTRA); Keuangan (BKU, Cash Opname, Jurnal, Laporan Keuangan); Laporan Kinerja; Arsip Dokumen |
| Administrasi Sistem (menu profil, khusus Administrator) | Pengguna, Peran dan Izin, Master Data, Tautan Akses, Audit Log |

Aturan:

- Alur: launcher, modul, item sidebar, halaman. Kembali ke launcher lewat tombol grid; pindah bidang cepat lewat Ctrl+K.
- Item menu hanya tampil bila pengguna punya izin view. Tombol aksi tanpa izin disembunyikan; tombol yang diizinkan tetapi terblokir oleh status dokumen dinonaktifkan dengan tooltip alasan.
- Pencarian global mencakup nomor kwitansi, nama muzakki atau mustahik, kode tracking, nomor surat, dan nomor aset; hasil dikelompokkan per jenis dan hanya memuat yang diizinkan.
- Notifikasi berupa popover berisi daftar (SK UPZ segera habis, disposisi masuk, persetujuan menunggu); tiap item menuju halaman terkait dan penanda belum dibaca berupa titik hijau.

### 14.7 Komponen

| Komponen | Spesifikasi |
| --- | --- |
| Tombol | Primer (hijau terisi), sekunder (outline netral), ghost, destruktif (merah, hanya untuk hapus atau batalkan). Maksimal satu primer per area. Saat loading: spinner kecil dan klik ganda dinonaktifkan |
| Input | Label di atas, tinggi 36 px; teks bantu di bawah; galat berwarna merah dengan pesan spesifik; wajib ditandai teks (wajib), bukan hanya bintang; masker untuk Rupiah, NIK, dan telepon |
| Select dan combobox | Dapat dicari; lookup muzakki dan mustahik menyediakan Tambah baru secara inline dan menampilkan nama serta nomor identitas yang disamarkan |
| Tanggal | Date picker berlokal Indonesia, dapat diketik manual (dd/mm/yyyy); pemilih periode dengan pilihan cepat: Hari ini, Bulan ini, Triwulan, Semester, Tahun |
| Tabel | Header lengket, tanpa zebra, garis horizontal tipis, hover baris lembut, nominal rata kanan, kepadatan dapat dipilih (nyaman 48 px, padat 36 px), urut kolom, pemilih kolom, baris total bila relevan, paginasi; aksi baris lewat menu titik tiga dan satu aksi utama yang terlihat |
| Badge status | Titik 8 px dan label, latar lembut sesuai semantik; kosakata seragam: Draft, Diproses, Disetujui, Final, Dibatalkan |
| Tab | Garis bawah 2 px hijau untuk tab aktif; dipakai pada halaman detail (Ringkasan, Dokumen, Riwayat) |
| Drawer | Panel kanan 480 sampai 640 px untuk lihat cepat dan ubah master data; dialog hanya untuk konfirmasi dan tugas singkat |
| Konfirmasi | Dialog untuk aksi tak terbalikkan (finalkan, batalkan); menyebut objek dan akibatnya; tombol berlabel kata kerja (Finalkan Kwitansi), bukan OK |
| Unggah berkas | Area seret-lepas dan tombol; pratinjau PDF atau gambar; batas ukuran dan tipe tertulis; progres; hapus |
| Timeline | Untuk tracking status: titik dan garis vertikal; tiap langkah memuat status, pelaku (internal), waktu, catatan |
| Toast | Pojok kanan bawah, maksimal 4 detik, untuk konfirmasi hasil aksi; galat memakai banner inline, bukan toast |
| Keadaan kosong | Satu kalimat penjelas dan satu aksi; tanpa ilustrasi besar |
| Pemuatan | Skeleton sesuai bentuk konten untuk tabel dan panel; spinner hanya untuk aksi tombol |
| Kartu ringkasan | Angka besar, label caption, perbandingan dengan periode sebelumnya (opsional); maksimal 4 per baris; tanpa hiasan |

### 14.8 Pola Halaman

| Pola | Dipakai di | Spesifikasi |
| --- | --- | --- |
| Dasbor bidang | Awal tiap modul | Baris ringkasan (maksimal 4 angka kunci), satu atau dua grafik bermakna (tren penerimaan harian, komposisi per sumber dana), dan daftar perlu tindakan (permohonan menunggu disposisi, SK UPZ segera habis, surat belum didisposisi) |
| Daftar data | Muzakki, mustahik, penerimaan, surat, aset | Header halaman; bilah filter (pencarian, filter utama, filter lanjutan di panel); tabel; filter aktif tampil sebagai chip yang dapat dihapus; ekspor; klik baris membuka detail |
| Form transaksi | Penerimaan, penyaluran, surat keluar | Satu halaman dengan seksi bertajuk, dua kolom di desktop; ringkasan lengket di kanan (total, status); bilah aksi lengket di bawah (Simpan Draft, Simpan dan Cetak); dapat dioperasikan dengan Tab dan Enter; autosave draft; validasi saat blur dan saat simpan |
| Detail dokumen | Permohonan, surat masuk, penyaluran | Header: nomor, status, aksi utama sesuai status; kiri: tab (Ringkasan, Dokumen, Disposisi, Riwayat); kanan: timeline status dan pratinjau berkas; spanduk Final, terkunci bila tidak dapat diubah |
| Laporan | Semua laporan periodik | Pemilih periode (Harian, Bulanan, Triwulan, Semester, Tahunan) dan filter; baris ringkasan; grafik opsional; tabel; tombol Cetak, PDF, Excel; pratinjau cetak sama dengan hasil |
| Master data | Asnaf, program, jenis dana, wilayah | Tabel sederhana dan drawer tambah atau ubah; nonaktifkan, bukan hapus |
| Kanban | Tugas harian amil | Kolom To do, Doing, Review, Done; kartu ringkas (judul, penanggung jawab, tenggat, prioritas); seret-lepas dengan alternatif menu untuk keyboard |
| Matriks izin | Peran dan Izin | Baris: izin per modul (dapat dilipat); kolom: peran; sel: kotak centang; pencarian; ringkasan perubahan sebelum simpan; peran bawaan terkunci |

Grafik: garis untuk tren, batang untuk perbandingan, batang bertumpuk untuk komposisi (maksimal 5 segmen); hindari pai dan donat; label langsung bila memungkinkan; angka berformat Indonesia; tooltip presisi; warna mengikuti token data; selalu tersedia tabel atau ekspor sebagai pengganti; tanpa efek 3D dan tanpa animasi masuk.

### 14.9 Layar Kunci

| Layar | Struktur dan perilaku |
| --- | --- |
| Login | Kartu 400 px di tengah latar muda dengan gelombang identitas; logo, judul, username atau email, kata sandi (tampilkan/sembunyikan), tombol Masuk, tautan lupa kata sandi; langkah kedua untuk 2FA; pesan galat tidak membocorkan keberadaan akun; tanpa ilustrasi |
| Launcher | Sesuai Bagian 6 dan tangkapan layar; variasi 1 sampai 4 kartu sesuai izin; chip pengguna di header |
| Form Penerimaan (layar paling sering dipakai) | Kiri: Muzakki (lookup), Rincian (tabel item inline: sumber dana, nominal, keterangan; baris baru dengan Enter), Pembayaran (metode, rekening, UPZ). Kanan, lengket: total, terbilang, status draft, tombol Simpan dan Cetak Kwitansi. Setelah simpan: ringkasan sukses dengan pratinjau kwitansi dan tombol Cetak, Unduh PDF, Input Baru (fokus kembali ke Muzakki) |
| Detail Permohonan | Header: kode, mustahik, program, nominal, status. Tab Ringkasan, Dokumen, Disposisi, Riwayat. Kanan: timeline status. Aksi menyesuaikan peran dan status: Verifikasi, Disposisikan, Setujui, Tolak (alasan wajib), Cairkan; lembar disposisi dapat dipratinjau |
| Laporan Penerimaan | Pemilih periode dan filter jenis dana, sumber dana, UPZ; baris ringkasan total per jenis dana; grafik tren; tabel rinci dengan total; tombol Cetak, PDF, Excel |
| Arsip Dokumen (pimpinan, kabag) | Bergaya portal PPID: pencarian, filter kategori dan tahun, daftar baris (judul, nomor, tahun, tanggal pengesahan, ukuran), tombol Lihat, Cetak, Unduh; pratinjau PDF di dalam halaman |
| Dasbor Kinerja (tautan khusus, tanpa login) | Header publik; ringkasan penerimaan dan penyaluran tahun berjalan; tren bulanan; komposisi per sumber dana dan per asnaf; tabel per bulan; pemilih tahun; keterangan Data per tanggal dan jam; tanpa data pribadi |
| Tracking Permohonan (publik) | Kolom kode tracking besar satu kolom dan verifikasi tambahan; hasil berupa badge status besar, timeline vertikal, tanggal; bahasa sederhana; tanpa nama pelaku internal dan tanpa data pribadi lengkap |
| Verifikasi SK UPZ (publik) | Kolom cari (nama UPZ atau desa); hasil berupa kartu: nama, jenis, wilayah, nomor SK, masa berlaku, badge Aktif atau Habis; tautan berkas SK bila diizinkan |

### 14.10 Dokumen Cetak dan PDF

Aturan umum: A4 dengan margin 20 mm kecuali disebut lain; kop surat (logo BAZNAS, nama lembaga, alamat, kontak) dengan garis pemisah; surat dinas memakai huruf serif formal (misalnya Noto Serif 11 sampai 12 pt), dokumen keuangan memakai sans (Inter 10 pt); nomor dokumen jelas; QR verifikasi di pojok bawah; blok tanda tangan; watermark DRAFT diagonal tipis untuk dokumen non-final dan label SALINAN atau CETAK ULANG ke-n untuk cetak ulang; terbaca baik dalam hitam-putih (tanpa latar gelap); pratinjau di layar identik dengan hasil cetak.

| Dokumen | Isi dan tata letak |
| --- | --- |
| Kwitansi penerimaan | A5 lanskap \[USULAN\]: nomor, tanggal, diterima dari, jumlah dalam angka dan terbilang, untuk pembayaran (jenis dan sumber dana), tanda tangan amil, QR |
| Berita acara harian (penerimaan dan penyaluran) | Tanggal, tabel rincian per sumber dana atau program dengan total, tanda tangan petugas dan penanggung jawab |
| Lembar disposisi | Nomor dan perihal, ringkasan permohonan, kolom instruksi, jalur paraf berjenjang |
| Surat keluar | Kop, nomor, lampiran, perihal, isi, penutup, tanda tangan; susunan menyesuaikan jenis surat |
| Laporan periodik | Tabel padat, header berulang tiap halaman, nomor halaman, baris total |
| Label aset | Nomor aset dan QR, ukuran kecil sesuai stiker |

### 14.11 Responsif dan Aksesibilitas

- Desktop-first untuk staf. Ponsel diprioritaskan untuk launcher, login, halaman publik, persetujuan disposisi, notifikasi, absensi, dan dasbor kinerja pimpinan.
- Di ponsel: sidebar menjadi panel geser; tabel menjadi kartu ringkas (judul, status, dua atribut) atau gulir horizontal dengan kolom pertama lengket; form satu kolom; bilah aksi lengket di bawah; target sentuh 44 px.
- Aksesibilitas WCAG 2.2 AA: seluruh fungsi dapat dioperasikan dengan keyboard; indikator fokus 2 px hijau dengan offset; urutan tab logis; label terhubung dengan input; pesan galat diumumkan (aria-live); tabel memiliki header terstruktur; tidak ada informasi yang hanya lewat warna; hormati preferensi gerak berkurang; teks dapat diperbesar sampai 200% tanpa kehilangan fungsi.
- Kinerja persepsi: interaksi terasa instan di bawah 100 ms; skeleton untuk pemuatan di atas 300 ms; halaman publik ringan untuk koneksi lambat \[USULAN\].

### 14.12 Konten dan Mikrocopy

- Bahasa Indonesia baku, ringkas, sopan-formal; kapitalisasi kalimat (Title Case hanya untuk nama menu dan dokumen).
- Tombol berupa kata kerja spesifik: Simpan Draft, Cetak Kwitansi, Disposisikan, Setujui, Tolak.
- Istilah konsisten dengan lingkungan BAZNAS: muzakki, mustahik, asnaf, penerimaan, penyaluran, pendayagunaan, UPZ, DSKL; singkatan dijelaskan pada kemunculan pertama atau lewat tooltip.
- Pesan galat menyebut masalah dan cara memperbaikinya (NIK harus 16 digit), bukan Terjadi kesalahan.
- Tanpa emoji, tanpa tanda seru, tanpa humor pada konteks keuangan, dan tanpa sapaan basa-basi di setiap halaman.
- Format: Rp 1.250.000; 15 Nov 2026; 14.30 WIB; terbilang pada dokumen resmi.

### 14.13 Gerak dan Umpan Balik

- Durasi 120 sampai 200 ms dengan easing ease-out, hanya untuk transisi fungsional (drawer, dialog, hover, ekspansi); tanpa animasi dekoratif.
- Setiap aksi memberi umpan balik dalam 100 ms (status tombol, toast, atau perubahan baris); aksi yang lama menampilkan progres.
- Perubahan yang dapat dibatalkan (misalnya memindahkan kartu kanban) menyediakan Urungkan.

### 14.14 Panduan untuk Agen AI Perancang UI

Urutan kerja:

1. Baca Bagian 3, 5, 6, 7, dan 8 untuk peran, izin, dan kebutuhan layar.
2. Bangun fondasi lebih dulu: token (warna, tipografi, jarak, radius, bayangan) sebagai CSS variables dan konfigurasi Tailwind yang dapat dipakai di React; lalu komponen dasar (14.7) lengkap dengan keadaan default, hover, fokus, aktif, nonaktif, error, dan loading.
3. Susun kerangka (launcher, aplikasi, publik), lalu pola halaman (14.8), lalu layar kunci (14.9).
4. Rancang semua keadaan layar: normal, memuat, kosong, galat, tanpa izin, dan dokumen terkunci.
5. Pakai data realistis BAZNAS Kabupaten Cianjur (nama muzakki, UPZ DKM, desa, dan sekolah, nominal rupiah, nomor kwitansi) di semua mockup.

Keluaran yang diminta:

- Dokumen token dan panduan gaya singkat.
- Pustaka komponen dengan seluruh keadaannya.
- Layar prioritas MVP: Login; Launcher (variasi 1 sampai 4 kartu); Dasbor Pengumpulan; Daftar dan Form Penerimaan beserta pratinjau Kwitansi; Daftar Muzakki; Daftar dan Detail Permohonan dengan timeline; Form Penyaluran dan Lembar Disposisi; Laporan Penerimaan; Data UPZ dan SK UPZ; Surat Masuk dan Surat Keluar; Aset; Peran dan Izin; Tracking Permohonan publik; Verifikasi SK UPZ; seluruh dokumen cetak.
- Versi desktop dan ponsel untuk layar yang diprioritaskan di ponsel (14.11).

Kriteria penerimaan:

- Tidak ada satu pun butir pada daftar Yang Harus Dihindari (14.2) yang terlihat.
- Semua teks dan komponen lulus kontras AA; seluruh fungsi dapat dijangkau dengan keyboard.
- Satu pola untuk satu jenis tugas: tidak ada dua gaya tabel, form, atau tombol berbeda untuk tugas yang sama.
- Hanya token yang dipakai; tidak ada warna, jarak, atau radius di luar skala.
- Layar tetap rapi pada data panjang, data kosong, dan nominal besar (misalnya Rp 1.250.000.000).
- Identitas selaras dengan desain launcher yang sudah ada.

### 14.15 Implementasi UI dengan shadcn/ui

Keputusan: UI tidak dirancang lebih dulu di alat desain. Tema dan komponen langsung memakai preset shadcn/ui yang diinisialisasi dengan perintah berikut, pada proyek Laravel dengan React (Inertia):

`yarn dlx shadcn@latest init --preset b3Z3SCCVl2 --base radix --template laravel --pointer`

Dampaknya pada Bagian 14:

- Preset menjadi sumber kebenaran untuk warna dasar, radius, font, dan pustaka ikon. Nilai pada 14.3 dan 14.4 menjadi panduan maksud dan dipakai untuk menilai kecocokan preset, bukan nilai yang dipaksakan.
- Token tambahan di luar preset dibuat di app.css (mode terang dan gelap): brand BAZNAS, empat warna bidang, gold, status (success, warning, info), dan pemetaan warna dana ke token chart preset.
- Hijau BAZNAS hanya menjadi identitas di launcher, login, dan halaman publik; aksi utama di dalam aplikasi memakai primary dari preset.
- Komponen berasal dari shadcn/ui (Radix): Sidebar, Table dengan TanStack Table, Sheet, Dialog, AlertDialog, Command, Calendar, Chart, dan Sonner. Komponen komposit aplikasi berada di resources/js/components/app.
- Daftar yang harus dihindari (14.2), pola halaman (14.8), layar kunci (14.9), dan kriteria penerimaan (14.14) tetap berlaku.
- Brief untuk agen coding ada di dokumen terpisah, Prompt Implementasi UI SISFOZIS dengan shadcn/ui (Preset). Prompt Figma dan FigCraft sebelumnya tidak dipakai lagi.

## Lampiran. Matriks Peran dan Bidang (usulan awal)

Keterangan: K = kelola penuh, L = lihat/laporan, A = approve, - = tidak ada akses.

| Peran | Pengumpulan | Distribusi | Perencanaan dan Keuangan | SDM, Adm, Umum, UPZ | Admin Sistem |
| --- | --- | --- | --- | --- | --- |
| Administrator | K | K | K | K | K |
| Pimpinan | L, A | L, A | L, A | L | - |
| Kepala Pelaksana / Kabid | K di bidangnya, L lainnya | K di bidangnya, L lainnya | K di bidangnya, L lainnya | K di bidangnya, L lainnya | - |
| Staf Pengumpulan | K | - | - | UPZ (K) | - |
| Staf Pendistribusian | - | K | - | - | - |
| Staf Perencanaan | L | L | K (Perencanaan, Laporan, Arsip) | - | - |
| Staf Keuangan | L | L | K (Keuangan) | Aset (L) | - |
| Staf SDM / Admin / Umum | - | - | - | K sesuai tugas | - |
