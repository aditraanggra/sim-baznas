<?php

namespace App\Http\Controllers\Pengumpulan;

use App\Actions\Pengumpulan\AjukanPembatalanPenerimaan;
use App\Actions\Pengumpulan\CetakKwitansi;
use App\Actions\Pengumpulan\FinalkanPenerimaan;
use App\Actions\Pengumpulan\SetujuiPembatalanPenerimaan;
use App\Actions\Pengumpulan\SimpanDraftPenerimaan;
use App\Http\Controllers\Controller;
use App\Models\Desa;
use App\Models\MetodeBayar;
use App\Models\Muzakki;
use App\Models\PembatalanPenerimaan;
use App\Models\Penerimaan;
use App\Models\RekeningBank;
use App\Models\SumberDana;
use App\Models\Upz;
use App\Services\Terbilang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PenerimaanController extends Controller
{
    public function index(Request $request): Response
    {
        $cari = trim($request->string('cari')->toString());
        $status = $request->string('status')->toString();

        $daftar = Penerimaan::query()
            ->with(['muzakki:id,kode,nama,nik_masked', 'upz:id,nama', 'metodeBayar:id,nama', 'kwitansi:id,penerimaan_id,nomor', 'items.sumberDana:id,nama'])
            ->when($status !== '' && in_array($status, ['draft', 'final', 'batal'], true), fn ($q) => $q->where('status', $status))
            ->when($cari !== '', fn ($q) => $q->where(function ($sub) use ($cari): void {
                $sub->where('nomor', 'ilike', "%{$cari}%")
                    ->orWhereHas('kwitansi', fn ($k) => $k->where('nomor', 'ilike', "%{$cari}%"))
                    ->orWhereHas('muzakki', fn ($m) => $m->where('nama', 'ilike', "%{$cari}%"));
            }))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('pengumpulan/penerimaan/index', [
            'penerimaan' => $daftar->through(fn (Penerimaan $p) => [
                'id' => $p->id,
                'nomor' => $p->kwitansi?->nomor ?? $p->nomor ?? '(Draft)',
                'tanggal' => $p->tanggal?->format('d/m/Y'),
                'status' => $p->status,
                'muzakki' => $p->muzakki?->nama,
                'upz' => $p->upz?->nama,
                'metode' => $p->metodeBayar?->nama,
                'sumber_dana' => $p->items->map(fn ($i) => $i->sumberDana?->nama)->filter()->implode(', '),
                'total' => $p->totalRupiah(),
            ]),
            'filter' => ['cari' => $cari, 'status' => $status],
            'bisa_kelola' => $request->user()->bolehAksiBidang('pengumpulan', 'create'),
        ]);
    }

    public function create(Request $request): Response
    {
        $draftId = $request->integer('draft_id');
        $draft = $draftId > 0 ? Penerimaan::with('items')->find($draftId) : null;

        return Inertia::render('pengumpulan/penerimaan/form', [
            'draft' => $draft && $draft->status === 'draft' ? [
                'id' => $draft->id,
                'tanggal' => $draft->tanggal?->format('Y-m-d'),
                'muzakki_id' => $draft->muzakki_id,
                'upz_id' => $draft->upz_id,
                'metode_bayar_id' => $draft->metode_bayar_id,
                'rekening_id' => $draft->rekening_id,
                'catatan' => $draft->catatan,
                'items' => $draft->items->map(fn ($i) => [
                    'sumber_dana_id' => $i->sumber_dana_id,
                    'jumlah' => $i->jumlah,
                    'keterangan' => $i->keterangan ?? '',
                ])->values()->all(),
            ] : null,
            'muzakki_list' => Muzakki::where('aktif', true)->orderBy('nama')->get(['id', 'kode', 'npwz', 'nama', 'nik_masked', 'telepon']),
            'sumber_dana_list' => SumberDana::with('jenisDana:id,nama')->where('aktif', true)->orderBy('id')->get()
                ->map(fn (SumberDana $s) => ['id' => $s->id, 'kode' => $s->kode, 'nama' => $s->nama, 'jenis_dana' => $s->jenisDana?->nama]),
            'metode_bayar_list' => MetodeBayar::where('aktif', true)->orderBy('id')->get(['id', 'kode', 'nama', 'butuh_rekening']),
            'rekening_list' => RekeningBank::where('aktif', true)->orderBy('bank')->get(['id', 'bank', 'no_rekening', 'atas_nama']),
            'upz_list' => Upz::where('aktif', true)->orderBy('nama')->get(['id', 'kode', 'nama']),
            'desa_list' => Desa::with('kecamatan:id,nama')->where('aktif', true)->orderBy('nama')->get()
                ->map(fn (Desa $d) => ['id' => $d->id, 'nama' => $d->nama, 'kecamatan' => $d->kecamatan?->nama]),
        ]);
    }

    public function storeDraft(Request $request, SimpanDraftPenerimaan $action): RedirectResponse
    {
        $action->jalankan($request->all());

        return to_route('pengumpulan.penerimaan.index')
            ->with('sukses', 'Draft penerimaan tersimpan di daftar penerimaan.');
    }

    public function finalkan(Request $request, FinalkanPenerimaan $action): RedirectResponse
    {
        $penerimaan = $action->jalankan($request->all());

        return to_route('pengumpulan.penerimaan.show', [$penerimaan, 'cetak' => 1])
            ->with('sukses', "Penerimaan difinalkan. Kwitansi {$penerimaan->kwitansi?->nomor} siap dicetak.");
    }

    public function show(Request $request, Penerimaan $penerimaan): Response
    {
        $penerimaan->load([
            'muzakki:id,kode,npwz,jenis,nama,jenis_kelamin,nik_masked,telepon,alamat',
            'upz:id,kode,nama',
            'metodeBayar:id,nama',
            'rekening:id,bank,no_rekening,atas_nama',
            'petugas:id,name',
            'beritaAcara:id,nomor,status',
            'items.sumberDana.jenisDana',
            'kwitansi.cetakLogs.user:id,name',
            'pembatalan.peminta:id,name',
            'pembatalan.penyetuju:id,name',
        ]);

        $total = $penerimaan->totalRupiah();
        $user = $request->user();
        $pembatalan = $penerimaan->pembatalan;

        $bisaSetujuiBatal = $pembatalan
            && $pembatalan->status === 'menunggu'
            && $user->bolehAksiBidang('pengumpulan', 'approve')
            && (int) $pembatalan->diminta_oleh !== (int) $user->id
            && (int) $penerimaan->petugas_id !== (int) $user->id;

        return Inertia::render('pengumpulan/penerimaan/show', [
            'penerimaan' => [
                'id' => $penerimaan->id,
                'nomor' => $penerimaan->nomor,
                'tanggal' => $penerimaan->tanggal?->format('d/m/Y'),
                'status' => $penerimaan->status,
                'catatan' => $penerimaan->catatan,
                'petugas' => $penerimaan->petugas?->name,
                'muzakki' => $penerimaan->muzakki,
                'upz' => $penerimaan->upz,
                'metode' => $penerimaan->metodeBayar?->nama,
                'rekening' => $penerimaan->rekening ? "{$penerimaan->rekening->bank} ({$penerimaan->rekening->no_rekening})" : null,
                'berita_acara' => $penerimaan->beritaAcara,
                'items' => $penerimaan->items->map(fn ($i) => [
                    'id' => $i->id,
                    'jenis_dana' => $i->sumberDana?->jenisDana?->nama,
                    'sumber_dana' => $i->sumberDana?->nama,
                    'jumlah' => (int) $i->jumlah,
                    'keterangan' => $i->keterangan,
                ]),
                'total' => $total,
                'terbilang' => Terbilang::rupiah($total),
                'kwitansi' => $penerimaan->kwitansi ? [
                    'id' => $penerimaan->kwitansi->id,
                    'nomor' => $penerimaan->kwitansi->nomor,
                    'token_verifikasi' => $penerimaan->kwitansi->token_verifikasi,
                    'jumlah_cetak' => $penerimaan->kwitansi->cetakLogs->count(),
                    'riwayat_cetak' => $penerimaan->kwitansi->cetakLogs->map(fn ($l) => [
                        'cetak_ke' => $l->cetak_ke,
                        'pencetak' => $l->user?->name ?? 'Sistem',
                        'waktu' => $l->waktu?->format('d/m/Y H:i'),
                    ]),
                ] : null,
                'pembatalan' => $pembatalan ? [
                    'id' => $pembatalan->id,
                    'alasan' => $pembatalan->alasan,
                    'status' => $pembatalan->status,
                    'peminta' => $pembatalan->peminta?->name,
                    'penyetuju' => $pembatalan->penyetuju?->name,
                    'catatan_penyetuju' => $pembatalan->catatan_penyetuju,
                    'waktu_diminta' => $pembatalan->waktu_diminta?->format('d/m/Y H:i'),
                    'waktu_disetujui' => $pembatalan->waktu_disetujui?->format('d/m/Y H:i'),
                ] : null,
            ],
            'bisa_ajukan_batal' => $penerimaan->status === 'final' && ! $pembatalan && $user->bolehAksiBidang('pengumpulan', 'create'),
            'bisa_setujui_batal' => $bisaSetujuiBatal,
        ]);
    }

    public function cetakKwitansi(Request $request, Penerimaan $penerimaan, CetakKwitansi $action): HttpResponse
    {
        $hasil = $action->jalankan(['penerimaan_id' => $penerimaan->id]);
        $namaBerkas = str_replace('/', '-', $hasil['kwitansi']->nomor).'.pdf';

        if ($request->boolean('unduh')) {
            return $hasil['pdf']->download($namaBerkas);
        }

        return $hasil['pdf']->stream($namaBerkas);
    }

    public function ajukanPembatalan(Request $request, Penerimaan $penerimaan, AjukanPembatalanPenerimaan $action): RedirectResponse
    {
        $action->jalankan([
            'penerimaan_id' => $penerimaan->id,
            'alasan' => $request->input('alasan'),
        ]);

        return back()->with('sukses', 'Pengajuan pembatalan dikirim untuk disetujui penyetuju.');
    }

    public function setujuiPembatalan(Request $request, PembatalanPenerimaan $pembatalan, SetujuiPembatalanPenerimaan $action): RedirectResponse
    {
        $action->jalankan([
            'pembatalan_id' => $pembatalan->id,
            'keputusan' => $request->input('keputusan'),
            'catatan_penyetuju' => $request->input('catatan_penyetuju'),
        ]);

        return back()->with('sukses', 'Keputusan pembatalan transaksi berhasil diproses.');
    }
}
