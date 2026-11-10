# Pertanyaan Terbuka dan Asumsi

Aturan (PRD 15): hal [TBC] **tidak boleh ditebak**. Catat di sini, pakai asumsi paling konservatif, dan tandai kode yang terdampak.

## Blocker keputusan (D1-D7)

| # | Keputusan | Default direkomendasikan | Status | Blokir |
|---|---|---|---|---|
| D1 | Dokumen sumber RKAT 2026 (Excel/PDF) untuk seed pos baku L2-L7 dan fixture | **Wajib dari pengguna** | **TERSELESAIKAN SEBAGIAN**: berkas Google Sheets "Rencana 2022-2031" diterima (lihat `docs/fixtures/rkat-2026.json`); 3 celah tersisa (temuan 17-19) | PK0 seed, PK1 |
| D2 | Model ukuran ganda: satu baris `pos_anggaran` per ukuran (rupiah/orang/badan/jiwa/unit/persen) | Setuju | Dipakai di skema | - |
| D3 | `sifat_waktu` aliran/stok; orang diinput per bulan | Aliran per bulan | Dipakai di skema | PK2 |
| D4 | Peran yang mencatat status Disahkan | Staf Perencanaan | Dipakai | PK1 |
| D5 | Login email atau username | Email | Dipakai (starter kit berbasis email) | PK0 |
| D6 | RKAT 2021-2025 dikirim | Minta sekarang | **TERBUKA** | PK1 impor lama |
| D7 | Library: spatie/laravel-permission, Laravel Excel, dompdf | Setuju | Terpasang | PK0 |

## Pertanyaan PRD bagian 16 (Q1-Q9)

| No | Pertanyaan | Dibutuhkan untuk | Asumsi konservatif bila belum dijawab | Status |
|---|---|---|---|---|
| Q1 | Format laporan keuangan: jenis, periode, cakupan | PK4 | Hanya jalur "dokumen laporan" (unggah + metadata + status); tanpa mesin template | TERBUKA |
| Q2 | Template RKAT 2021-2025 sama atau beda; ada Perubahan? | PK1 | **SEBAGIAN TERJAWAB**: berkas sumber memuat kolom 2022-2031 dalam satu template; 2021 tidak ada; versi Perubahan belum jelas; status "Disahkan" per tahun belum jelas | TERBUKA (sisa: 2021, versi Perubahan) |
| Q3 | Sumber data realisasi 2021-2026 dan penginput per bulan | PK2 | Impor Excel per kelompok sebagai jalur utama | TERBUKA |
| Q4 | Target pengentasan kemiskinan resmi (660 atau 5.659/3.962) | Lampiran 5 | Berkas sumber memuat 9.621 = 3.962 (ekstrem) + 5.659 (garis kemiskinan); angka 660 tidak ada di berkas | TERBUKA (sisa: arti angka 660) |
| Q5 | Perlu plotting bulanan/triwulanan; ambang warna | FR-L-06, FR-L-10 | Proporsi waktu linear (FR-L-10 default), ambang dapat dikonfigurasi | TERBUKA |
| Q6 | Jumlah orang diinput kumulatif tahunan atau per bulan | L5 | **Per bulan (aliran)** agar BR-07 berlaku | TERBUKA |
| Q7 | Sumber dan bentuk data luar neraca | L2 | **TERJAWAB**: sheet A2 berkas sumber; rincian 2026: Zakat Fitrah 23.959.000.000, Kurban 110.000.000.000, Infak/Sedekah 4.000.000.000, Lainnya 0 = 137.959.000.000 | TERSELESAIKAN |
| Q8 | Daftar pengguna awal dan peran; pembagian bidang Wakil Ketua | Bagian 2, 9 | Dashboard sama untuk semua Pimpinan; filter bidang ditunda (P-D) | TERBUKA |
| Q9 | Domain, hosting, cara login | PK0 | Email; hosting satu VPS | TERBUKA |

## Temuan teknis yang menjadi asumsi

