<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LaporanPenerimaanService
{
    public function __construct(private readonly PeriodeService $periodeService) {}

    /**
     * @return array<string, mixed>
     */
    public function ringkas(array $filter): array
    {
        $periode = $this->periodeService->selesaikan($filter, 'bulanan');

        $base = DB::table('penerimaan_item as pi')
            ->join('penerimaan as p', 'p.id', '=', 'pi.penerimaan_id')
            ->join('sumber_dana as sd', 'sd.id', '=', 'pi.sumber_dana_id')
            ->join('jenis_dana as jd', 'jd.id', '=', 'sd.jenis_dana_id')
            ->join('muzakki as m', 'm.id', '=', 'p.muzakki_id')
            ->join('metode_bayar as mb', 'mb.id', '=', 'p.metode_bayar_id')
            ->leftJoin('upz as u', 'u.id', '=', 'p.upz_id')
            ->leftJoin('kwitansi as k', 'k.penerimaan_id', '=', 'p.id')
            ->where('p.status', 'final')
            ->whereBetween('p.tanggal', [$periode['mulai']->toDateString(), $periode['selesai']->toDateString()])
            ->when(! empty($filter['jenis_dana_id']), fn ($q) => $q->where('sd.jenis_dana_id', (int) $filter['jenis_dana_id']))
            ->when(! empty($filter['sumber_dana_id']), fn ($q) => $q->where('pi.sumber_dana_id', (int) $filter['sumber_dana_id']))
            ->when(! empty($filter['upz_id']), fn ($q) => $q->where('p.upz_id', (int) $filter['upz_id']))
            ->when(! empty($filter['metode_bayar_id']), fn ($q) => $q->where('p.metode_bayar_id', (int) $filter['metode_bayar_id']));

        $totalNominal = (int) (clone $base)->sum('pi.jumlah');
        $jumlahTransaksi = (int) (clone $base)->distinct()->count('p.id');

        $perJenisDana = (clone $base)
            ->select([
                'jd.id',
                'jd.kode',
                'jd.nama',
                DB::raw('SUM(pi.jumlah)::bigint as total'),
            ])
            ->groupBy('jd.id', 'jd.kode', 'jd.nama')
            ->orderBy('jd.id')
            ->get()
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'kode' => $r->kode,
                'nama' => $r->nama,
                'total' => (int) $r->total,
            ])
            ->all();

        $perSumberDana = (clone $base)
            ->select([
                'sd.id',
                'sd.kode',
                'sd.nama',
                'jd.nama as jenis_dana',
                DB::raw('SUM(pi.jumlah)::bigint as total'),
            ])
            ->groupBy('sd.id', 'sd.kode', 'sd.nama', 'jd.nama')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'kode' => $r->kode,
                'nama' => $r->nama,
                'jenis_dana' => $r->jenis_dana,
                'total' => (int) $r->total,
            ])
            ->all();

        $trenTanggal = (clone $base)
            ->select([
                'p.tanggal',
                DB::raw('SUM(pi.jumlah)::bigint as total'),
            ])
            ->groupBy('p.tanggal')
            ->orderBy('p.tanggal')
            ->get()
            ->map(fn ($r) => [
                'label' => Carbon::parse($r->tanggal)->format('d/m'),
                'nilai' => (int) $r->total,
            ])
            ->all();

        $baris = (clone $base)
            ->select([
                'pi.id',
                'p.id as penerimaan_id',
                'p.tanggal',
                DB::raw('COALESCE(k.nomor, p.nomor) as nomor_kwitansi'),
                'm.nama as muzakki',
                'jd.nama as jenis_dana',
                'sd.nama as sumber_dana',
                'mb.nama as metode_bayar',
                'u.nama as upz_nama',
                'pi.jumlah',
                'pi.keterangan',
            ])
            ->orderByDesc('p.tanggal')
            ->orderByDesc('p.id')
            ->limit(1000)
            ->get()
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'penerimaan_id' => (int) $r->penerimaan_id,
                'tanggal' => Carbon::parse($r->tanggal)->format('d/m/Y'),
                'nomor_kwitansi' => $r->nomor_kwitansi,
                'muzakki' => $r->muzakki,
                'jenis_dana' => $r->jenis_dana,
                'sumber_dana' => $r->sumber_dana,
                'metode_bayar' => $r->metode_bayar,
                'upz_nama' => $r->upz_nama,
                'jumlah' => (int) $r->jumlah,
                'keterangan' => $r->keterangan,
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
            ],
            'total_nominal' => $totalNominal,
            'jumlah_transaksi' => $jumlahTransaksi,
            'per_jenis_dana' => $perJenisDana,
            'per_sumber_dana' => $perSumberDana,
            'tren_harian' => $trenTanggal,
            'baris' => $baris,
        ];
    }
}
