<?php

namespace App\Services;

use App\Models\DanaKelompok;
use App\Models\SumberDana;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SaldoDanaService
{
    public function semuaSaldo(): Collection
    {
        return DB::table('v_saldo_kelompok_dana')
            ->orderBy('dana_kelompok_id')
            ->get()
            ->map(fn ($r) => [
                'dana_kelompok_id' => (int) $r->dana_kelompok_id,
                'kode' => $r->kode,
                'nama' => $r->nama,
                'total_masuk' => (int) $r->total_masuk,
                'total_keluar' => (int) $r->total_keluar,
                'total_komitmen' => (int) $r->total_komitmen,
                'saldo_tersedia' => (int) $r->saldo_tersedia,
            ]);
    }

    /**
     * Menghitung saldo per Kelompok Dana, dengan opsi mengecualikan penyaluran tertentu
     * (misal saat mencairkan penyaluran yang sebelumnya sudah berstatus disetujui).
     *
     * @param  array<int, array{sumber_dana_id: int, jumlah: int}>  $items
     * @return array{cukup: bool, peringatan: list<string>, detail: list<array<string, mixed>>}
     */
    public function periksaKecukupan(array $items, ?int $kecualikanPenyaluranId = null): array
    {
        if (empty($items)) {
            return ['cukup' => true, 'peringatan' => [], 'detail' => []];
        }

        $sumberList = SumberDana::query()
            ->with('danaKelompok:id,kode,nama')
            ->whereIn('id', collect($items)->pluck('sumber_dana_id')->unique()->all())
            ->get()
            ->keyBy('id');

        $kebutuhanPerKelompok = [];
        foreach ($items as $item) {
            $sumber = $sumberList->get((int) $item['sumber_dana_id']);
            if (! $sumber || ! $sumber->dana_kelompok_id) {
                continue;
            }
            $kelompokId = (int) $sumber->dana_kelompok_id;
            $kebutuhanPerKelompok[$kelompokId] = ($kebutuhanPerKelompok[$kelompokId] ?? 0) + (int) $item['jumlah'];
        }

        if (empty($kebutuhanPerKelompok)) {
            return ['cukup' => true, 'peringatan' => [], 'detail' => []];
        }

        $kelompokIds = array_keys($kebutuhanPerKelompok);
        $saldoView = DB::table('v_saldo_kelompok_dana')
            ->whereIn('dana_kelompok_id', $kelompokIds)
            ->get()
            ->keyBy('dana_kelompok_id');

        $komitmenSendiri = [];
        if ($kecualikanPenyaluranId !== null) {
            $komitmenSendiri = DB::table('penyaluran_item as pyi')
                ->join('penyaluran as py', 'py.id', '=', 'pyi.penyaluran_id')
                ->join('sumber_dana as sd', 'sd.id', '=', 'pyi.sumber_dana_id')
                ->where('py.id', $kecualikanPenyaluranId)
                ->where('py.status', 'disetujui')
                ->whereIn('sd.dana_kelompok_id', $kelompokIds)
                ->groupBy('sd.dana_kelompok_id')
                ->selectRaw('sd.dana_kelompok_id, COALESCE(SUM(pyi.jumlah), 0)::bigint as total')
                ->pluck('total', 'sd.dana_kelompok_id')
                ->all();
        }

        $kelompokModel = DanaKelompok::whereIn('id', $kelompokIds)->get()->keyBy('id');

        $peringatan = [];
        $detail = [];
        $semuaCukup = true;

        foreach ($kebutuhanPerKelompok as $kelompokId => $dibutuhkan) {
            $baris = $saldoView->get($kelompokId);
            $namaKelompok = $baris?->nama ?? $kelompokModel->get($kelompokId)?->nama ?? "Kelompok #{$kelompokId}";
            $saldoTersedia = (int) ($baris?->saldo_tersedia ?? 0) + (int) ($komitmenSendiri[$kelompokId] ?? 0);
            $cukup = $saldoTersedia >= $dibutuhkan;

            if (! $cukup) {
                $semuaCukup = false;
                $peringatan[] = sprintf(
                    'Saldo Kelompok Dana %s tidak mencukupi (tersedia Rp %s, dibutuhkan Rp %s).',
                    $namaKelompok,
                    number_format($saldoTersedia, 0, ',', '.'),
                    number_format($dibutuhkan, 0, ',', '.'),
                );
            }

            $detail[] = [
                'dana_kelompok_id' => $kelompokId,
                'nama' => $namaKelompok,
                'saldo_tersedia' => $saldoTersedia,
                'dibutuhkan' => $dibutuhkan,
                'cukup' => $cukup,
            ];
        }

        return [
            'cukup' => $semuaCukup,
            'peringatan' => $peringatan,
            'detail' => $detail,
        ];
    }
}
