# TASK.md: Rencana Kerja SISFOZIS BAZNAS Kabupaten Cianjur

Sumber: `docs/PRD.md` (v0.1, 14 Nov 2026), dibandingkan dengan kode per commit `7afdeee` (22 Nov 2026).

## 1. Keputusan yang sudah dikunci (22 Nov 2026)

| # | Keputusan | Dampak |
|---|---|---|
| K1 | `docs/PRD.md` menjadi sumber kebenaran utama. RKAT berbasis pos L2-L7 (PK0/PK1) dipertahankan sebagai PRC-01/PRC-02 dan dijembatani ke `program`/`bidang`/`sumber_dana`. | `AGENTS.md` dan `docs/prd-pkp.md` perlu diperbarui (F0-01). Tidak ada refaktor tabel `rkat_*`. |
| K2 | Uang tetap **BIGINT rupiah utuh** (aturan AGENTS.md), bukan `numeric(18,2)` seperti PRD 10. | Semua tabel baru (penerimaan_item, penyaluran_item, aset, cash_opname, jurnal) memakai `bigInteger`. Catat sebagai penyimpangan PRD di `open-questions.md`. |
| K3 | RBAC memakai **peran tetap**: 5 peran yang ada ditambah peran baru per bidang. Tidak memakai izin granular `modul.sumberdaya.aksi`. | Penegakan lewat middleware `peran:` + pengecekan ulang di Action. Launcher dan sidebar dibangun dari peran. Matriks izin (PRD 14.8) diganti tabel peran x bidang statis. |
| K4 | Desain gabungan: `docs/DESIGN.md` di dalam aplikasi; hijau BAZNAS di launcher, login, dan halaman publik. | Preset shadcn `b3Z3SCCVl2` tidak dijalankan; token diterapkan manual di `app.css`. |
| K5 | Rekomendasi model: campuran Claude + Gemini (lihat bagian 3). | - |
| K6 | Sinkronisasi VPS dan server lokal di luar MVP (Fase 4). Fase 0 hanya Docker + FrankenPHP. | - |
| K7 | 18 peran per bidang (jawaban Q-01, Q-19, Q-21). Semua pengguna internal otomatis memiliki role dasar `amil` (profil, SK amil, tugas) ditambah role spesifik. Role lama `pimpinan` dipecah menjadi `ketua` dan `waka_1..4`. Kabag dapat melihat dashboard bidang lain (view only), kelola penuh di bidangnya. | F0-03, launcher, sidebar, uji RBAC X1-01. |
| K8 | Rute permohonan mengikuti PRD 7.2 (7 langkah lengkap): Pelayanan Mustahik (input/verifikasi) → Disposisi Ketua → Kabag Pendis Pendag (hasil asesmen) → Wakil Ketua II (ajuan besaran bantuan) → Ketua (ACC besaran bantuan) → Staf Pendis/Pendag (meminta uang ke bagian keuangan) → Pelayanan Mustahik (bantuan diterima mustahik). Penugasan Staf Pendis vs Pendag fleksibel. | PDP-T04, PDP-T08. |
| K9 | Rute arsip umum / surat masuk: Admin Arsip → Ketua → Wakil Ketua I/II/III/IV (bisa multi Waka) → instruksi tindak lanjut ke bawahan. | ADM-T02. |
| K10 | Jenis dana = Zakat, Infak/Sedekah, DSKL, **CSR**. Asnaf hanya wajib untuk dana Zakat; dana lain tanpa asnaf. | F0-10 seed `jenis_dana`; PDP-T08 validasi asnaf. |
| K11 | Peringatan "saldo tidak cukup" dihitung per **Kelompok Dana** (Zakat, Infak/Sedekah, DSKL, CSR). | PDP-T08. |
| K12 | Peran `upz` adalah akun pengguna eksternal (pengurus UPZ luar kantor) yang login untuk melihat data setoran dan status SK UPZ-nya sendiri. | F0-03, UPZ-T01, UPZ-T04. |
| K13 | Master Wilayah Kabupaten Cianjur bersumber dari `docs/Daerah Kabupaten Cianjur.csv` (360 desa, kecamatan lengkap di Kab. Cianjur). | F0-09 seed wilayah. |
| K14 | Master Dana, Asnaf, dan Program bersumber dari `docs/Jenis Dana_Sumber Dana_Asnaf_Program.md`: <br>• **Jenis Dana (4):** Zakat, Infak, DSKL, CSR. <br>• **Sumber Dana (9):** Zakat Fitrah (`ZF`), Zakat Mal (`ZM`), Infak Sedekah Tidak Terikat (`IFTT`), Infak Sedekah Terikat (`IFT`), Fidyah (`FDY`), Kurban (`KRB`), Kotak Amal (`KA`), Kafarat (`KFT`), CSR (`CSR`). <br>• **Asnaf (8, khusus Zakat):** Fakir, Miskin, Amil, Mualaf, Riqob, Ibnu Sabil, Gharimin, Fisabilillah. <br>• **Program Bidang Pendistribusian:** Kemanusiaan, Kesehatan, Pendidikan, Dakwah-Advokasi. <br>• **Program Bidang Pendayagunaan:** Ekonomi, Kesehatan, Pendidikan. | F0-10, PDP-T08 (validasi asnaf Zakat & penugasan staf pendis vs pendag). |
| K15 | Penomoran dokumen dibuat **berbasis template konfigurasi** (`config/penomoran.php` dengan token seperti `{jenis}`, `{urut:4}`, `{romawi_bulan}`, `{tahun}`) di atas tabel `nomor_sequence` dengan row-locking. Format baku diabaikan sementara; ketika format resmi telah ditetapkan, format dapat diubah hanya dari 1 baris config tanpa mengubah kode action atau struktur database. | F0-13, ADM-T01, UMM-T01. |
| K17 | **Alur Penyusunan RKAT Kolaboratif 3 Bidang (`docs/Alur penyusunan RKAT BAZNAS Kabupaten Cianjur.md`, 26 Nov 2026):** PKP menetapkan 1 target pengumpulan Neraca (L2) RAKORNAS & menyesuaikan 22 baris aturan Hak Amil (`<= maks`); Bidang Pengumpulan, Pendis/Pendag (7 Kelompok Dana + L4 + L5/2), dan SAU (Hak Amil + Hibah/Saldo Awal -> L7) menyusun rincian kegiatan (`rkat_jenis_kegiatan` & `rkat_kegiatan_rencana`) dan pos pelengkap di modul masing-masing, lalu diverifikasi Kabag PKP (revisi Pengumpulan me-reset status Pendis/SAU ke menunggu tanpa menghapus data). PKP menggabungkan otomatis ke L2–L7 (100% read-only pada versi kolaboratif) dan menambah 9 pos L2 baru (`L2/2.8..2.13`, `L2/3.2`, `L2/5.3`, `L2/5.4`) tanpa merusak data historis 2022–2026 (`mode_penyusunan = 'manual'`). | `PosAnggaranAlurBaruSeeder`, `AlokasiRkatService`, `SinkronisasiNilaiRkatService`, menu `Perencanaan RKAT` per bidang. |

