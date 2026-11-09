# Fixtures

## rkat-2022-2031.xlsx (berkas sumber)

Arsip asli dari Google Sheets "Rencana 2022-2031" (7 sheet A1-A7 = Lampiran 2-7). Diunduh 6 November 2026 dari tautan yang diberikan pengguna. Data organisasi, aman dipakai sebagai data uji (PRD 15).

## rkat-2026.json (fixture uji)

Dibangun dari kolom "Tahun 2026" berkas sumber, 112 pos dengan kode `Lx/kode` sesuai kolom "No" tiap sheet (contoh `L2/1.3` = Zakat Mal-Perorangan via UPZ).

Asumsi penamaan (tercatat di `docs/open-questions.md`):

- Luar neraca (sheet A2) diberi awalan `L2/LN/` karena sumber tidak menomori bagian itu.
- Semua pos `sifat_waktu: aliran` (D3).
- `L5/1.2` Muzakki Badan bertipe `badan`; baris L5 lain bertipe `orang`.

Verifikasi kontrol (bagian `kontrol` dalam JSON, sudah dicocokkan PRD 10.1):

| Kontrol | Nilai fixture | Harapan PRD | Status |
|---|---|---|---|
| VAL-1 | 22.015.000.000 | 22.015.000.000 | cocok |
| VAL-2 | 12.898.442.400 + 5.591.321.600 | sama | cocok |
| VAL-3 | 1.325.440.000 + 1.946.580.400 + 253.215.600 | sama | cocok |
| VAL-4 | 3.775.236.000 | 3.775.236.000 | cocok |
| VAL-5 | orang 201.430; mustahik 35.372; badan 29 | sama | cocok |
| Luar neraca | 137.959.000.000 | 137.959.000.000 | cocok |
| Program Prioritas | tidak ada di sumber | 1.835.000.000 | celah (temuan 18) |
| Saldo awal dana | tidak ada di sumber | 1.500.000.000 | celah (temuan 19) |
