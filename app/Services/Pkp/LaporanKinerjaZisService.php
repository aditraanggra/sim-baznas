<?php

namespace App\Services\Pkp;

use App\Models\Mustahik;
use App\Models\Muzakki;
use App\Models\Permohonan;
use App\Models\PosAnggaran;
use App\Models\TahunAnggaran;
use App\Services\Perencanaan\RealisasiRkatService;
use App\Services\Rkat\HitungRkat;
use Illuminate\Support\Facades\DB;

class LaporanKinerjaZisService
{
    public const NAMA_BULAN = [
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'Mei',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Agu',
        9 => 'Sep',
        10 => 'Okt',
        11 => 'Nov',
        12 => 'Des',
    ];

    public const NAMA_BULAN_PANJANG = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    private const WARNA_JENIS_DANA = [
        'ZAKAT' => ['label' => 'Zakat', 'warna' => '#0A7A3C'],
        'INFAK' => ['label' => 'Infak', 'warna' => '#E0A100'],
        'DSKL' => ['label' => 'DSKL', 'warna' => '#2563EB'],
        'CSR' => ['label' => 'CSR', 'warna' => '#6B7480'],
    ];

    public function __construct(
        private readonly RealisasiRkatService $realisasiService,
    ) {}

    public function tentukanTahunDefault(?int $tahunInput = null): int
    {
        if ($tahunInput !== null && $tahunInput >= 2000 && $tahunInput <= 2100) {
            return $tahunInput;
        }

        return (int) (TahunAnggaran::where('status', TahunAnggaran::STATUS_BERJALAN)->value('tahun') ?: date('Y'));
    }

    /**
     * Daftar tahun tersedia untuk filter kinerja (tahun_anggaran + tahun transaksi + tahun berjalan).
     *
     * @return list<array{id: int|null, tahun: int, status: string}>
     */
    public function daftarTahunTersedia(): array
    {
        $tahunAnggaran = TahunAnggaran::orderByDesc('tahun')
            ->get(['id', 'tahun', 'status'])
            ->keyBy('tahun');

        $tahunPenerimaan = DB::table('penerimaan')
            ->where('status', 'final')
            ->selectRaw('DISTINCT EXTRACT(YEAR FROM tanggal)::int as tahun')
            ->pluck('tahun');

        $tahunPenyaluran = DB::table('penyaluran')
            ->whereIn('status', ['disetujui', 'dicairkan'])
            ->selectRaw('DISTINCT EXTRACT(YEAR FROM tanggal)::int as tahun')
            ->pluck('tahun');

        $semuaTahun = collect([(int) date('Y')])
            ->merge($tahunAnggaran->keys())
            ->merge($tahunPenerimaan)
            ->merge($tahunPenyaluran)
            ->map(fn ($t) => (int) $t)
            ->filter(fn (int $t) => $t >= 2000 && $t <= 2100)
            ->unique()
            ->sortDesc()
            ->values();

        return $semuaTahun->map(function (int $tahun) use ($tahunAnggaran): array {
            $ta = $tahunAnggaran->get($tahun);

            return [
                'id' => $ta?->id,
                'tahun' => $tahun,
                'status' => $ta?->status ?? 'draft',
            ];
        })->all();
    }