### 1.1 Daftar peran (K7)

Kode usulan; peran `perencanaan`, `keuangan`, dan `admin` yang sudah ada dipertahankan agar PK1 tidak rusak.

| No | Peran | Kode | Bidang / kartu launcher |
|---|---|---|---|
| 1 | Pelayanan Muzakki | `pelayanan_muzakki` | Pengumpulan |
| 2 | Pelayanan Mustahik | `pelayanan_mustahik` | Pendistribusian dan Pendayagunaan |
| 3 | Admin Arsip | `admin_arsip` | SDM, Administrasi dan Umum (SAU) |
| 4 | Staff Perencanaan | `perencanaan` (ada) | PKP |
| 5 | Staff Keuangan | `keuangan` (ada) | PKP |
| 6 | Kepala Bagian SAU | `kabag_sau` | SAU |
| 7 | Kepala Bagian Pengumpulan | `kabag_pengumpulan` | Pengumpulan |
| 8 | Kepala Bagian Pendis Pendag | `kabag_pendis_pendag` | Pendistribusian dan Pendayagunaan |
| 9 | Kepala Bagian PKP | `kabag_pkp` | PKP |
| 10 | Staff Pendistribusian | `staf_pendistribusian` | Pendistribusian dan Pendayagunaan |
| 11 | Staff Pendayagunaan | `staf_pendayagunaan` | Pendistribusian dan Pendayagunaan |
| 12 | Amil | `amil` | Akses khusus (Q-19) |
| 13 | Wakil Ketua I | `waka_1` | Pengumpulan |
| 14 | Wakil Ketua II | `waka_2` | Pendistribusian dan Pendayagunaan |
| 15 | Wakil Ketua III | `waka_3` | PKP |
| 16 | Wakil Ketua IV | `waka_4` | SAU |
| 17 | Ketua | `ketua` | Semua kartu (lihat dan setujui) |
| 18 | UPZ | `upz` | Akses terbatas ke data UPZ sendiri (Q-20) |
| - | Administrator sistem | `admin` (ada) | Semua kartu + Administrasi Sistem |

## 2. Ringkasan kondisi saat ini vs PRD

