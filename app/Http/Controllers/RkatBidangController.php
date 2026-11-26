<?php

namespace App\Http\Controllers;

use App\Actions\Rkat\AjukanTahapBidangRkat;
use App\Actions\Rkat\RkatBidangAction;
use App\Actions\Rkat\SimpanJenisKegiatanRkat;
use App\Actions\Rkat\SimpanKegiatanRkat;
use App\Actions\Rkat\SimpanPelengkapBidangRkat;
use App\Models\PosAnggaran;
use App\Models\RkatJenisKegiatan;
use App\Models\RkatVersion;
use App\Services\Rkat\AlokasiRkatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RkatBidangController extends Controller
{
    public function index(Request $request, string $bidang): Response
    {
        $this->pastikanAksesBidang($request, $bidang);

        $daftarVersi = RkatVersion::query()
            ->where('arsip', false)
            ->where('mode_penyusunan', RkatVersion::MODE_KOLABORATIF)
            ->orderByDesc('tahun')
            ->orderByDesc('id')
            ->get(['id', 'tahun', 'jenis', 'nomor_perubahan', 'status', 'target_pengumpulan']);

        $versiDipilihId = $request->integer('versi');
        $versiAktif = $versiDipilihId
            ? $daftarVersi->firstWhere('id', $versiDipilihId)
            : ($daftarVersi->firstWhere('status', 'draft') ?? $daftarVersi->first());

        $versiModel = $versiAktif ? RkatVersion::find($versiAktif->id) : null;
        $ringkasan = $versiModel ? AlokasiRkatService::ringkasan($versiModel) : null;

        $kegiatan = $versiModel
            ? $versiModel->kegiatanRencana()
                ->aktif()
                ->where('bidang_kode', $bidang)
                ->with([
                    'jenisKegiatan:id,kode,nama,satuan_default',
                    'posAnggaran:id,kode,nama',
                    'posProgram:id,kode,nama',
                ])
                ->orderBy('id')
                ->get()
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'rkat_jenis_kegiatan_id' => $item->rkat_jenis_kegiatan_id,
                    'nama_kegiatan' => $item->nama_kegiatan,
                    'pos_anggaran_id' => $item->pos_anggaran_id,
                    'pos_kode' => $item->posAnggaran?->kode,
                    'pos_nama' => $item->posAnggaran?->nama,
                    'pos_program_id' => $item->pos_program_id,
                    'program_kode' => $item->posProgram?->kode,
                    'program_nama' => $item->posProgram?->nama,
                    'jumlah' => (int) $item->jumlah,
                    'satuan' => $item->satuan,
                    'frekuensi' => (int) $item->frekuensi,
                    'besaran' => (int) $item->besaran,
                    'total' => (int) $item->total,
                    'keterangan' => $item->keterangan,
                ])
                ->values()
            : collect();

        $pelengkapTersimpan = $versiModel
            ? $versiModel->pelengkapBidang()
                ->where('bidang_kode', $bidang)
                ->pluck('nilai', 'pos_anggaran_id')
                ->map(fn ($v) => (int) $v)
                ->all()
            : [];

        $jenisKegiatan = RkatJenisKegiatan::query()
            ->where('bidang_kode', $bidang)
            ->with(['posAnggaran:id,kode,nama', 'posProgram:id,kode,nama'])
            ->orderByDesc('aktif')
            ->orderBy('nama')
            ->get()
            ->map(fn (RkatJenisKegiatan $j) => [
                'id' => $j->id,
                'kode' => $j->kode,
                'nama' => $j->nama,
                'pos_anggaran_id' => $j->pos_anggaran_id,
                'pos_kode' => $j->posAnggaran?->kode,
                'pos_nama' => $j->posAnggaran?->nama,
                'pos_program_id' => $j->pos_program_id,
                'program_kode' => $j->posProgram?->kode,
                'program_nama' => $j->posProgram?->nama,
                'satuan_default' => $j->satuan_default,
                'keterangan' => $j->keterangan,
                'aktif' => $j->aktif,
            ])
            ->values();

        return Inertia::render('rkat/bidang/index', [
            'bidang' => config("sisfozis.bidang.{$bidang}"),
            'daftar_versi' => $daftarVersi->map(fn (RkatVersion $v) => [
                'id' => $v->id,
                'tahun' => $v->tahun,
                'label' => $v->label(),
                'status' => $v->status,
                'target_pengumpulan' => $v->target_pengumpulan,
            ])->values(),
            'versi_aktif' => $versiModel ? [
                'id' => $versiModel->id,
                'tahun' => $versiModel->tahun,
                'label' => $versiModel->label(),
                'status' => $versiModel->status,
                'terkunci' => $versiModel->terkunci(),
                'target_pengumpulan' => $versiModel->target_pengumpulan,
            ] : null,
            'tahap' => $ringkasan['tahap'][$bidang] ?? null,
            'ringkasan' => $ringkasan,
            'kegiatan' => $kegiatan,
            'jenis_kegiatan' => $jenisKegiatan,
            'pelengkap' => $pelengkapTersimpan,
            'opsi_pos' => $this->opsiPos($bidang),
            'opsi_program' => $this->opsiProgram($bidang),
            'opsi_pelengkap' => $this->opsiPelengkap($bidang),
            'dapat_kelola' => (bool) $request->user()?->hasAnyRole(RkatBidangAction::PERAN_BIDANG[$bidang]),
        ]);
    }

    public function simpanJenisKegiatan(Request $request, string $bidang, SimpanJenisKegiatanRkat $action): RedirectResponse
    {
        $this->pastikanAksesBidang($request, $bidang);
        $aksi = $request->input('aksi', 'simpan');
        $action->jalankan([...$request->all(), 'bidang_kode' => $bidang]);

        return back()->with('sukses', $aksi === 'toggle_aktif' ? 'Status jenis kegiatan diperbarui.' : 'Jenis kegiatan tersimpan.');
    }

    public function simpanKegiatan(Request $request, string $bidang, SimpanKegiatanRkat $action): RedirectResponse
    {
        $this->pastikanAksesBidang($request, $bidang);
        $aksi = $request->input('aksi', 'simpan');
        $action->jalankan([...$request->all(), 'bidang_kode' => $bidang]);

        return back()->with('sukses', $aksi === 'arsip' ? 'Baris kegiatan diarsipkan.' : 'Rincian kegiatan tersimpan.');
    }

    public function simpanPelengkap(Request $request, string $bidang, SimpanPelengkapBidangRkat $action): RedirectResponse
    {
        $this->pastikanAksesBidang($request, $bidang);
        $action->jalankan([...$request->all(), 'bidang_kode' => $bidang]);

        return back()->with('sukses', 'Data pelengkap RKAT tersimpan.');
    }

    public function ajukan(Request $request, string $bidang, RkatVersion $versi, AjukanTahapBidangRkat $action): RedirectResponse
    {
        $this->pastikanAksesBidang($request, $bidang);
        $action->jalankan(['rkat_version_id' => $versi->id, 'bidang_kode' => $bidang]);

        return back()->with('sukses', 'Rincian RKAT bidang diajukan ke Kepala Bagian PKP untuk diverifikasi.');
    }

    private function pastikanAksesBidang(Request $request, string $bidang): void
    {
        abort_unless(isset(RkatBidangAction::PERAN_BIDANG[$bidang]), 404);
        if ($bidang === 'umum' && $request->user()?->adalahUpzEksternal()) {
            abort(403, 'Akun UPZ tidak memiliki akses ke perencanaan RKAT SAU.');
        }
    }

    /** @return list<array<string, mixed>> */
    private function opsiPos(string $bidang): array
    {
        if ($bidang === 'pengumpulan') {
            $kodeUrut = collect(config('rkat_amil.baris', []))->pluck('kode_pos')->all();

            return PosAnggaran::query()
                ->whereIn('kode', $kodeUrut)
                ->where('tipe_nilai', 'rupiah')
                ->where('aktif', true)
                ->orderBy('urutan')
                ->get(['id', 'kode', 'nama'])
                ->map(fn (PosAnggaran $p) => ['id' => $p->id, 'kode' => $p->kode, 'nama' => $p->nama])
                ->values()
                ->all();
        }

        if ($bidang === 'distribusi') {
            $kelompokConfig = config('rkat_amil.kelompok_penyaluran', []);
            $l3KeKelompok = [];
            foreach ($kelompokConfig as $kodeKelompok => $cfg) {
                foreach ($cfg['kode_pos_l3'] as $kodeL3) {
                    $l3KeKelompok[$kodeL3] = ['kode' => $kodeKelompok, 'label' => $cfg['label']];
                }
            }

            return PosAnggaran::query()
                ->whereIn('kode', array_keys($l3KeKelompok))
                ->where('tipe_nilai', 'rupiah')
                ->where('aktif', true)
                ->orderBy('urutan')
                ->get(['id', 'kode', 'nama'])
                ->map(fn (PosAnggaran $p) => [
                    'id' => $p->id,
                    'kode' => $p->kode,
                    'nama' => $p->nama,
                    'kelompok' => $l3KeKelompok[$p->kode]['kode'] ?? null,
                    'kelompok_label' => $l3KeKelompok[$p->kode]['label'] ?? null,
                ])
                ->values()
                ->all();
        }

        return PosAnggaran::query()
            ->where('lampiran', 'L7')
            ->where('agregasi', 'input_langsung')
            ->where('aktif', true)
            ->with('induk:id,kode,nama')
            ->orderBy('urutan')
            ->get(['id', 'kode', 'nama', 'induk_id'])
            ->map(fn (PosAnggaran $p) => [
                'id' => $p->id,
                'kode' => $p->kode,
                'nama' => $p->induk ? "{$p->induk->nama} — {$p->nama}" : $p->nama,
            ])
            ->values()
            ->all();
    }

    /** @return list<array<string, mixed>> */
    private function opsiProgram(string $bidang): array
    {
        if ($bidang !== 'distribusi') {
            return [];
        }

        return PosAnggaran::query()
            ->where('lampiran', 'L4')
            ->where('agregasi', 'input_langsung')
            ->where('aktif', true)
            ->orderBy('urutan')
            ->get(['id', 'kode', 'nama'])
            ->map(fn (PosAnggaran $p) => ['id' => $p->id, 'kode' => $p->kode, 'nama' => $p->nama])
            ->values()
            ->all();
    }

    /** @return list<array<string, mixed>> */
    private function opsiPelengkap(string $bidang): array
    {
        $kodeList = config("rkat_amil.pos_pelengkap.{$bidang}", []);

        return PosAnggaran::query()
            ->whereIn('kode', $kodeList)
            ->orderBy('urutan')
            ->get(['id', 'kode', 'nama', 'tipe_nilai'])
            ->map(fn (PosAnggaran $p) => [
                'id' => $p->id,
                'kode' => $p->kode,
                'nama' => $p->nama,
                'tipe_nilai' => $p->tipe_nilai,
            ])
            ->values()
            ->all();
    }
}