1. ~~**Dokumen RKAT 2026 tidak ada di repo.**~~ **Terjawab 6 Nov 2026**: berkas sumber diterima dan `docs/fixtures/rkat-2026.json` dibuat dari kolom Tahun 2026 (112 pos, kontrol VAL cocok). Celah tersisa: Program Prioritas, jiwa/unit/desa, saldo awal dana (temuan 18-19).
2. **Ukuran ganda per pos.** Setiap ukuran = satu baris `pos_anggaran` dengan `tipe_nilai`; satu kolom `nilai` BIGINT.
3. **BR-07 vs data stok.** Pos punya `sifat_waktu` (`aliran`/`stok`). Default konservatif: aliran; orang per bulan.
4. **Masa berlaku pos.** `berlaku_dari_tahun`/`berlaku_sampai_tahun`; nonaktif tanpa hard delete.
5. **`pos_alias` many-to-one saja.** Satu kode lama pecah ke beberapa kode baku tidak terwakili; ditandai tak terpetakan.
6. **Tanggal peralihan sumber** (PRD 1.4) belum dimodelkan. v1 hanya `manual` dan `impor`; `sistem` dan `source_cutover` ditunda (P-D). Kolom `sumber` tetap ada.
7. **Impor ulang (BR-14).** Impor menimpa nilai Draft dengan audit dan sumber `impor`; ditolak pada periode Terkunci. Unik per (tahun, bulan, pos).
8. **Siapa mengesahkan.** Perencanaan mencatat Diajukan dan Disahkan; pengesahan sebenarnya di luar sistem. Admin membatalkan lewat versi Perubahan, bukan edit.
9. **Kunci per (tahun, bulan, kelompok).** Saldo akhir (Keuangan) bergantung pada penerimaan dan penyaluran (Pelaporan); saldo akhir dihitung hidup dan ditandai "belum final" bila kelompok sumber belum terkunci.
10. **VAL-6 dengan batas 0% (DSKL).** Batas 0 berarti setiap nilai amil > 0 melewati batas; rasio ditampilkan n/a.
11. **Rollup memuat semua nilai tersimpan** (Draft dan Terkunci) plus penanda `terkunci` per periode. Capaian dan Dashboard memakai rollup yang sama.
12. **Dimensi "via UPZ"** hanya bisa dipenuhi bila RKAT 2026 memisahkannya. Bila tidak, dimensi dihapus (P-D).
13. **Dashboard Wakil Ketua** belum terdefinisi; v1 tampilan sama untuk semua Pimpinan.
14. **Ekspor PDF "tata letak Lampiran asli"** perlu berkas Lampiran asli. Default dompdf.
15. **PK4 bergantung Q1.** Mulai dari jalur dokumen laporan.
16. **Jadwal PRD** tidak dipakai sebagai target; kalibrasi ulang setelah P0.

## Temuan dari berkas sumber RKAT (6 November 2026)

17. **Berkas sumber** adalah Google Sheets "Rencana 2022-2031" (7 sheet A1-A7 = Lampiran 2-7), arsip di `docs/fixtures/rkat-2022-2031.xlsx`. Kolom Tahun 2026 cocok dengan seluruh nilai kontrol PRD 10.1: VAL-1 22.015.000.000; VAL-2 12.898.442.400 + 5.591.321.600; VAL-3 1.325.440.000 + 1.946.580.400 + 253.215.600; VAL-4 3.775.236.000; VAL-5 orang 201.430, mustahik 35.372, badan 29; luar neraca 137.959.000.000.
18. **Program Prioritas (10 program, Rp 1.835.000.000) dan kolom jiwa/unit/desa tidak ada di berkas sumber.** Lampiran 4 hanya memuat 4 bidang pendistribusian + 3 pendayagunaan dalam rupiah. Seed PK0 tidak bisa memuat Program Prioritas sampai pengguna melengkapi.
19. **Saldo awal dana (Rp 1.500.000.000) tidak ada di berkas sumber**; yang ada hanya saldo awal dana amil (L6) = 0. Saldo awal tetap diinput manual di PK2 (`saldo_awal`).
20. **Kolom persen Lampiran 2-7 tidak ada di berkas sumber** (hanya rupiah/orang). Persen tetap dihitung sistem (BR-03, BR-12), tidak diimpor.
21. **VAL-5: baris total muzakki di sumber memuat badan (201.459)**, sedangkan kontrol PRD memakai orang saja (201.430, tanpa badan 29). Uji VAL-5 menjumlah hanya baris bertipe orang; badan diverifikasi terpisah.
22. **Dimensi "via UPZ" ada** di penerimaan (kode 1.3, 1.5, 2.2, 2.5, 5.2) dan penyaluran tidak memisahkannya. Temuan 12 lama terjawab: kartu "langsung vs via UPZ" bisa dibangun dari pos penerimaan.
23. **Penyaluran sumber punya kelompok tambahan** yang tidak disebut eksplisit di PRD: Fidyah (7), Infak Penyaluran (5), Penyaluran Dana Infak Operasional (9). Ikut sebagai pos baku; tidak mengubah VAL mana pun.
24. **A3 "Penyaluran Dana Infak/Sedekah Tidak Terikat" total 9.732.902.000** vs penerimaan infak tidak terikat + terikat via UPZ dll — selisih antar lampiran dibiarkan apa adanya sesuai sumber; VAL-1 memakai total lampiran, bukan antar-rincian.