| Area PRD | Kondisi kode | Status |
|---|---|---|
| Login, rate limit login | Starter kit Laravel (email), rate limit 5x, sesi 120 menit | Sebagian |
| Username login, kebijakan sandi, riwayat login, 2FA | Tidak ada | Belum |
| RBAC | spatie terpasang; 5 peran PKP (`perencanaan`, `pelaporan`, `keuangan`, `pimpinan`, `admin`); middleware `PastikanPeran` | Sebagian |
| Audit log + pola Action | `audit_logs` (jsonb lama/baru, alasan, ip), `App\Actions\Action`, trait `MencatatAudit` | Ada |
| Mesin impor Excel idempoten | `import_jobs`, `import_rows`, pratinjau, galat per baris | Ada |
| Launcher 2x2 | Tidak ada; `/dashboard` berisi ringkasan PKP | Belum |
| Kerangka modul (top bar, sidebar per bidang) | Sidebar starter kit dengan menu PKP | Sebagian |
| Master data (bidang, wilayah, jenis/sumber dana, asnaf, program, metode bayar, rekening, dll) | Tidak ada | Belum |
| Penomoran dokumen (`nomor_sequence`) | Tidak ada | Belum |
| PDF (kop, QR, watermark, terbilang) | dompdf terpasang; dipakai untuk ekspor RKAT saja | Sebagian |
| Token desain | `app.css` masih netral bawaan starter kit; `DESIGN.md` belum diterapkan | Belum |
| Komponen (Table TanStack, Command, Calendar, Sonner, AlertDialog, Chart, Tabs) | Hanya komponen dasar starter kit | Belum |
| Docker | `Dockerfile` (php base, bukan FrankenPHP) + `docker-compose.yml` (app + postgres) | Sebagian |
| PRC-01 RKAT + revisi | Versi Awal/Perubahan, status, VAL-1..6, impor, ekspor, bandingkan, dokumen | Ada (menunggu review) |
| PRC-02 RKAT vs realisasi otomatis | Tidak ada (realisasi PK2 masih placeholder) | Belum |
| PRC-03..06 RENJA, RENSTRA, Laporan Kinerja, tabel kegiatan | Tidak ada | Belum |
| Pengumpulan, UPZ, Pendistribusian, halaman publik | Tidak ada | Belum |
| Administrasi (surat), Umum (aset) | Tidak ada | Belum |
| Keuangan, Arsip, SDM | Tidak ada (`pkp/keuangan` placeholder) | Belum (Fase 2-3) |

Catatan lingkungan: `AGENTS.md` telah diselaraskan ke workspace `D:\IT\sim-baznas` dengan Docker.

## 3. Kompleksitas dan rekomendasi model

| Tingkat | Ciri tugas | Model utama | Alternatif |
|---|---|---|---|
| **S** (sederhana) | Seed, master CRUD berpola sama, halaman statis, pembaruan dokumen, wiring menu | Gemini Flash | Claude Haiku |
| **M** (menengah) | Modul CRUD + Action + uji, laporan dengan filter + ekspor, halaman daftar/detail, scheduler sederhana | Claude Sonnet | Gemini Pro |
| **M-UI** (menengah, visual) | Halaman yang menerjemahkan spesifikasi desain (launcher, dasbor, form padat, halaman publik) | Gemini Pro | Claude Sonnet |
| **L** (kompleks) | Aturan bisnis keuangan, mesin status/persetujuan, maker-checker, penomoran konkuren, PDF presisi, keamanan | Claude Opus | Gemini Pro |
| **XL** (sangat kompleks) | Lintas modul, desain skema baru yang berdampak luas, akuntansi PSAK 109, sinkronisasi data | Claude Opus (dengan rencana tertulis dan review) | - |

Aturan pakai: tugas L/XL selalu mulai dengan rencana singkat yang di-review (alur AGENTS.md langkah 2). Tugas S boleh langsung dikerjakan.

Prioritas: **P-A** jalur kritis, **P-B** wajib sebelum go-live fase, **P-C** boleh menyusul dalam fase yang sama.

---

## 4. Fase 0: Fondasi (MVP)

