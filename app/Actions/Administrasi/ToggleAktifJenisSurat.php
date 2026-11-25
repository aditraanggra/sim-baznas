<?php

namespace App\Actions\Administrasi;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\JenisSurat;
use App\Services\Administrasi\AksesPersuratanService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

class ToggleAktifJenisSurat extends Action
{
    use MencatatAudit;

    public function __construct(private readonly AksesPersuratanService $akses) {}

    public function jalankan(array $data): JenisSurat
    {
        $user = Auth::user();
        if (! $user || ! $this->akses->bolehKelolaSurat($user)) {
            throw new AuthorizationException('Anda tidak memiliki wewenang mengubah status jenis surat.');
        }

        $tervalidasi = validator($data, [
            'id' => ['required', 'integer', 'exists:jenis_surat,id'],
        ])->validate();

        $jenis = JenisSurat::findOrFail((int) $tervalidasi['id']);
        $lama = ['aktif' => $jenis->aktif];
        $jenis->update(['aktif' => ! $jenis->aktif]);

        $this->catatAudit(
            $jenis->aktif ? 'aktifkan' : 'nonaktifkan',
            JenisSurat::class,
            $jenis->id,
            $lama,
            ['aktif' => $jenis->aktif],
        );

        return $jenis->fresh();
    }
}
