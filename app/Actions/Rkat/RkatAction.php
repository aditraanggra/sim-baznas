<?php

namespace App\Actions\Rkat;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\RkatVersion;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/** Dasar Action RKAT: hak tulis (PRD 2: perencanaan, admin) dan penguncian status ditegakkan di server. */
abstract class RkatAction extends Action
{
    use MencatatAudit;

    public const PERAN_TULIS = ['perencanaan', 'admin'];

    protected function pastikanPenulis(): void
    {
        if (! Auth::user()?->hasAnyRole(self::PERAN_TULIS)) {
            throw new AuthorizationException('Hanya Staf Perencanaan atau Admin yang dapat mengubah RKAT.');
        }
    }

    protected function versiTerkunci(int $id): RkatVersion
    {
        return RkatVersion::query()->lockForUpdate()->findOrFail($id);
    }

    /** BR-02: versi selain draft terkunci. */
    protected function pastikanDraft(RkatVersion $versi): void
    {
        if ($versi->terkunci()) {
            $this->gagal('versi', "Versi berstatus {$versi->status} terkunci; perubahan lewat versi Perubahan baru (BR-02).");
        }
    }

    protected function gagal(string $kunci, string $pesan): never
    {
        throw ValidationException::withMessages([$kunci => $pesan]);
    }
}
