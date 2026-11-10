<?php

namespace App\Actions\Rkat;

use App\Models\PosAlias;
use App\Models\PosAnggaran;
use App\Models\RkatValue;
use Illuminate\Support\Facades\DB;

/**
 * BR-10: memetakan kode tak terpetakan ke pos baku (pos_alias per tahun) lalu memetakan ulang nilai di versi Draft tahun itu.
 * Versi terkunci tidak diubah (BR-02); pemetaannya baru berlaku lewat versi Perubahan.
 */
class PetakanAliasRkat extends RkatAction
{
    /** @return array{alias: PosAlias, dipetakan: int} */
    public function jalankan(array $data): array
    {
        $this->pastikanPenulis();
        $data = validator($data, [
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'kode_alias' => ['required', 'string', 'max:64'],
            'pos_anggaran_id' => ['required', 'integer', 'exists:pos_anggaran,id'],
        ])->validate();

        $pos = PosAnggaran::findOrFail($data['pos_anggaran_id']);
        if ($pos->agregasi !== 'input_langsung') {
            $this->gagal('pos_anggaran_id', "Pos {$pos->kode} dihitung sistem; pilih pos daun (BR-03).");
        }

        return DB::transaction(function () use ($data, $pos) {
            $alias = PosAlias::firstOrNew(['tahun' => $data['tahun'], 'kode_alias' => $data['kode_alias']]);
            $lama = $alias->exists ? ['pos_anggaran_id' => $alias->pos_anggaran_id] : null;
            $alias->pos_anggaran_id = $pos->id;
            $alias->save();
            $this->catatAudit($lama ? 'ubah' : 'buat', PosAlias::class, $alias->id, $lama, [
                'tahun' => $alias->tahun, 'kode_alias' => $alias->kode_alias, 'pos_anggaran_id' => $pos->id,
            ]);

            $dipetakan = RkatValue::query()
                ->whereNull('pos_anggaran_id')
                ->where('kode_asal', $data['kode_alias'])
                ->where('tipe_nilai', $pos->tipe_nilai)
                ->whereHas('versi', fn ($q) => $q->where('tahun', $data['tahun'])->where('status', 'draft'))
                ->update(['pos_anggaran_id' => $pos->id]);

            if ($dipetakan > 0) {
                $this->catatAudit('petakan_alias', PosAlias::class, $alias->id, null, ['kode_alias' => $alias->kode_alias, 'dipetakan' => $dipetakan]);
            }

            return ['alias' => $alias, 'dipetakan' => $dipetakan];
        });
    }
}