## Temuan implementasi PK1 (9 November 2026)

25. **Seed PK0 salah induk `L5/1.2` (Muzakki Badan).** Pencarian induk memakai `kode|tipe_nilai` sehingga baris bertipe `badan` kehilangan induk `L5/1`. Diperbaiki (`PosAnggaranSeeder` mencari induk per kode); akar pos 28 menjadi 27. `L5/1` kini 201.459 (sama dengan sumber); orang saja (VAL-5) = `L5/1 − L5/1.2` = 201.430.
26. **Keputusan pengguna (O-1): impor historis lolos BR-04 dengan peringatan.** Versi Awal `sumber=impor` boleh diajukan dan disahkan walau VAL gagal (hasil hitung berkas: 2022-2024 gagal VAL-1/2; 2023 juga VAL-4). Input manual, versi Perubahan, dan versi impor yang sudah diedit tetap kena BR-04. Menyimpang dari PRD 10.2 BR-04 untuk jalur impor.
27. **Definisi VAL atas kode baku.** Hak amil = `L3/1.3 + 2.2 + 3.2 + 4.2 + 8.2`; Kurban `L3/6.2` dan Fidyah `L3/7.2` dikecualikan (tidak ada baris L6 dan tidak ada batas; 0 pada 2022-2026). VAL-4 memakai `L6/1.1..1.4 + L6/1.6..1.8`; Infak Operasional `L6/1.5` tidak termasuk rumus (harfiah PRD), sehingga RKAT 2023 (950 jt) gagal VAL-4. Hibah APBN/APBD ada di L6 pada pos baku, bukan L7 seperti PRD 5.2.
28. **VAL-5 tautologi** bila induk dihitung dari daun (BR-03); tetap diuji, dan impor melaporkan peringatan bila subtotal berkas berbeda dari hitungan sistem.
29. **Persen Lampiran 2-7** = nilai pos ÷ jumlah pos puncak sejenis dalam satu lampiran (luar neraca L2/LN terpisah); hanya untuk pos rupiah. Kolom persen tidak ada di sumber (temuan 20); asumsi menunggu konfirmasi.
30. **Masa berlaku pos belum dipakai.** Seed PK0 memberi `berlaku_dari_tahun = 2026` padahal berkas sumber memuat 2022-2031 dengan kode sama; pemetaan impor dan hitung RKAT mengabaikan masa berlaku (hanya `aktif`).
31. **Target pengentasan (Q4)** disimpan dalam dua kolom nullable (`target_pengentasan_ekstrem`, `target_pengentasan_garis`); arti angka 660 tetap terbuka. `target_orang` disimpan apa adanya (arti belum dikonfirmasi).
32. **Dokumen setelah Disahkan.** Nilai, catatan, dan batas terkunci; PDF surat pengesahan/lembar penetapan boleh ditambah (berkas lama diarsipkan, tanpa hard delete).
33. **Alias hanya many-to-one per tahun dan hanya memetakan ulang versi Draft.** Versi terkunci tidak berubah (BR-02); pemetaan baru berlaku lewat versi Perubahan.
34. **Format yang belum dikirim (menunggu pengguna):** RKAT 2021; status kolom 2022-2025 (disahkan atau rencana) dan ada tidaknya versi Perubahan; Lampiran 2-7 asli untuk tata letak ekspor; catatan pengesahan tahun selain 2026; format nomor surat; Program Prioritas, jiwa/unit/desa L4, saldo awal dana.