| ID | Tugas | Kondisi | Kmplx | Model | Prio | Bergantung |
|---|---|---|---|---|---|---|
| F0-01 | Perbarui `AGENTS.md` (PRD.md sumber utama, K1-K6, path dan perintah Docker), tambah bagian SISFOZIS di `docs/open-questions.md` | Selesai | S | Gemini Flash | P-A | - |
| F0-02 | Ganti `Dockerfile` ke FrankenPHP; tambah service queue worker dan scheduler; healthcheck; compose staging | Selesai | M | Claude Sonnet | P-B | - |
| F0-03 | Tambah 18 peran (bagian 1.1) ke `PeranSeeder`, matriks peran x bidang x aksi di `config/sisfozis.php`, cakupan data per UPZ untuk peran `upz` | Selesai | L | Claude Opus | P-A | Q-19, Q-20, Q-21 |
| F0-04 | Kolom standar `created_by` (trait model + isi otomatis di Action) | Selesai | S | Gemini Flash | P-A | - |
| F0-05 | Service maker-checker (pembuat tidak boleh menyetujui) + uji | Selesai | L | Claude Opus | P-A | F0-03 |
| F0-06 | Login username atau email, kebijakan sandi kuat, tabel `login_history`, batas waktu sesi | Selesai | M | Claude Sonnet | P-B | - |
| F0-07 | 2FA wajib untuk peran admin dan keuangan | Selesai | L | Claude Opus | P-B | Q-10 |
| F0-08 | Master `bidang` + seed 4 bidang (kode, warna, urutan) | Selesai | S | Gemini Flash | P-A | - |
| F0-09 | Master wilayah `provinsi/kabupaten/kecamatan/desa` + seed Kab. Cianjur dari `docs/Daerah Kabupaten Cianjur.csv` (360 desa) | Selesai | S | Gemini Flash | P-A | - |
| F0-10 | Master dana: `jenis_dana`, `dana_kelompok`, `sumber_dana` (9 kode), `asnaf` (8), `program` (berdasarkan bidang pendis/pendag) dari `docs/Jenis Dana_Sumber Dana_Asnaf_Program.md` | Selesai | M | Claude Sonnet | P-A | - |
| F0-11 | Master operasional: `metode_bayar`, `rekening_bank`, `jenis_dokumen`, `status_dokumen`, `bentuk_bantuan` | Selesai | S | Gemini Flash | P-A | - |
| F0-12 | Halaman master data generik (tabel + drawer + nonaktifkan, tanpa hard delete) dipakai semua master | Selesai | M | Claude Sonnet | P-B | F0-14 |
| F0-13 | `nomor_sequence` + service penomoran berbasis template dinamis `config/penomoran.php` (kunci baris, reset per tahun, mudah diganti formatnya) + uji konkuren | Selesai | L | Claude Opus | P-A | - |
| F0-14 | Token desain di `app.css` (DESIGN.md di aplikasi; brand hijau, gold, 4 warna bidang, status, warna dana); font Inter + Plus Jakarta Sans | Selesai | M-UI | Gemini Pro | P-A | - |
| F0-15 | Komponen dasar: Table (TanStack), Command, Calendar + date picker id, Sonner, AlertDialog, Tabs, Combobox, Chart | Selesai | M-UI | Gemini Pro | P-A | F0-14 |
| F0-16 | Komponen aplikasi di `components/app`: InputRupiah, InputNIK, StatusBadge, Timeline, EmptyState, PageHeader, PemilihPeriode, UnggahBerkas | Selesai | M | Claude Sonnet | P-A | F0-15 |
| F0-17 | Halaman launcher 2x2 (kartu sesuai peran, chip pengguna, satu kolom di ponsel); redirect setelah login ke launcher | Selesai | M-UI | Gemini Pro | P-A | F0-03, F0-14 |
| F0-18 | Kerangka modul: top bar (tombol grid, chip bidang, notifikasi, profil), sidebar per bidang sesuai peran; pindahkan menu PKP ke kartu Perencanaan | Selesai | M | Claude Sonnet | P-A | F0-17 |
| F0-19 | Desain ulang halaman login sesuai PRD 14.9 | Selesai | S | Gemini Flash | P-B | F0-14 |
| F0-20 | Menu Administrasi Sistem (Pengguna, Master Data, Audit Log); pindahkan `pengaturan/*` | Selesai | S | Gemini Flash | P-B | F0-18 |
| F0-21 | Pengelolaan pengguna: banyak peran, reset sandi, nonaktif; perubahan peran tercatat di audit | Selesai | M | Claude Sonnet | P-B | F0-03 |
| F0-22 | Mesin PDF: layout kop A4/A5, watermark DRAFT, label CETAK ULANG ke-n, QR, helper terbilang, font tertanam di dompdf | Selesai | L | Claude Opus | P-A | Q-15 |
| F0-23 | Penyimpanan berkas privat + URL bertanda tangan + log unduh | Selesai | M | Claude Sonnet | P-A | - |
| F0-24 | Notifikasi in-app: tabel `notifikasi`, popover, tandai dibaca | Selesai | M | Claude Sonnet | P-B | F0-18 |
| F0-25 | Kerangka halaman publik (header logo, maks 720 px, footer hijau tua, `noindex` opsional) | Selesai | M-UI | Gemini Pro | P-B | F0-14 |

**Selesai bila:** peran lintas bidang menampilkan kartu yang benar; audit aktif pada semua Action baru; master data terisi; nomor dokumen unik di bawah beban paralel; PDF contoh lulus cetak hitam-putih.

---

## 5. Fase 1A: MVP inti

### 5.1 Pengumpulan (PGM-01..06)

| ID | Tugas | Kmplx | Model | Prio | Bergantung |
|---|---|---|---|---|---|
| PGM-T01 | Skema + Action `muzakki` (perorangan/lembaga, desa), peringatan duplikat NIK/telepon, masking NIK — **Selesai (22 Nov)** | M | Claude Sonnet | P-A | F0-09, Q-11 |
| PGM-T02 | Halaman daftar, detail (riwayat setoran), tambah cepat inline muzakki — **Selesai (22 Nov)** | M-UI | Gemini Pro | P-A | PGM-T01 |
| PGM-T03 | Skema `penerimaan` + `penerimaan_item`; Action simpan draft dan finalkan; uji aturan — **Selesai (22 Nov)** | L | Claude Opus | P-A | F0-10, F0-11, F0-05 |
| PGM-T04 | Form penerimaan (layar terpenting): item inline dengan Enter, ringkasan lengket, terbilang, autosave draft, keyboard penuh — **Selesai (22 Nov)** | L | Gemini Pro | P-A | PGM-T03, F0-16 |
| PGM-T05 | Kwitansi: nomor per tahun saat final, PDF A5, QR verifikasi, `kwitansi_cetak_log` — **Selesai (22 Nov)** | L | Claude Opus | P-A | F0-13, F0-22 |
| PGM-T06 | Halaman publik verifikasi kwitansi (tanpa data muzakki) — **Selesai (22 Nov)** | S | Gemini Flash | P-B | PGM-T05, F0-25 |
| PGM-T07 | Pembatalan penerimaan: alasan, persetujuan maker-checker, status batal — **Selesai (22 Nov)** | L | Claude Opus | P-A | PGM-T03, Q-06 |
| PGM-T08 | Berita acara penerimaan harian: dibuat dari total hari itu, draft lalu final terkunci, PDF — **Selesai (22 Nov)** | L | Claude Opus | P-A | PGM-T03, Q-06 |
| PGM-T09 | Laporan penerimaan (harian s.d. tahunan), filter jenis/sumber dana, UPZ, metode; ekspor PDF dan Excel — **Selesai (22 Nov)** | M | Claude Sonnet | P-A | PGM-T03 |
| PGM-T10 | Dasbor Pengumpulan (maks 4 angka, tren harian, komposisi per sumber dana, daftar perlu tindakan) — **Selesai (22 Nov)** | M-UI | Gemini Pro | P-C | PGM-T09 |

