<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_realisasi_rkat AS
            WITH penerimaan_nom AS (
                SELECT
                    EXTRACT(YEAR FROM p.tanggal)::int AS tahun,
                    EXTRACT(MONTH FROM p.tanggal)::int AS bulan,
                    pm.pos_anggaran_id,
                    COALESCE(SUM(pi.jumlah), 0)::bigint AS nilai_otomatis
                FROM pos_anggaran_pemetaan pm
                JOIN penerimaan p ON p.status = 'final'
                JOIN penerimaan_item pi ON pi.penerimaan_id = p.id
                JOIN sumber_dana sd ON sd.id = pi.sumber_dana_id
                JOIN muzakki m ON m.id = p.muzakki_id
                WHERE pm.aktif = true
                  AND pm.sumber_transaksi = 'penerimaan'
                  AND (
                      (pm.sumber_dana_id IS NOT NULL AND pi.sumber_dana_id = pm.sumber_dana_id)
                      OR (pm.sumber_dana_id IS NULL AND pm.jenis_dana_id IS NOT NULL AND sd.jenis_dana_id = pm.jenis_dana_id)
                      OR (pm.sumber_dana_id IS NULL AND pm.jenis_dana_id IS NULL)
                  )
                  AND (
                      pm.filter_via_upz IS NULL
                      OR (pm.filter_via_upz = true AND p.upz_id IS NOT NULL)
                      OR (pm.filter_via_upz = false AND p.upz_id IS NULL)
                  )
                  AND (pm.filter_jenis_muzakki IS NULL OR m.jenis = pm.filter_jenis_muzakki)
                GROUP BY EXTRACT(YEAR FROM p.tanggal)::int, EXTRACT(MONTH FROM p.tanggal)::int, pm.pos_anggaran_id
            ),
            penyaluran_nom AS (
                SELECT
                    EXTRACT(YEAR FROM py.tanggal)::int AS tahun,
                    EXTRACT(MONTH FROM py.tanggal)::int AS bulan,
                    pm.pos_anggaran_id,
                    COALESCE(SUM(pyi.jumlah), 0)::bigint AS nilai_otomatis
                FROM pos_anggaran_pemetaan pm
                JOIN penyaluran py ON py.status = 'dicairkan'
                JOIN penyaluran_item pyi ON pyi.penyaluran_id = py.id
                JOIN sumber_dana sd ON sd.id = pyi.sumber_dana_id
                WHERE pm.aktif = true
                  AND pm.sumber_transaksi = 'penyaluran'
                  AND (pm.program_id IS NULL OR py.program_id = pm.program_id)
                  AND (
                      (pm.sumber_dana_id IS NOT NULL AND pyi.sumber_dana_id = pm.sumber_dana_id)
                      OR (pm.sumber_dana_id IS NULL AND pm.jenis_dana_id IS NOT NULL AND sd.jenis_dana_id = pm.jenis_dana_id)
                      OR (pm.sumber_dana_id IS NULL AND pm.jenis_dana_id IS NULL)
                  )
                  AND (pm.asnaf_id IS NULL OR pyi.asnaf_id = pm.asnaf_id)
                GROUP BY EXTRACT(YEAR FROM py.tanggal)::int, EXTRACT(MONTH FROM py.tanggal)::int, pm.pos_anggaran_id
            ),
            penerimaan_org AS (
                SELECT
                    EXTRACT(YEAR FROM p.tanggal)::int AS tahun,
                    EXTRACT(MONTH FROM p.tanggal)::int AS bulan,
                    pm.pos_anggaran_id,
                    COUNT(DISTINCT p.muzakki_id)::bigint AS nilai_otomatis
                FROM pos_anggaran_pemetaan pm
                JOIN penerimaan p ON p.status = 'final'
                JOIN penerimaan_item pi ON pi.penerimaan_id = p.id
                JOIN sumber_dana sd ON sd.id = pi.sumber_dana_id
                JOIN muzakki m ON m.id = p.muzakki_id
                WHERE pm.aktif = true
                  AND pm.sumber_transaksi = 'penerimaan_orang'
                  AND (
                      (pm.sumber_dana_id IS NOT NULL AND pi.sumber_dana_id = pm.sumber_dana_id)
                      OR (pm.sumber_dana_id IS NULL AND pm.jenis_dana_id IS NOT NULL AND sd.jenis_dana_id = pm.jenis_dana_id)
                      OR (pm.sumber_dana_id IS NULL AND pm.jenis_dana_id IS NULL)
                  )
                  AND (
                      pm.filter_via_upz IS NULL
                      OR (pm.filter_via_upz = true AND p.upz_id IS NOT NULL)
                      OR (pm.filter_via_upz = false AND p.upz_id IS NULL)
                  )
                  AND (pm.filter_jenis_muzakki IS NULL OR m.jenis = pm.filter_jenis_muzakki)
                GROUP BY EXTRACT(YEAR FROM p.tanggal)::int, EXTRACT(MONTH FROM p.tanggal)::int, pm.pos_anggaran_id
            ),
            penyaluran_org AS (
                SELECT
                    EXTRACT(YEAR FROM py.tanggal)::int AS tahun,
                    EXTRACT(MONTH FROM py.tanggal)::int AS bulan,
                    pm.pos_anggaran_id,
                    COUNT(DISTINCT py.mustahik_id)::bigint AS nilai_otomatis
                FROM pos_anggaran_pemetaan pm
                JOIN penyaluran py ON py.status = 'dicairkan'
                JOIN penyaluran_item pyi ON pyi.penyaluran_id = py.id
                JOIN sumber_dana sd ON sd.id = pyi.sumber_dana_id
                WHERE pm.aktif = true
                  AND pm.sumber_transaksi = 'penyaluran_orang'
                  AND (pm.program_id IS NULL OR py.program_id = pm.program_id)
                  AND (
                      (pm.sumber_dana_id IS NOT NULL AND pyi.sumber_dana_id = pm.sumber_dana_id)
                      OR (pm.sumber_dana_id IS NULL AND pm.jenis_dana_id IS NOT NULL AND sd.jenis_dana_id = pm.jenis_dana_id)
                      OR (pm.sumber_dana_id IS NULL AND pm.jenis_dana_id IS NULL)
                  )
                  AND (pm.asnaf_id IS NULL OR pyi.asnaf_id = pm.asnaf_id)
                GROUP BY EXTRACT(YEAR FROM py.tanggal)::int, EXTRACT(MONTH FROM py.tanggal)::int, pm.pos_anggaran_id
            ),
            otomatis AS (
                SELECT * FROM penerimaan_nom
                UNION ALL
                SELECT * FROM penyaluran_nom
                UNION ALL
                SELECT * FROM penerimaan_org
                UNION ALL
                SELECT * FROM penyaluran_org
            ),
            manual AS (
                SELECT
                    tahun::int AS tahun,
                    bulan::int AS bulan,
                    pos_anggaran_id,
                    SUM(nilai)::bigint AS nilai_manual
                FROM realisasi_manual
                GROUP BY tahun, bulan, pos_anggaran_id
            )
            SELECT
                COALESCE(o.tahun, m.tahun) AS tahun,
                COALESCE(o.bulan, m.bulan) AS bulan,
                COALESCE(o.pos_anggaran_id, m.pos_anggaran_id) AS pos_anggaran_id,
                pm.bidang_id,
                COALESCE(pm.sumber_transaksi, 'manual')::varchar(32) AS sumber_transaksi,
                COALESCE(o.nilai_otomatis, 0)::bigint AS nilai_otomatis,
                COALESCE(m.nilai_manual, 0)::bigint AS nilai_manual,
                (COALESCE(o.nilai_otomatis, 0) + COALESCE(m.nilai_manual, 0))::bigint AS nilai_realisasi
            FROM otomatis o
            FULL OUTER JOIN manual m
                ON m.tahun = o.tahun
               AND m.bulan = o.bulan
               AND m.pos_anggaran_id = o.pos_anggaran_id
            LEFT JOIN pos_anggaran_pemetaan pm
                ON pm.pos_anggaran_id = COALESCE(o.pos_anggaran_id, m.pos_anggaran_id)
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_realisasi_rkat');
    }
};
