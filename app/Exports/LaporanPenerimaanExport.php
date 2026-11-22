<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanPenerimaanExport implements FromArray, WithColumnFormatting, WithColumnWidths, WithStyles, WithTitle
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(private readonly array $data) {}

    public function title(): string
    {
        return 'Laporan Penerimaan';
    }

    public function array(): array
    {
        $rows = [
            ['BAZNAS KABUPATEN CIANJUR'],
            ['LAPORAN PENERIMAAN DANA ZIS'],
            ['Periode: '.($this->data['periode']['label'] ?? '-')],
            ['Total Penerimaan (Rp)', (int) ($this->data['total_nominal'] ?? 0)],
            [''],
            ['No', 'Tanggal', 'No. Kwitansi', 'Muzakki', 'Jenis Dana', 'Sumber Dana', 'Metode Bayar', 'UPZ', 'Nominal (Rp)', 'Keterangan'],
        ];

        foreach ($this->data['baris'] ?? [] as $idx => $b) {
            $rows[] = [
                $idx + 1,
                $b['tanggal'],
                $b['nomor_kwitansi'],
                $b['muzakki'],
                $b['jenis_dana'],
                $b['sumber_dana'],
                $b['metode_bayar'],
                $b['upz_nama'] ?? '-',
                (int) $b['jumlah'],
                $b['keterangan'] ?? '-',
            ];
        }

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'B4' => '#,##0',
            'I' => '#,##0',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 14,
            'C' => 26,
            'D' => 28,
            'E' => 16,
            'F' => 26,
            'G' => 16,
            'H' => 24,
            'I' => 20,
            'J' => 28,
        ];
    }

    public function styles(Worksheet $sheet): ?array
    {
        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A6:J6')->getFont()->setBold(true);

        return null;
    }
}
