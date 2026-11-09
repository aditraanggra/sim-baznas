# Modul PK2: Realisasi

Rilis P2. Cakupan dari PRD 6 dan kriteria selesai PRD 14.2.

## Cakupan

- `realisasi_values` (unik tahun, bulan, pos; sumber), `realisasi_periods` (kunci per tahun/bulan/kelompok), `saldo_awal`, `rollup_realisasi`.
- Input Bulanan (FR-L-01/02/05): grid, Draft, Kunci, buka kunci Admin dengan alasan; penguncian di server.
- Capaian vs Serapan, BR-07 (rencana nol = n/a), BR-05 (negatif butuh catatan), BR-09 (di luar rencana), BR-12.
- Impor Excel per kelompok + template unduhan (FR-L-03).
- Hak amil per jenis dana + peringatan 90% (FR-L-07, BR-08).
- Saldo akhir terhitung (FR-L-08); riwayat per sel (FR-L-11); halaman Capaian + ekspor; indikator kelengkapan (FR-L-12).
- Salin bulan lalu (FR-L-04); ambang warna terkonfigurasi (FR-L-06).

## Kriteria selesai (PRD 14.2)

1. Input bulanan dan impor Excel bekerja.
2. Kumulatif dan capaian benar (uji data sintetis).
3. Kunci dan buka kunci tercatat di audit.
4. Rasio hak amil dan peringatan muncul.
5. Saldo akhir terhitung.
6. Riwayat perubahan per sel.

## Uji wajib

- BR-05..BR-09, BR-12, BR-14 dengan nama memuat ID.
- Rollup = penjumlahan sumber.
- Penguncian server-side: ubah via request langsung ke periode terkunci harus ditolak.
- Rencana nol menghasilkan n/a; DSKL batas 0%.

## Gerbang rilis P2

Satu bulan realisasi nyata dibandingkan hitung manual Pelaporan; selisih nol atau terjelaskan.
