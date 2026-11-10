<?php

namespace App\Services\Rkat;

use App\Models\PosAlias;
use App\Models\PosAnggaran;

/** Kode dari berkas -> pos baku: kode baku langsung, lalu pos_alias tahun itu; selain itu tak terpetakan (BR-10). */
final class PemetaanPos
{
    /**
     * @param  list<string>  $kodeList
     * @return array<string, ?PosAnggaran> kode => pos (null = tak terpetakan)
     */
    public static function untuk(int $tahun, array $kodeList): array
    {
        $baku = PosAnggaran::where('aktif', true)->whereIn('kode', $kodeList)->get()->keyBy('kode');
        $alias = PosAlias::with('pos')->where('tahun', $tahun)->whereIn('kode_alias', $kodeList)->get()->keyBy('kode_alias');

        $hasil = [];
        foreach ($kodeList as $kode) {
            $hasil[$kode] = $baku->get($kode) ?? $alias->get($kode)?->pos;
        }

        return $hasil;
    }
}
