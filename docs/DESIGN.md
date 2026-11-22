---
version: alpha
name: SISFOZIS Dashboard
description: >-
  Bahasa visual dashboard produk modern yang bersih: kanvas abu kebiruan muda,
  kartu putih berradius besar dengan bayangan sangat lembut, satu warna aksi
  biru, status hijau dan merah, data padat tetapi lapang. Diadaptasi dari
  dashboard referensi (Shopeers) untuk aplikasi pengelolaan dana ZIS BAZNAS
  Kabupaten Cianjur. Seluruh nilai diperkirakan dari gambar raster.

colors:
  canvas: "#F3F5F9"
  surface: "#FFFFFF"
  surface-subtle: "#F8F9FC"
  sidebar: "#F8F9FC"
  ink: "#101828"
  ink-secondary: "#475467"
  ink-muted: "#667085"
  hairline: "#E7EAF1"
  hairline-strong: "#D0D5DD"
  primary: "#2F5BEA"
  primary-hover: "#2549CC"
  primary-active: "#1F3DAE"
  primary-soft: "#EAF0FF"
  on-primary: "#FFFFFF"
  success: "#137A3F"
  success-soft: "#E6F6EC"
  danger: "#C4312C"
  danger-soft: "#FDECEC"
  warning: "#A85F00"
  warning-soft: "#FFF3DC"
  chart-1: "#2F5BEA"
  chart-2: "#0D9488"
  chart-3: "#F59E0B"
  chart-4: "#64748B"
  chart-5: "#D0D5DD"
  chart-bar-idle: "#E4E7EC"
  overlay: "rgba(16, 24, 40, 0.45)"
  # Identitas BAZNAS (hanya launcher, login, halaman publik)
  brand: "#0A7A3C"
  brand-deep: "#064E2B"
  gold: "#F7B500"
  bidang-pengumpulan: "#0A7A3C"
  bidang-distribusi: "#F7B500"
  bidang-umum: "#1D4ED8"
  bidang-pkp: "#5B21B6"

typography:
  font-sans: "Inter Variable, -apple-system, system-ui, sans-serif"
  hero-metric: { fontSize: 40px, lineHeight: 48px, fontWeight: 600, letterSpacing: -0.02em }
  metric: { fontSize: 24px, lineHeight: 32px, fontWeight: 600, letterSpacing: -0.01em }
  h1: { fontSize: 22px, lineHeight: 30px, fontWeight: 600, letterSpacing: -0.01em }
  h2: { fontSize: 16px, lineHeight: 24px, fontWeight: 600 }
  body: { fontSize: 14px, lineHeight: 20px, fontWeight: 400 }
  body-medium: { fontSize: 14px, lineHeight: 20px, fontWeight: 500 }
  small: { fontSize: 13px, lineHeight: 18px, fontWeight: 400 }
  caption: { fontSize: 12px, lineHeight: 16px, fontWeight: 500 }
  table-head: { fontSize: 11px, lineHeight: 16px, fontWeight: 600, letterSpacing: 0.04em, textTransform: uppercase }

rounded:
  xs: 4px
  sm: 6px
  md: 8px
  lg: 12px
  xl: 16px
  full: 9999px

spacing:
  unit: 4px
  xs: 4px
  sm: 8px
  md: 12px
  lg: 16px
  xl: 20px
  2xl: 24px
  3xl: 32px

layout:
  sidebar-width: 248px
  sidebar-collapsed: 64px
  topbar-height: 56px
  page-padding: 24px
  card-padding: 20px
  kpi-padding: 16px
  grid-gap: 16px
  content-max: 1440px
  control-height: 36px
  control-height-sm: 32px
  row-height: 48px
  row-height-compact: 40px

elevation:
  card: "0 1px 2px rgba(16, 24, 40, 0.04), 0 4px 16px rgba(16, 24, 40, 0.04)"
  popover: "0 8px 24px rgba(16, 24, 40, 0.12)"
  tooltip: "0 4px 12px rgba(16, 24, 40, 0.12)"
  focus-ring: "0 0 0 3px rgba(47, 91, 234, 0.20)"

