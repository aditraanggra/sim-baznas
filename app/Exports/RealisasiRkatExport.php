<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RealisasiRkatExport implements FromArray, WithColumnFormatting, WithColumnWidths, WithStyles, WithTitle
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        private readonly array $data,
        private readonly string $periodeLabel,
    ) {}

    public function title(): string
    {
        return 'RKAT vs Realisasi';
    }

    public function array(): array
    {
        $rows = [
            ['BAZNAS KABUPATEN CIANJUR'],
            ['LAPORAN PERBANDINGAN RKAT VS REALISASI'],
            ['Periode: '.$this->periodeLabel],
            ['Versi RKAT: '.($this->data['versi_rkat'] ?? 'Belum ada disahkan'), 'Status Tahun: '.strtoupper((string) ($this->data['status_tahun'] ?? 'draft'))],
            [''],
            ['Kode Pos', 'Lampiran', 'Uraian Pos', 'Tipe Nilai', 'Sumber', 'Target RKAT', 'Realisasi Otomatis', 'Realisasi Manual', 'Total Realisasi', 'Selisih', 'Capaian'],
        ];

        foreach ($this->data['baris'] ?? [] as $b) {
            $indent = str_repeat('  ', max(0, ((int) $b['tingkat']) - 1));
            $rows[] = [
                $b['kode'],
                $b['grup'],
                $indent.$b['nama'],
                $b['tipe_nilai'],
                $b['agregasi'] === 'jumlah_anak' ? 'Rollup' : $b['sumber'],
                (int) $b['target_rkat'],
                (int) $b['nilai_otomatis'],
                (int) $b['nilai_manual'],
                (int) $b['realisasi'],
                (int) $b['selisih'],
                $b['persen_label'],
            ];
        }

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'F' => '#,##0',
            'G' => '#,##0',
            'H' => '#,##0',
            'I' => '#,##0',
            'J' => '#,##0',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14,
            'B' => 10,
            'C' => 44,
            'D' => 12,
            'E' => 18,
            'F' => 20,
            'G' => 20,
            'H' => 18,
            'I' => 20,
            'J' => 20,
            'K' => 12,
        ];
    }

    public function styles(Worksheet $sheet): ?array
    {
        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A6:K6')->getFont()->setBold(true);

        return null;
    }
}
