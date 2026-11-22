<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LaporanPendistribusianService
{
    public function __construct(private readonly PeriodeService $periodeService) {}

    /**
     * @return array<string, mixed>
     */
    public function ringkas(array $filter): array
    {
        $periode = $this->periodeService->selesaikan($filter, 'bulanan');

        $base = DB::table('penyaluran_item as pyi')
            ->join('penyaluran as py', 'py.id', '=', 'pyi.penyaluran_id')
            ->join('program as pr', 'pr.id', '=', 'py.program_id')
            ->join('sumber_dana as sd', 'sd.id', '=', 'pyi.sumber_dana_id')
            ->join('jenis_dana as jd', 'jd.id', '=', 'sd.jenis_dana_id')
            ->join('bentuk_bantuan as bb', 'bb.id', '=', 'pyi.bentuk_bantuan_id')
            ->join('mustahik as m', 'm.id', '=', 'py.mustahik_id')
            ->join('metode_bayar as mb', 'mb.id', '=', 'py.metode_bayar_id')
            ->leftJoin('asnaf as a', 'a.id', '=', 'pyi.asnaf_id')
            ->leftJoin('kecamatan as kec', 'kec.id', '=', 'm.kecamatan_id')
            ->where('py.status', 'dicairkan')
            ->whereBetween('py.tanggal', [$periode['mulai']->toDateString(), $periode['selesai']->toDateString()])
            ->when(! empty($filter['sub_bidang']), fn ($q) => $q->where('py.sub_bidang', (string) $filter['sub_bidang']))
            ->when(! empty($filter['program_id']), fn ($q) => $q->where('py.program_id', (int) $filter['program_id']))
            ->when(! empty($filter['jenis_dana_id']), fn ($q) => $q->where('sd.jenis_dana_id', (int) $filter['jenis_dana_id']))
            ->when(! empty($filter['sumber_dana_id']), fn ($q) => $q->where('pyi.sumber_dana_id', (int) $filter['sumber_dana_id']))
            ->when(! empty($filter['asnaf_id']), fn ($q) => $q->where('pyi.asnaf_id', (int) $filter['asnaf_id']))
            ->when(! empty($filter['kecamatan_id']), fn ($q) => $q->where('m.kecamatan_id', (int) $filter['kecamatan_id']))
            ->when(! empty($filter['desa_id']), fn ($q) => $q->where('m.desa_id', (int) $filter['desa_id']));

        $totalNominal = (int) (clone $base)->sum('pyi.jumlah');
        $jumlahTransaksi = (int) (clone $base)->distinct()->count('py.id');
        $jumlahMustahik = (int) (clone $base)->distinct()->count('py.mustahik_id');

        $perSubBidang = (clone $base)
            ->select([
                'py.sub_bidang',
                DB::raw('SUM(pyi.jumlah)::bigint as total'),
            ])
            ->groupBy('py.sub_bidang')
            ->orderBy('py.sub_bidang')
            ->get()
            ->map(fn ($r) => [
                'kode' => $r->sub_bidang,
                'nama' => $r->sub_bidang === 'pendayagunaan' ? 'Pendayagunaan' : 'Pendistribusian',
                'total' => (int) $r->total,
            ])
            ->all();

        $perProgram = (clone $base)
            ->select([
                'pr.id',
                'pr.kode',
                'pr.nama',
                'pr.sub_bidang',
                DB::raw('SUM(pyi.jumlah)::bigint as total'),
            ])
            ->groupBy('pr.id', 'pr.kode', 'pr.nama', 'pr.sub_bidang')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'kode' => $r->kode,
                'nama' => $r->nama,
                'sub_bidang' => $r->sub_bidang,
                'total' => (int) $r->total,
            ])
            ->all();

        $perAsnaf = (clone $base)
            ->select([
                DB::raw("COALESCE(a.nama, 'Non-Zakat / Tanpa Asnaf') as nama_asnaf"),
                DB::raw('SUM(pyi.jumlah)::bigint as total'),
            ])
            ->groupBy(DB::raw("COALESCE(a.nama, 'Non-Zakat / Tanpa Asnaf')"))
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'nama' => $r->nama_asnaf,
                'total' => (int) $r->total,
            ])
            ->all();

        $perKecamatan = (clone $base)
            ->select([
                DB::raw("COALESCE(kec.nama, 'Luar / Belum Terpetakan') as nama_kecamatan"),
                DB::raw('COUNT(DISTINCT py.mustahik_id)::int as mustahik'),
                DB::raw('SUM(pyi.jumlah)::bigint as total'),
            ])
            ->groupBy(DB::raw("COALESCE(kec.nama, 'Luar / Belum Terpetakan')"))
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'nama' => $r->nama_kecamatan,
                'mustahik' => (int) $r->mustahik,
                'total' => (int) $r->total,
            ])
            ->all();

        $trenTanggal = (clone $base)
            ->select([
                'py.tanggal',
                DB::raw('SUM(pyi.jumlah)::bigint as total'),
            ])
            ->groupBy('py.tanggal')
            ->orderBy('py.tanggal')
            ->get()
            ->map(fn ($r) => [
                'label' => Carbon::parse($r->tanggal)->format('d/m'),
                'nilai' => (int) $r->total,
            ])
            ->all();

        $baris = (clone $base)
            ->select([
                'pyi.id',
                'py.id as penyaluran_id',
                'py.tanggal',
                'py.nomor as nomor_penyaluran',
                'py.sub_bidang',
                'm.nama as nama_mustahik',
                'pr.nama as nama_program',
                'jd.nama as jenis_dana',
                'sd.nama as sumber_dana',
                'a.nama as nama_asnaf',
                'bb.nama as bentuk_bantuan',
                'pyi.uraian_barang',
                'mb.nama as metode',
                'pyi.jumlah',
            ])
            ->orderByDesc('py.tanggal')
            ->orderByDesc('py.id')
            ->limit(500)
            ->get()
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'penyaluran_id' => (int) $r->penyaluran_id,
                'tanggal' => Carbon::parse($r->tanggal)->format('d/m/Y'),
                'nomor_penyaluran' => $r->nomor_penyaluran ?? '-',
                'sub_bidang' => $r->sub_bidang,
                'mustahik' => $r->nama_mustahik,
                'program' => $r->nama_program,
                'jenis_dana' => $r->jenis_dana,
                'sumber_dana' => $r->sumber_dana,
                'asnaf' => $r->nama_asnaf ?? '-',
                'bentuk_bantuan' => $r->bentuk_bantuan,
                'uraian_barang' => $r->uraian_barang,
                'metode' => $r->metode,
                'jumlah' => (int) $r->jumlah,
            ])
            ->all();

        return [
            'periode' => [
                'jenis' => $periode['jenis'],
                'tahun' => $periode['tahun'],
                'bulan' => $periode['bulan'],
                'triwulan' => $periode['triwulan'],
                'semester' => $periode['semester'],
                'tanggal' => $periode['tanggal'],
                'label' => $periode['label'],
                'mulai' => $periode['mulai']->toDateString(),
                'selesai' => $periode['selesai']->toDateString(),
            ],
            'total_nominal' => $totalNominal,
            'jumlah_transaksi' => $jumlahTransaksi,
            'jumlah_mustahik' => $jumlahMustahik,
            'per_sub_bidang' => $perSubBidang,
            'per_program' => $perProgram,
            'per_asnaf' => $perAsnaf,
            'per_kecamatan' => $perKecamatan,
            'tren_tanggal' => $trenTanggal,
            'baris' => $baris,
        ];
    }
}
