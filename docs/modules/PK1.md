# Modul PK1: Rencana (RKAT)

Rilis P1. Cakupan dari PRD 5 dan kriteria selesai PRD 14.2.

## Cakupan

- `rkat_versions`, `rkat_values`, `rkat_notes`, `rkat_documents`.
- BR-01/02/03/11: satu Disahkan aktif per tahun, terkunci, hitung server, tanpa hard delete.
- Grid input pohon (hanya daun input; subtotal dan persen dihitung server).
- VAL-1..VAL-6 sebagai service murni + daftar periksa UI; BR-04 blokir pengajuan.
- Status Draft -> Diajukan -> Disahkan -> Digantikan; metadata pengesahan; unggah PDF.
- Impor Excel RKAT 2026 dan pemetaan alias; impor RKAT 2021-2025 dengan penandaan pos tak terpetakan (BR-10).
- Bandingkan versi/tahun; daftar RKAT; ekspor Excel; ekspor PDF tata letak Lampiran (spike dompdf).

## Kebutuhan fungsional

FR-R-01..FR-R-09 (PRD 5.3).

## Kriteria selesai (PRD 14.2)

1. RKAT 2026 dapat diinput dan diimpor; enam validasi lulus pada nilai kontrol PRD 10.1.
2. Status versi dan penguncian bekerja.
3. RKAT 2021-2025 dapat diimpor dengan pemetaan alias dan pos tak terpetakan ditandai.
4. Bandingkan versi dan tahun.
5. Ekspor tata letak Lampiran.

## Uji wajib

- Satu uji bernama VAL-1..VAL-6 memakai angka kontrol 2026.
- Satu uji bernama BR-01..BR-04, BR-10, BR-11.
- BR-04: pengajuan ditolak bila satu validasi gagal.

## Gerbang rilis P1

RKAT 2026 lulus enam validasi; satu RKAT tahun lain terimpor tanpa galat tak terjelaskan.

## Blocker

- D1 (dokumen RKAT 2026) dan D6 (RKAT 2021-2025).
