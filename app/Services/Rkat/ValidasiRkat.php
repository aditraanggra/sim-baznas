<?php

namespace App\Services\Rkat;

/**
 * Enam validasi silang RKAT (PRD 10.1) atas kode pos baku. Murni: tanpa DB.
 * Masukan: nilai agregat per kode (HitungRkat::untukVersi()['per_kode']) dan batas hak amil (basis poin).
 */
final class ValidasiRkat
{
    /** Kode baku "untuk Amil" di L3: zakat, infak tidak terikat, infak terikat, CSR, Kurban, Fidyah, DSKL. */
    public const AMIL_L3 = ['L3/1.3', 'L3/2.2', 'L3/3.2', 'L3/4.2', 'L3/6.2', 'L3/7.2', 'L3/8.2'];

    public const AMIL_L6 = ['L6/1.1', 'L6/1.2', 'L6/1.3', 'L6/1.4'];

    public const HIBAH_L6 = ['L6/1.6', 'L6/1.7', 'L6/1.8'];

    public const BIDANG_MUSTAHIK = ['L5/2.1', 'L5/2.2', 'L5/2.3', 'L5/2.4', 'L5/2.5'];

    public const ORANG_MUZAKKI = ['L5/1.1', 'L5/1.3', 'L5/1.4', 'L5/1.5'];

    /** jenis dana => [daftar kode penerimaan L2, kode amil L3] */
    public const JENIS_DANA = [
        'zakat' => [['L2/1'], ['L3/1.3']],
        'infak' => [['L2/2'], ['L3/2.2', 'L3/3.2']],
        'csr' => [['L2/3'], ['L3/4.2']],
        'dskl' => [['L2/4', 'L2/5'], ['L3/6.2', 'L3/7.2', 'L3/8.2']],
    ];

    /**
     * @param  array<string, int>  $n  kode => nilai agregat
     * @param  array{zakat: ?int, infak: ?int, csr: ?int, dskl: ?int}  $batasBp  basis poin (1250 = 12,5%)
     * @return list<array{id: string, nama: string, lulus: bool, kiri: int, kanan: int, selisih: int, rincian: array}>
     */
    public static function evaluasi(array $n, array $batasBp): array
    {
        $s = fn (array $kode) => array_sum(array_map(fn ($k) => $n[$k] ?? 0, $kode));
        $penerimaan = $s(['L2/1', 'L2/2', 'L2/3', 'L2/4', 'L2/5']);
        $penyaluran = $s(['L3/1', 'L3/2', 'L3/3', 'L3/4', 'L3/5', 'L3/6', 'L3/7', 'L3/8', 'L3/9']);
        $hakAmil = $s(self::AMIL_L3);

        $hasil = [];
        $tambah = function (string $id, string $nama, int $kiri, int $kanan, array $rincian = []) use (&$hasil) {
            $hasil[] = ['id' => $id, 'nama' => $nama, 'lulus' => $kiri === $kanan, 'kiri' => $kiri, 'kanan' => $kanan, 'selisih' => $kiri - $kanan, 'rincian' => $rincian];
        };

        $tambah('VAL-1', 'Total penerimaan L2 = total penyaluran L3', $penerimaan, $penyaluran);

        $tambah('VAL-2', 'Pendistribusian + pendayagunaan L4 = penerimaan − hak amil', $s(['L4/1', 'L4/2']), $penerimaan - $hakAmil);

        $tambah('VAL-3', 'Bagian amil L6 = penyaluran untuk amil L3', $s(self::AMIL_L6), $hakAmil);

        $tambah('VAL-4', 'Dana operasional L6 = hak amil + hibah operasional', $s(['L6/1']), $s(self::AMIL_L6) + $s(self::HIBAH_L6));

        $mustahik = $s(['L5/2']);
        $orang = $s(['L5/1']) - $s(['L5/1.2']);
        $v5a = $mustahik === $s(self::BIDANG_MUSTAHIK);
        $v5b = $orang === $s(self::ORANG_MUZAKKI);
        $hasil[] = [
            'id' => 'VAL-5', 'nama' => 'Total mustahik = jumlah per bidang; total muzakki + munfiq + donatur = rincian',
            'lulus' => $v5a && $v5b,
            'kiri' => $mustahik + $orang, 'kanan' => $s(self::BIDANG_MUSTAHIK) + $s(self::ORANG_MUZAKKI),
            'selisih' => ($mustahik - $s(self::BIDANG_MUSTAHIK)) + ($orang - $s(self::ORANG_MUZAKKI)),
            'rincian' => [
                'mustahik' => ['total' => $mustahik, 'jumlah_bidang' => $s(self::BIDANG_MUSTAHIK), 'lulus' => $v5a],
                'orang' => ['total' => $orang, 'jumlah_rincian' => $s(self::ORANG_MUZAKKI), 'lulus' => $v5b],
            ],
        ];

        $rincian6 = [];
        foreach (self::JENIS_DANA as $jenis => [$kodeTerima, $kodeAmil]) {
            $terima = $s((array) $kodeTerima);
            $amil = $s($kodeAmil);
            $batas = $batasBp[$jenis] ?? null;
            // Bilangan bulat: amil/terima <= batas/10000. Batas 0: setiap amil > 0 gagal (temuan 10).
            $lulus = $batas !== null && $amil * 10000 <= $terima * $batas;
            $rincian6[$jenis] = [
                'penerimaan' => $terima, 'amil' => $amil, 'batas_bp' => $batas, 'lulus' => $lulus,
                'rasio_persen' => $terima > 0 ? $amil * 100 / $terima : null,
                'keterangan' => $batas === null ? 'Batas belum diisi' : null,
            ];
        }
        $gagal6 = count(array_filter($rincian6, fn ($r) => ! $r['lulus']));
        $hasil[] = [
            'id' => 'VAL-6', 'nama' => 'Bagian amil per jenis dana tidak melebihi batas catatan pengesahan',
            'lulus' => $gagal6 === 0, 'kiri' => $gagal6, 'kanan' => 0, 'selisih' => $gagal6, 'rincian' => $rincian6,
        ];

        return $hasil;
    }

    /** @param  list<array{lulus: bool}>  $hasil */
    public static function semuaLulus(array $hasil): bool
    {
        return array_reduce($hasil, fn (bool $ok, array $h) => $ok && $h['lulus'], true);
    }
}