    /**
     * Menghitung ringkasan agregat kinerja pengelolaan ZIS tahun tertentu (tanpa data pribadi).
     *
     * @return array<string, mixed>
     */
    public function ringkasanTahunan(int $tahun): array
    {
        $awalTahun = "{$tahun}-01-01";
        $akhirTahun = "{$tahun}-12-31";

        $realisasi = $this->realisasiService->hitung($tahun);
        $baris = collect($realisasi['baris']);

        $cuplikanPosL2 = $baris
            ->filter(fn (array $b) => $b['grup'] === 'L2' && $b['induk_id'] === null)
            ->values()
            ->all();
        $cuplikanPosL3 = $baris
            ->filter(fn (array $b) => $b['grup'] === 'L3' && $b['induk_id'] === null)
            ->values()
            ->all();

        $kinerjaPengumpulan = $this->hitungKinerjaPengumpulan($awalTahun, $akhirTahun);
        $kinerjaDistribusi = $this->hitungKinerjaDistribusi(
            $awalTahun,
            $akhirTahun,
            $kinerjaPengumpulan['total_penerimaan'],
        );

        $rekapBulanan = $this->susunRekapBulanan(
            $kinerjaPengumpulan['per_bulan_rinci'],
            $kinerjaDistribusi['per_bulan_rinci'],
        );

        return [
            'tahun_terpilih' => $tahun,
            'daftar_tahun' => $this->daftarTahunTersedia(),
            'status_tahun' => $realisasi['status_tahun'],
            'versi_rkat' => $realisasi['versi_rkat'],
            'diperbarui_pada' => now('Asia/Jakarta')->translatedFormat('d M Y, H:i').' WIB',
            'ringkasan' => [
                'total_rkat' => $realisasi['total_rkat'],
                'total_realisasi' => $realisasi['total_realisasi'],
                'persen_serapan' => $realisasi['persen_total'],
                'persen_serapan_label' => $realisasi['persen_total_label'],
                'total_pos' => PosAnggaran::where('aktif', true)->count(),
            ],
            'ringkasan_lampiran' => $realisasi['ringkasan_lampiran'] ?? [],
            'cuplikan_pos' => $cuplikanPosL2,
            'cuplikan_pos_l3' => $cuplikanPosL3,
            'kinerja_pengumpulan' => collect($kinerjaPengumpulan)->except('per_bulan_rinci')->all(),
            'kinerja_distribusi' => collect($kinerjaDistribusi)->except('per_bulan_rinci')->all(),
            'rekap_bulanan' => $rekapBulanan,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function hitungKinerjaPengumpulan(string $awalTahun, string $akhirTahun): array
    {
        $ringkasPenerimaan = DB::table('penerimaan as p')
            ->leftJoin('penerimaan_item as pi', 'pi.penerimaan_id', '=', 'p.id')
            ->where('p.status', 'final')
            ->whereBetween('p.tanggal', [$awalTahun, $akhirTahun])
            ->selectRaw('
                COALESCE(SUM(pi.jumlah), 0)::bigint as total_penerimaan,
                COUNT(DISTINCT p.id) as jumlah_transaksi,
                COUNT(DISTINCT p.muzakki_id) as muzakki_menyetor,
                COALESCE(SUM(CASE WHEN p.upz_id IS NULL THEN pi.jumlah ELSE 0 END), 0)::bigint as total_langsung,
                COALESCE(SUM(CASE WHEN p.upz_id IS NOT NULL THEN pi.jumlah ELSE 0 END), 0)::bigint as total_upz,
                COUNT(DISTINCT p.upz_id) as upz_menyetor
            ')
            ->first();

        $perBulan = DB::table('penerimaan as p')
            ->join('penerimaan_item as pi', 'pi.penerimaan_id', '=', 'p.id')
            ->where('p.status', 'final')
            ->whereBetween('p.tanggal', [$awalTahun, $akhirTahun])
            ->groupByRaw('EXTRACT(MONTH FROM p.tanggal)::int')
            ->selectRaw('
                EXTRACT(MONTH FROM p.tanggal)::int as bulan,
                COALESCE(SUM(pi.jumlah), 0)::bigint as total,
                COUNT(DISTINCT p.id) as jumlah_transaksi
            ')
            ->get()
            ->keyBy('bulan');

        $trenBulanan = [];
        $perBulanRinci = [];
        foreach (self::NAMA_BULAN as $angka => $label) {
            $baris = $perBulan->get($angka);
            $total = (int) ($baris->total ?? 0);
            $trenBulanan[] = [
                'label' => $label,
                'nilai' => $total,
            ];
            $perBulanRinci[$angka] = [
                'total' => $total,
                'transaksi' => (int) ($baris->jumlah_transaksi ?? 0),
            ];
        }

        $perJenisDana = DB::table('penerimaan_item as pi')
            ->join('penerimaan as p', 'p.id', '=', 'pi.penerimaan_id')
            ->join('sumber_dana as sd', 'sd.id', '=', 'pi.sumber_dana_id')
            ->join('jenis_dana as jd', 'jd.id', '=', 'sd.jenis_dana_id')
            ->where('p.status', 'final')
            ->whereBetween('p.tanggal', [$awalTahun, $akhirTahun])
            ->groupBy('jd.kode')
            ->selectRaw('jd.kode, COALESCE(SUM(pi.jumlah), 0)::bigint as total')
            ->pluck('total', 'kode');

        $komposisiDana = [];
        foreach (self::WARNA_JENIS_DANA as $kode => $meta) {
            $komposisiDana[] = [
                'kode' => $kode,
                'label' => $meta['label'],
                'nilai' => (int) ($perJenisDana[$kode] ?? 0),
                'warna' => $meta['warna'],
            ];
        }

        $perSumberDana = DB::table('sumber_dana as sd')
            ->join('jenis_dana as jd', 'jd.id', '=', 'sd.jenis_dana_id')
            ->leftJoin('penerimaan_item as pi', 'pi.sumber_dana_id', '=', 'sd.id')
            ->leftJoin('penerimaan as p', function ($join) use ($awalTahun, $akhirTahun): void {
                $join->on('p.id', '=', 'pi.penerimaan_id')
                    ->where('p.status', '=', 'final')
                    ->whereBetween('p.tanggal', [$awalTahun, $akhirTahun]);
            })
            ->where('sd.aktif', true)
            ->groupBy('sd.id', 'sd.kode', 'sd.nama', 'jd.nama')
            ->orderBy('sd.id')
            ->selectRaw('
                sd.kode,
                sd.nama,
                jd.nama as jenis_dana,
                COALESCE(SUM(CASE WHEN p.id IS NOT NULL THEN pi.jumlah ELSE 0 END), 0)::bigint as nilai
            ')
            ->get()
            ->map(fn ($r) => [
                'kode' => $r->kode,
                'label' => $r->nama,
                'jenis_dana' => $r->jenis_dana,
                'nilai' => (int) $r->nilai,
            ])
            ->all();

        return [
            'total_penerimaan' => (int) ($ringkasPenerimaan->total_penerimaan ?? 0),
            'jumlah_transaksi' => (int) ($ringkasPenerimaan->jumlah_transaksi ?? 0),
            'muzakki_menyetor' => (int) ($ringkasPenerimaan->muzakki_menyetor ?? 0),
            'total_muzakki_aktif' => Muzakki::where('aktif', true)->count(),
            'total_langsung' => (int) ($ringkasPenerimaan->total_langsung ?? 0),
            'total_upz' => (int) ($ringkasPenerimaan->total_upz ?? 0),
            'upz_menyetor' => (int) ($ringkasPenerimaan->upz_menyetor ?? 0),
            'total_upz_aktif' => DB::table('upz')->whereNull('deleted_at')->where('aktif', true)->count(),
            'tren_bulanan' => $trenBulanan,
            'komposisi_dana' => $komposisiDana,
            'per_sumber_dana' => $perSumberDana,
            'per_bulan_rinci' => $perBulanRinci,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function hitungKinerjaDistribusi(string $awalTahun, string $akhirTahun, int $totalPenerimaan): array
    {
        $ringkasPenyaluran = DB::table('penyaluran as py')
            ->leftJoin('penyaluran_item as pyi', 'pyi.penyaluran_id', '=', 'py.id')
            ->whereIn('py.status', ['disetujui', 'dicairkan'])
            ->whereBetween('py.tanggal', [$awalTahun, $akhirTahun])
            ->selectRaw("
                COALESCE(SUM(CASE WHEN py.status = 'dicairkan' THEN pyi.jumlah ELSE 0 END), 0)::bigint as total_penyaluran,
                COALESCE(SUM(pyi.jumlah), 0)::bigint as total_komitmen,
                COUNT(DISTINCT CASE WHEN py.status = 'dicairkan' THEN py.id END) as jumlah_transaksi,
                COUNT(DISTINCT CASE WHEN py.status = 'dicairkan' THEN py.mustahik_id END) as mustahik_terbantu,
                COALESCE(SUM(CASE WHEN py.status = 'dicairkan' AND py.sub_bidang = 'pendistribusian' THEN pyi.jumlah ELSE 0 END), 0)::bigint as total_pendistribusian,
                COALESCE(SUM(CASE WHEN py.status = 'dicairkan' AND py.sub_bidang = 'pendayagunaan' THEN pyi.jumlah ELSE 0 END), 0)::bigint as total_pendayagunaan
            ")
            ->first();

        $perBulan = DB::table('penyaluran as py')
            ->join('penyaluran_item as pyi', 'pyi.penyaluran_id', '=', 'py.id')
            ->where('py.status', 'dicairkan')
            ->whereBetween('py.tanggal', [$awalTahun, $akhirTahun])
            ->groupByRaw('EXTRACT(MONTH FROM py.tanggal)::int')
            ->selectRaw('
                EXTRACT(MONTH FROM py.tanggal)::int as bulan,
                COALESCE(SUM(pyi.jumlah), 0)::bigint as total,
                COUNT(DISTINCT py.id) as jumlah_transaksi
            ')
            ->get()
            ->keyBy('bulan');

        $trenBulanan = [];
        $perBulanRinci = [];
        foreach (self::NAMA_BULAN as $angka => $label) {
            $baris = $perBulan->get($angka);
            $total = (int) ($baris->total ?? 0);
            $trenBulanan[] = [
                'label' => $label,
                'nilai' => $total,
            ];
            $perBulanRinci[$angka] = [
                'total' => $total,
                'transaksi' => (int) ($baris->jumlah_transaksi ?? 0),
            ];
        }

        $perProgram = DB::table('penyaluran_item as pyi')
            ->join('penyaluran as py', 'py.id', '=', 'pyi.penyaluran_id')
            ->join('program as pr', 'pr.id', '=', 'py.program_id')
            ->where('py.status', 'dicairkan')
            ->whereBetween('py.tanggal', [$awalTahun, $akhirTahun])
            ->groupBy('pr.nama')
            ->selectRaw('pr.nama as label, COALESCE(SUM(pyi.jumlah), 0)::bigint as nilai')
            ->orderByDesc('nilai')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'nilai' => (int) $r->nilai])
            ->all();

        $perAsnaf = DB::table('penyaluran_item as pyi')
            ->join('penyaluran as py', 'py.id', '=', 'pyi.penyaluran_id')
            ->leftJoin('asnaf as a', 'a.id', '=', 'pyi.asnaf_id')
            ->where('py.status', 'dicairkan')
            ->whereBetween('py.tanggal', [$awalTahun, $akhirTahun])
            ->groupBy(DB::raw("COALESCE(a.nama, 'Non-Zakat')"))
            ->selectRaw("COALESCE(a.nama, 'Non-Zakat') as label, COALESCE(SUM(pyi.jumlah), 0)::bigint as nilai")
            ->orderByDesc('nilai')
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'nilai' => (int) $r->nilai])
            ->all();

        $permohonanAktif = Permohonan::query()
            ->whereBetween('tanggal', [$awalTahun, $akhirTahun])
            ->whereNotIn('status_kode', ['diterima_mustahik', 'ditolak'])
            ->count();
        $permohonanSelesai = Permohonan::query()
            ->whereBetween('tanggal', [$awalTahun, $akhirTahun])
            ->where('status_kode', 'diterima_mustahik')
            ->count();
        $permohonanDitolak = Permohonan::query()
            ->whereBetween('tanggal', [$awalTahun, $akhirTahun])
            ->where('status_kode', 'ditolak')
            ->count();

        $totalPenyaluran = (int) ($ringkasPenyaluran->total_penyaluran ?? 0);
        $rasioPenyaluran = HitungRkat::persen($totalPenyaluran, $totalPenerimaan);

        return [
            'total_penyaluran' => $totalPenyaluran,
            'total_komitmen' => (int) ($ringkasPenyaluran->total_komitmen ?? 0),
            'jumlah_transaksi' => (int) ($ringkasPenyaluran->jumlah_transaksi ?? 0),
            'mustahik_terbantu' => (int) ($ringkasPenyaluran->mustahik_terbantu ?? 0),
            'total_mustahik_terverifikasi' => Mustahik::where('aktif', true)->where('status_verifikasi', 'terverifikasi')->count(),
            'total_pendistribusian' => (int) ($ringkasPenyaluran->total_pendistribusian ?? 0),
            'total_pendayagunaan' => (int) ($ringkasPenyaluran->total_pendayagunaan ?? 0),
            'permohonan_aktif' => $permohonanAktif,
            'permohonan_selesai' => $permohonanSelesai,
            'permohonan_ditolak' => $permohonanDitolak,
            'rasio_penyaluran' => $rasioPenyaluran,
            'rasio_penyaluran_label' => HitungRkat::tampil($rasioPenyaluran),
            'tren_bulanan' => $trenBulanan,
            'penyaluran_per_program' => $perProgram,
            'penyaluran_per_asnaf' => $perAsnaf,
            'per_bulan_rinci' => $perBulanRinci,
        ];
    }

    /**
     * @param  array<int, array{total: int, transaksi: int}>  $terima
     * @param  array<int, array{total: int, transaksi: int}>  $salur
     * @return list<array<string, mixed>>
     */
    private function susunRekapBulanan(array $terima, array $salur): array
    {
        $baris = [];
        foreach (self::NAMA_BULAN_PANJANG as $bulan => $nama) {
            $penerimaan = (int) ($terima[$bulan]['total'] ?? 0);
            $penyaluran = (int) ($salur[$bulan]['total'] ?? 0);

            $baris[] = [
                'bulan' => $bulan,
                'label' => $nama,
                'label_singkat' => self::NAMA_BULAN[$bulan],
                'penerimaan' => $penerimaan,
                'penyaluran' => $penyaluran,
                'selisih' => $penerimaan - $penyaluran,
                'transaksi_terima' => (int) ($terima[$bulan]['transaksi'] ?? 0),
                'transaksi_salur' => (int) ($salur[$bulan]['transaksi'] ?? 0),
            ];
        }

        return $baris;
    }
}
