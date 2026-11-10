<?php

namespace App\Actions\Rkat;

use App\Models\RkatVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** BR-01: satu Disahkan aktif per tahun; versi Disahkan sebelumnya menjadi Digantikan dalam transaksi yang sama. */
class SahkanRkat extends RkatAction
{
    public function jalankan(array $data): RkatVersion
    {
        $this->pastikanPenulis();
        $data = validator($data, [
            'rkat_version_id' => ['required', 'integer', 'exists:rkat_versions,id'],
            'nomor_surat' => ['required', 'string', 'max:128'],
            'tanggal_surat' => ['required', 'date'],
        ])->validate();

        return DB::transaction(function () use ($data) {
            $versi = $this->versiTerkunci($data['rkat_version_id']);
            if ($versi->status !== 'diajukan') {
                $this->gagal('versi', 'Hanya versi Diajukan yang dapat disahkan.');
            }

            RkatVersion::where('tahun', $versi->tahun)->where('status', 'disahkan')->lockForUpdate()->get()
                ->each(function (RkatVersion $lama) use ($versi) {
                    $lama->update(['status' => 'digantikan']);
                    $this->catatAudit('digantikan', RkatVersion::class, $lama->id, ['status' => 'disahkan'], ['status' => 'digantikan', 'oleh_versi_id' => $versi->id]);
                });

            $versi->update([
                'status' => 'disahkan',
                'nomor_surat' => $data['nomor_surat'],
                'tanggal_surat' => $data['tanggal_surat'],
                'disahkan_pada' => now(),
                'disahkan_oleh' => Auth::id(),
            ]);

            $this->catatAudit('sahkan', RkatVersion::class, $versi->id, ['status' => 'diajukan'], [
                'status' => 'disahkan', 'nomor_surat' => $versi->nomor_surat, 'tanggal_surat' => $versi->tanggal_surat?->toDateString(),
            ]);

            return $versi;
        });
    }
}
