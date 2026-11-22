<?php

namespace App\Actions\Master;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Services\MasterDataRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SimpanMasterGenerik extends Action
{
    use MencatatAudit;

    public function jalankan(array $data): Model
    {
        $jenis = (string) ($data['jenis'] ?? '');
        $definisi = MasterDataRegistry::ambil($jenis);
        $kelas = $definisi['model'];
        $id = isset($data['id']) && $data['id'] !== '' ? (int) $data['id'] : null;

        /** @var callable(int|null, array): array<string, mixed> $pembuatAturan */
        $pembuatAturan = $definisi['aturan'];
        $aturan = $pembuatAturan($id, $data);

        $tervalidasi = validator($data, $aturan)->validate();

        return DB::transaction(function () use ($kelas, $id, $tervalidasi): Model {
            /** @var Model $model */
            $model = $id ? $kelas::findOrFail($id) : new $kelas;
            $lama = $model->exists ? $model->toArray() : null;

            $atribut = $tervalidasi;
            if (! $model->exists) {
                $atribut = $this->denganPembuat($atribut);
                $atribut['aktif'] = true;
            }

            $model->fill($atribut);
            $model->save();

            $this->catatAudit(
                $model->wasRecentlyCreated ? 'buat' : 'ubah',
                $kelas,
                $model->getKey(),
                $lama,
                $model->fresh()->toArray(),
            );

            return $model;
        });
    }
}
