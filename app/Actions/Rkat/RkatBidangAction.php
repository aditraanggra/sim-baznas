<?php

namespace App\Actions\Rkat;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\PosAnggaran;
use App\Models\RkatTahapBidang;
use App\Models\RkatVersion;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Dasar Action penyusunan RKAT kolaboratif per bidang & verifikasi Kabag PKP.
 */
abstract class RkatBidangAction extends Action
{
    use MencatatAudit;

    public const PERAN_BIDANG = [
        'pengumpulan' => ['kabag_pengumpulan', 'admin'],
        'distribusi' => ['kabag_pendis_pendag', 'admin'],
        'umum' => ['kabag_sau', 'admin'],
    ];

    public const LABEL_BIDANG = [
        'pengumpulan' => 'Pengumpulan',
        'distribusi' => 'Pendistribusian dan Pendayagunaan',
        'umum' => 'SDM, Administrasi dan Umum (SAU)',
    ];

    protected function pastikanPenulisBidang(string $bidang): void
    {
        $peranDiizinkan = self::PERAN_BIDANG[$bidang] ?? null;
        if ($peranDiizinkan === null) {
            $this->gagal('bidang_kode', 'Kode bidang tidak valid.');
        }

        if (! Auth::user()?->hasAnyRole($peranDiizinkan)) {
            $label = self::LABEL_BIDANG[$bidang];
            throw new AuthorizationException("Hanya Kepala Bagian {$label} atau Admin yang dapat mengelola perencanaan RKAT bidang ini.");
        }
    }

    protected function pastikanVerifikatorPkp(): void
    {
        if (! Auth::user()?->hasAnyRole(['kabag_pkp', 'admin'])) {
            throw new AuthorizationException('Hanya Kepala Bagian PKP atau Admin yang dapat memverifikasi atau mengembalikan tahap bidang RKAT.');
        }
    }

    protected function versiTerkunci(int $id): RkatVersion
    {
        return RkatVersion::query()->lockForUpdate()->findOrFail($id);
    }

    protected function pastikanKolaboratifDraft(RkatVersion $versi): void
    {
        if (! $versi->isKolaboratif()) {
            $this->gagal('versi', 'Versi RKAT ini menggunakan mode penyusunan manual.');
        }

        if ($versi->terkunci()) {
            $this->gagal('versi', "Versi berstatus {$versi->status} terkunci; perubahan hanya melalui versi Perubahan baru (BR-02).");
        }
    }

    protected function ambilTahapTerkunci(RkatVersion $versi, string $bidang): RkatTahapBidang
    {
        return RkatTahapBidang::query()
            ->where('rkat_version_id', $versi->id)
            ->where('bidang_kode', $bidang)
            ->lockForUpdate()
            ->firstOrFail();
    }

    protected function pastikanTahapDraft(RkatTahapBidang $tahap): void
    {
        if ($tahap->status === RkatTahapBidang::STATUS_MENUNGGU) {
            $this->gagal('tahap', 'Tahap bidang ini menunggu verifikasi tahap Pengumpulan oleh Kepala Bagian PKP.');
        }

        if ($tahap->status !== RkatTahapBidang::STATUS_DRAFT) {
            $this->gagal('tahap', "Tahap bidang berstatus {$tahap->status} tidak dapat diubah.");
        }
    }

    /** @return list<string> */
    protected function kodePosValidUntukBidang(string $bidang): array
    {
        return match ($bidang) {
            'pengumpulan' => collect(config('rkat_amil.baris', []))->pluck('kode_pos')->values()->all(),
            'distribusi' => collect(config('rkat_amil.kelompok_penyaluran', []))
                ->flatMap(fn (array $cfg) => $cfg['kode_pos_l3'])
                ->values()
                ->all(),
            'umum' => PosAnggaran::query()
                ->where('lampiran', 'L7')
                ->where('agregasi', 'input_langsung')
                ->where('aktif', true)
                ->pluck('kode')
                ->values()
                ->all(),
            default => [],
        };
    }

    protected function gagal(string $kunci, string $pesan): never
    {
        throw ValidationException::withMessages([$kunci => $pesan]);
    }
}