### 5.2 UPZ (UPZ-01..05)

| ID | Tugas | Kmplx | Model | Prio | Bergantung |
|---|---|---|---|---|---|
| UPZ-T01 | Skema + CRUD `jenis_upz`, `upz` (pengurus, PIC, desa, aktif) — **Selesai (22 Nov)** | M | Claude Sonnet | P-A | F0-09, Q-08 |
| UPZ-T02 | `sk_upz` + unggah berkas SK; status aktif/habis sebagai view — **Selesai (22 Nov)** | M | Claude Sonnet | P-A | UPZ-T01, F0-23 |
| UPZ-T03 | Scheduler notifikasi SK H-90/H-60/H-30 (in-app; email/WA ditunda) — **Selesai (22 Nov)** | M | Claude Sonnet | P-B | UPZ-T02, F0-24 |
| UPZ-T04 | Halaman publik verifikasi SK UPZ (cari nama/desa, rate limit, data non-sensitif) — **Selesai (22 Nov)** | M-UI | Gemini Pro | P-B | UPZ-T02, F0-25 |
| UPZ-T05 | Laporan setoran per UPZ dari data penerimaan — **Selesai (22 Nov)** | S | Gemini Flash | P-B | PGM-T09 |

### 5.3 Pendistribusian dan Pendayagunaan (PDP-01..08)

| ID | Tugas | Kmplx | Model | Prio | Bergantung |
|---|---|---|---|---|---|
| PDP-T01 | Skema + Action `mustahik`, `mustahik_asnaf`, `mustahik_dokumen`, status verifikasi — **Selesai (22 Nov)** | M | Claude Sonnet | P-A | F0-09, F0-10, Q-11 |
| PDP-T02 | Halaman daftar dan detail mustahik + unggah dokumen — **Selesai (22 Nov)** | M-UI | Gemini Pro | P-A | PDP-T01 |
| PDP-T03 | `permohonan` (uuid, kode tracking acak), `permohonan_dokumen`, `permohonan_status_log` (`tampil_publik`) — **Selesai (22 Nov)** | M | Claude Sonnet | P-A | PDP-T01 |
| PDP-T04 | Mesin status disposisi 7 langkah sesuai PRD (K8) + pemetaan ke status publik PDP-05; integrasi minta dana ke Keuangan saat di Staf Pendis/Pendag; tolak dengan alasan; uji tiap transisi — **Selesai (22 Nov)** | XL | Claude Opus | P-A | PDP-T03, F0-05, Q-14 |
| PDP-T05 | Halaman Disposisi Saya + notifikasi disposisi masuk — **Selesai (22 Nov)** | M | Claude Sonnet | P-B | PDP-T04, F0-24 |
| PDP-T06 | Detail permohonan: tab Ringkasan/Dokumen/Disposisi/Riwayat, timeline, aksi sesuai peran dan status — **Selesai (22 Nov)** | L | Gemini Pro | P-A | PDP-T04 |
| PDP-T07 | Lembar disposisi PDF (jalur paraf berjenjang) — **Selesai (22 Nov)** | M | Claude Sonnet | P-B | PDP-T04, F0-22 |
| PDP-T08 | `penyaluran` + `penyaluran_item`: status draft/disetujui/dicairkan/batal, maker-checker, asnaf wajib hanya untuk dana Zakat (K10), peringatan saldo per Kelompok Dana (K11) — **Selesai (22 Nov)** | XL | Claude Opus | P-A | PDP-T04, PGM-T03, Q-16 |
| PDP-T09 | Form penyaluran (UI) — **Selesai (22 Nov)** | M-UI | Gemini Pro | P-A | PDP-T08 |
| PDP-T10 | Berita acara penyaluran harian (pakai ulang pola PGM-T08) — **Selesai (22 Nov)** | M | Claude Sonnet | P-A | PDP-T08 |
| PDP-T11 | Laporan pendistribusian per asnaf, program, wilayah, sumber dana; ekspor — **Selesai (22 Nov)** | M | Claude Sonnet | P-A | PDP-T08 |
| PDP-T12 | Halaman publik tracking: kode + 4 digit akhir telepon, rate limit, captcha, tanpa nama pelaku internal — **Selesai (22 Nov)** | M | Claude Sonnet | P-A | PDP-T04, F0-25, Q-09 |
| PDP-T13 | Dasbor Pendistribusian — **Selesai (22 Nov)** | M-UI | Gemini Pro | P-C | PDP-T11 |

### 5.4 Lintas Fase 1A

