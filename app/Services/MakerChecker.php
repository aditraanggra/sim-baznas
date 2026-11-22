<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MakerChecker
{
    public function ekstrakPembuatId(Model|int|null $pembuatAtauModel): ?int
    {
        if (is_int($pembuatAtauModel)) {
            return $pembuatAtauModel;
        }

        if ($pembuatAtauModel instanceof Model) {
            foreach (['created_by', 'dibuat_oleh', 'diminta_oleh', 'petugas_id', 'diunggah_oleh', 'dicatat_oleh'] as $kolom) {
                $nilai = $pembuatAtauModel->getAttribute($kolom);
                if ($nilai !== null) {
                    return (int) $nilai;
                }
            }
        }

        return null;
    }

    public function pastikanBukanPembuat(
        Model|int|null $pembuatAtauModel,
        ?User $penyetuju = null,
        string $konteks = 'transaksi ini',
        bool $izinkanAdmin = false,
    ): void {
        $penyetuju ??= Auth::user();

        if (! $penyetuju) {
            throw new AuthorizationException('Pengguna harus login untuk melakukan persetujuan.');
        }

        if ($izinkanAdmin && $penyetuju->hasRole('admin')) {
            return;
        }

        $pembuatId = $this->ekstrakPembuatId($pembuatAtauModel);

        if ($pembuatId !== null && $pembuatId === (int) $penyetuju->id) {
            throw ValidationException::withMessages([
                'maker_checker' => "Pembuat {$konteks} tidak boleh menyetujui {$konteks} yang dibuatnya sendiri (maker-checker).",
            ]);
        }
    }

    public function pastikanBerhakMenyetujui(
        string $bidang,
        Model|int|null $pembuatAtauModel,
        ?User $penyetuju = null,
        string $konteks = 'transaksi ini',
    ): void {
        $penyetuju ??= Auth::user();

        if (! $penyetuju || ! $penyetuju->bolehAksiBidang($bidang, 'approve')) {
            throw new AuthorizationException("Anda tidak memiliki wewenang persetujuan pada bidang {$bidang}.");
        }

        $this->pastikanBukanPembuat($pembuatAtauModel, $penyetuju, $konteks);
    }
}
