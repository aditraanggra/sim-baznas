<?php

namespace App\Http\Controllers\Pengaturan;

use App\Actions\Master\SimpanMasterGenerik;
use App\Actions\Master\ToggleAktifMasterGenerik;
use App\Http\Controllers\Controller;
use App\Services\MasterDataRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MasterDataController extends Controller
{
    public function index(Request $request): Response
    {
        $definisiSemua = MasterDataRegistry::definisi();
        $jenis = (string) $request->string('jenis', 'sumber_dana');
        if (! isset($definisiSemua[$jenis])) {
            $jenis = 'sumber_dana';
        }

        $definisi = $definisiSemua[$jenis];
        $kelas = $definisi['model'];
        $cari = trim($request->string('cari')->toString());

        $query = $kelas::query();
        if (! empty($definisi['relasi'])) {
            $query->with($definisi['relasi']);
        }
        if ($cari !== '') {
            $query->where(function ($q) use ($cari, $jenis): void {
                if ($jenis === 'rekening_bank') {
                    $q->where('bank', 'ilike', "%{$cari}%")
                        ->orWhere('no_rekening', 'ilike', "%{$cari}%")
                        ->orWhere('atas_nama', 'ilike', "%{$cari}%");

                    return;
                }

                $q->where('nama', 'ilike', "%{$cari}%")
                    ->orWhere('kode', 'ilike', "%{$cari}%");
            });
        }

        $baris = $query
            ->orderBy($definisi['urutan_default'] ?? 'id')
            ->limit(250)
            ->get();

        return Inertia::render('pengaturan/master-data', [
            'jenis_aktif' => $jenis,
            'cari' => $cari,
            'daftar_jenis' => collect($definisiSemua)->map(fn ($item, $kode) => [
                'kode' => $kode,
                'label' => $item['label'],
                'deskripsi' => $item['deskripsi'],
            ])->values()->all(),
            'skema' => [
                'label' => $definisi['label'],
                'deskripsi' => $definisi['deskripsi'],
                'kolom' => $definisi['kolom'],
            ],
            'opsi' => MasterDataRegistry::opsiSelect(),
            'data' => $baris->map(fn ($item) => $item->toArray())->values()->all(),
        ]);
    }

    public function simpan(Request $request, string $jenis, SimpanMasterGenerik $action): RedirectResponse
    {
        $action->jalankan([...$request->all(), 'jenis' => $jenis]);

        return back()->with('sukses', 'Master data tersimpan.');
    }

    public function toggleAktif(Request $request, string $jenis, ToggleAktifMasterGenerik $action): RedirectResponse
    {
        $action->jalankan([...$request->all(), 'jenis' => $jenis]);

        return back()->with('sukses', 'Status master data diperbarui.');
    }
}
