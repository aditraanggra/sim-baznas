<?php

namespace App\Actions\Administrasi;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\TemplateSurat;
use App\Services\Administrasi\AksesPersuratanService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

class ToggleAktifTemplateSurat extends Action
{
    use MencatatAudit;

    public function __construct(private readonly AksesPersuratanService $akses) {}

    public function jalankan(array $data): TemplateSurat
    {
        $user = Auth::user();
        if (! $user || ! $this->akses->bolehKelolaSurat($user)) {
            throw new AuthorizationException('Anda tidak memiliki wewenang mengubah status template surat.');
        }

        $tervalidasi = validator($data, [
            'id' => ['required', 'integer', 'exists:template_surat,id'],
        ])->validate();

        $template = TemplateSurat::findOrFail((int) $tervalidasi['id']);
        $lama = ['aktif' => $template->aktif];
        $template->update(['aktif' => ! $template->aktif]);

        $this->catatAudit(
            $template->aktif ? 'aktifkan' : 'nonaktifkan',
            TemplateSurat::class,
            $template->id,
            $lama,
            ['aktif' => $template->aktif],
        );

        return $template->fresh();
    }
}
