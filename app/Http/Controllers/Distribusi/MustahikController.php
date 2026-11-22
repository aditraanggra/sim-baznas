<?php

namespace App\Http\Controllers\Distribusi;

use App\Actions\Distribusi\SimpanMustahik;
use App\Actions\Distribusi\UnggahDokumenMustahik;
use App\Actions\Distribusi\VerifikasiMustahik;
use App\Http\Controllers\Controller;
use App\Models\Asnaf;
use App\Models\Desa;
use App\Models\JenisDokumen;
use App\Models\Mustahik;
use App\Services\BerkasPrivatService;
use App\Services\IdentitasPribadiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MustahikController extends Controller
{
    public function index(Request $request): Response
    {
        $cari = trim($request->string('cari')->toString());
        $verifikasi = $request->string('verifikasi')->toString();

        $daftar = Mustahik::query()
            ->with(['kecamatan:id,nama', 'desa:id,nama', 'asnaf:id,nama'])
            ->when(in_array($verifikasi, ['menunggu', 'terverifikasi', 'ditolak'], true), fn ($q) => $q->where('status_verifikasi', $verifikasi))
            ->when($cari !== '', fn ($q) => $q->where(function ($sub) use ($cari): void {
                $sub->where('nama', 'ilike', "%{$cari}%")
                    ->orWhere('kode', 'ilike', "%{$cari}%")
                    ->orWhere('telepon', 'ilike', "%{$cari}%");
            }))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('distribusi/mustahik/index', [
            'mustahik' => $daftar->through(fn (Mustahik $m) => [
                'id' => $m->id,
                'kode' => $m->kode,
                'jenis' => $m->jenis,
                'nama' => $m->nama,
                'nik_masked' => $m->nik_masked,
                'telepon' => $m->telepon,
                'wilayah' => trim(($m->desa?->nama ? "Desa {$m->desa->nama}, " : '').($m->kecamatan?->nama ?? ''), ', '),
                'asnaf' => $m->asnaf->pluck('nama')->all(),
                'status_verifikasi' => $m->status_verifikasi,
                'aktif' => $m->aktif,
            ]),
            'filter' => ['cari' => $cari, 'verifikasi' => $verifikasi],
            'asnaf_list' => Asnaf::where('aktif', true)->orderBy('urutan')->get(['id', 'kode', 'nama']),
            'desa_list' => Desa::with('kecamatan:id,nama')->where('aktif', true)->orderBy('nama')->get()
                ->map(fn (Desa $d) => ['id' => $d->id, 'nama' => $d->nama, 'kecamatan' => $d->kecamatan?->nama]),
            'bisa_kelola' => $request->user()->bolehAksiBidang('distribusi', 'create'),
        ]);
    }

    public function cekDuplikat(Request $request, SimpanMustahik $action): JsonResponse
    {
        return response()->json([
            'duplikat' => $action->cekKemungkinanDuplikat(
                $request->string('nik')->toString() ?: null,
                $request->string('telepon')->toString() ?: null,
                $request->integer('ignore_id') ?: null,
            ),
        ]);
    }

    public function store(Request $request, SimpanMustahik $action): RedirectResponse
    {
        $mustahik = $action->jalankan($request->all());

        return back()->with('sukses', "Data mustahik {$mustahik->nama} ({$mustahik->kode}) berhasil disimpan.");
    }

    public function show(Request $request, Mustahik $mustahik, IdentitasPribadiService $identitas, BerkasPrivatService $berkasService): Response
    {
        $mustahik->load([
            'kecamatan:id,nama',
            'desa:id,nama',
            'diverifikasiOleh:id,name',
            'asnaf:id,kode,nama',
            'dokumen.jenisDokumen:id,nama',
            'dokumen.berkasPrivat',
            'permohonan' => fn ($q) => $q->with('program:id,nama')->latest('id')->limit(20),
            'penyaluran' => fn ($q) => $q->with(['program:id,nama', 'items'])->latest('id')->limit(20),
        ]);

        return Inertia::render('distribusi/mustahik/show', [
            'mustahik' => [
                'id' => $mustahik->id,
                'kode' => $mustahik->kode,
                'jenis' => $mustahik->jenis,
                'nama' => $mustahik->nama,
                'nik_masked' => $mustahik->nik_masked,
                'nik_asli' => $request->user()->hasAnyRole(['admin', 'kabag_pendis_pendag', 'pelayanan_mustahik'])
                    ? $identitas->dekripsi($mustahik->nik_encrypted)
                    : null,
                'telepon' => $mustahik->telepon,
                'alamat' => $mustahik->alamat,
                'kecamatan_id' => $mustahik->kecamatan_id,
                'desa_id' => $mustahik->desa_id,
                'kecamatan' => $mustahik->kecamatan?->nama,
                'desa' => $mustahik->desa?->nama,
                'penanggung_jawab' => $mustahik->penanggung_jawab,
                'status_verifikasi' => $mustahik->status_verifikasi,
                'diverifikasi_oleh' => $mustahik->diverifikasiOleh?->name,
                'waktu_verifikasi' => $mustahik->waktu_verifikasi?->format('d/m/Y H:i'),
                'catatan_verifikasi' => $mustahik->catatan_verifikasi,
                'aktif' => $mustahik->aktif,
                'asnaf_ids' => $mustahik->asnaf->pluck('id')->all(),
                'asnaf_nama' => $mustahik->asnaf->pluck('nama')->all(),
            ],
            'dokumen' => $mustahik->dokumen->map(fn ($d) => [
                'id' => $d->id,
                'jenis' => $d->jenisDokumen?->nama,
                'nama_file' => $d->berkasPrivat?->nama_asli,
                'keterangan' => $d->keterangan,
                'url_unduh' => $d->berkasPrivat ? $berkasService->urlBertandaTangan($d->berkasPrivat) : null,
            ]),
            'riwayat_permohonan' => $mustahik->permohonan->map(fn ($p) => [
                'id' => $p->id,
                'nomor' => $p->nomor,
                'tanggal' => $p->tanggal?->format('d/m/Y'),
                'program' => $p->program?->nama,
                'nominal_diajukan' => $p->nominal_diajukan,
                'nominal_disetujui' => $p->nominal_disetujui,
                'status_kode' => $p->status_kode,
            ]),
            'riwayat_penyaluran' => $mustahik->penyaluran->map(fn ($py) => [
                'id' => $py->id,
                'nomor' => $py->nomor ?? '(Draft)',
                'tanggal' => $py->tanggal?->format('d/m/Y'),
                'program' => $py->program?->nama,
                'status' => $py->status,
                'total' => $py->totalRupiah(),
            ]),
            'jenis_dokumen_list' => JenisDokumen::where('aktif', true)->orderBy('id')->get(['id', 'kode', 'nama']),
            'asnaf_list' => Asnaf::where('aktif', true)->orderBy('urutan')->get(['id', 'kode', 'nama']),
            'desa_list' => Desa::with('kecamatan:id,nama')->where('aktif', true)->orderBy('nama')->get()
                ->map(fn (Desa $d) => ['id' => $d->id, 'nama' => $d->nama, 'kecamatan' => $d->kecamatan?->nama]),
            'bisa_kelola' => $request->user()->bolehAksiBidang('distribusi', 'create'),
            'bisa_verifikasi' => $request->user()->hasAnyRole(['pelayanan_mustahik', 'kabag_pendis_pendag', 'admin']),
        ]);
    }

    public function verifikasi(Request $request, Mustahik $mustahik, VerifikasiMustahik $action): RedirectResponse
    {
        $action->jalankan([
            'mustahik_id' => $mustahik->id,
            'status_verifikasi' => $request->input('status_verifikasi'),
            'catatan_verifikasi' => $request->input('catatan_verifikasi'),
        ]);

        return back()->with('sukses', 'Status verifikasi mustahik berhasil diperbarui.');
    }

    public function unggahDokumen(Request $request, Mustahik $mustahik, UnggahDokumenMustahik $action): RedirectResponse
    {
        $action->jalankan([
            'mustahik_id' => $mustahik->id,
            'jenis_dokumen_id' => $request->input('jenis_dokumen_id'),
            'berkas' => $request->file('berkas'),
            'keterangan' => $request->input('keterangan'),
        ]);

        return back()->with('sukses', 'Dokumen pendukung mustahik berhasil diunggah.');
    }
}
