<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class VerifikasiSkUpzController extends Controller
{
    public function index(Request $request): Response
    {
        $kataKunci = trim($request->string('q')->toString());
        $hasil = [];

        if ($kataKunci !== '') {
            $hasil = DB::table('upz as u')
                ->join('jenis_upz as ju', 'ju.id', '=', 'u.jenis_upz_id')
                ->join('desa as d', 'd.id', '=', 'u.desa_id')
                ->join('kecamatan as k', 'k.id', '=', 'd.kecamatan_id')
                ->leftJoin('v_sk_upz_status as v', function ($join): void {
                    $join->on('v.upz_id', '=', 'u.id');
                })
                ->whereNull('u.deleted_at')
                ->where('u.aktif', true)
                ->where(function ($q) use ($kataKunci): void {
                    $q->where('u.nama', 'ilike', "%{$kataKunci}%")
                        ->orWhere('d.nama', 'ilike', "%{$kataKunci}%")
                        ->orWhere('k.nama', 'ilike', "%{$kataKunci}%")
                        ->orWhere('u.nomor_sk', 'ilike', "%{$kataKunci}%")
                        ->orWhere('v.nomor', 'ilike', "%{$kataKunci}%");
                })
                ->orderBy('u.nama')
                ->orderByDesc('v.berlaku_sampai')
                ->limit(25)
                ->get([
                    'u.id',
                    'u.nama',
                    'ju.nama as jenis',
                    'd.nama as desa',
                    'k.nama as kecamatan',
                    DB::raw('COALESCE(v.nomor, u.nomor_sk) as nomor_sk'),
                    'v.berlaku_mulai',
                    'v.berlaku_sampai',
                    'v.status_berlaku',
                ])
                ->unique('id')
                ->values()
                ->map(fn ($r) => [
                    'id' => (int) $r->id,
                    'nama' => $r->nama,
                    'jenis' => $r->jenis,
                    'wilayah' => "Desa {$r->desa}, Kec. {$r->kecamatan}",
                    'nomor_sk' => $r->nomor_sk,
                    'berlaku_mulai' => $r->berlaku_mulai ? Carbon::parse($r->berlaku_mulai)->format('d/m/Y') : null,
                    'berlaku_sampai' => $r->berlaku_sampai ? Carbon::parse($r->berlaku_sampai)->format('d/m/Y') : null,
                    'status' => $r->status_berlaku ?? 'belum_ada',
                ])
                ->all();
        }

        return Inertia::render('publik/verifikasi-sk-upz', [
            'q' => $kataKunci,
            'hasil' => $hasil,
        ]);
    }
}
