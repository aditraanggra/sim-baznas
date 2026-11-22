<?php

namespace App\Actions\Master;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Services\MasterDataRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ToggleAktifMasterGenerik extends Action
{
    use MencatatAudit;

    public function jalankan(array $data): Model
    {
        $jenis = (string) ($data['jenis'] ?? '');
        $kelas = MasterDataRegistry::kelasModel($jenis);

        $tervalidasi = validator($data, [
            'id' => ['required', 'integer'],
            'aktif' => ['nullable', 'boolean'],
            'alasan' => ['nullable', 'string', 'max:255'],
        ])->validate();

        return DB::transaction(function () use ($kelas, $tervalidasi): Model {
            /** @var Model $model */
            $model = $kelas::findOrFail($tervalidasi['id']);
            $lama = ['aktif' => (bool) $model->getAttribute('aktif')];
            $statusBaru = array_key_exists('aktif', $tervalidasi) && $tervalidasi['aktif'] !== null
                ? (bool) $tervalidasi['aktif']
                : ! $lama['aktif'];

            $model->forceFill(['aktif' => $statusBaru])->save();

            $this->catatAudit(
                $statusBaru ? 'aktifkan' : 'nonaktifkan',
                $kelas,
                $model->getKey(),
                $lama,
                ['aktif' => $statusBaru],
                $tervalidasi['alasan'] ?? null,
            );

            return $model;
        });
    }
}
