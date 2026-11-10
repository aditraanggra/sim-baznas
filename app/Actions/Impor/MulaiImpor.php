<?php

namespace App\Actions\Impor;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\ImportJob;
use App\Models\ImportRow;
use Illuminate\Support\Facades\Auth;

/**
 * Membuat job impor lalu memproses baris secara sinkron (dedaunan kecil).
 * Antrean database dipakai bila baris > 500 via ProcessImportJob (modul berikut).
 */
class MulaiImpor extends Action
{
    use MencatatAudit;

    public function jalankan(array $data): ImportJob
    {
        $data = validator($data, [
            'jenis' => ['required', 'in:rkat,realisasi,pos'],
            'nama_berkas' => ['required', 'string', 'max:255'],
            'path' => ['required', 'string', 'max:255'],
            'baris' => ['required', 'array', 'min:1'],
            'baris.*.nomor' => ['required', 'integer', 'min:1'],
            'baris.*.kunci' => ['required', 'string', 'max:128'],
            'baris.*.data' => ['required', 'array'],
        ])->validate();

        $job = ImportJob::create([
            'user_id' => Auth::id(),
            'jenis' => $data['jenis'],
            'nama_berkas' => $data['nama_berkas'],
            'path' => $data['path'],
            'status' => 'proses',
            'total_baris' => count($data['baris']),
            'mulai_pada' => now(),
        ]);

        $sukses = 0;
        foreach ($data['baris'] as $baris) {
            $galat = $this->validasiBaris($data['jenis'], $baris['data']);
            $status = $galat === [] ? 'valid' : 'galat';

            ImportRow::updateOrCreate(
                ['jenis' => $data['jenis'], 'kunci' => $baris['kunci']],
                [
                    'jenis' => $data['jenis'],
                    'import_job_id' => $job->id,
                    'nomor_baris' => $baris['nomor'],
                    'status' => $status,
                    'galat' => $galat ?: null,
                    'data' => $baris['data'],
                ],
            );

            $sukses += $galat === [] ? 1 : 0;
        }

        $job->update([
            'status' => 'selesai',
            'baris_sukses' => $sukses,
            'baris_gagal' => $job->total_baris - $sukses,
            'ringkasan' => ['jenis' => $data['jenis'], 'total' => $job->total_baris],
            'selesai_pada' => now(),
        ]);

        $this->catatAudit('impor', ImportJob::class, $job->id, null, $job->only(['jenis', 'nama_berkas', 'total_baris']));

        return $job->fresh('rows');
    }

    private function validasiBaris(string $jenis, array $data): array
    {
        $aturan = match ($jenis) {
            'rkat', 'pos' => [
                'kode_pos' => ['required', 'string'],
                'tipe_nilai' => ['required', 'in:rupiah,orang,badan,jiwa,unit,persen'],
                'nilai' => ['required', 'integer'],
            ],
            'realisasi' => [
                'tahun' => ['required', 'integer', 'between:2000,2100'],
                'bulan' => ['required', 'integer', 'between:1,12'],
                'kode_pos' => ['required', 'string'],
                'tipe_nilai' => ['required', 'in:rupiah,orang,badan,jiwa,unit,persen'],
                'nilai' => ['required', 'integer'],
            ],
        };

        $validator = validator($data, $aturan);

        return $validator->fails() ? $validator->errors()->toArray() : [];
    }
}
