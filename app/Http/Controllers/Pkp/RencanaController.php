<?php

namespace App\Http\Controllers\Pkp;

use App\Actions\Rkat\AjukanRkat;
use App\Actions\Rkat\ArsipkanRkat;
use App\Actions\Rkat\BuatVersiRkat;
use App\Actions\Rkat\KembalikanRkat;
use App\Actions\Rkat\KembalikanTahapBidangRkat;
use App\Actions\Rkat\RkatBidangAction;
use App\Actions\Rkat\SahkanRkat;
use App\Actions\Rkat\SimpanAturanAmilRkat;
use App\Actions\Rkat\SimpanCatatanRkat;
use App\Actions\Rkat\SimpanNilaiRkat;
use App\Actions\Rkat\TetapkanTargetRkat;
use App\Actions\Rkat\UnggahDokumenRkat;
use App\Actions\Rkat\VerifikasiTahapBidangRkat;
use App\Http\Controllers\Controller;
use App\Models\PosAnggaran;
use App\Models\RkatDocument;
use App\Models\RkatNote;
use App\Models\RkatTahapBidang;
use App\Models\RkatVersion;
use App\Services\Rkat\AlokasiRkatService;
use App\Services\Rkat\BarisLampiran;
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
        $versi = RkatVersion::with('tahapBidang')
            ->where('arsip', false)
            ->orderBy('jenis')
            ->orderBy('nomor_perubahan')
            ->get()
            ->groupBy('tahun');
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
                'mode_penyusunan' => $acuan?->mode_penyusunan,
                'target_pengumpulan' => $acuan?->target_pengumpulan,
                'tahap_terverifikasi' => $acuan?->isKolaboratif()
                    ? $acuan->tahapBidang->where('status', RkatTahapBidang::STATUS_TERVERIFIKASI)->count()
                    : null,
                'nomor_surat' => $acuan?->nomor_surat,
                'total_penerimaan' => $jumlah(['L2/1', 'L2/2', 'L2/3', 'L2/4', 'L2/5']),
                'total_penyaluran' => $jumlah(['L3/1', 'L3/2', 'L3/3', 'L3/4', 'L3/5', 'L3/6', 'L3/7', 'L3/8', 'L3/9']),
                'total_operasional' => $jumlah(['L7/1']),
                'versi' => $list->map(fn (RkatVersion $v) => [
                    'id' => $v->id,
                    'label' => $v->label(),
                    'status' => $v->status,
                    'mode_penyusunan' => $v->mode_penyusunan,
                ])->values(),
                'punya_disahkan' => $list->contains('status', 'disahkan'),
            ];
        }

        return Inertia::render('pkp/rencana/index', ['daftar' => $daftar, 'dapat_tulis' => $this->dapatTulis()]);
    }

    public function show(RkatVersion $versi): Response
    {
        $h = HitungRkat::untukVersi($versi);
        $total = HitungRkat::totalPuncak($h['pos'], $h['nilai']);
        $isKolaboratif = $versi->isKolaboratif();
        $dapatEdit = $this->dapatTulis() && ! $versi->terkunci() && ! $isKolaboratif;
        $catatan = $versi->catatan;
        $batas = $catatan?->batasBp() ?? ['zakat' => null, 'infak' => null, 'csr' => null, 'dskl' => null];

        $kolaboratif = null;
        if ($isKolaboratif) {
            $ringkasan = AlokasiRkatService::ringkasan($versi);
            $tahapRows = $versi->tahapBidang()->with(['pengaju:id,name', 'verifikator:id,name'])->get()->keyBy('bidang_kode');
            $tahapBidang = [];
            foreach (RkatTahapBidang::BIDANG as $kodeBidang) {
                $row = $tahapRows->get($kodeBidang);
                $tahapBidang[] = [
                    'bidang_kode' => $kodeBidang,
                    'label' => RkatBidangAction::LABEL_BIDANG[$kodeBidang],
                    'status' => $row?->status ?? RkatTahapBidang::STATUS_MENUNGGU,
                    'diajukan_pada' => $row?->diajukan_pada?->toIso8601String(),
                    'pengaju_nama' => $row?->pengaju?->name,
                    'diverifikasi_pada' => $row?->diverifikasi_pada?->toIso8601String(),
                    'verifikator_nama' => $row?->verifikator?->name,
                    'catatan_verifikasi' => $row?->catatan_verifikasi,
                ];
            }

            $kolaboratif = [
                'ringkasan' => $ringkasan,
                'tahap_bidang' => $tahapBidang,
                'aturan_amil' => $ringkasan['pengumpulan']['rincian_amil'],
                'kegiatan_gabungan' => BarisLampiran::kegiatanGabungan($versi),
            ];
        }

        return Inertia::render('pkp/rencana/show', [
            'versi' => [
                'id' => $versi->id, 'tahun' => $versi->tahun, 'jenis' => $versi->jenis, 'label' => $versi->label(),
                'status' => $versi->status, 'sumber' => $versi->sumber, 'arsip' => $versi->arsip,
                'mode_penyusunan' => $versi->mode_penyusunan ?? RkatVersion::MODE_MANUAL,
                'target_pengumpulan' => $versi->target_pengumpulan,
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
                'mode_penyusunan' => $v->mode_penyusunan ?? RkatVersion::MODE_MANUAL,
                'nomor_surat' => $v->nomor_surat, 'disahkan_pada' => $v->disahkan_pada?->toIso8601String(),
            ])->values(),
            'dapat_tulis' => $this->dapatTulis(),
            'dapat_verifikasi' => (bool) auth()->user()?->hasAnyRole(['kabag_pkp', 'admin']),
            'kolaboratif' => $kolaboratif,
        ]);
    }

    public function buat(Request $request, BuatVersiRkat $action): RedirectResponse
    {
        $versi = $action->jalankan($request->only(['tahun', 'jenis', 'salin_dari_id', 'mode_penyusunan', 'target_pengumpulan']));

        return to_route('pkp.rencana.show', $versi)->with('sukses', "Versi {$versi->label()} tahun {$versi->tahun} dibuat.");
    }

    public function tetapkanTarget(Request $request, RkatVersion $versi, TetapkanTargetRkat $action): RedirectResponse
    {
        $action->jalankan([
            'rkat_version_id' => $versi->id,
            'target_pengumpulan' => $request->input('target_pengumpulan'),
        ]);

        return back()->with('sukses', 'Target pengumpulan berhasil diperbarui.');
    }

    public function simpanAturanAmil(Request $request, RkatVersion $versi, SimpanAturanAmilRkat $action): RedirectResponse
    {
        $action->jalankan([
            'rkat_version_id' => $versi->id,
            'persen' => $request->input('persen', []),
        ]);

        return back()->with('sukses', 'Persentase Hak Amil diperbarui.');
    }

    public function verifikasiTahap(RkatVersion $versi, string $bidang, VerifikasiTahapBidangRkat $action): RedirectResponse
    {
        $action->jalankan([
            'rkat_version_id' => $versi->id,
            'bidang_kode' => $bidang,
        ]);

        return back()->with('sukses', 'Tahap bidang berhasil diverifikasi.');
    }

    public function kembalikanTahap(Request $request, RkatVersion $versi, string $bidang, KembalikanTahapBidangRkat $action): RedirectResponse
    {
        $action->jalankan([
            'rkat_version_id' => $versi->id,
            'bidang_kode' => $bidang,
            'alasan' => $request->input('alasan'),
        ]);

        return back()->with('sukses', 'Tahap bidang dikembalikan ke Draft.');
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
        return (bool) auth()->user()?->hasAnyRole(['perencanaan', 'kabag_pkp', 'admin']);
    }
}
