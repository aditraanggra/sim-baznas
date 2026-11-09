# Modul PK5: Dashboard

Rilis P3. Cakupan dari PRD 9 dan kriteria selesai PRD 14.2.

## Cakupan

- Kartu ringkas: penerimaan, penyaluran, belanja operasional (realisasi, rencana, capaian); hak amil terhadap batas; jumlah muzakki dan mustahik.
- Grafik kumulatif bulanan penerimaan dan penyaluran terhadap target tahunan + garis tahun lalu.
- Capaian per jenis dana (zakat, infak, CSR, DSKL).
- Peringatan: hak amil mendekati/melewati batas; bulan belum diinput/belum dikunci; RKAT belum lulus validasi; pos di luar rencana.
- Pintasan ke Input Bulanan, Capaian, Antar Tahun.
- Label "diperbarui pada ..." selalu tampil. Pimpinan tanpa tombol ubah.

## Kriteria selesai (PRD 14.2)

Dashboard terbuka di bawah 2 detik dengan data 6 tahun; angka kartu sama dengan halaman Capaian (uji konsistensi); peringatan muncul sesuai aturan.

## Uji wajib

- Konsistensi: Dashboard = Capaian = Kinerja untuk periode sama.
- Performa: < 2 detik dengan seed 6 tahun.

## Ditunda (P-D)

Filter bidang untuk Wakil Ketua.
