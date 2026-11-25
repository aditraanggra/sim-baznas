<?php

namespace App\Http\Controllers\Umum;

use App\Actions\Upz\SimpanSkUpz;
use App\Actions\Upz\SimpanUpz;
use App\Actions\Upz\ToggleAktifUpz;
use App\Http\Controllers\Controller;
use App\Models\Desa;
use App\Models\JenisUpz;
use App\Models\Penerimaan;
use App\Models\SkUpz;
use App\Models\Upz;
use App\Services\BerkasPrivatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class UpzController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $cari = trim($request->string('cari')->toString());
        $jenisId = $request->integer('jenis_upz_id');

        $skStatusMap = DB::table('v_sk_upz_status')
            ->orderByDesc('berlaku_sampai')
            ->get()
            ->groupBy('upz_id')
            ->map(fn ($items) => $items->first());

        $upzList = Upz::query()
            ->untukPengguna($user)
            ->with(['jenisUpz:id,kode,nama', 'desa.kecamatan:id,nama'])
            ->when($cari !== '', fn ($q) => $q->where(function ($sub) use ($cari): void {
                $sub->where('nama', 'ilike', "%{$cari}%")
                    ->orWhere('kode', 'ilike', "%{$cari}%")
                    ->orWhere('nomor_sk', 'ilike', "%{$cari}%")
                    ->orWhere('ketua', 'ilike', "%{$cari}%");
            }))
            ->when($jenisId > 0, fn ($q) => $q->where('jenis_upz_id', $jenisId))
            ->orderBy('nama')
            ->get()
            ->map(function (Upz $u) use ($skStatusMap): array {
                $sk = $skStatusMap->get($u->id);

                return [
                    'id' => $u->id,
                    'kode' => $u->kode,
                    'nama' => $u->nama,
                    'nomor_sk' => $u->nomor_sk,
                    'jenis_upz_id' => $u->jenis_upz_id,
                    'jenis_upz' => $u->jenisUpz?->nama,
                    'desa_id' => $u->desa_id,
                    'desa' => $u->desa?->nama,
                    'kecamatan' => $u->desa?->kecamatan?->nama,
                    'ketua' => $u->ketua,
                    'sekretaris' => $u->sekretaris,
                    'bendahara' => $u->bendahara,
                    'alamat' => $u->alamat,
                    'pic_nama' => $u->pic_nama,
                    'pic_telepon' => $u->pic_telepon,
                    'aktif' => $u->aktif,
                    'sk_nomor' => $sk?->nomor ?? $u->nomor_sk,
                    'sk_berlaku_sampai' => $sk?->berlaku_sampai,
                    'sk_sisa_hari' => $sk ? (int) $sk->sisa_hari : null,
                    'sk_status' => $sk?->status_berlaku ?? 'belum_ada',
                ];
            });

        return Inertia::render('umum/upz/index', [
            'upz_list' => $upzList,
            'jenis_upz' => JenisUpz::where('aktif', true)->orderBy('nama')->get(['id', 'kode', 'nama']),
            'desa_list' => Desa::with('kecamatan:id,nama')->where('aktif', true)->orderBy('nama')->get()
                ->map(fn (Desa $d) => ['id' => $d->id, 'nama' => $d->nama, 'kecamatan' => $d->kecamatan?->nama]),
            'filter' => ['cari' => $cari, 'jenis_upz_id' => $jenisId ?: null],
            'bisa_kelola' => $user->bolehAksiBidang('umum', 'create'),
        ]);
    }

    public function store(Request $request, SimpanUpz $action): RedirectResponse
    {
        $upz = $action->jalankan($request->all());

        return back()->with('sukses', "Data UPZ {$upz->nama} tersimpan.");
    }

    public function toggleAktif(Request $request, ToggleAktifUpz $action): RedirectResponse
    {
        $action->jalankan($request->all());

        return back()->with('sukses', 'Status keaktifan UPZ diperbarui.');
    }

    public function show(Request $request, Upz $upz, BerkasPrivatService $berkasService): Response
    {
        $user = $request->user();
        if ($user->adalahUpzEksternal() && (int) $user->upz_id !== (int) $upz->id) {
            abort(403, 'Anda hanya diizinkan melihat data UPZ Anda sendiri.');
        }

        $upz->load(['jenisUpz:id,kode,nama', 'desa.kecamatan:id,nama']);

        $skStatus = DB::table('v_sk_upz_status')
            ->where('upz_id', $upz->id)
            ->get()
            ->keyBy('id');

        $daftarSk = SkUpz::query()
            ->where('upz_id', $upz->id)
            ->with('berkasPrivat')
            ->orderByDesc('berlaku_sampai')
            ->get()
            ->map(function (SkUpz $sk) use ($skStatus, $berkasService): array {
                $v = $skStatus->get($sk->id);

                return [
                    'id' => $sk->id,
                    'nomor' => $sk->nomor,
                    'tanggal_sk' => $sk->tanggal_sk?->format('Y-m-d'),
                    'berlaku_mulai' => $sk->berlaku_mulai?->format('Y-m-d'),
                    'berlaku_sampai' => $sk->berlaku_sampai?->format('Y-m-d'),
                    'sisa_hari' => $v ? (int) $v->sisa_hari : $sk->sisaHari(),
                    'status_berlaku' => $v?->status_berlaku ?? $sk->statusBerlaku(),
                    'keterangan' => $sk->keterangan,
                    'berkas_nama' => $sk->berkasPrivat?->nama_asli,
                    'berkas_url' => $sk->berkasPrivat ? $berkasService->urlBertandaTangan($sk->berkasPrivat, 30, 'lihat') : null,
                ];
            });

        $setoran = Penerimaan::query()
            ->where('upz_id', $upz->id)
            ->with(['muzakki:id,nama', 'metodeBayar:id,nama', 'kwitansi:id,penerimaan_id,nomor', 'items'])
            ->latest('tanggal')
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn (Penerimaan $p) => [
                'id' => $p->id,
                'nomor' => $p->kwitansi?->nomor ?? $p->nomor ?? '(Draft)',
                'tanggal' => $p->tanggal?->format('d/m/Y'),
                'muzakki' => $p->muzakki?->nama ?? '-',
                'metode' => $p->metodeBayar?->nama ?? '-',
                'status' => $p->status,
                'total' => $p->totalRupiah(),
            ]);

        return Inertia::render('umum/upz/show', [
            'upz' => [
                'id' => $upz->id,
                'kode' => $upz->kode,
                'nama' => $upz->nama,
                'nomor_sk' => $upz->nomor_sk,
                'jenis_upz' => $upz->jenisUpz?->nama,
                'desa' => $upz->desa?->nama,
                'kecamatan' => $upz->desa?->kecamatan?->nama,
                'ketua' => $upz->ketua,
                'sekretaris' => $upz->sekretaris,
                'bendahara' => $upz->bendahara,
                'alamat' => $upz->alamat,
                'pic_nama' => $upz->pic_nama,
                'pic_telepon' => $upz->pic_telepon,
                'aktif' => $upz->aktif,
            ],
            'daftar_sk' => $daftarSk,
            'setoran' => $setoran,
            'bisa_kelola' => $user->bolehAksiBidang('umum', 'create'),
        ]);
    }

    public function storeSk(Request $request, Upz $upz, SimpanSkUpz $action): RedirectResponse
    {
        $sk = $action->jalankan([...$request->all(), 'upz_id' => $upz->id]);

        return back()->with('sukses', "SK UPZ nomor {$sk->nomor} tersimpan.");
    }

    public function laporanSetoran(Request $request): Response
    {
        $user = $request->user();
        $tahun = $request->integer('tahun', (int) date('Y'));

        $query = DB::table('upz as u')
            ->join('jenis_upz as ju', 'ju.id', '=', 'u.jenis_upz_id')
            ->join('desa as d', 'd.id', '=', 'u.desa_id')
            ->join('kecamatan as k', 'k.id', '=', 'd.kecamatan_id')
            ->leftJoin('penerimaan as p', function ($join) use ($tahun): void {
                $join->on('p.upz_id', '=', 'u.id')
                    ->where('p.status', '=', 'final')
                    ->whereBetween('p.tanggal', ["{$tahun}-01-01", "{$tahun}-12-31"]);
            })
            ->leftJoin('penerimaan_item as pi', 'pi.penerimaan_id', '=', 'p.id')
            ->whereNull('u.deleted_at');

        if ($user->adalahUpzEksternal()) {
            $query->where('u.id', $user->upz_id ?? 0);
        }

        $baris = $query
            ->select([
                'u.id',
                'u.kode',
                'u.nama',
                'ju.nama as jenis_upz',
                'd.nama as desa',
                'k.nama as kecamatan',
                DB::raw('COUNT(DISTINCT p.id) as jumlah_transaksi'),
                DB::raw('COALESCE(SUM(pi.jumlah), 0)::bigint as total_setoran'),
            ])
            ->groupBy('u.id', 'u.kode', 'u.nama', 'ju.nama', 'd.nama', 'k.nama')
            ->orderByDesc('total_setoran')
            ->orderBy('u.nama')
            ->get()
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'kode' => $r->kode,
                'nama' => $r->nama,
                'jenis_upz' => $r->jenis_upz,
                'wilayah' => "{$r->desa}, Kec. {$r->kecamatan}",
                'jumlah_transaksi' => (int) $r->jumlah_transaksi,
                'total_setoran' => (int) $r->total_setoran,
            ]);

        $konteksModul = $request->routeIs('pkp.*') ? 'pkp' : 'umum';

        return Inertia::render('umum/upz/laporan-setoran', [
            'tahun' => $tahun,
            'baris' => $baris,
            'total_semua' => $baris->sum('total_setoran'),
            'konteks_modul' => $konteksModul,
            'bisa_lihat_upz' => $user->hasAnyRole(['admin', 'ketua', 'kabag_sau', 'admin_arsip']),
        ]);
    }
}
