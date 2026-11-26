<?php

namespace App\Exports;

use App\Models\RkatVersion;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RkatKegiatanGabunganSheet implements FromArray, WithColumnFormatting, WithColumnWidths, WithStyles, WithTitle
{
    /** @param  list<array{bidang_kode: string, bidang_label: string, subtotal: int, baris: list<array<string, mixed>>}>  $kelompokBidang */
    public function __construct(private RkatVersion $versi, private array $kelompokBidang) {}

    public function title(): string
    {
        return 'Kegiatan 3 Bidang';
    }

    public function array(): array
    {
        $rows = [
            ["Konsolidasi Rincian Kegiatan RKAT {$this->versi->tahun} - {$this->versi->label()}"],
            ['Status: '.ucfirst($this->versi->status)],
            [''],
            ['Bidang', 'Kode Pos', 'Pos RKAT', 'Program L4', 'Kegiatan', 'Jumlah', 'Satuan', 'Frek', 'Besaran', 'Total'],
        ];

        foreach ($this->kelompokBidang as $kelompok) {
            foreach ($kelompok['baris'] as $b) {
                $rows[] = [
                    $kelompok['bidang_label'],
                    $b['pos_kode'],
                    $b['pos_nama'],
                    $b['program_nama'] ? "{$b['program_kode']} - {$b['program_nama']}" : '—',
                    $b['nama_kegiatan'],
                    $b['jumlah'],
                    $b['satuan'],
                    $b['frekuensi'],
                    $b['besaran'],
                    $b['total'],
                ];
            }

            $rows[] = [
                "Subtotal {$kelompok['bidang_label']}",
                '', '', '', '', '', '', '', '',
                $kelompok['subtotal'],
            ];
        }

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'F' => '#,##0',
            'H' => '#,##0',
            'I' => '#,##0',
            'J' => '#,##0',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 26,
            'B' => 12,
            'C' => 36,
            'D' => 28,
            'E' => 38,
            'F' => 12,
            'G' => 12,
            'H' => 8,
            'I' => 18,
            'J' => 20,
        ];
    }

    public function styles(Worksheet $sheet): ?array
    {
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A4:J4')->getFont()->setBold(true);

        $barisKe = 5;
        foreach ($this->kelompokBidang as $kelompok) {
            $barisKe += count($kelompok['baris']);
            $sheet->getStyle("A{$barisKe}:J{$barisKe}")->getFont()->setBold(true);
            $barisKe++;
        }

        return null;
    }
}