| ID | Tugas | Kmplx | Model | Prio |
|---|---|---|---|---|
| X1-01 | Uji fitur RBAC: tiap peran x tiap rute modul baru (ditolak di server, bukan hanya disembunyikan) — **Selesai (22 Nov)** | M | Claude Sonnet | P-A |
| X1-02 | Template impor data lama (muzakki, mustahik, UPZ) memakai mesin impor yang ada — **Selesai (22 Nov)** | L | Claude Opus | P-B |
| X1-03 | Pencarian global Ctrl+K (kwitansi, muzakki, mustahik, kode tracking), hasil difilter peran — **Selesai (22 Nov)** | M | Claude Sonnet | P-C |
| X1-04 | Seed data uji 5 tahun + uji kinerja laporan bulanan < 5 detik — **Selesai (22 Nov)** | M | Claude Sonnet | P-B |

**Selesai bila:** operasional harian Pengumpulan dan Pendistribusian berjalan; laporan periodik keluar otomatis; tracking publik berfungsi.

---

## 6. Fase 1B: MVP pendukung

### 6.1 Perencanaan dan Laporan Kinerja (PRC-01..06)

| ID | Tugas | Kmplx | Model | Prio | Bergantung |
|---|---|---|---|---|---|
| PRC-T01 | Review dan merge PK1 (RKAT) yang menunggu review; selesaikan temuan 25-34 — **Selesai (22 Nov)** | M | Claude Sonnet | P-A | - |
| PRC-T02 | Rancang jembatan pos L2-L7 ke `bidang`, `jenis_dana`, `sumber_dana`, `program` (= tabel kegiatan PRC-06); rencana tertulis dulu — **Selesai (24 Nov)** | XL | Claude Opus | P-A | F0-10, Q-12 |
| PRC-T03 | Tabel `tahun_anggaran` (draft/berjalan/tutup) dikaitkan ke `rkat_versions.tahun` — **Selesai (24 Nov)** | M | Claude Sonnet | P-B | PRC-T02 |
| PRC-T04 | View `v_realisasi_rkat` dari penerimaan dan penyaluran + halaman RKAT vs realisasi; rollup diuji terhadap penjumlahan sumber — **Selesai (24 Nov)** | L | Claude Opus | P-A | PRC-T02, PGM-T03, PDP-T08 |
| PRC-T05 | Master `indikator` + RENSTRA (`renstra`, `renstra_sasaran`, `renstra_target`) | M | Claude Sonnet | P-B | - |
| PRC-T06 | RENJA (`renja`, `renja_kegiatan`, `renja_target`) per tahun dan bidang | M | Claude Sonnet | P-B | PRC-T05 |
| PRC-T07 | Laporan Kinerja tahunan dan 5 tahunan: capaian vs target, realisasi anggaran, narasi, PDF | L | Claude Opus | P-B | PRC-T04, PRC-T06, Q-17 |
| PRC-T08 | Putuskan nasib placeholder PK2-PK5 (realisasi manual, kinerja, keuangan, dashboard) — **Selesai (24 Nov)** | S | Gemini Flash | P-A | Q-12 |

### 6.2 Administrasi / Surat (ADM-01..03)

| ID | Tugas | Kmplx | Model | Prio | Bergantung |
|---|---|---|---|---|---|
| ADM-T01 | Master `jenis_surat` (format nomor, pisah urutan), `template_surat` (editor placeholder `{{variabel}}`) — **Selesai (24 Nov)** | S | Gemini Flash | P-A | Q-02 |
| ADM-T02 | Surat masuk: nomor agenda, unggah, status log, disposisi Admin Arsip → Ketua → multi Waka I-IV → instruksi bawahan (K9), selesai otomatis per cabang, PDF Lembar Disposisi, notifikasi — **Selesai (24 Nov)** | L | Claude Opus | P-A | F0-23, F0-24 |
| ADM-T03 | Surat keluar: render template berplaceholder, nomor otomatis saat disetujui (global & pisah urutan SK), Maker-Checker, PDF kop serif + QR verifikasi publik & cetak ulang — **Selesai (24 Nov)** | L | Claude Opus | P-A | ADM-T01, F0-13, F0-22 |
| ADM-T04 | Arsip dan pencarian surat (full-text Postgres), ekspor Buku Agenda Excel/PDF, Ctrl+K, & Dasbor Bidang Umum — **Selesai (24 Nov)** | M | Claude Sonnet | P-B | ADM-T02 |

### 6.3 Umum / Aset (UMM-01..03)

| ID | Tugas | Kmplx | Model | Prio | Bergantung |
|---|---|---|---|---|---|
| UMM-T01 | `kategori_aset` (umur ekonomis, residu %), `kondisi_aset`, `aset` + nomor aset otomatis | M | Claude Sonnet | P-A | F0-13, Q-02 |
| UMM-T02 | Cetak label aset QR (stiker) | M | Claude Sonnet | P-B | UMM-T01, F0-22 |
| UMM-T03 | `periode_akuntansi` + penyusutan garis lurus bulanan (scheduler) + view nilai buku; uji pembulatan rupiah | L | Claude Opus | P-B | UMM-T01, Q-18 |

**Selesai bila:** Laporan Kinerja dihasilkan dari data transaksi.

---

## 7. Fase 2: Keuangan dan Arsip (bukan MVP)

