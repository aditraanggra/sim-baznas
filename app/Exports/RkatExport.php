<?php

namespace App\Exports;

use App\Models\RkatVersion;
use App\Services\Rkat\BarisLampiran;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RkatExport implements Export, WithMultipleSheets
{
    use Exportable;

    public function __construct(private RkatVersion $versi) {}

    public function sheets(): array
    {
        $data = BarisLampiran::untuk($this->versi);
        $akhir = array_key_last($data['bagian']);

        return array_map(
            fn (array $bagian, int $i) => new RkatLampiranSheet($bagian, $i === $akhir ? $data['catatan'] : []),
            $data['bagian'],
            array_keys($data['bagian']),
        );
    }
}
