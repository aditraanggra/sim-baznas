# Rencana Implementasi PK0 (Fondasi)

Status: **diimplementasikan 9 November 2026**. Bagian review di bawah dipertahankan sebagai catatan keputusan.

Update 6 November 2026: D1 sebagian terjawab — berkas sumber RKAT diterima dan fixture `docs/fixtures/rkat-2026.json` (112 pos) sudah dibuat serta lolos semua nilai kontrol PRD 10.1. Celah tersisa (Program Prioritas, jiwa/unit/desa, saldo awal) tidak menghalangi seed pos baku; lihat `docs/open-questions.md` temuan 17-24. Pertanyaan reviewer no. 4 tidak lagi relevan: seed langsung dari fixture.

## Hasil implementasi (9 November 2026)

- Migrasi: `pos_anggaran`, `pos_alias`, `audit_logs`, `import_jobs`, `import_rows`, tabel spatie. Rollback bersih.
- Model: `PosAnggaran`, `PosAlias`, `AuditLog` (append-only, menolak update/delete), `ImportJob`, `ImportRow`, trait `MencatatAudit`.
- Action: `SimpanPos`, `NonaktifkanPos`, `SimpanPengguna`, `MulaiImpor`; semua menulis satu baris audit dengan nilai lama/baru.
- `import_rows` memakai kunci alami unik `(jenis, kunci)` sehingga impor ulang menimpa baris yang sama (idempoten); `import_job_id` boleh null dan hanya menunjuk job terakhir.
- Seed: 5 peran + admin awal (`admin@pkp.local`), 112 pos dari fixture; seed ulang tidak menggandakan.
- Halaman: dashboard ringkas, Pengguna, Pos Anggaran (tabel pohon + filter lampiran), Impor (wizard 3 langkah + hasil per baris), Audit (filter entitas + paginasi), placeholder PKP, halaman galat Inertia 403/404.
- Login berbasis email, pembatasan laju 5 percobaan, pendaftaran mandiri dihapus, `SESSION_EXPIRE_ON_CLOSE=true`.
- Uji: `tests/Feature/Pk0/` — RBAC 5 peran, audit tercatat, append-only, impor idempoten, galat per baris, seed idempoten, sandi wajib pengguna baru. Suite penuh 34 uji lulus.
- Lingkungan: PHP hanya ada di WSL (herd-lite); jalankan `wsl.exe -d Ubuntu-22.04 -- bash -lc "cd /mnt/d/IT/sim-baznas && ~/.config/herd-lite/bin/php artisan ..."`. Node di Windows; `node node_modules/vite/bin/vite.js build`.
