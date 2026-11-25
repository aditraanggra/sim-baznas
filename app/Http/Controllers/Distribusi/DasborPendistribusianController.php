<?php

namespace App\Http\Controllers\Distribusi;

use App\Http\Controllers\Controller;
use App\Models\Mustahik;
use App\Models\Penyaluran;
use App\Models\Permohonan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DasborPendistribusianController extends Controller
{
    public function index(Request $request): Response
    {
        $awalBulan = Carbon::today()->startOfMonth()->toDateString();
        $akhirBulan = Carbon::today()->endOfMonth()->toDateString();

        $totalDicairkanBulan = (int) DB::table('penyaluran_item as pyi')
            ->join('penyaluran as py', 'py.id', '=', 'pyi.penyaluran_id')
            ->where('py.status', 'dicairkan')
            ->whereBetween('py.tanggal', [$awalBulan, $akhirBulan])
            ->sum('pyi.jumlah');

        $jumlahMustahikBulan = (int) Penyaluran::query()
            ->where('status', 'dicairkan')
            ->whereBetween('tanggal', [$awalBulan, $akhirBulan])
            ->distinct()
            ->count('mustahik_id');

        $permohonanAktif = Permohonan::query()
            ->whereNotIn('status_kode', ['diterima_mustahik', 'ditolak'])
            ->count();

        $mustahikTerverifikasi = Mustahik::query()
            ->where('aktif', true)
            ->where('status_verifikasi', 'terverifikasi')
            ->count();

        $perAsnaf = DB::table('penyaluran_item as pyi')
            ->join('penyaluran as py', 'py.id', '=', 'pyi.penyaluran_id')
            ->leftJoin('asnaf as a', 'a.id', '=', 'pyi.asnaf_id')
            ->where('py.status', 'dicairkan')
            ->whereBetween('py.tanggal', [$awalBulan, $akhirBulan])
            ->groupBy(DB::raw("COALESCE(a.nama, 'Non-Zakat')"))
            ->selectRaw("COALESCE(a.nama, 'Non-Zakat') as label, SUM(pyi.jumlah)::bigint as nilai")
            ->orderByDesc('nilai')
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'nilai' => (int) $r->nilai])
            ->all();

        $perProgram = DB::table('penyaluran_item as pyi')
            ->join('penyaluran as py', 'py.id', '=', 'pyi.penyaluran_id')
            ->join('program as pr', 'pr.id', '=', 'py.program_id')
            ->where('py.status', 'dicairkan')
            ->whereBetween('py.tanggal', [$awalBulan, $akhirBulan])
            ->groupBy('pr.nama')
            ->selectRaw('pr.nama as label, SUM(pyi.jumlah)::bigint as nilai')
            ->orderByDesc('nilai')
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'nilai' => (int) $r->nilai])
            ->all();

        $permohonanTerbaru = Permohonan::query()
            ->with(['mustahik:id,nama', 'program:id,nama'])
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (Permohonan $p) => [
                'id' => $p->id,
                'nomor' => $p->nomor,
                'tanggal' => $p->tanggal?->format('d/m/Y'),
                'mustahik' => $p->mustahik?->nama,
                'program' => $p->program?->nama,
                'nominal_diajukan' => $p->nominal_diajukan,
                'status_kode' => $p->status_kode,
                'langkah_sekarang' => $p->langkah_sekarang,
            ]);

        return Inertia::render('distribusi/dasbor', [
            'ringkasan' => [
                'total_dicairkan_bulan' => $totalDicairkanBulan,
                'jumlah_mustahik_bulan' => $jumlahMustahikBulan,
                'permohonan_aktif' => $permohonanAktif,
                'mustahik_terverifikasi' => $mustahikTerverifikasi,
            ],
            'grafik_program' => $perProgram,
            'grafik_asnaf' => $perAsnaf,
            'permohonan_terbaru' => $permohonanTerbaru,
        ]);
    }

    public function disposisiSaya(Request $request): Response
    {
        $user = $request->user();
        $peranUser = $user->getRoleNames()->all();

        $antreanDisposisi = Permohonan::query()
            ->with(['mustahik:id,kode,nama', 'program:id,nama', 'disposisi' => fn ($q) => $q->where('status', 'aktif')])
            ->whereNotIn('status_kode', ['diterima_mustahik', 'ditolak'])
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get()
            ->filter(function (Permohonan $p) use ($user, $peranUser): bool {
                $aktif = $p->disposisi->first();
                if (! $aktif) {
                    return false;
                }
                if ($aktif->ke_user_id === $user->id) {
                    return true;
                }
                if ($aktif->ke_peran && in_array($aktif->ke_peran, $peranUser, true)) {
                    return true;
                }
                if ($p->status_kode === 'asesmen_kabag' && $p->petugas_lapangan_id === $user->id) {
                    return true;
                }
                if ($p->status_kode === 'minta_keuangan' && $p->kesiapan_keuangan === 'siap' && $user->hasRole('pelayanan_mustahik')) {
                    return true;
                }
                if ($p->status_kode === 'minta_keuangan' && $user->hasAnyRole(['keuangan', 'staf_pendistribusian', 'staf_pendayagunaan'])) {
                    return true;
                }

                return $user->hasRole('admin');
            })
            ->values()
            ->map(fn (Permohonan $p) => [
                'id' => $p->id,
                'nomor' => $p->nomor,
                'tanggal' => $p->tanggal?->format('d/m/Y'),
                'mustahik' => $p->mustahik?->nama,
                'program' => $p->program?->nama,
                'nominal_diajukan' => $p->nominal_diajukan,
                'nominal_disetujui' => $p->nominal_disetujui,
                'status_kode' => $p->status_kode,
                'langkah_sekarang' => $p->langkah_sekarang,
                'kesiapan_keuangan' => $p->kesiapan_keuangan,
                'instruksi' => $p->disposisi->first()?->instruksi,
            ]);

        return Inertia::render('distribusi/disposisi-saya', [
            'antrean' => $antreanDisposisi,
            'peran_user' => $peranUser,
        ]);
    }
}
