<?php

namespace App\Http\Controllers\Pkp;

use App\Exports\RkatExport;
use App\Http\Controllers\Controller;
use App\Models\RkatVersion;
use App\Services\Rkat\BarisLampiran;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class EksporRencanaController extends Controller
{
    public function excel(RkatVersion $versi): Response
    {
        return Excel::download(new RkatExport($versi), $this->nama($versi, 'xlsx'));
    }

    public function pdf(RkatVersion $versi): Response
    {
        return Pdf::loadView('pdf.rkat', ['versi' => $versi, ...BarisLampiran::untuk($versi)])
            ->setPaper('a4')
            ->setOption(['defaultFont' => 'DejaVu Sans'])
            ->download($this->nama($versi, 'pdf'));
    }

    private function nama(RkatVersion $versi, string $ekstensi): string
    {
        return "RKAT-{$versi->tahun}-".Str::slug($versi->label()).".{$ekstensi}";
    }
}
