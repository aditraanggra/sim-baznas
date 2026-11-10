<?php

namespace App\Services\Rkat;

use App\Models\RkatValue;
use App\Models\RkatVersion;
use Illuminate\Support\Collection;

/**
 * FR-R-07: selisih dan persen per pos baku antara dua versi RKAT.
 * BR-10: nilai tak terpetakan tidak ikut total; hanya dilaporkan sebagai peringatan.
 */
final class BandingkanRkat
{
    /**
     * @return array{baris: array<int, array<string, mixed>>, tak_terpetakan_a: array{jumlah: int, kode: list<string>}, tak_terpetakan_b: array{jumlah: int, kode: list<string>}}
     */
    public static function bandingkan(RkatVersion $a, RkatVersion $b): array
    {
        $ha = HitungRkat::untukVersi($a);
        $hb = HitungRkat::untukVersi($b);

        $baris = $ha['pos']->keyBy('id')->union($hb['pos']->keyBy('id'))
            ->sortBy([['lampiran', 'asc'], ['urutan', 'asc']])
            ->map(function ($p) use ($ha, $hb) {
                $nilaiA = $ha['nilai'][$p->id] ?? 0;
                $nilaiB = $hb['nilai'][$p->id] ?? 0;
                $selisih = $nilaiB - $nilaiA;
                $persen = $nilaiA !== 0 ? $selisih * 100 / $nilaiA : null;

                return [
                    'id' => $p->id, 'kode' => $p->kode, 'nama' => $p->nama, 'lampiran' => $p->lampiran,
                    'grup' => HitungRkat::grup($p), 'induk_id' => $p->induk_id, 'tipe_nilai' => $p->tipe_nilai,
                    'agregasi' => $p->agregasi, 'nilai_a' => $nilaiA, 'nilai_b' => $nilaiB,
                    'selisih' => $selisih, 'persen' => $persen, 'persen_tampil' => HitungRkat::tampil($persen),
                ];
            })
            ->values()
            ->all();

        return [
            'baris' => $baris,
            'tak_terpetakan_a' => self::ringkas($ha['tak_terpetakan']),
            'tak_terpetakan_b' => self::ringkas($hb['tak_terpetakan']),
        ];
    }

    /** @param  Collection<int, RkatValue>  $tak
     * @return array{jumlah: int, kode: list<string>} */
    private static function ringkas(Collection $tak): array
    {
        return ['jumlah' => $tak->count(), 'kode' => $tak->pluck('kode_asal')->values()->all()];
    }
}
