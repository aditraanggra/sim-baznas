<?php

namespace App\Services;

use App\Models\PosAlias;
use App\Models\PosAnggaran;
use Illuminate\Support\Collection;

class PohonPos
{
    /**
     * Pos memakai path yang memuat kode; untuk alias tahun tertentu dipetakan dulu.
     * BR-10: pos tanpa pemetaan dikembalikan terpisah, tidak ikut agregasi.
     */
    public static function petakan(int $tahun, array $kodeList): array
    {
        $pos = PosAnggaran::query()
            ->where('aktif', true)
            ->where('berlaku_dari_tahun', '<=', $tahun)
            ->where(fn ($q) => $q->whereNull('berlaku_sampai_tahun')->orWhere('berlaku_sampai_tahun', '>=', $tahun))
            ->get();

        $byKode = $pos->keyBy(fn (PosAnggaran $p) => $p->kode.'|'.$p->tipe_nilai);
        $alias = PosAlias::where('tahun', $tahun)->get()->keyBy('kode_alias');

        $dipetakan = [];
        $takTerpetakan = [];

        foreach ($kodeList as $kode) {
            if (isset($byKode[$kode])) {
                $dipetakan[$kode] = $byKode[$kode];
            } elseif ($alias->has($kode)) {
                $dipetakan[$kode] = $alias[$kode]->pos;
            } else {
                $takTerpetakan[] = $kode;
            }
        }

        return ['dipetakan' => $dipetakan, 'tak_terpetakan' => $takTerpetakan];
    }

    /** @return Collection<int, PosAnggaran> */
    public static function anak(PosAnggaran $pos): Collection
    {
        return PosAnggaran::where('induk_id', $pos->id)->orderBy('urutan')->get();
    }

    /** Agregasi: jumlah anak bila jumlah_anak, nilai sendiri bila input_langsung. */
    public static function nilaiAgregat(PosAnggaran $pos, callable $nilaiDaun): int
    {
        if ($pos->agregasi !== 'jumlah_anak') {
            return (int) $nilaiDaun($pos);
        }

        return self::anak($pos)->sum(fn (PosAnggaran $a) => self::nilaiAgregat($a, $nilaiDaun));
    }
}
