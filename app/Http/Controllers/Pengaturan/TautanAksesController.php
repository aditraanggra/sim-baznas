<?php

namespace App\Http\Controllers\Pengaturan;

use App\Actions\Pengaturan\BuatTautanAkses;
use App\Actions\Pengaturan\CabutTautanAkses;
use App\Actions\Pengaturan\PutarTautanAkses;
use App\Http\Controllers\Controller;
use App\Models\AksesDokumenLog;
use App\Models\TautanAkses;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TautanAksesController extends Controller
{
    public function index(): Response
    {
        $daftarTautan = TautanAkses::query()
            ->with(['pembuat:id,name', 'pencabut:id,name'])
            ->latest('id')
            ->get()
            ->map(fn (TautanAkses $t) => [
                'id' => $t->id,
                'label' => $t->label,
                'jenis' => $t->jenis,
                'jenis_label' => TautanAkses::labelJenis($t->jenis),
                'token_prefix' => $t->token_prefix,
                'status' => $t->statusKode(),
                'kadaluarsa_at' => $t->kadaluarsa_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
                'dicabut_at' => $t->dicabut_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
                'terakhir_diakses_at' => $t->terakhir_diakses_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
                'jumlah_akses' => $t->jumlah_akses,
                'catatan' => $t->catatan,
                'pembuat' => $t->pembuat?->name,
                'pencabut' => $t->pencabut?->name,
                'created_at' => $t->created_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
            ])
            ->all();

        $logTerbaru = AksesDokumenLog::query()
            ->with(['tautanAkses:id,label,jenis', 'dokumen:id,judul,versi', 'user:id,name'])
            ->latest('waktu')
            ->limit(25)
            ->get()
            ->map(fn (AksesDokumenLog $log) => [
                'id' => $log->id,
                'konteks' => $log->konteks,
                'aksi' => $log->aksi,
                'target' => $log->dokumen
                    ? "{$log->dokumen->judul} (v{$log->dokumen->versi})"
                    : ($log->tahun_filter ? "Laporan Kinerja Tahun {$log->tahun_filter}" : 'Portal Kinerja'),
                'sumber' => $log->tautanAkses?->label ?? $log->user?->name ?? 'Tamu',
                'ip_address' => $log->ip_address,
                'waktu' => $log->waktu?->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
            ])
            ->all();

        return Inertia::render('pengaturan/tautan-akses', [
            'daftar_tautan' => $daftarTautan,
            'daftar_jenis' => collect(TautanAkses::DAFTAR_JENIS)->map(fn (string $kode) => [
                'kode' => $kode,
                'label' => TautanAkses::labelJenis($kode),
            ])->values()->all(),
            'log_terbaru' => $logTerbaru,
        ]);
    }

    public function buat(Request $request, BuatTautanAkses $action): RedirectResponse
    {
        $hasil = $action->jalankan($request->all());
        $tautan = $hasil['tautan'];

        return back()
            ->with('sukses', 'Tautan akses khusus berhasil dibuat. Segera salin URL karena token hanya ditampilkan satu kali.')
            ->with('tautan_baru', [
                'id' => $tautan->id,
                'label' => $tautan->label,
                'jenis' => $tautan->jenis,
                'jenis_label' => TautanAkses::labelJenis($tautan->jenis),
                'url' => route('akses.portal', ['token' => $hasil['token_mentah']]),
                'kadaluarsa_at' => $tautan->kadaluarsa_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
            ]);
    }

    public function cabut(Request $request, TautanAkses $tautan, CabutTautanAkses $action): RedirectResponse
    {
        $action->jalankan([
            'id' => $tautan->id,
            'alasan' => $request->input('alasan'),
        ]);

        return back()->with('sukses', 'Tautan akses khusus telah dicabut dan tidak dapat digunakan lagi.');
    }

    public function putar(TautanAkses $tautan, PutarTautanAkses $action): RedirectResponse
    {
        $hasil = $action->jalankan(['id' => $tautan->id]);
        $tautanBaru = $hasil['tautan'];

        return back()
            ->with('sukses', 'Token tautan akses berhasil diputar. Token lama otomatis dicabut.')
            ->with('tautan_baru', [
                'id' => $tautanBaru->id,
                'label' => $tautanBaru->label,
                'jenis' => $tautanBaru->jenis,
                'jenis_label' => TautanAkses::labelJenis($tautanBaru->jenis),
                'url' => route('akses.portal', ['token' => $hasil['token_mentah']]),
                'kadaluarsa_at' => $tautanBaru->kadaluarsa_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
            ]);
    }
}
