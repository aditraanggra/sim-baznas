<?php

namespace App\Http\Controllers\Pengaturan;

use App\Actions\Impor\MulaiImpor;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ImportJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImporController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('pengaturan/impor', [
            'jobs' => ImportJob::withCount('rows')
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn (ImportJob $j) => [
                    'id' => $j->id,
                    'jenis' => $j->jenis,
                    'nama_berkas' => $j->nama_berkas,
                    'status' => $j->status,
                    'total_baris' => $j->total_baris,
                    'baris_sukses' => $j->baris_sukses,
                    'baris_gagal' => $j->baris_gagal,
                    'created_at' => $j->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function simpan(Request $request, MulaiImpor $action): RedirectResponse
    {
        $validated = $request->validate([
            'jenis' => ['required', 'in:rkat,realisasi,pos,muzakki,mustahik,upz'],
            'berkas' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $path = $request->file('berkas')->store('impor');
        $absolut = Storage::disk('local')->path($path);

        $sheet = IOFactory::load($absolut)->getActiveSheet();
        $baris = [];
        $header = null;
        $nomor = 0;

        foreach ($sheet->toArray(null, true, false, false) as $row) {
            $nomor++;
            if ($header === null) {
                $header = array_map(fn ($h) => strtolower(trim((string) $h)), $row);

                continue;
            }
            if (array_filter($row, fn ($v) => $v !== null && $v !== '') === []) {
                continue;
            }

            $potongRow = array_slice(array_pad($row, count($header), null), 0, count($header));
            $data = [];
            foreach ($header as $idx => $kolomNama) {
                if ($kolomNama === '') {
                    continue;
                }
                $nilaiMentah = $potongRow[$idx];
                if (in_array($kolomNama, ['nik', 'telepon', 'kode', 'kode_pos', 'npwp', 'asnaf', 'desa', 'jenis_upz', 'nama'], true)) {
                    $data[$kolomNama] = $nilaiMentah !== null ? trim((string) $nilaiMentah) : null;
                } else {
                    $data[$kolomNama] = is_numeric($nilaiMentah) ? (int) $nilaiMentah : trim((string) $nilaiMentah);
                }
            }

            $kunci = match ($validated['jenis']) {
                'realisasi' => sprintf('%s/%s/%s/%s', $data['tahun'] ?? '', $data['bulan'] ?? '', $data['kode_pos'] ?? '', $data['tipe_nilai'] ?? ''),
                'muzakki', 'mustahik' => sprintf('%s|%s|%s', $data['kode'] ?? '', $data['nik'] ?? '', $data['telepon'] ?? ($data['nama'] ?? $nomor)),
                'upz' => sprintf('%s|%s', $data['kode'] ?? '', strtolower((string) ($data['nama'] ?? $nomor)).'|'.strtolower((string) ($data['desa'] ?? ''))),
                default => sprintf('%s/%s', $data['kode_pos'] ?? '', $data['tipe_nilai'] ?? ''),
            };

            $baris[] = ['nomor' => $nomor, 'kunci' => mb_substr($kunci, 0, 128), 'data' => $data];
        }

        if ($baris === []) {
            return back()->withErrors(['berkas' => 'Berkas tidak memuat baris data.']);
        }

        $job = $action->jalankan([
            'jenis' => $validated['jenis'],
            'nama_berkas' => $request->file('berkas')->getClientOriginalName(),
            'path' => $path,
            'baris' => $baris,
        ]);

        return to_route('pengaturan.impor.hasil', $job)->with('sukses', 'Impor selesai.');
    }

    public function hasil(ImportJob $job): Response
    {
        return Inertia::render('pengaturan/impor-hasil', [
            'job' => [
                'id' => $job->id,
                'jenis' => $job->jenis,
                'nama_berkas' => $job->nama_berkas,
                'status' => $job->status,
                'total_baris' => $job->total_baris,
                'baris_sukses' => $job->baris_sukses,
                'baris_gagal' => $job->baris_gagal,
            ],
            'rows' => $job->rows()->get()->map(fn ($r) => [
                'id' => $r->id,
                'nomor_baris' => $r->nomor_baris,
                'kunci' => $r->kunci,
                'status' => $r->status,
                'galat' => $r->galat,
            ]),
        ]);
    }

    public function audit(Request $request): Response
    {
        $log = AuditLog::with('user:id,name')
            ->when($request->string('entitas')->toString(), fn ($q, $e) => $q->where('entitas_tipe', 'like', "%{$e}%"))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('pengaturan/audit', [
            'log' => $log->through(fn (AuditLog $a) => [
                'id' => $a->id,
                'waktu' => $a->created_at?->toIso8601String(),
                'pengguna' => $a->user?->name ?? 'sistem',
                'aksi' => $a->aksi,
                'entitas' => class_basename($a->entitas_tipe).'#'.$a->entitas_id,
                'nilai_lama' => $a->nilai_lama,
                'nilai_baru' => $a->nilai_baru,
                'alasan' => $a->alasan,
            ]),
            'filter' => ['entitas' => $request->string('entitas')->toString()],
        ]);
    }
}
