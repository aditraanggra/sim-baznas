<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanPendistribusianExport implements FromArray, WithColumnFormatting, WithColumnWidths, WithStyles, WithTitle
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(private readonly array $data) {}

    public function title(): string
    {
        return 'Laporan Penyaluran';
    }

    public function array(): array
    {
        $rows = [
            ['BAZNAS KABUPATEN CIANJUR'],
            ['LAPORAN PENDISTRIBUSIAN DAN PENDAYAGUNAAN'],
            ['Periode: '.($this->data['periode']['label'] ?? '-')],
            ['Total Penyaluran (Rp)', (int) ($this->data['total_nominal'] ?? 0)],
            [''],
            ['No', 'Tanggal', 'Nomor Penyaluran', 'Sub Bidang', 'Mustahik', 'Program', 'Jenis Dana', 'Sumber Dana', 'Asnaf', 'Bentuk', 'Uraian Barang', 'Nominal (Rp)'],
        ];

        foreach ($this->data['baris'] ?? [] as $idx => $b) {
            $rows[] = [
                $idx + 1,
                $b['tanggal'],
                $b['nomor_penyaluran'],
                $b['sub_bidang'] === 'pendayagunaan' ? 'Pendayagunaan' : 'Pendistribusian',
                $b['mustahik'],
                $b['program'],
                $b['jenis_dana'],
                $b['sumber_dana'],
                $b['asnaf'],
                $b['bentuk_bantuan'],
                $b['uraian_barang'] ?? '-',
                (int) $b['jumlah'],
            ];
        }

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'B4' => '#,##0',
            'L' => '#,##0',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 14,
            'C' => 24,
            'D' => 18,
            'E' => 26,
            'F' => 24,
            'G' => 14,
            'H' => 22,
            'I' => 16,
            'J' => 14,
            'K' => 24,
            'L' => 20,
        ];
    }

    public function styles(Worksheet $sheet): ?array
    {
        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A6:L6')->getFont()->setBold(true);

        return null;
    }
}
