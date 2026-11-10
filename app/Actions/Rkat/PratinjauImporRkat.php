<?php

namespace App\Actions\Rkat;

use App\Actions\Impor\MulaiImpor;
use App\Models\ImportJob;
use App\Services\Rkat\ParserRkatExcel;
use App\Services\Rkat\PemetaanPos;
use Illuminate\Validation\ValidationException;

/**
 * FR-R-03 langkah 1-2: baca berkas, simpan ke import_rows lewat mesin impor PK0 (galat per baris).
 * Kunci baris = rkat/{tahun}/{kode}/{tipe}: pratinjau ulang tahun yang sama menimpa baris yang sama (idempoten).
 */
class PratinjauImporRkat extends RkatAction
{
    public function jalankan(array $data): ImportJob
    {
        $this->pastikanPenulis();
        $data = validator($data, [
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'path_absolut' => ['required', 'string'],
            'path' => ['required', 'string', 'max:255'],
            'nama_berkas' => ['required', 'string', 'max:255'],
        ])->validate();

        try {
            $isi = ParserRkatExcel::baca($data['path_absolut'], $data['tahun']);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['berkas' => $e->getMessage()]);
        }
        if ($isi === []) {
            $this->gagal('berkas', 'Berkas tidak memuat baris data.');
        }

        $peta = PemetaanPos::untuk($data['tahun'], array_column($isi, 'kode'));

        $baris = array_map(function (array $b) use ($peta, $data) {
            $pos = $peta[$b['kode']];
            $tipe = $pos?->tipe_nilai ?? (str_starts_with($b['kode'], 'L5/') ? 'orang' : 'rupiah');

            return [
                'nomor' => $b['nomor'],
                'kunci' => mb_substr("rkat/{$data['tahun']}/{$b['kode']}/$tipe", 0, 128),
                'data' => [
                    'kode_pos' => $b['kode'], 'tipe_nilai' => $tipe, 'nilai' => $b['nilai'],
                    'tahun' => $data['tahun'], 'nama' => $b['nama'], 'sheet' => $b['sheet'], 'subtotal' => $b['subtotal'],
                ],
            ];
        }, $isi);

        return app(MulaiImpor::class)->jalankan([
            'jenis' => 'rkat',
            'nama_berkas' => $data['nama_berkas'],
            'path' => $data['path'],
            'baris' => $baris,
        ]);
    }
}
