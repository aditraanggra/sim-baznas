<?php

namespace App\Http\Controllers\Pkp;

use App\Actions\Rkat\AjukanRkat;
use App\Actions\Rkat\ArsipkanRkat;
use App\Actions\Rkat\BuatVersiRkat;
use App\Actions\Rkat\KembalikanRkat;
use App\Actions\Rkat\SahkanRkat;
use App\Actions\Rkat\SimpanCatatanRkat;
use App\Actions\Rkat\SimpanNilaiRkat;
use App\Actions\Rkat\UnggahDokumenRkat;
use App\Http\Controllers\Controller;
use App\Models\PosAnggaran;
use App\Models\RkatDocument;
use App\Models\RkatNote;
use App\Models\RkatVersion;
use App\Services\Rkat\HitungRkat;
use App\Services\Rkat\ValidasiRkat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RencanaController extends Controller
{
    /** FR-R / PRD 5.1: tabel tahun dengan versi acuan (Disahkan, atau versi terbaru bila belum ada). */
    public function index(): Response
    {
        $versi = RkatVersion::where('arsip', false)->orderBy('jenis')->orderBy('nomor_perubahan')->get()->groupBy('tahun');
        $maks = max((int) date('Y'), (int) $versi->keys()->max());

        $daftar = [];
        foreach (range($maks, 2021) as $tahun) {
            $list = $versi->get($tahun, collect());
            $acuan = $list->firstWhere('status', 'disahkan') ?? $list->sortByDesc('id')->first();
            $n = $acuan ? HitungRkat::untukVersi($acuan)['per_kode'] : null;
            $jumlah = fn (array $kode) => $n === null ? null : array_sum(array_map(fn ($k) => $n[$k] ?? 0, $kode));

            $daftar[] = [
                'tahun' => $tahun,
                'acuan_id' => $acuan?->id,
                'acuan_label' => $acuan?->label(),
                'status' => $acuan?->status,
                'sumber' => $acuan?->sumber,
                'nomor_surat' => $acuan?->nomor_surat,
                'total_penerimaan' => $jumlah(['L2/1', 'L2/2', 'L2/3', 'L2/4', 'L2/5']),
                'total_penyaluran' => $jumlah(['L3/1', 'L3/2', 'L3/3', 'L3/4', 'L3/5', 'L3/6', 'L3/7', 'L3/8', 'L3/9']),
                'total_operasional' => $jumlah(['L7/1']),
                'versi' => $list->map(fn (RkatVersion $v) => ['id' => $v->id, 'label' => $v->label(), 'status' => $v->status])->values(),
                'punya_disahkan' => $list->contains('status', 'disahkan'),
            ];
        }

        return Inertia::render('pkp/rencana/index', ['daftar' => $daftar, 'dapat_tulis' => $this->dapatTulis()]);
    }

    public function show(RkatVersion $versi): Response
    {
        $h = HitungRkat::untukVersi($versi);
        $total = HitungRkat::totalPuncak($h['pos'], $h['nilai']);
        $dapatEdit = $this->dapatTulis() && ! $versi->terkunci();
        $catatan = $versi->catatan;
        $batas = $catatan?->batasBp() ?? ['zakat' => null, 'infak' => null, 'csr' => null, 'dskl' => null];

        return Inertia::render('pkp/rencana/show', [
            'versi' => [
                'id' => $versi->id, 'tahun' => $versi->tahun, 'jenis' => $versi->jenis, 'label' => $versi->label(),
                'status' => $versi->status, 'sumber' => $versi->sumber, 'arsip' => $versi->arsip,
                'nomor_surat' => $versi->nomor_surat, 'tanggal_surat' => $versi->tanggal_surat?->toDateString(),
                'disahkan_pada' => $versi->disahkan_pada?->toIso8601String(),
                'terkunci' => $versi->terkunci(),
            ],
            'pos' => $h['pos']->map(function (PosAnggaran $p) use ($h, $total, $dapatEdit) {
                $nilai = $h['nilai'][$p->id];

                return [
                    'id' => $p->id, 'kode' => $p->kode, 'nama' => $p->nama, 'lampiran' => $p->lampiran,
                    'grup' => HitungRkat::grup($p), 'induk_id' => $p->induk_id, 'tipe_nilai' => $p->tipe_nilai,
                    'agregasi' => $p->agregasi, 'editable' => $dapatEdit && $p->agregasi === 'input_langsung',
                    'nilai' => $nilai,
                    'persen' => $p->tipe_nilai === 'rupiah' ? HitungRkat::persen($nilai, $total[HitungRkat::grup($p)] ?? 0) : null,
                ];
            })->values(),
            'tak_terpetakan' => $h['tak_terpetakan']->map(fn ($v) => [
                'kode_asal' => $v->kode_asal, 'nama_asal' => $v->nama_asal, 'tipe_nilai' => $v->tipe_nilai, 'nilai' => $v->nilai,
            ])->values(),
            'validasi' => ValidasiRkat::evaluasi($h['per_kode'], $batas),
            'catatan' => $catatan?->only(RkatNote::KOLOM),
            'dokumen' => $versi->dokumen()->orderByDesc('id')->get()->map(fn (RkatDocument $d) => [
                'id' => $d->id, 'jenis' => $d->jenis, 'nama_berkas' => $d->nama_berkas, 'ukuran' => $d->ukuran,
                'diarsipkan' => $d->diarsipkan_pada !== null, 'diunggah_pada' => $d->created_at?->toIso8601String(),
            ])->values(),
            'riwayat' => RkatVersion::where('tahun', $versi->tahun)->orderBy('jenis')->orderBy('nomor_perubahan')->get()->map(fn (RkatVersion $v) => [
                'id' => $v->id, 'label' => $v->label(), 'status' => $v->status, 'sumber' => $v->sumber, 'arsip' => $v->arsip,
                'nomor_surat' => $v->nomor_surat, 'disahkan_pada' => $v->disahkan_pada?->toIso8601String(),
            ])->values(),
            'dapat_tulis' => $this->dapatTulis(),
        ]);
    }

    public function buat(Request $request, BuatVersiRkat $action): RedirectResponse
    {
        $versi = $action->jalankan($request->only(['tahun', 'jenis', 'salin_dari_id']));

        return to_route('pkp.rencana.show', $versi)->with('sukses', "Versi {$versi->label()} tahun {$versi->tahun} dibuat.");
    }

    public function simpanNilai(Request $request, RkatVersion $versi, SimpanNilaiRkat $action): RedirectResponse
    {
        $action->jalankan(['rkat_version_id' => $versi->id, 'nilai' => $request->input('nilai', [])]);

        return back()->with('sukses', 'Nilai tersimpan.');
    }

    public function simpanCatatan(Request $request, RkatVersion $versi, SimpanCatatanRkat $action): RedirectResponse
    {
        $action->jalankan(['rkat_version_id' => $versi->id, ...$request->only(RkatNote::KOLOM)]);

        return back()->with('sukses', 'Catatan pengesahan tersimpan.');
    }

    public function ajukan(RkatVersion $versi, AjukanRkat $action): RedirectResponse
    {
        $hasil = $action->jalankan(['rkat_version_id' => $versi->id]);
        $gagal = collect($hasil['validasi'])->where('lulus', false)->pluck('id')->implode(', ');

        return back()->with('sukses', $hasil['peringatan'] ? "Versi diajukan dengan peringatan (versi impor). Validasi gagal: $gagal." : 'Versi diajukan.');
    }

    public function kembalikan(Request $request, RkatVersion $versi, KembalikanRkat $action): RedirectResponse
    {
        $action->jalankan(['rkat_version_id' => $versi->id, 'alasan' => $request->input('alasan')]);

        return back()->with('sukses', 'Versi dikembalikan ke Draft.');
    }

    public function sahkan(Request $request, RkatVersion $versi, SahkanRkat $action): RedirectResponse
    {
        $action->jalankan(['rkat_version_id' => $versi->id, ...$request->only(['nomor_surat', 'tanggal_surat'])]);

        return back()->with('sukses', 'Versi disahkan.');
    }

    public function arsipkan(Request $request, RkatVersion $versi, ArsipkanRkat $action): RedirectResponse
    {
        $action->jalankan(['rkat_version_id' => $versi->id, 'alasan' => $request->input('alasan')]);

        return to_route('pkp.rencana')->with('sukses', 'Versi diarsipkan.');
    }

    public function unggahDokumen(Request $request, RkatVersion $versi, UnggahDokumenRkat $action): RedirectResponse
    {
        $action->jalankan(['rkat_version_id' => $versi->id, 'jenis' => $request->input('jenis'), 'berkas' => $request->file('berkas')]);

        return back()->with('sukses', 'Dokumen terunggah.');
    }

    public function unduhDokumen(RkatDocument $dokumen): StreamedResponse
    {
        return Storage::download($dokumen->path, $dokumen->nama_berkas);
    }

    private function dapatTulis(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['perencanaan', 'admin']);
    }
}