| ID | Tugas | Kmplx | Model |
|---|---|---|---|
| KEU-T01 | `coa` (PSAK 109, per kelompok dana) + seed bersama akuntan | XL | Claude Opus |
| KEU-T02 | BKU Kas Masuk (view dari penerimaan_item) | M | Claude Sonnet |
| KEU-T03 | `pengeluaran_kas` + BKU Kas Keluar + kwitansi pengeluaran | L | Claude Opus |
| KEU-T04 | Cash opname per pecahan + berita acara | M | Claude Sonnet |
| KEU-T05 | Template jurnal + jurnal otomatis dari transaksi + jurnal manual + posting + kunci periode + jurnal balik | XL | Claude Opus |
| KEU-T06 | Laporan PSAK 109 (posisi keuangan, perubahan dana, arus kas, CaLK) | XL | Claude Opus |
| ARS-T01 | `kategori_dokumen`, `dokumen_arsip`, unggah dokumen disahkan — **Selesai (25 Nov)** | M | Claude Sonnet |
| ARS-T02 | `tautan_akses` (token hash, cabut, kedaluwarsa, noindex, log akses) — **Selesai (25 Nov)** | L | Claude Opus |
| ARS-T03 | Portal arsip gaya PPID + `akses_dokumen_log` — **Selesai (25 Nov)** | M-UI | Gemini Pro |
| LKP-T01 | Dasbor kinerja publik via tautan khusus (agregat saja) + Beranda Layanan Publik (`/publik`) — **Selesai (25 Nov)** | M-UI | Gemini Pro |

## 8. Fase 3: SDM (bukan MVP)

| ID | Tugas | Kmplx | Model |
|---|---|---|---|
| SDM-T01 | `jabatan`, `amil` + dokumen; tautkan `users.amil_id` | M | Claude Sonnet |
| SDM-T02 | Absensi web (masuk/pulang, izin, cuti, sakit), geofence opsional | L | Claude Opus |
| SDM-T03 | Kanban tugas (papan, kolom, tugas, seret-lepas + menu keyboard, urungkan) | M-UI | Gemini Pro |
| SDM-T04 | Laporan kehadiran bulanan (view) | S | Gemini Flash |
| SDM-T05 | KPI amil (indikator, bobot, skor otomatis + penilaian atasan) | L | Claude Opus |

## 9. Fase 4: Pasca-MVP

| ID | Tugas | Kmplx | Model |
|---|---|---|---|
| P4-01 | Spike lalu implementasi sinkronisasi VPS dan server lokal (resolusi konflik, urutan nomor dokumen) | XL | Claude Opus |
| P4-02 | Notifikasi email dan WhatsApp (SK UPZ, disposisi) | M | Claude Sonnet |
| P4-03 | Aplikasi Flutter absensi/tugas | XL | Gemini Pro |

---

## 10. Pertanyaan terbuka (perlu jawaban sebelum tugas terkait)

Asumsi konservatif dipakai sampai dijawab; jangan ditebak (AGENTS.md aturan 8).

