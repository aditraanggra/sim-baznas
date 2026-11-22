<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class NotifikasiService
{
    public function kirimKeUser(
        int|User $user,
        string $jenis,
        string $judul,
        string $pesan,
        ?string $tautan = null,
        ?string $refTabel = null,
        ?int $refId = null,
    ): Notifikasi {
        $userId = $user instanceof User ? $user->id : $user;

        return Notifikasi::create([
            'user_id' => $userId,
            'jenis' => $jenis,
            'judul' => mb_substr($judul, 0, 160),
            'pesan' => $pesan,
            'tautan' => $tautan,
            'ref_tabel' => $refTabel,
            'ref_id' => $refId,
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * @param  list<string>|string  $peran
     * @return Collection<int, Notifikasi>
     */
    public function kirimKePeran(
        array|string $peran,
        string $jenis,
        string $judul,
        string $pesan,
        ?string $tautan = null,
        ?string $refTabel = null,
        ?int $refId = null,
        ?int $kecualikanUserId = null,
    ): Collection {
        $daftarPeran = is_array($peran) ? $peran : [$peran];

        $penerima = User::query()
            ->where('status', 'aktif')
            ->role($daftarPeran)
            ->when($kecualikanUserId !== null, fn ($q) => $q->where('id', '!=', $kecualikanUserId))
            ->get(['id']);

        return $penerima->map(fn (User $u) => $this->kirimKeUser(
            $u->id,
            $jenis,
            $judul,
            $pesan,
            $tautan,
            $refTabel,
            $refId,
        ));
    }

    public function tandaiDibaca(Notifikasi $notifikasi, User $user): bool
    {
        if ((int) $notifikasi->user_id !== (int) $user->id) {
            return false;
        }

        if ($notifikasi->dibaca_at === null) {
            $notifikasi->forceFill(['dibaca_at' => now()])->save();
        }

        return true;
    }

    public function tandaiSemuaDibaca(User $user): int
    {
        return Notifikasi::query()
            ->where('user_id', $user->id)
            ->whereNull('dibaca_at')
            ->update(['dibaca_at' => now()]);
    }
}
