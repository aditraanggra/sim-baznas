<?php

namespace App\Http\Controllers\Distribusi;

use App\Actions\Distribusi\BatalkanPenyaluran;
use App\Actions\Distribusi\CairkanPenyaluran;
use App\Actions\Distribusi\SetujuiPenyaluran;
use App\Actions\Distribusi\SimpanPenyaluran;
use App\Http\Controllers\Controller;
use App\Models\Asnaf;
use App\Models\BentukBantuan;
use App\Models\MetodeBayar;
use App\Models\Mustahik;
use App\Models\Penyaluran;
use App\Models\Permohonan;
use App\Models\Program;
use App\Models\RekeningBank;
use App\Models\SumberDana;
use App\Services\SaldoDanaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PenyaluranController extends Controller
{
    public function index(Request $request, SaldoDanaService $saldoService): Response
    {
        $cari = trim($request->string('cari')->toString());
        $status = $request->string('status')->toString();

        $daftar = Penyaluran::query()
            ->with(['mustahik:id,kode,nama', 'program:id,nama,sub_bidang', 'metodeBayar:id,nama', 'items.sumberDana:id,nama', 'items.asnaf:id,nama', 'items.bentukBantuan:id,kode,nama'])
            ->when(in_array($status, ['draft', 'disetujui', 'dicairkan', 'batal'], true), fn ($q) => $q->where('status', $status))
            ->when($cari !== '', fn ($q) => $q->where(function ($sub) use ($cari): void {
                $sub->where('nomor', 'ilike', "%{$cari}%")
                    ->orWhereHas('mustahik', fn ($m) => $m->where('nama', 'ilike', "%{$cari}%"));
            }))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('distribusi/penyaluran/index', [
            'penyaluran' => $daftar->through(fn (Penyaluran $py) => [
                'id' => $py->id,
                'nomor' => $py->nomor ?? '(Draft)',
                'tanggal' => $py->tanggal?->format('d/m/Y'),
                'status' => $py->status,
                'mustahik' => $py->mustahik?->nama,
                'program' => $py->program?->nama,
                'sub_bidang' => $py->sub_bidang,
                'metode' => $py->metodeBayar?->nama,
                'peringatan_saldo' => $py->peringatan_saldo,
                'total' => $py->totalRupiah(),
                'ringkas_item' => $py->items->map(fn ($i) => ($i->sumberDana?->nama ?? '').($i->asnaf ? " ({$i->asnaf->nama})" : ''))->implode(', '),
            ]),
            'saldo_kelompok' => $saldoService->semuaSaldo()->values()->all(),
            'filter' => ['cari' => $cari, 'status' => $status],
            'bisa_kelola' => $request->user()->bolehAksiBidang('distribusi', 'create'),
            'bisa_setujui' => $request->user()->bolehAksiBidang('distribusi', 'approve'),
        ]);
    }

    public function create(Request $request, SaldoDanaService $saldoService): Response
    {
        $draftId = $request->integer('draft_id');
        $permohonanId = $request->integer('permohonan_id');

        $draft = $draftId > 0 ? Penyaluran::with('items')->find($draftId) : null;
        $permohonan = $permohonanId > 0 ? Permohonan::find($permohonanId) : null;

        return Inertia::render('distribusi/penyaluran/form', [
            'draft' => $draft && $draft->status === 'draft' ? [
                'id' => $draft->id,
                'tanggal' => $draft->tanggal?->format('Y-m-d'),
                'permohonan_id' => $draft->permohonan_id,
                'mustahik_id' => $draft->mustahik_id,
                'program_id' => $draft->program_id,
                'metode_bayar_id' => $draft->metode_bayar_id,
                'rekening_id' => $draft->rekening_id,
                'catatan' => $draft->catatan,
                'items' => $draft->items->map(fn ($i) => [
                    'sumber_dana_id' => $i->sumber_dana_id,
                    'asnaf_id' => $i->asnaf_id,
                    'bentuk_bantuan_id' => $i->bentuk_bantuan_id,
                    'uraian_barang' => $i->uraian_barang ?? '',
                    'kuantitas' => $i->kuantitas ? (string) $i->kuantitas : '',
                    'satuan' => $i->satuan ?? '',
                    'jumlah' => $i->jumlah,
                    'keterangan' => $i->keterangan ?? '',
                ])->values()->all(),
            ] : null,
            'prefill_permohonan' => $permohonan ? [
                'id' => $permohonan->id,
                'nomor' => $permohonan->nomor,
                'mustahik_id' => $permohonan->mustahik_id,
                'program_id' => $permohonan->program_id,
                'asnaf_id' => $permohonan->asnaf_id,
                'nominal' => $permohonan->nominal_disetujui ?? $permohonan->nominal_diajukan,
            ] : null,
            'mustahik_list' => Mustahik::where('aktif', true)->orderBy('nama')->get(['id', 'kode', 'nama']),
            'program_list' => Program::where('aktif', true)->orderBy('kode')->get(['id', 'kode', 'nama', 'sub_bidang']),
            'sumber_dana_list' => SumberDana::with(['jenisDana:id,nama,wajib_asnaf', 'danaKelompok:id,nama'])->where('aktif', true)->orderBy('id')->get()
                ->map(fn (SumberDana $s) => [
                    'id' => $s->id,
                    'kode' => $s->kode,
                    'nama' => $s->nama,
                    'jenis_dana' => $s->jenisDana?->nama,
                    'wajib_asnaf' => (bool) $s->jenisDana?->wajib_asnaf,
                    'kelompok_id' => $s->dana_kelompok_id,
                    'kelompok_nama' => $s->danaKelompok?->nama,
                ]),
            'asnaf_list' => Asnaf::where('aktif', true)->orderBy('urutan')->get(['id', 'kode', 'nama']),
            'bentuk_bantuan_list' => BentukBantuan::where('aktif', true)->orderBy('id')->get(['id', 'kode', 'nama']),
            'metode_bayar_list' => MetodeBayar::where('aktif', true)->orderBy('id')->get(['id', 'kode', 'nama', 'butuh_rekening']),
            'rekening_list' => RekeningBank::where('aktif', true)->orderBy('bank')->get(['id', 'bank', 'no_rekening', 'atas_nama']),
            'saldo_kelompok' => $saldoService->semuaSaldo()->values()->all(),
        ]);
    }

    public function storeDraft(Request $request, SimpanPenyaluran $action): RedirectResponse
    {
        $penyaluran = $action->jalankan($request->all());

        $redirect = to_route('distribusi.penyaluran.index')
            ->with('sukses', 'Draft penyaluran bantuan tersimpan.');

        if ($penyaluran->peringatan_saldo) {
            $redirect->with('peringatan', $penyaluran->peringatan_saldo);
        }

        return $redirect;
    }

    public function setujui(Request $request, Penyaluran $penyaluran, SetujuiPenyaluran $action): RedirectResponse
    {
        $hasil = $action->jalankan([
            'id' => $penyaluran->id,
            'catatan' => $request->input('catatan'),
        ]);

        $redirect = back()->with('sukses', "Penyaluran {$hasil->nomor} disetujui.");

        if ($hasil->peringatan_saldo) {
            $redirect->with('peringatan', $hasil->peringatan_saldo);
        }

        return $redirect;
    }

    public function cairkan(Request $request, Penyaluran $penyaluran, CairkanPenyaluran $action): RedirectResponse
    {
        $hasil = $action->jalankan([
            'id' => $penyaluran->id,
            'bukti_serah_terima' => $request->file('bukti_serah_terima'),
            'catatan' => $request->input('catatan'),
        ]);

        return back()->with('sukses', "Penyaluran {$hasil->nomor} berhasil dicairkan.");
    }

    public function batalkan(Request $request, Penyaluran $penyaluran, BatalkanPenyaluran $action): RedirectResponse
    {
        $action->jalankan([
            'id' => $penyaluran->id,
            'alasan' => $request->input('alasan'),
        ]);

        return back()->with('sukses', 'Penyaluran berhasil dibatalkan.');
    }

    public function cekSaldo(Request $request, SaldoDanaService $saldoService): JsonResponse
    {
        $items = $request->input('items', []);

        return response()->json($saldoService->periksaKecukupan(is_array($items) ? $items : []));
    }
}
