<?php

namespace App\Actions\Perencanaan;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

abstract class PerencanaanAction extends Action
{
    use MencatatAudit;

    protected function pastikanAksi(string $aksi, string $pesan = 'Anda tidak memiliki izin untuk aksi perencanaan ini.'): void
    {
        $user = Auth::user();

        if (! $user || ! $user->bolehAksiBidang('pkp', $aksi)) {
            throw new AuthorizationException($pesan);
        }
    }
}