components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.on-primary}"
    typography: "{typography.body-medium}"
    rounded: "{rounded.md}"
    height: "{layout.control-height}"
    padding: "0 14px"
    hoverBackgroundColor: "{colors.primary-hover}"
    activeBackgroundColor: "{colors.primary-active}"
  button-secondary:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    border: "1px solid {colors.hairline-strong}"
    typography: "{typography.body-medium}"
    rounded: "{rounded.md}"
    height: "{layout.control-height}"
    hoverBackgroundColor: "{colors.surface-subtle}"
  button-ghost:
    backgroundColor: transparent
    textColor: "{colors.ink-secondary}"
    rounded: "{rounded.md}"
    hoverBackgroundColor: "{colors.canvas}"
  input-search:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    placeholderColor: "{colors.ink-muted}"
    border: "1px solid {colors.hairline-strong}"
    rounded: "{rounded.md}"
    height: "{layout.control-height}"
    focusRing: "{elevation.focus-ring}"
  card:
    backgroundColor: "{colors.surface}"
    border: "1px solid {colors.hairline}"
    rounded: "{rounded.xl}"
    padding: "{layout.card-padding}"
    shadow: "{elevation.card}"
  kpi-card:
    extends: card
    padding: "{layout.kpi-padding}"
  nested-panel:
    backgroundColor: "{colors.surface}"
    border: "1px solid {colors.hairline}"
    rounded: "{rounded.lg}"
    padding: "{spacing.md}"
  stat-tile:
    backgroundColor: "{colors.surface}"
    border: "1px solid {colors.hairline}"
    borderBottom: "3px solid (warna kategori)"
    rounded: "{rounded.md}"
  badge-delta-up:
    backgroundColor: "{colors.success-soft}"
    textColor: "{colors.success}"
    typography: "{typography.caption}"
    rounded: "{rounded.sm}"
    padding: "2px 6px"
  badge-delta-down:
    backgroundColor: "{colors.danger-soft}"
    textColor: "{colors.danger}"
    typography: "{typography.caption}"
    rounded: "{rounded.sm}"
    padding: "2px 6px"
  badge-count:
    backgroundColor: "{colors.success-soft}"
    textColor: "{colors.success}"
    typography: "{typography.caption}"
    rounded: "{rounded.full}"
  tag-chip:
    backgroundColor: "{colors.surface-subtle}"
    textColor: "{colors.ink-secondary}"
    typography: "{typography.caption}"
    rounded: "{rounded.sm}"
    padding: "2px 8px"
  nav-item:
    textColor: "{colors.ink-secondary}"
    typography: "{typography.body-medium}"
    rounded: "{rounded.md}"
    height: 40px
    hoverBackgroundColor: "{colors.canvas}"
  nav-item-active:
    backgroundColor: "{colors.primary-soft}"
    textColor: "{colors.primary-hover}"
    indicator: "3px bar {colors.primary} di tepi kiri"
  table-row:
    height: "{layout.row-height}"
    borderBottom: "1px solid {colors.hairline}"
    hoverBackgroundColor: "{colors.surface-subtle}"
  chart-tooltip:
    backgroundColor: "{colors.surface}"
    border: "1px solid {colors.hairline}"
    rounded: "{rounded.lg}"
    shadow: "{elevation.tooltip}"
    typography: "{typography.caption}"
  sheet:
    backgroundColor: "{colors.surface}"
    width: 480px
    rounded: "{rounded.xl} (sisi dalam)"
    overlay: "{colors.overlay}"
---

# SISFOZIS Dashboard Design System

## Overview

Kepribadian: bersih, tenang, modern, dan terorganisasi. Kanvas abu kebiruan yang sangat muda menjadi lantai halaman; konten hidup di atas kartu putih berradius besar dengan garis tipis dan bayangan nyaris tak terlihat. Hanya satu warna aksi (biru), dipakai untuk tombol utama, item navigasi aktif, tautan, dan seri data utama. Warna status (hijau, merah, oranye) muncul kecil dan bermakna, terutama sebagai badge perubahan.

Sumber: dashboard e-commerce referensi (Shopeers) yang diberikan sebagai gambar. Gambarnya raster, jadi semua warna, ukuran, dan radius di dokumen ini adalah perkiraan. Verifikasi dengan eyedropper bila perlu persis.

Ciri khas yang harus dipertahankan:

- Hierarki dari ukuran dan bobot huruf, bukan dari warna: angka metrik besar dan tebal, label kecil berwarna redup.
- Kartu berradius besar (16 px) dengan padding lega (16 sampai 20 px) dan jarak antar kartu 16 px.
- Kepadatan data tinggi tanpa terasa sesak: tabel dan daftar memakai baris 48 px dengan garis pemisah tipis, tanpa zebra.
- Detail kecil yang rapi: ikon garis 16 sampai 18 px, badge perubahan dengan panah, angka rata kanan, tooltip grafik berkartu.
- Grafik minimal: garis biru dengan isian gradasi lembut, batang abu dengan satu batang disorot.

## Layout

Struktur tetap: sidebar kiri, area utama di kanan.

| Region | Spesifikasi |
|---|---|
| Sidebar | Lebar 248 px (terlipat 64 px), permukaan sidebar (#F8F9FC) dengan garis kanan 1 px. Atas: logo dan tombol lipat. Tengah: daftar navigasi. Bawah: Pengaturan dan Bantuan |
| Top bar | Tinggi 56 px di area utama. Kiri: kolom pencarian global dengan petunjuk pintasan (Ctrl+K). Kanan: ikon notifikasi dan avatar bulat 28 px |
| Header halaman | Judul H1 di kiri. Kanan: pemilih rentang tanggal, pemilih periode cepat (Last 30 days), tombol outline, tombol utama berikon (Export) |
| Baris KPI | 4 kartu sama lebar, tinggi sekitar 112 px |
| Grid utama | Dua kolom: kiri 2/3 (kartu metrik utama dengan grafik, lalu tabel), kanan 1/3 (beberapa kartu ringkas bertumpuk) |

Aturan grid:

- Padding halaman 24 px; lebar konten maksimal 1440 px; jarak antar kartu 16 px.
- Lebar 1280 px ke atas: 4 kolom KPI dan grid 2/3 + 1/3. Antara 1024 dan 1279 px: kolom kanan turun di bawah kolom kiri, KPI menjadi 2 kolom. Di bawah 768 px: satu kolom, sidebar menjadi Sheet.
- Sidebar memakai satu tingkat grup: item induk dengan ikon, grup yang dapat dilipat (chevron) berisi sub-item tanpa ikon dengan garis panduan vertikal di kiri.

## Colors

- Kanvas halaman `canvas`, semua panel `surface` putih. Garis tipis `hairline`; batas kontrol `hairline-strong`.
- Teks: `ink` untuk judul dan angka; `ink-secondary` untuk isi; `ink-muted` untuk label, keterangan, dan placeholder.
- Aksi: `primary` untuk tombol utama, item aktif (latar `primary-soft`, teks biru), seri grafik utama, dan batang yang disorot. Hover dan pressed memakai `primary-hover` dan `primary-active`.
- Status: `success` dan `success-soft` untuk kenaikan dan hitungan; `danger` untuk penurunan; `warning` untuk perhatian. Status selalu disertai panah atau label, tidak hanya warna.
- Seri data: `chart-1` sampai `chart-5`; seri kategori dana: zakat = `chart-1`, infak dan sedekah = `chart-2`, DSKL = `chart-3`, lainnya = `chart-4`. Pemetaan ini tetap di seluruh aplikasi.
- Identitas BAZNAS (`brand`, `brand-deep`, `gold`, empat warna `bidang-*`) hanya dipakai di launcher, login, dan halaman publik, serta sebagai penanda kecil bidang di top bar. Jangan dipakai sebagai warna aksi di dalam aplikasi.
- Overlay Sheet dan dialog memakai `overlay`.

## Typography

Keluarga huruf: sans netral sejenis Inter. Referensi tampak memakai sans geometris netral; pakai Inter Variable (Fontsource) atau font dari preset bila sepadan. Angka memakai tabular-nums.

| Token | Ukuran / tinggi baris | Bobot | Pemakaian |
|---|---|---|---|
| hero-metric | 40 / 48 | 600 | Angka utama di kartu besar (mis. total penerimaan) |
| metric | 24 / 32 | 600 | Angka di kartu KPI |
| h1 | 22 / 30 | 600 | Judul halaman |
| h2 | 16 / 24 | 600 | Judul kartu |
| body | 14 / 20 | 400 | Isi, sel tabel |
| body-medium | 14 / 20 | 500 | Tombol, item navigasi, nama di tabel |
| small | 13 / 18 | 400 | Teks pendukung padat |
| caption | 12 / 16 | 500 | Label KPI, badge, keterangan, sumbu grafik |
| table-head | 11 / 16 | 600, huruf kapital, tracking 0,04em | Kepala kolom tabel |

Hanya bobot 400, 500, 600. Tanpa bobot 700 ke atas.

## Elevation & Depth

Kedalaman sangat halus. Kartu: garis 1 px `hairline` ditambah `elevation.card` (dua lapis bayangan hitam kebiruan 4%). Popover dan menu memakai `elevation.popover`; tooltip grafik memakai `elevation.tooltip`. Sidebar dan top bar tanpa bayangan. Fokus memakai cincin 3 px biru 20% ditambah batas biru pada kontrol. Jangan menambah bayangan tebal atau efek glow.

## Shapes

| Token | Nilai | Dipakai untuk |
|---|---|---|
| xs | 4 px | Aksen kecil |
| sm | 6 px | Badge perubahan, chip tag, item menu |
| md | 8 px | Tombol, input, item navigasi, stat tile |
| lg | 12 px | Panel bersarang, tooltip grafik, kartu pratinjau di Sheet |
| xl | 16 px | Kartu utama, Sheet, dialog |
| full | pill | Badge hitungan di navigasi, avatar |

Ikon produk di sel tabel berupa kotak 28 px berradius 8 px dengan latar abu muda.

## Components

- **Sidebar item**: tinggi 40 px, ikon 18 px dan label body-medium. Aktif: latar `primary-soft`, teks biru, bar biru 3 px di tepi kiri. Badge hitungan hijau berbentuk pill di kanan untuk jumlah yang perlu tindakan.
- **Top bar**: kolom pencarian 36 px dengan ikon kaca pembesar, placeholder redup, dan petunjuk pintasan di kanan; ikon notifikasi dan avatar.
- **Header halaman**: H1 di kiri; di kanan pemilih rentang tanggal (ikon kalender), pemilih periode cepat, satu tombol outline, dan satu tombol utama biru berikon.
- **Kartu KPI**: baris atas label caption berwarna redup dengan ikon kecil biru di kanan; tengah angka metric dan badge perubahan; bawah caption pembanding (mis. vs periode sebelumnya).
- **Kartu metrik utama**: label, hero-metric, badge perubahan, dan keterangan di kiri; grafik area di kanan dengan sumbu tipis dan tooltip berkartu saat hover (tanggal dan dua seri berpenanda kotak berwarna). Di bawahnya panel bersarang berisi tiga stat tile.
- **Stat tile**: ikon kecil, label, angka; garis bawah 3 px berwarna kategori.
- **Grafik batang**: tujuh batang berujung membulat, abu `chart-bar-idle`; satu batang disorot `primary` dengan label nilai melayang di atasnya; label hari di bawah.
- **Tabel data**: kepala table-head; baris 48 px; sel pertama muted untuk ID; sel nama dengan ikon kotak 28 px; angka perubahan berwarna dengan panah; tanpa zebra; menu titik tiga di kepala kartu.
- **Sheet samping**: lebar 480 px; judul dan tombol tutup; daftar kartu dengan pratinjau di kiri, judul semibold, deskripsi redup, chip tag, dan tombol kecil di kanan bawah.
- **Tag chip**: latar `surface-subtle`, teks caption, radius 6 px.
- **Tombol**: utama biru terisi, sekunder outline, ghost; tinggi 36 px, ikon 16 px di kiri.

## Data Visualization

- Garis tren: tebal 2 px, warna `chart-1`, isian gradasi dari biru 18% ke transparan; sumbu dan garis bantu tipis `hairline`; penanda titik aktif dan garis vertikal putus-putus saat hover.
- Batang: sudut atas membulat, satu seri disorot; label nilai pada batang yang disorot.
- Panel bersarang tiga kategori memakai garis bawah berwarna, bukan diagram pai.
- Format angka ringkas pada sumbu (15K, 10K); tooltip menampilkan nilai penuh.
- Tabel atau ekspor selalu tersedia sebagai pengganti grafik.

## Responsive

- 1280 px ke atas: tata letak penuh. 1024 sampai 1279 px: sidebar terlipat ikon, kolom kanan turun. Di bawah 768 px: sidebar menjadi Sheet, KPI satu atau dua kolom, tabel menjadi kartu daftar.
- Target sentuh 44 px di mobile; kontrol 36 px di desktop.
- Referensi tidak menampilkan tampilan mobile; turunan mobile mengikuti token yang sama.

## Do's and Don'ts

Do:

- Gunakan satu warna aksi (`primary`) per layar dan biarkan sisanya netral.
- Bangun hierarki dari ukuran dan bobot huruf; pertahankan label redup dan angka tebal.
- Pakai kartu putih berradius 16 px dengan bayangan lembut dan jarak 16 px.
- Tampilkan perubahan sebagai badge kecil dengan panah dan warna status.
- Pakai data nyata dan realistis di semua kartu dan grafik.

Don't:

- Jangan memakai gradasi mencolok, glow, glassmorphism, atau kartu miring 3D.
- Jangan menambah kartu promosi, banner upgrade, orb 3D, atau asisten AI hiasan.
- Jangan membuat dasbor dari belasan kartu angka tanpa makna; maksimal 4 KPI.
- Jangan memakai pai atau donat; jangan memakai lebih dari tiga bobot huruf.
- Jangan memakai warna bidang atau hijau BAZNAS sebagai warna tombol di dalam aplikasi.
- Jangan menimpa gaya komponen dengan kelas ad hoc per halaman; ubah di varian komponen.

## Adaptasi untuk SISFOZIS

| Elemen referensi | Padanan di SISFOZIS |
|---|---|
| 4 kartu KPI (Page Views, Visitors, Click, Orders) | Penerimaan Hari Ini, Penyaluran Hari Ini, Permohonan Menunggu, SK UPZ Segera Habis (nilai dan pembanding dari data) |
| Total Profit (angka besar dan grafik area) | Total Penerimaan (Rp) dengan tren harian atau bulanan |
| Tiga stat tile (Retailers, Distributors, Wholesalers) | Zakat, Infak dan Sedekah, DSKL dengan garis bawah warna dana |
| Most Day Active (batang per hari) | Penerimaan per Hari dalam Seminggu, hari tertinggi disorot |
| Repeat Customer Rate (gauge) | Realisasi vs RKAT sebagai Progress horizontal (hindari gauge dan donat) |
| AI Assistant | Perlu Tindakan: permohonan menunggu disposisi, SK UPZ segera habis, surat belum didisposisi |
| Best Selling Products (tabel) | Penerimaan Terbaru: No. Kwitansi, Muzakki, Sumber Dana, Nominal, Status |
| Add Widget (Sheet) | Pola Sheet untuk lihat cepat dan master data (bukan fitur widget) |
| Sidebar dengan grup Finances dan badge Orders | Sidebar per modul dengan grup dan sub-item; badge hitungan untuk permohonan menunggu |
| Pemilih tanggal dan tombol Export | Pemilih periode laporan (Harian sampai Tahunan) dan tombol Export (Excel, PDF) |

Top bar SISFOZIS menambahkan tombol grid (kembali ke launcher) dan chip modul di kiri sebelum pencarian. Launcher, login, dan halaman publik tidak ada di referensi dan mengikuti desain launcher yang sudah disetujui, dengan token identitas (`brand`, `gold`, `bidang-*`). Layar yang tidak ada di referensi (form transaksi, detail dokumen, laporan, dokumen cetak) diturunkan dari token dan komponen yang sama.

## Tidak Diadopsi dari Referensi

- Kartu promosi Upgrade to Premium di sidebar.
- Kartu AI Assistant dengan orb 3D dan kolom obrolan.
- Galeri Add Widget dengan ilustrasi pratinjau dan fitur dasbor yang dapat disusun pengguna.
- Kartu mengambang miring bergaya pemasaran.
- Gauge setengah lingkaran, rating bintang, dan tombol pengalih tema (mode gelap tidak masuk MVP).

## shadcn Mapping Notes

Hubungan dengan preset (init memakai preset b3Z3SCCVl2, base Radix, template Laravel):

1. Preset menentukan base, gaya geometri (padding kontrol, kerapatan, kebulatan dasar), dan pustaka ikon. DESIGN.md menimpa token tema di berkas CSS utama: warna, radius, bayangan, font, dan tipografi. Edit hanya berkas CSS utama untuk token; pertahankan nilai hex (jangan dikonversi ke OKLCH).
2. Peta peran: `canvas` ke `--background` (bila kartu perlu kontras, gunakan `--card` putih dan `--background` kanvas); `ink` ke `--foreground`; `primary` dan `on-primary` ke `--primary` dan `--primary-foreground`; `ink-muted` ke `--muted-foreground`; `surface-subtle` ke `--muted`; `primary-soft` ke `--accent` (dan aksen sidebar aktif); `hairline` ke `--border` dan `--input`; `primary` ke `--ring`; `danger` ke `--destructive`; `chart-1` sampai `chart-5` ke `--chart-1` sampai `--chart-5`; `sidebar` ke `--sidebar-*`.
3. Token tanpa slot shadcn (`primary-active`, `success`, `success-soft`, `warning`, `danger-soft`, `brand`, `gold`, `bidang-*`) dibuat sebagai variabel di `:root` dan didaftarkan di `@theme inline`.
4. Radius disematkan di `@theme inline` sesuai kelas yang dipakai komponen: `--radius-md: 6px` (item menu), `--radius-lg: 8px` (tombol, input), `--radius-xl: 16px` (kartu, dialog, Sheet), `--radius-2xl: 16px`, `--radius-4xl: 9999px` (Badge pill). Badge perubahan butuh `rounded-md` (6 px) sehingga dibuat varian sendiri; badge hitungan tetap pill.
5. Bayangan ditimpa di `@theme`: `--shadow-xs` hampir nol; `--shadow-sm` dan `--shadow-lg` diisi dari `elevation.card` dan `elevation.popover`.
6. Tinggi kontrol 36 px (h-9) harus diubah serentak pada tombol, input, select, tab, toggle, dan komponen sejenis. Cek dulu nilai gaya preset (sering h-8) sebelum mengedit.
7. Resep komponen diterapkan lewat varian `cva` yang ada; pertahankan nama varian dan tambahkan varian baru hanya untuk badge perubahan dan kartu.
8. Kontras: periksa semua pasangan teks dan latar terhadap WCAG AA, dan laporkan yang di bawah 4,5:1.

## Known Gaps

- Warna, radius, dan ukuran diperkirakan dari gambar raster dan perlu diverifikasi dengan eyedropper.
- Keluarga huruf referensi tidak diketahui pasti; Inter adalah pengganti terdekat.
- Teks redup dan teks badge pada referensi tampak di bawah 4,5:1; nilai `ink-muted`, `success`, dan `danger` di sini sudah digelapkan agar lulus AA.
- Referensi tidak memuat mode gelap, tampilan mobile, form, halaman detail, laporan, atau dokumen cetak.
- Pustaka ikon referensi tidak diketahui; gunakan pustaka dari preset (garis, 16 sampai 18 px).
- Opsi warna aksi: bila memilih hijau BAZNAS sebagai warna aksi di dalam aplikasi, ganti `primary` ke `#0A7A3C`, `primary-hover` ke `#086A33`, `primary-active` ke `#07602F`, `primary-soft` ke `#E7F4EC`; ubah `chart-1` mengikuti, dan pastikan badge `success` tetap dapat dibedakan dari aksi lewat ikon panah dan label.

## Iteration Guide

1. Bangun token lebih dulu (warna, radius, bayangan, tipografi), lalu tinggi kontrol, lalu resep komponen.
2. Bangun kerangka (sidebar, top bar, header halaman) dan satu dasbor contoh yang mereplikasi referensi dengan data SISFOZIS; bandingkan berdampingan dengan gambar referensi.
3. Turunkan layar lain (daftar, form, detail, laporan) dari komponen yang sama sebelum menambah gaya baru.
4. Sebelum selesai, ubah setiap Don't menjadi pemeriksaan: tidak ada radius di luar skala, tidak ada warna aksi ke-2, tidak ada bobot di atas 600, tidak ada elemen yang tercantum di Tidak Diadopsi.