| # | Pertanyaan | Memblokir | Asumsi sementara |
|---|---|---|---|
| ~~Q-01~~ | Daftar peran per bidang | F0-03 | **TERJAWAB 22 Nov** → K7, bagian 1.1 |
| ~~Q-02~~ | Format nomor kwitansi, berita acara, penyaluran, surat keluar, agenda surat masuk, aset | F0-13, ADM-T01, UMM-T01 | **TERJAWAB 22 Nov** → K15 (dibuat modular berbasis konfigurasi template `config/penomoran.php` sehingga dapat diubah sewaktu-waktu tanpa mengubah kode/database) |
| ~~Q-03~~ | Rute permohonan | PDP-T04 | **TERJAWAB 22 Nov** → K8 (7 langkah sesuai PRD 7.2) |
| ~~Q-04~~ | Kesesuaian sumber dana dengan asnaf | PDP-T08 | **TERJAWAB 22 Nov** → K10 (asnaf wajib hanya untuk Zakat) |
| ~~Q-05~~ | Saldo untuk peringatan "saldo tidak cukup": per sumber dana atau per kelompok dana? | PDP-T08 | **TERJAWAB 22 Nov** → K11 (per Kelompok Dana) |
| Q-06 | Bila berita acara hari itu sudah final: boleh ada penerimaan susulan bertanggal sama? Bagaimana pembatalan transaksi di BA final? | PGM-T07, PGM-T08 | **DIKUNCI KONSERVATIF (22 Nov):** Tanggal terkunci; pembatalan Maker-Checker menerbitkan BA Revisi bernomor |
| ~~Q-07~~ | Sumber data wilayah (kode Kemendagri/BPS) untuk kecamatan dan desa Cianjur | F0-09 | **TERJAWAB 22 Nov** → `docs/Daerah Kabupaten Cianjur.csv` (K13) |
| Q-08 | Arti DIBALEKA dan daftar jenis UPZ final | UPZ-T01 | **DIKUNCI KONSERVATIF (22 Nov):** Seed 7 jenis UPZ (`DKM`, `DESA`, `DIBALEKA`, `PERUSAHAAN`, `SEKOLAH`, `YAYASAN`, `INSTANSI`) via Master Data |
| Q-09 | Penyedia captcha (server tanpa akses luar?) | PDP-T12 | **DIKUNCI KONSERVATIF (22 Nov):** Rate limit `10/menit` + `kode_tracking` + 4 digit akhir telepon; captcha menyusul pasca-MVP |
| Q-10 | Metode 2FA (aplikasi TOTP, email OTP) | F0-07 | **DIKUNCI KONSERVATIF (22 Nov):** RFC 6238 TOTP wajib untuk `admin` dan `keuangan` |
| Q-11 | NIK: enkripsi saat disimpan (UU PDP) bertentangan dengan unik/cari. Setuju kolom hash untuk cari + enkripsi nilai? | PGM-T01, PDP-T01 | **DIKUNCI KONSERVATIF (22 Nov):** Simpan terenkripsi (`nik_encrypted`) + `nik_hash` HMAC-SHA256 unik + `nik_masked` |
| ~~Q-12~~ | Realisasi historis 2022-2025 (sebelum go-live) masih perlu diinput manual/impor (rencana PK2 lama)? | PRC-T02, PRC-T08 | **DIKUNCI (24 Nov 2026):** Ya, lewat input/impor `realisasi_manual`; data transaksi sejak go-live otomatis |
| ~~Q-13~~ | Rincian sumber dana per jenis dana, daftar asnaf, dan program per bidang | F0-10 | **TERJAWAB 22 Nov** → `docs/Jenis Dana_Sumber Dana_Asnaf_Program.md` (K14) |
| Q-14 | Pemetaan 7 langkah rute K8 ke 7 status publik (Diajukan, Disposisi, Verifikasi, Acc Pimpinan, Disetujui, Dicairkan, Ditolak) | PDP-T04 | **DIKUNCI KONSERVATIF (22 Nov):** 8 status internal → 7 status publik (`minta_keuangan` → `disetujui`) |
| Q-15 | Aset kop surat: logo resmi, alamat, kontak, penandatangan per dokumen | F0-22 | Placeholder sampai dikirim |
| Q-16 | Bantuan barang: dicatat dengan nilai rupiah taksiran? Masuk saldo? | PDP-T08 | **DIKUNCI KONSERVATIF (22 Nov):** Wajib uraian barang & taksiran rupiah `BIGINT > 0`, mengurangi saldo Kelompok Dana |
| ~~Q-17~~ | Capaian indikator non-keuangan (jumlah mustahik, dll) dihitung otomatis atau diinput? | PRC-T07 | **DIKUNCI (24 Nov 2026):** Otomatis bila terpetakan ke data transaksi, selain itu input manual |
| ~~Q-18~~ | Umur ekonomis dan nilai residu per kategori aset; penyusutan mulai bulan perolehan atau bulan berikutnya | UMM-T03 | **DIKUNCI (24 Nov 2026):** Mulai bulan berikutnya, nilai residu default 0% (dapat disesuaikan per kategori) |
| ~~Q-19~~ | Peran `amil`: akses apa saja? Apakah semua pengguna otomatis `amil`? | F0-03 | **TERJAWAB 22 Nov** → K7 (semua user internal otomatis punya role `amil` untuk profil, SK amil, tugas pribadi) |
| ~~Q-20~~ | Peran `upz`: siapa dan aksesnya apa? | F0-03, UPZ-T01 | **TERJAWAB 22 Nov** → K12 (pengguna eksternal login di luar kantor untuk pantau setoran & SK UPZ sendiri) |
| ~~Q-21~~ | Peran lama `pimpinan`, pemecahan peran, dan hak lihat Kabag lintas bidang | F0-03, PRC-T01 | **TERJAWAB 22 Nov** → K7 (`pimpinan` dipecah jadi `ketua` & `waka_1..4`; Kabag view dashboard bidang lain) |
| ~~Q-22~~ | Rute permohonan 7 langkah PRD, staf pendis/pendag, dan posisi pencairan keuangan | PDP-T04, PDP-T08 | **TERJAWAB 22 Nov** → K8 (7 langkah PRD, staf fleksibel, minta dana ke keuangan saat di staf setelah ACC Ketua) |
| ~~Q-23~~ | Rute arsip umum / surat masuk | ADM-T02 | **TERJAWAB 22 Nov** → K9 (Admin Arsip → Ketua → multi Waka I-IV → instruksi tindak lanjut ke bawahan) |

## 11. Urutan kerja yang disarankan

1. F0-01, PRC-T01 (bereskan dokumen dan PR yang menunggu).
2. Jalur kritis Fase 0: F0-03, F0-04, F0-05, F0-08..F0-11, F0-13, F0-14..F0-18, F0-22, F0-23.
3. Fase 1A paralel dua jalur: Pengumpulan + UPZ, dan Pendistribusian (semua blocker alur & peran sudah terpecahkan).
4. Fase 1B: PRC-T02 lebih dulu (desain jembatan), lalu ADM dan UMM paralel.
5. Satu tugas = satu cabang = satu PR kecil (AGENTS.md aturan 9).

## 12. Berkas yang ditunggu dari pengguna

*Semua berkas data master dan kebijakan alur yang dibutuhkan untuk memulai implementasi Fase 0 dan Fase 1A sudah lengkap dan terpecahkan! Format penomoran dokumen resmi dapat disusulkan kemudian dan langsung dimasukkan ke `config/penomoran.php` tanpa hambatan.*

