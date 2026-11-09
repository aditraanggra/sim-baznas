# Aturan: FR, VAL, BR

Ringkasan dari `docs/prd-pkp.md`. Bila berbeda, PRD yang berlaku. Setiap ID VAL dan BR wajib punya uji yang namanya memuat ID tersebut.

## VAL: validasi silang RKAT (PRD 10.1)

| ID | Aturan | Nilai RKAT 2026 |
|---|---|---|
| VAL-1 | Total penerimaan L2 = total penyaluran L3 | Rp 22.015.000.000 |
| VAL-2 | Pendistribusian + pendayagunaan L4 = penerimaan dikurangi hak amil | 12.898.442.400 + 5.591.321.600 = 18.489.764.000 |
| VAL-3 | Bagian amil pada penerimaan dana operasional (L6) = jumlah penyaluran untuk amil di L3 | 1.325.440.000 + 1.946.580.400 + 253.215.600 = 3.525.236.000 |
| VAL-4 | Dana operasional L6 = hak amil + hibah operasional APBN/APBD (L7) | 3.525.236.000 + 250.000.000 = 3.775.236.000 |
| VAL-5 | Total mustahik L5 = jumlah per bidang; total muzakki + munfiq + donatur = jumlah rincian | 35.372 orang; 201.430 orang (176.434 + 24.846 + 150) |
| VAL-6 | Bagian amil per jenis dana tidak melebihi batas pada catatan pengesahan | Zakat 12,5%; infak 20%; CSR 10%; DSKL 0% |

Nilai kontrol lain RKAT 2026: luar neraca Rp 137.959.000.000; saldo awal dan saldo akhir dana masing-masing Rp 1.500.000.000; Program Prioritas Rp 1.835.000.000; badan 29.

## BR: aturan bisnis umum (PRD 10.2)

| ID | Aturan |
|---|---|
| BR-01 | Hanya satu versi RKAT berstatus Disahkan yang aktif per tahun |
| BR-02 | Versi Disahkan terkunci; perubahan lewat versi Perubahan baru |
| BR-03 | Subtotal, total, dan persentase selalu dihitung sistem, tidak diinput |
| BR-04 | Versi tidak dapat diajukan bila ada validasi VAL-1 sampai VAL-6 yang gagal |
| BR-05 | Realisasi per tahun, bulan, pos baku; nilai negatif hanya untuk koreksi dengan catatan |
| BR-06 | Periode Terkunci tidak dapat diubah; buka kunci hanya Admin dengan alasan dan jejak audit |
| BR-07 | Kumulatif = jumlah realisasi bulan 1 sampai n; capaian = kumulatif / rencana aktif; rencana nol = n/a |
| BR-08 | Rasio hak amil per jenis dana dibandingkan batas; peringatan pada 90% batas [ASUMSI] |
| BR-09 | Realisasi pada pos yang tidak ada di RKAT ditandai di luar rencana |
| BR-10 | Perbandingan antar tahun memakai pos baku; pos belum terpetakan tidak ikut total pembanding |
| BR-11 | Tanpa hard delete pada RKAT dan realisasi; data lama diarsipkan |
| BR-12 | Rupiah bulat (BIGINT); persen satu desimal dihitung dari nilai asli |
| BR-13 | Tahun anggaran 1 Januari sampai 31 Desember |
| BR-14 | Nilai realisasi menyimpan sumber (manual, impor, sistem); satu periode pos tidak boleh memakai dua sumber |

## FR: kebutuhan fungsional

- FR-R-01..09 (Rencana): PRD 5.3.
- FR-L-01..12 (Realisasi): PRD 6.2.
- FR-K-01..07 (Keuangan): PRD 8.

## Matriks hak akses (PRD 2)

K = kelola, L = lihat, V = kunci/memutuskan, - = tidak ada.

| Objek | perencanaan | pelaporan | keuangan | pimpinan | admin |
|---|---|---|---|---|---|
| RKAT | K | L | L | L | K |
| Realisasi penerimaan, penyaluran, jumlah orang | L | K, V | L | L | K |
| Realisasi operasional dan saldo | L | L | K, V | L | K |
| Buka kunci periode | - | - | - | - | V |
| Kinerja | L | L | L | L | L |
| Laporan keuangan | L | L | K, V | L | K |
| Dashboard | L | L | L | L | L |
| Pengguna, pos anggaran, audit | - | - | - | - | K |
