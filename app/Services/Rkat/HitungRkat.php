<?php

namespace App\Services\Rkat;

use App\Models\PosAnggaran;
use App\Models\RkatValue;
use App\Models\RkatVersion;
use Illuminate\Support\Collection;

/**
 * BR-03/BR-12: subtotal dan persen selalu dihitung di server dari nilai daun.
 * Persen dihitung dari nilai asli; pembulatan hanya saat tampil.
 */
final class HitungRkat
{
    /**
     * @return array{pos: Collection<int, PosAnggaran>, nilai: array<int, int>, per_kode: array<string, int>, tak_terpetakan: Collection<int, RkatValue>}
     */
    public static function untukVersi(RkatVersion $versi): array
    {
        $nilaiTersimpan = $versi->nilai()->get();
        $daun = $nilaiTersimpan->whereNotNull('pos_anggaran_id')->pluck('nilai', 'pos_anggaran_id')->all();

        // Masa berlaku pos (berlaku_dari/sampai_tahun) belum dipakai; lihat docs/open-questions.md temuan 30 (seed PK0 memberi 2026 padahal data 2022-2031 memakai kode sama).
        $pos = PosAnggaran::query()
            ->where(fn ($q) => $q->where('aktif', true)->orWhereIn('id', array_keys($daun)))
            ->get()
            ->sortBy(fn (PosAnggaran $p) => [$p->lampiran, self::grup($p) === 'L2/LN' ? 1 : 0, $p->urutan, $p->id])
            ->values();

        $nilai = self::agregat($pos, $daun);

        return [
            'pos' => $pos,
            'nilai' => $nilai,
            'per_kode' => $pos->mapWithKeys(fn (PosAnggaran $p) => [$p->kode => $nilai[$p->id]])->all(),
            'tak_terpetakan' => $nilaiTersimpan->whereNull('pos_anggaran_id')->values(),
        ];
    }

    /**
     * Pos jumlah_anak = jumlah anak; selain itu nilai daun sendiri (0 bila kosong).
     *
     * @param  Collection<int, PosAnggaran>  $pos
     * @param  array<int, int>  $daun  pos_anggaran_id => nilai
     * @return array<int, int> pos_anggaran_id => nilai agregat
     */
    public static function agregat(Collection $pos, array $daun): array
    {
        $anak = $pos->groupBy('induk_id');
        $hasil = [];

        $hitung = function (PosAnggaran $p) use (&$hitung, &$hasil, $anak, $daun): int {
            if (isset($hasil[$p->id])) {
                return $hasil[$p->id];
            }

            return $hasil[$p->id] = $p->agregasi === 'jumlah_anak'
                ? (int) ($anak[$p->id] ?? collect())->sum(fn (PosAnggaran $a) => $hitung($a))
                : (int) ($daun[$p->id] ?? 0);
        };

        foreach ($pos as $p) {
            $hitung($p);
        }

        return $hasil;
    }

    /**
     * Penyebut persen: jumlah pos puncak sejenis dalam satu lampiran (luar neraca L2/LN terpisah).
     *
     * @param  Collection<int, PosAnggaran>  $pos
     * @param  array<int, int>  $nilai
     * @return array<string, int> kunci grup => total
     */
    public static function totalPuncak(Collection $pos, array $nilai): array
    {
        $total = [];
        foreach ($pos->whereNull('induk_id') as $p) {
            $kunci = self::grup($p);
            $total[$kunci] = ($total[$kunci] ?? 0) + $nilai[$p->id];
        }

        return $total;
    }

    public static function grup(PosAnggaran $pos): string
    {
        return str_starts_with($pos->kode, 'L2/LN/') ? 'L2/LN' : $pos->lampiran;
    }

    /** Persen tanpa pembulatan; null (n/a) bila penyebut nol. */
    public static function persen(int $nilai, int $total): ?float
    {
        return $total > 0 ? $nilai * 100 / $total : null;
    }

    public static function tampil(?float $persen): string
    {
        return $persen === null ? 'n/a' : number_format($persen, 1, ',', '.').'%';
    }
}
