<?php

namespace App\Actions\Arsip;

use App\Actions\Perencanaan\PerencanaanAction;
use App\Models\DokumenArsip;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CabutDokumenArsip extends PerencanaanAction
{
    public function jalankan(array $data): DokumenArsip
    {
        $this->pastikanAksi('approve', 'Anda tidak memiliki izin untuk mencabut dokumen arsip PKP.');

        $tervalidasi = validator($data, [
            'id' => ['required', 'integer', 'exists:dokumen_arsip,id'],
            'alasan_cabut' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'alasan_cabut.required' => 'Alasan pencabutan dokumen wajib diisi.',
            'alasan_cabut.min' => 'Alasan pencabutan minimal 5 karakter.',
        ])->validate();

        return DB::transaction(function () use ($tervalidasi): DokumenArsip {
            $dokumen = DokumenArsip::lockForUpdate()->findOrFail((int) $tervalidasi['id']);

            if ($dokumen->status === DokumenArsip::STATUS_DICABUT) {
                throw ValidationException::withMessages([
                    'id' => 'Dokumen ini sudah berstatus dicabut.',
                ]);
            }

            $nilaiLama = $dokumen->only(['status', 'dicabut_oleh', 'dicabut_at', 'alasan_cabut']);

            $dokumen->update([
                'status' => DokumenArsip::STATUS_DICABUT,
                'dicabut_oleh' => Auth::id(),
                'dicabut_at' => now(),
                'alasan_cabut' => $tervalidasi['alasan_cabut'],
            ]);

            $this->catatAudit(
                'cabut',
                DokumenArsip::class,
                $dokumen->id,
                $nilaiLama,
                $dokumen->fresh()->only(['status', 'dicabut_oleh', 'dicabut_at', 'alasan_cabut']),
                $tervalidasi['alasan_cabut'],
            );

            return $dokumen->fresh();
        });
    }
}
