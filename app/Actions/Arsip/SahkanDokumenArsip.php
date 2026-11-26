<?php

namespace App\Actions\Arsip;

use App\Actions\Perencanaan\PerencanaanAction;
use App\Models\DokumenArsip;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SahkanDokumenArsip extends PerencanaanAction
{
    public function jalankan(array $data): DokumenArsip
    {
        $this->pastikanAksi('approve', 'Anda tidak memiliki izin untuk mengesahkan dokumen arsip PKP.');

        $tervalidasi = validator($data, [
            'id' => ['required', 'integer', 'exists:dokumen_arsip,id'],
            'tanggal_pengesahan' => ['nullable', 'date'],
            'cabut_versi_lama' => ['nullable', 'boolean'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi): DokumenArsip {
            $dokumen = DokumenArsip::lockForUpdate()->findOrFail((int) $tervalidasi['id']);

            if ($dokumen->status !== DokumenArsip::STATUS_DRAFT) {
                throw ValidationException::withMessages([
                    'id' => 'Hanya dokumen berstatus Draft yang dapat disahkan.',
                ]);
            }

            $nilaiLama = $dokumen->only(['status', 'tanggal_pengesahan', 'disahkan_oleh', 'disahkan_at']);
            $tanggalPengesahan = $tervalidasi['tanggal_pengesahan']
                ?? $dokumen->tanggal_pengesahan?->format('Y-m-d')
                ?? now()->format('Y-m-d');

            $dokumen->update([
                'status' => DokumenArsip::STATUS_DISAHKAN,
                'tanggal_pengesahan' => $tanggalPengesahan,
                'disahkan_oleh' => Auth::id(),
                'disahkan_at' => now(),
            ]);

            $this->catatAudit(
                'sahkan',
                DokumenArsip::class,
                $dokumen->id,
                $nilaiLama,
                $dokumen->fresh()->only(['status', 'tanggal_pengesahan', 'disahkan_oleh', 'disahkan_at']),
            );

            $cabutVersiLama = array_key_exists('cabut_versi_lama', $tervalidasi)
                ? (bool) $tervalidasi['cabut_versi_lama']
                : true;

            if ($cabutVersiLama && $dokumen->induk_versi_id !== null) {
                $induk = DokumenArsip::lockForUpdate()->find($dokumen->induk_versi_id);
                if ($induk && $induk->status === DokumenArsip::STATUS_DISAHKAN) {
                    $indukLama = $induk->only(['status', 'dicabut_oleh', 'dicabut_at', 'alasan_cabut']);
                    $alasanOtomatis = "Digantikan oleh versi v{$dokumen->versi}";

                    $induk->update([
                        'status' => DokumenArsip::STATUS_DICABUT,
                        'dicabut_oleh' => Auth::id(),
                        'dicabut_at' => now(),
                        'alasan_cabut' => $alasanOtomatis,
                    ]);

                    $this->catatAudit(
                        'cabut',
                        DokumenArsip::class,
                        $induk->id,
                        $indukLama,
                        $induk->fresh()->only(['status', 'dicabut_oleh', 'dicabut_at', 'alasan_cabut']),
                        $alasanOtomatis,
                    );
                }
            }

            return $dokumen->fresh();
        });
    }
}
