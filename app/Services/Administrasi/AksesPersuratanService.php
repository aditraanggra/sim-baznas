<?php

namespace App\Services\Administrasi;

use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;

class AksesPersuratanService
{
    public const PERAN_PENUH_NON_RAHASIA = [
        'admin',
        'admin_arsip',
        'kabag_sau',
        'ketua',
        'waka_1',
        'waka_2',
        'waka_3',
        'waka_4',
    ];

    public const PERAN_KELOLA_SURAT = [
        'admin',
        'admin_arsip',
        'kabag_sau',
    ];

    public const PERAN_PENYETUJU_KELUAR = [
        'admin',
        'ketua',
        'waka_1',
        'waka_2',
        'waka_3',
        'waka_4',
        'kabag_sau',
    ];

    public function pastikanBukanUpz(User $user): void
    {
        if ($user->adalahUpzEksternal()) {
            throw new AuthorizationException('Akun UPZ eksternal tidak memiliki akses ke modul administrasi dan persuratan internal.');
        }
    }

    public function bolehLihatSemuaNonRahasia(User $user): bool
    {
        return ! $user->adalahUpzEksternal() && $user->hasAnyRole(self::PERAN_PENUH_NON_RAHASIA);
    }

    public function bolehKelolaSurat(User $user): bool
    {
        return ! $user->adalahUpzEksternal() && $user->hasAnyRole(self::PERAN_KELOLA_SURAT);
    }

    public function bolehSetujuiSuratKeluar(User $user): bool
    {
        return ! $user->adalahUpzEksternal() && $user->hasAnyRole(self::PERAN_PENYETUJU_KELUAR);
    }

    public function bolehDisposisiDariKetua(User $user): bool
    {
        return ! $user->adalahUpzEksternal() && $user->hasAnyRole(['admin', 'ketua']);
    }

    public function bolehTutupSuratMasuk(User $user): bool
    {
        return ! $user->adalahUpzEksternal() && $user->hasAnyRole(['admin', 'ketua', 'kabag_sau', 'admin_arsip']);
    }

    public function bolehLihatSuratMasuk(User $user, SuratMasuk $surat): bool
    {
        if ($user->adalahUpzEksternal()) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'admin_arsip', 'ketua'])) {
            return true;
        }

        if ($surat->sifat === 'rahasia') {
            return $this->terlibatPadaSuratMasuk($user, $surat);
        }

        if ($this->bolehLihatSemuaNonRahasia($user)) {
            return true;
        }

        return $this->terlibatPadaSuratMasuk($user, $surat);
    }

    public function bolehLihatSuratKeluar(User $user, SuratKeluar $surat): bool
    {
        if ($user->adalahUpzEksternal()) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'admin_arsip', 'ketua'])) {
            return true;
        }

        if ($surat->sifat === 'rahasia') {
            return $this->terlibatPadaSuratKeluar($user, $surat);
        }

        if ($this->bolehLihatSemuaNonRahasia($user)) {
            return true;
        }

        return $this->terlibatPadaSuratKeluar($user, $surat);
    }

    /**
     * @param  Builder<SuratMasuk>  $query
     * @return Builder<SuratMasuk>
     */
    public function terapkanCakupanSuratMasuk(Builder $query, User $user): Builder
    {
        if ($user->adalahUpzEksternal()) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasAnyRole(['admin', 'admin_arsip', 'ketua'])) {
            return $query;
        }

        $userId = (int) $user->id;
        $lihatSemuaNonRahasia = $this->bolehLihatSemuaNonRahasia($user);

        return $query->where(function (Builder $q) use ($userId, $lihatSemuaNonRahasia): void {
            if ($lihatSemuaNonRahasia) {
                $q->where('sifat', '!=', 'rahasia');
            }

            $q->orWhere(function (Builder $sub) use ($userId): void {
                $sub->where('dicatat_oleh', $userId)
                    ->orWhere('created_by', $userId)
                    ->orWhereHas('disposisi', fn (Builder $d) => $d
                        ->where('kepada_user_id', $userId)
                        ->orWhere('dari_user_id', $userId));
            });
        });
    }

    /**
     * @param  Builder<SuratKeluar>  $query
     * @return Builder<SuratKeluar>
     */
    public function terapkanCakupanSuratKeluar(Builder $query, User $user): Builder
    {
        if ($user->adalahUpzEksternal()) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasAnyRole(['admin', 'admin_arsip', 'ketua'])) {
            return $query;
        }

        $userId = (int) $user->id;
        $lihatSemuaNonRahasia = $this->bolehLihatSemuaNonRahasia($user);

        return $query->where(function (Builder $q) use ($userId, $lihatSemuaNonRahasia): void {
            if ($lihatSemuaNonRahasia) {
                $q->where('sifat', '!=', 'rahasia');
            }

            $q->orWhere(function (Builder $sub) use ($userId): void {
                $sub->where('dibuat_oleh', $userId)
                    ->orWhere('created_by', $userId)
                    ->orWhere('disetujui_oleh', $userId)
                    ->orWhereHas('suratMasuk.disposisi', fn (Builder $d) => $d
                        ->where('kepada_user_id', $userId)
                        ->orWhere('dari_user_id', $userId));
            });
        });
    }

    private function terlibatPadaSuratMasuk(User $user, SuratMasuk $surat): bool
    {
        $userId = (int) $user->id;

        if ((int) $surat->dicatat_oleh === $userId || (int) $surat->created_by === $userId) {
            return true;
        }

        return $surat->disposisi()
            ->where(fn (Builder $q) => $q->where('kepada_user_id', $userId)->orWhere('dari_user_id', $userId))
            ->exists();
    }

    private function terlibatPadaSuratKeluar(User $user, SuratKeluar $surat): bool
    {
        $userId = (int) $user->id;

        if (
            (int) $surat->dibuat_oleh === $userId
            || (int) $surat->created_by === $userId
            || (int) $surat->disetujui_oleh === $userId
        ) {
            return true;
        }

        if ($surat->surat_masuk_id && $surat->suratMasuk) {
            return $this->terlibatPadaSuratMasuk($user, $surat->suratMasuk);
        }

        return false;
    }
}
