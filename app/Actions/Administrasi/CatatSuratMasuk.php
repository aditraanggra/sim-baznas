<?php

namespace App\Actions\Administrasi;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\StatusDokumen;
use App\Models\SuratMasuk;
use App\Models\SuratMasukStatusLog;
use App\Services\Administrasi\AksesPersuratanService;
use App\Services\BerkasPrivatService;
use App\Services\NotifikasiService;
use App\Services\PenomoranService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CatatSuratMasuk extends Action
{
    use MencatatAudit;

    public function __construct(
        private readonly AksesPersuratanService $akses,
        private readonly PenomoranService $penomoran,
        private readonly BerkasPrivatService $berkasService,
        private readonly NotifikasiService $notifikasi,
    ) {}

    public function jalankan(array $data): SuratMasuk
    {
        $user = Auth::user();
        if (! $user || ! $this->akses->bolehKelolaSurat($user)) {
            throw new AuthorizationException('Anda tidak memiliki wewenang mencatat surat masuk.');
        }

        $tervalidasi = validator($data, [
            'nomor_surat' => ['required', 'string', 'max:128'],
            'tanggal_surat' => ['required', 'date'],
            'tanggal_terima' => ['required', 'date'],
            'pengirim' => ['required', 'string', 'max:200'],
            'perihal' => ['required', 'string', 'max:255'],
            'ringkasan' => ['nullable', 'string', 'max:2000'],
            'sifat' => ['required', 'in:biasa,penting,segera,rahasia'],
            'langsung_teruskan_ketua' => ['nullable', 'boolean'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'berkas' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ])->validate();

        $tanggalTerima = Carbon::parse($tervalidasi['tanggal_terima']);
        $langsungKetua = (bool) ($tervalidasi['langsung_teruskan_ketua'] ?? false);
        $statusKode = $langsungKetua ? 'diteruskan_ketua' : 'diterima_arsip';

        return DB::transaction(function () use ($tervalidasi, $user, $tanggalTerima, $langsungKetua, $statusKode): SuratMasuk {
            $statusDok = StatusDokumen::where('kelompok', 'surat_masuk')->where('kode', $statusKode)->first();
            $nomorAgenda = $this->penomoran->berikutnya('surat_masuk', $tanggalTerima);

            $surat = SuratMasuk::create($this->denganPembuat([
                'nomor_agenda' => $nomorAgenda,
                'nomor_surat' => trim($tervalidasi['nomor_surat']),
                'tanggal_surat' => Carbon::parse($tervalidasi['tanggal_surat'])->toDateString(),
                'tanggal_terima' => $tanggalTerima->toDateString(),
                'pengirim' => trim($tervalidasi['pengirim']),
                'perihal' => trim($tervalidasi['perihal']),
                'ringkasan' => ! empty($tervalidasi['ringkasan']) ? trim((string) $tervalidasi['ringkasan']) : null,
                'sifat' => $tervalidasi['sifat'],
                'status_kode' => $statusKode,
                'status_id' => $statusDok?->id,
                'dicatat_oleh' => $user->id,
            ]));

            if (($tervalidasi['berkas'] ?? null) instanceof UploadedFile) {
                $berkas = $this->berkasService->unggah($tervalidasi['berkas'], 'surat-masuk', [
                    'entitas_tipe' => SuratMasuk::class,
                    'entitas_id' => $surat->id,
                    'bidang_kode' => 'umum',
                ]);
                $surat->update(['berkas_privat_id' => $berkas->id]);
            }

            $statusAwal = StatusDokumen::where('kelompok', 'surat_masuk')->where('kode', 'diterima_arsip')->first();
            SuratMasukStatusLog::create($this->denganPembuat([
                'surat_masuk_id' => $surat->id,
                'status_kode' => 'diterima_arsip',
                'status_id' => $statusAwal?->id,
                'user_id' => $user->id,
                'waktu' => now(),
                'catatan' => $tervalidasi['catatan'] ?? 'Surat masuk dicatat oleh Admin Arsip.',
            ]));

            if ($langsungKetua) {
                SuratMasukStatusLog::create($this->denganPembuat([
                    'surat_masuk_id' => $surat->id,
                    'status_kode' => 'diteruskan_ketua',
                    'status_id' => $statusDok?->id,
                    'user_id' => $user->id,
                    'waktu' => now(),
                    'catatan' => 'Diteruskan langsung ke Ketua untuk disposisi.',
                ]));

                $this->notifikasi->kirimKePeran(
                    'ketua',
                    'surat_masuk_ketua',
                    'Surat Masuk Menunggu Disposisi',
                    "Agenda {$surat->nomor_agenda} dari {$surat->pengirim}: {$surat->perihal}",
                    route('umum.surat.masuk.show', $surat->id, false),
                    SuratMasuk::class,
                    $surat->id,
                    $user->id,
                );
            }

            $this->catatAudit(
                'buat',
                SuratMasuk::class,
                $surat->id,
                null,
                $surat->only(['nomor_agenda', 'nomor_surat', 'pengirim', 'perihal', 'sifat', 'status_kode']),
            );

            return $surat->fresh();
        });
    }
}
