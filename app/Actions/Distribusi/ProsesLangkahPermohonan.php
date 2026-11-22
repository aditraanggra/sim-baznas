<?php

namespace App\Actions\Distribusi;

use App\Actions\Action;
use App\Models\BentukBantuan;
use App\Models\BeritaAcaraPenyaluran;
use App\Models\Concerns\MencatatAudit;
use App\Models\Disposisi;
use App\Models\MetodeBayar;
use App\Models\Penyaluran;
use App\Models\PenyaluranItem;
use App\Models\Permohonan;
use App\Models\PermohonanStatusLog;
use App\Models\SumberDana;
use App\Models\User;
use App\Services\MakerChecker;
use App\Services\NotifikasiService;
use App\Services\PenomoranService;
use App\Services\SaldoDanaService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProsesLangkahPermohonan extends Action
{
    use MencatatAudit;

    public function __construct(
        private readonly MakerChecker $makerChecker,
        private readonly PenomoranService $penomoran,
        private readonly NotifikasiService $notifikasi,
        private readonly SaldoDanaService $saldoService,
    ) {}

    public function jalankan(array $data): Permohonan
    {
        $tervalidasi = validator($data, [
            'permohonan_id' => ['required', 'integer', 'exists:permohonan,id'],
            'aksi' => ['nullable', Rule::in(['teruskan', 'minta_dana_staf', 'siapkan_keuangan'])],
            'nominal_usulan' => ['nullable', 'integer', 'min:1'],
            'nominal_disetujui' => ['nullable', 'integer', 'min:1'],
            'petugas_lapangan_id' => ['nullable', 'integer', 'exists:users,id'],
            'catatan_asesmen' => ['nullable', 'string', 'max:2000'],
            'instruksi' => ['nullable', 'string', 'max:1000'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'sumber_dana_id' => ['nullable', 'integer', 'exists:sumber_dana,id'],
            'bentuk_bantuan_id' => ['nullable', 'integer', 'exists:bentuk_bantuan,id'],
            'metode_bayar_id' => ['nullable', 'integer', 'exists:metode_bayar,id'],
            'rekening_id' => ['nullable', 'integer', 'exists:rekening_bank,id'],
        ])->validate();

        $aksi = $tervalidasi['aksi'] ?? 'teruskan';
        $user = Auth::user();

        if (! $user instanceof User) {
            throw new AuthorizationException('Pengguna harus login terlebih dahulu.');
        }

        return DB::transaction(function () use ($tervalidasi, $aksi, $user): Permohonan {
            $permohonan = Permohonan::with('mustahik')->lockForUpdate()->findOrFail($tervalidasi['permohonan_id']);

            if (in_array($permohonan->status_kode, ['diterima_mustahik', 'ditolak'], true)) {
                throw ValidationException::withMessages([
                    'status_kode' => 'Permohonan ini sudah selesai diproses dan tidak dapat diubah lagi.',
                ]);
            }

            if ($aksi === 'siapkan_keuangan') {
                return $this->siapkanDanaKeuangan($permohonan, $user, $tervalidasi);
            }

            if ($aksi === 'minta_dana_staf') {
                return $this->ajukanDanaKeKeuangan($permohonan, $user, $tervalidasi);
            }

            return match ($permohonan->status_kode) {
                'diajukan' => $this->langkah1Ke2($permohonan, $user, $tervalidasi),
                'disposisi_ketua' => $this->langkah2Ke3($permohonan, $user, $tervalidasi),
                'asesmen_kabag' => $this->langkah3Ke4($permohonan, $user, $tervalidasi),
                'ajuan_waka_2' => $this->langkah4Ke5($permohonan, $user, $tervalidasi),
                'acc_ketua' => $this->langkah5Ke6($permohonan, $user, $tervalidasi),
                'minta_keuangan' => $this->langkah6Ke7($permohonan, $user, $tervalidasi),
                default => throw ValidationException::withMessages([
                    'status_kode' => "Status permohonan {$permohonan->status_kode} tidak memiliki transisi lanjutan.",
                ]),
            };
        });
    }

    private function pastikanPeran(User $user, array $peranDiizinkan, string $konteks): void
    {
        if (! $user->hasAnyRole($peranDiizinkan)) {
            throw new AuthorizationException("Peran Anda tidak berwenang untuk tahap {$konteks}.");
        }
    }

    private function validasiPetugasLapangan(?int $userId): ?User
    {
        if (! $userId) {
            return null;
        }

        $petugas = User::findOrFail($userId);
        if (! $petugas->hasAnyRole(['staf_pendistribusian', 'staf_pendayagunaan', 'kabag_pendis_pendag', 'admin'])) {
            throw ValidationException::withMessages([
                'petugas_lapangan_id' => 'Petugas lapangan harus memiliki peran Staf Pendistribusian atau Staf Pendayagunaan.',
            ]);
        }

        return $petugas;
    }

    private function tutupDisposisiAktif(Permohonan $permohonan, User $user, ?string $catatan = null, ?int $nominalUsulan = null): void
    {
        Disposisi::query()
            ->where('permohonan_id', $permohonan->id)
            ->where('status', 'aktif')
            ->update([
                'status' => 'selesai',
                'catatan' => $catatan,
                'nominal_usulan' => $nominalUsulan,
                'waktu_tindak_lanjut' => now(),
            ]);
    }

    private function langkah1Ke2(Permohonan $permohonan, User $user, array $data): Permohonan
    {
        $this->pastikanPeran($user, ['pelayanan_mustahik', 'kabag_pendis_pendag', 'admin'], 'verifikasi awal Pelayanan Mustahik');

        $lama = $permohonan->only(['status_kode', 'status_publik', 'langkah_sekarang']);
        $this->tutupDisposisiAktif($permohonan, $user, $data['catatan'] ?? null);

        $permohonan->forceFill([
            'status_kode' => 'disposisi_ketua',
            'status_publik' => Permohonan::statusPublikUntuk('disposisi_ketua'),
            'langkah_sekarang' => 2,
        ])->save();

        Disposisi::create([
            'nomor' => $this->penomoran->berikutnya('disposisi', now()),
            'permohonan_id' => $permohonan->id,
            'langkah' => 2,
            'status_dari' => 'diajukan',
            'status_ke' => 'disposisi_ketua',
            'dari_user_id' => $user->id,
            'ke_peran' => 'ketua',
            'status' => 'aktif',
            'instruksi' => $data['instruksi'] ?? 'Mohon arahan dan disposisi Ketua untuk asesmen.',
            'created_by' => $user->id,
        ]);

        PermohonanStatusLog::create([
            'permohonan_id' => $permohonan->id,
            'status_kode' => 'disposisi_ketua',
            'status_publik' => 'disposisi',
            'judul_publik' => Permohonan::judulPublikUntuk('disposisi'),
            'keterangan_internal' => $data['catatan'] ?? 'Berkas diverifikasi awal dan diteruskan ke Ketua.',
            'keterangan_publik' => 'Permohonan telah diteruskan ke pimpinan untuk penugasan tindak lanjut.',
            'user_id' => $user->id,
            'waktu' => now(),
        ]);

        $this->catatAudit('langkah_2', Permohonan::class, $permohonan->id, $lama, $permohonan->only(['status_kode', 'langkah_sekarang']), $data['catatan'] ?? null);
        $this->notifikasi->kirimKePeran(['ketua'], 'disposisi_ketua', "Disposisi Permohonan {$permohonan->nomor}", 'Permohonan menunggu disposisi Ketua.', "/distribusi/permohonan/{$permohonan->id}", Permohonan::class, $permohonan->id);

        return $permohonan->fresh(['disposisi', 'statusLog']);
    }

    private function langkah2Ke3(Permohonan $permohonan, User $user, array $data): Permohonan
    {
        $this->pastikanPeran($user, ['ketua', 'admin'], 'disposisi Ketua');

        $petugas = $this->validasiPetugasLapangan($data['petugas_lapangan_id'] ?? null);
        $lama = $permohonan->only(['status_kode', 'status_publik', 'langkah_sekarang', 'petugas_lapangan_id']);
        $this->tutupDisposisiAktif($permohonan, $user, $data['catatan'] ?? null);

        $permohonan->forceFill([
            'status_kode' => 'asesmen_kabag',
            'status_publik' => Permohonan::statusPublikUntuk('asesmen_kabag'),
            'langkah_sekarang' => 3,
            'petugas_lapangan_id' => $petugas?->id ?? $permohonan->petugas_lapangan_id,
        ])->save();

        Disposisi::create([
            'nomor' => $this->penomoran->berikutnya('disposisi', now()),
            'permohonan_id' => $permohonan->id,
            'langkah' => 3,
            'status_dari' => 'disposisi_ketua',
            'status_ke' => 'asesmen_kabag',
            'dari_user_id' => $user->id,
            'ke_user_id' => $petugas?->id,
            'ke_peran' => 'kabag_pendis_pendag',
            'status' => 'aktif',
            'instruksi' => $data['instruksi'] ?? 'Lakukan asesmen kelayakan mustahik.',
            'created_by' => $user->id,
        ]);

        PermohonanStatusLog::create([
            'permohonan_id' => $permohonan->id,
            'status_kode' => 'asesmen_kabag',
            'status_publik' => 'verifikasi',
            'judul_publik' => Permohonan::judulPublikUntuk('verifikasi'),
            'keterangan_internal' => $data['instruksi'] ?? 'Didisposisikan ke Kabag Pendistribusian & Pendayagunaan.',
            'keterangan_publik' => 'Permohonan sedang dalam tahap verifikasi dan asesmen kelayakan.',
            'user_id' => $user->id,
            'waktu' => now(),
        ]);

        $this->catatAudit('langkah_3', Permohonan::class, $permohonan->id, $lama, $permohonan->only(['status_kode', 'langkah_sekarang', 'petugas_lapangan_id']), $data['instruksi'] ?? null);
        $this->notifikasi->kirimKePeran(['kabag_pendis_pendag'], 'asesmen_kabag', "Asesmen Permohonan {$permohonan->nomor}", 'Permohonan menunggu hasil asesmen.', "/distribusi/permohonan/{$permohonan->id}", Permohonan::class, $permohonan->id);

        return $permohonan->fresh(['disposisi', 'statusLog']);
    }

    private function langkah3Ke4(Permohonan $permohonan, User $user, array $data): Permohonan
    {
        $bolehStafDitugaskan = $permohonan->petugas_lapangan_id === $user->id
            && $user->hasAnyRole(['staf_pendistribusian', 'staf_pendayagunaan']);

        if (! $bolehStafDitugaskan) {
            $this->pastikanPeran($user, ['kabag_pendis_pendag', 'admin'], 'input hasil asesmen');
        }

        $catatanAsesmen = trim((string) ($data['catatan_asesmen'] ?? $permohonan->catatan_asesmen ?? ''));
        if ($catatanAsesmen === '') {
            throw ValidationException::withMessages([
                'catatan_asesmen' => 'Hasil asesmen wajib diisi sebelum diajukan ke Wakil Ketua II.',
            ]);
        }

        $petugas = $this->validasiPetugasLapangan($data['petugas_lapangan_id'] ?? null);
        $lama = $permohonan->only(['status_kode', 'langkah_sekarang', 'catatan_asesmen']);
        $this->tutupDisposisiAktif($permohonan, $user, $catatanAsesmen, isset($data['nominal_usulan']) ? (int) $data['nominal_usulan'] : null);

        $permohonan->forceFill([
            'status_kode' => 'ajuan_waka_2',
            'status_publik' => Permohonan::statusPublikUntuk('ajuan_waka_2'),
            'langkah_sekarang' => 4,
            'catatan_asesmen' => $catatanAsesmen,
            'petugas_lapangan_id' => $petugas?->id ?? $permohonan->petugas_lapangan_id,
        ])->save();

        Disposisi::create([
            'nomor' => $this->penomoran->berikutnya('disposisi', now()),
            'permohonan_id' => $permohonan->id,
            'langkah' => 4,
            'status_dari' => 'asesmen_kabag',
            'status_ke' => 'ajuan_waka_2',
            'dari_user_id' => $user->id,
            'ke_peran' => 'waka_2',
            'status' => 'aktif',
            'nominal_usulan' => isset($data['nominal_usulan']) ? (int) $data['nominal_usulan'] : $permohonan->nominal_diajukan,
            'instruksi' => $data['instruksi'] ?? 'Mohon penelaahan ajuan besaran bantuan oleh Wakil Ketua II.',
            'created_by' => $user->id,
        ]);

        PermohonanStatusLog::create([
            'permohonan_id' => $permohonan->id,
            'status_kode' => 'ajuan_waka_2',
            'status_publik' => 'acc_pimpinan',
            'judul_publik' => Permohonan::judulPublikUntuk('acc_pimpinan'),
            'keterangan_internal' => $catatanAsesmen,
            'keterangan_publik' => 'Hasil asesmen telah selesai dan sedang ditelaah pimpinan.',
            'user_id' => $user->id,
            'waktu' => now(),
        ]);

        $this->catatAudit('langkah_4', Permohonan::class, $permohonan->id, $lama, $permohonan->only(['status_kode', 'langkah_sekarang', 'catatan_asesmen']));
        $this->notifikasi->kirimKePeran(['waka_2'], 'ajuan_waka_2', "Ajuan Besaran {$permohonan->nomor}", 'Permohonan menunggu usulan besaran bantuan dari Wakil Ketua II.', "/distribusi/permohonan/{$permohonan->id}", Permohonan::class, $permohonan->id);

        return $permohonan->fresh(['disposisi', 'statusLog']);
    }

    private function langkah4Ke5(Permohonan $permohonan, User $user, array $data): Permohonan
    {
        $this->pastikanPeran($user, ['waka_2', 'admin'], 'ajuan besaran Wakil Ketua II');

        $nominalUsulan = (int) ($data['nominal_usulan'] ?? 0);
        if ($nominalUsulan < 1) {
            throw ValidationException::withMessages([
                'nominal_usulan' => 'Nominal ajuan besaran bantuan wajib lebih besar dari Rp 0.',
            ]);
        }

        $lama = $permohonan->only(['status_kode', 'langkah_sekarang']);
        $this->tutupDisposisiAktif($permohonan, $user, $data['catatan'] ?? null, $nominalUsulan);

        $permohonan->forceFill([
            'status_kode' => 'acc_ketua',
            'status_publik' => Permohonan::statusPublikUntuk('acc_ketua'),
            'langkah_sekarang' => 5,
        ])->save();

        Disposisi::create([
            'nomor' => $this->penomoran->berikutnya('disposisi', now()),
            'permohonan_id' => $permohonan->id,
            'langkah' => 5,
            'status_dari' => 'ajuan_waka_2',
            'status_ke' => 'acc_ketua',
            'dari_user_id' => $user->id,
            'ke_peran' => 'ketua',
            'status' => 'aktif',
            'nominal_usulan' => $nominalUsulan,
            'instruksi' => $data['instruksi'] ?? 'Mohon ACC besaran bantuan oleh Ketua.',
            'created_by' => $user->id,
        ]);

        PermohonanStatusLog::create([
            'permohonan_id' => $permohonan->id,
            'status_kode' => 'acc_ketua',
            'status_publik' => 'disetujui',
            'judul_publik' => Permohonan::judulPublikUntuk('disetujui'),
            'keterangan_internal' => 'Ajuan besaran Rp '.number_format($nominalUsulan, 0, ',', '.').' diteruskan ke Ketua.',
            'keterangan_publik' => 'Besaran bantuan telah diajukan untuk persetujuan akhir pimpinan.',
            'user_id' => $user->id,
            'waktu' => now(),
        ]);

        $this->catatAudit('langkah_5', Permohonan::class, $permohonan->id, $lama, ['status_kode' => 'acc_ketua', 'nominal_usulan' => $nominalUsulan]);
        $this->notifikasi->kirimKePeran(['ketua'], 'acc_ketua', "Menunggu ACC Ketua {$permohonan->nomor}", 'Ajuan besaran Rp '.number_format($nominalUsulan, 0, ',', '.').' siap di-ACC.', "/distribusi/permohonan/{$permohonan->id}", Permohonan::class, $permohonan->id);

        return $permohonan->fresh(['disposisi', 'statusLog']);
    }

    private function langkah5Ke6(Permohonan $permohonan, User $user, array $data): Permohonan
    {
        $this->pastikanPeran($user, ['ketua', 'admin'], 'ACC besaran bantuan Ketua');

        // Maker-checker: Ketua yang meng-ACC tidak boleh merupakan pengaju besaran (Waka II) maupun pembuat permohonan awal.
        $ajuanWaka2 = Disposisi::query()
            ->where('permohonan_id', $permohonan->id)
            ->where('langkah', 5)
            ->latest('id')
            ->first();

        if ($ajuanWaka2?->dari_user_id) {
            $this->makerChecker->pastikanBukanPembuat($ajuanWaka2->dari_user_id, $user, 'ajuan besaran bantuan', izinkanAdmin: true);
        }
        $this->makerChecker->pastikanBukanPembuat($permohonan->created_by, $user, 'permohonan bantuan ini', izinkanAdmin: true);

        $nominalDisetujui = (int) ($data['nominal_disetujui'] ?? $ajuanWaka2?->nominal_usulan ?? $permohonan->nominal_diajukan);
        if ($nominalDisetujui < 1) {
            throw ValidationException::withMessages([
                'nominal_disetujui' => 'Nominal yang di-ACC wajib lebih besar dari Rp 0.',
            ]);
        }

        $petugas = $this->validasiPetugasLapangan($data['petugas_lapangan_id'] ?? null);
        $lama = $permohonan->only(['status_kode', 'status_publik', 'langkah_sekarang', 'nominal_disetujui', 'kesiapan_keuangan']);
        $this->tutupDisposisiAktif($permohonan, $user, $data['catatan'] ?? null, $nominalDisetujui);

        $roleStaf = $permohonan->sub_bidang === 'pendayagunaan' ? 'staf_pendayagunaan' : 'staf_pendistribusian';
        $petugasFinalId = $petugas?->id ?? $permohonan->petugas_lapangan_id;
        if ($petugas?->hasRole('staf_pendayagunaan')) {
            $roleStaf = 'staf_pendayagunaan';
        } elseif ($petugas?->hasRole('staf_pendistribusian')) {
            $roleStaf = 'staf_pendistribusian';
        }

        $permohonan->forceFill([
            'status_kode' => 'minta_keuangan',
            // Q-14: status internal minta_keuangan (tampil_publik=false) memetakan status publik tetap pada 'disetujui'.
            'status_publik' => Permohonan::statusPublikUntuk('minta_keuangan'),
            'langkah_sekarang' => 6,
            'nominal_disetujui' => $nominalDisetujui,
            'petugas_lapangan_id' => $petugasFinalId,
            'kesiapan_keuangan' => 'diminta',
        ])->save();

        Disposisi::create([
            'nomor' => $this->penomoran->berikutnya('disposisi', now()),
            'permohonan_id' => $permohonan->id,
            'langkah' => 6,
            'status_dari' => 'acc_ketua',
            'status_ke' => 'minta_keuangan',
            'dari_user_id' => $user->id,
            'ke_user_id' => $petugasFinalId,
            'ke_peran' => $roleStaf,
            'status' => 'aktif',
            'nominal_usulan' => $nominalDisetujui,
            'instruksi' => $data['instruksi'] ?? 'Koordinasikan permintaan dana Rp '.number_format($nominalDisetujui, 0, ',', '.').' ke bagian Keuangan.',
            'created_by' => $user->id,
        ]);

        PermohonanStatusLog::create([
            'permohonan_id' => $permohonan->id,
            'status_kode' => 'minta_keuangan',
            'status_publik' => 'disetujui',
            'judul_publik' => Permohonan::judulPublikUntuk('disetujui'),
            'keterangan_internal' => 'ACC Ketua Rp '.number_format($nominalDisetujui, 0, ',', '.').'. Menunggu kesiapan Keuangan.',
            'keterangan_publik' => 'Permohonan telah disetujui dan memasuki tahap penyiapan pencairan dana.',
            'user_id' => $user->id,
            'waktu' => now(),
        ]);

        $this->catatAudit('langkah_6_acc_ketua', Permohonan::class, $permohonan->id, $lama, $permohonan->only(['status_kode', 'nominal_disetujui', 'kesiapan_keuangan']));
        $this->notifikasi->kirimKePeran([$roleStaf, 'keuangan'], 'minta_keuangan', "Permintaan Dana {$permohonan->nomor}", 'Permohonan sudah di-ACC Ketua dan menunggu penyiapan dana oleh Keuangan.', "/distribusi/permohonan/{$permohonan->id}", Permohonan::class, $permohonan->id);

        return $permohonan->fresh(['disposisi', 'statusLog']);
    }

    private function ajukanDanaKeKeuangan(Permohonan $permohonan, User $user, array $data): Permohonan
    {
        $this->pastikanPeran($user, ['staf_pendistribusian', 'staf_pendayagunaan', 'kabag_pendis_pendag', 'admin'], 'permintaan dana ke Keuangan');

        if ($permohonan->status_kode !== 'minta_keuangan') {
            throw ValidationException::withMessages([
                'status_kode' => 'Permintaan dana ke Keuangan hanya dapat diajukan setelah ACC Ketua.',
            ]);
        }

        PermohonanStatusLog::create([
            'permohonan_id' => $permohonan->id,
            'status_kode' => 'minta_keuangan',
            'status_publik' => 'disetujui',
            'judul_publik' => Permohonan::judulPublikUntuk('disetujui'),
            'keterangan_internal' => $data['catatan'] ?? 'Staf mengajukan permintaan pencairan dana ke bagian Keuangan.',
            'keterangan_publik' => 'Berkas pencairan sedang diproses untuk penyiapan dana.',
            'user_id' => $user->id,
            'waktu' => now(),
        ]);

        $this->catatAudit('minta_dana_keuangan', Permohonan::class, $permohonan->id, null, ['aksi' => 'minta_dana_staf'], $data['catatan'] ?? null);
        $this->notifikasi->kirimKePeran(['keuangan'], 'minta_dana_keuangan', "Permintaan Penyiapan Dana {$permohonan->nomor}", 'Staf meminta penyiapan dana sebesar Rp '.number_format((int) $permohonan->nominal_disetujui, 0, ',', '.').'.', "/distribusi/permohonan/{$permohonan->id}", Permohonan::class, $permohonan->id);

        return $permohonan->fresh(['disposisi', 'statusLog']);
    }

    private function siapkanDanaKeuangan(Permohonan $permohonan, User $user, array $data): Permohonan
    {
        $this->pastikanPeran($user, ['keuangan', 'admin'], 'konfirmasi kesiapan dana Keuangan');

        if ($permohonan->status_kode !== 'minta_keuangan') {
            throw ValidationException::withMessages([
                'status_kode' => 'Kesiapan dana hanya dapat ditandai pada tahap permintaan Keuangan.',
            ]);
        }

        $lama = $permohonan->only(['kesiapan_keuangan', 'keuangan_diproses_oleh']);

        $permohonan->forceFill([
            'kesiapan_keuangan' => 'siap',
            'keuangan_diproses_oleh' => $user->id,
            'keuangan_siap_at' => now(),
        ])->save();

        PermohonanStatusLog::create([
            'permohonan_id' => $permohonan->id,
            'status_kode' => 'minta_keuangan',
            'status_publik' => 'disetujui',
            'judul_publik' => 'Dana Bantuan Siap Diserahkan',
            'keterangan_internal' => $data['catatan'] ?? 'Bagian Keuangan menyatakan dana bantuan siap diserahkan.',
            'keterangan_publik' => 'Dana bantuan telah disiapkan untuk penyerahan kepada mustahik.',
            'user_id' => $user->id,
            'waktu' => now(),
        ]);

        $this->catatAudit('keuangan_siap', Permohonan::class, $permohonan->id, $lama, $permohonan->only(['kesiapan_keuangan', 'keuangan_diproses_oleh']), $data['catatan'] ?? null);
        $this->notifikasi->kirimKePeran(['pelayanan_mustahik', 'staf_pendistribusian', 'staf_pendayagunaan'], 'dana_siap', "Dana Siap Diserahkan {$permohonan->nomor}", 'Bagian Keuangan telah menyiapkan dana. Silakan serahkan bantuan ke mustahik.', "/distribusi/permohonan/{$permohonan->id}", Permohonan::class, $permohonan->id);

        return $permohonan->fresh(['disposisi', 'statusLog']);
    }

    private function langkah6Ke7(Permohonan $permohonan, User $user, array $data): Permohonan
    {
        $this->pastikanPeran($user, ['pelayanan_mustahik', 'admin'], 'konfirmasi bantuan diterima mustahik');

        if ($permohonan->kesiapan_keuangan !== 'siap') {
            throw ValidationException::withMessages([
                'kesiapan_keuangan' => 'Bagian Keuangan belum menandai dana siap untuk permohonan ini.',
            ]);
        }

        $lama = $permohonan->only(['status_kode', 'status_publik', 'langkah_sekarang']);
        $this->tutupDisposisiAktif($permohonan, $user, $data['catatan'] ?? 'Bantuan telah diserahkan kepada mustahik.');

        $permohonan->forceFill([
            'status_kode' => 'diterima_mustahik',
            'status_publik' => Permohonan::statusPublikUntuk('diterima_mustahik'),
            'langkah_sekarang' => 7,
        ])->save();

        Disposisi::create([
            'nomor' => $this->penomoran->berikutnya('disposisi', now()),
            'permohonan_id' => $permohonan->id,
            'langkah' => 7,
            'status_dari' => 'minta_keuangan',
            'status_ke' => 'diterima_mustahik',
            'dari_user_id' => $user->id,
            'ke_user_id' => $user->id,
            'ke_peran' => 'pelayanan_mustahik',
            'status' => 'selesai',
            'nominal_usulan' => $permohonan->nominal_disetujui,
            'catatan' => $data['catatan'] ?? 'Bantuan diterima mustahik.',
            'waktu_tindak_lanjut' => now(),
            'created_by' => $user->id,
        ]);

        PermohonanStatusLog::create([
            'permohonan_id' => $permohonan->id,
            'status_kode' => 'diterima_mustahik',
            'status_publik' => 'dicairkan',
            'judul_publik' => Permohonan::judulPublikUntuk('dicairkan'),
            'keterangan_internal' => $data['catatan'] ?? 'Bantuan telah diterima mustahik.',
            'keterangan_publik' => 'Bantuan telah dicairkan dan diterima oleh mustahik.',
            'user_id' => $user->id,
            'waktu' => now(),
        ]);

        $this->catatAudit('langkah_7_selesai', Permohonan::class, $permohonan->id, $lama, $permohonan->only(['status_kode', 'status_publik', 'langkah_sekarang']), $data['catatan'] ?? null);

        $this->terbitkanPenyaluran($permohonan, $user, $data);

        if ($permohonan->created_by && $permohonan->created_by !== $user->id) {
            $this->notifikasi->kirimKeUser($permohonan->created_by, 'permohonan_selesai', "Permohonan {$permohonan->nomor} Selesai", 'Bantuan telah diserahkan kepada mustahik.', "/distribusi/permohonan/{$permohonan->id}", Permohonan::class, $permohonan->id);
        }

        return $permohonan->fresh(['disposisi', 'statusLog']);
    }

    private function terbitkanPenyaluran(Permohonan $permohonan, User $user, array $data): Penyaluran
    {
        $tanggal = now()->toDateString();

        $terkunciBa = BeritaAcaraPenyaluran::query()
            ->whereDate('tanggal', $tanggal)
            ->where('status', 'final')
            ->exists();

        if ($terkunciBa) {
            throw ValidationException::withMessages([
                'tanggal' => 'Tanggal hari ini sudah dikunci Berita Acara Penyaluran final. Batalkan berita acara tersebut atau selesaikan serah terima pada hari berikutnya.',
            ]);
        }

        $nominal = (int) ($permohonan->nominal_disetujui ?? $permohonan->nominal_diajukan);

        $sumber = isset($data['sumber_dana_id'])
            ? SumberDana::with('jenisDana')->find($data['sumber_dana_id'])
            : SumberDana::with('jenisDana')
                ->where('aktif', true)
                ->where('jenis_dana_id', $permohonan->jenis_dana_id)
                ->orderBy('id')
                ->first();

        if (! $sumber || ($permohonan->jenis_dana_id && (int) $sumber->jenis_dana_id !== (int) $permohonan->jenis_dana_id)) {
            throw ValidationException::withMessages([
                'sumber_dana_id' => 'Pilih sumber dana yang sesuai dengan jenis dana permohonan untuk mencatat penyaluran.',
            ]);
        }

        $wajibAsnaf = (bool) $sumber->jenisDana?->wajib_asnaf;
        if ($wajibAsnaf && ! $permohonan->asnaf_id) {
            throw ValidationException::withMessages([
                'sumber_dana_id' => 'Asnaf wajib terisi pada permohonan untuk penyaluran bersumber dana Zakat.',
            ]);
        }

        $bentuk = isset($data['bentuk_bantuan_id'])
            ? BentukBantuan::find($data['bentuk_bantuan_id'])
            : BentukBantuan::where('aktif', true)->where('kode', '!=', 'BARANG')->orderBy('id')->first();

        $metode = isset($data['metode_bayar_id'])
            ? MetodeBayar::find($data['metode_bayar_id'])
            : MetodeBayar::where('aktif', true)->where('kode', 'TUNAI')->first() ?? MetodeBayar::where('aktif', true)->orderBy('id')->first();

        if (! $bentuk || ! $metode) {
            throw ValidationException::withMessages([
                'metode_bayar_id' => 'Master bentuk bantuan atau metode bayar belum tersedia.',
            ]);
        }

        if ($metode->butuh_rekening && empty($data['rekening_id'])) {
            throw ValidationException::withMessages([
                'rekening_id' => "Metode bayar {$metode->nama} wajib memilih rekening bank.",
            ]);
        }

        $item = [['sumber_dana_id' => $sumber->id, 'jumlah' => $nominal]];

        $penyaluran = Penyaluran::query()
            ->where('permohonan_id', $permohonan->id)
            ->whereIn('status', ['draft', 'disetujui', 'dicairkan'])
            ->lockForUpdate()
            ->latest('id')
            ->first();

        if ($penyaluran?->status === 'dicairkan') {
            return $penyaluran;
        }

        $hasilSaldo = $this->saldoService->periksaKecukupan($item, $penyaluran?->id);
        $peringatan = ! $hasilSaldo['cukup'] ? implode(' ', $hasilSaldo['peringatan']) : null;
        $lama = $penyaluran?->only(['status']);
        $catatan = $data['catatan'] ?? "Serah terima bantuan permohonan {$permohonan->nomor}.";

        if ($penyaluran) {
            $penyaluran->forceFill([
                'nomor' => $penyaluran->nomor ?: $this->penomoran->berikutnya('penyaluran', now()),
                'tanggal' => $tanggal,
                'status' => 'dicairkan',
                'disetujui_oleh' => $penyaluran->disetujui_oleh ?? $user->id,
                'disetujui_at' => $penyaluran->disetujui_at ?? now(),
                'dicairkan_oleh' => $user->id,
                'dicairkan_at' => now(),
                'peringatan_saldo' => $peringatan,
                'catatan' => $catatan,
            ])->save();
        } else {
            $penyaluran = Penyaluran::create([
                'nomor' => $this->penomoran->berikutnya('penyaluran', now()),
                'tanggal' => $tanggal,
                'status' => 'dicairkan',
                'permohonan_id' => $permohonan->id,
                'mustahik_id' => $permohonan->mustahik_id,
                'program_id' => $permohonan->program_id,
                'sub_bidang' => $permohonan->sub_bidang ?: 'pendistribusian',
                'metode_bayar_id' => $metode->id,
                'rekening_id' => $metode->butuh_rekening ? ($data['rekening_id'] ?? null) : null,
                'petugas_id' => $permohonan->petugas_lapangan_id ?? $user->id,
                'disetujui_oleh' => $user->id,
                'disetujui_at' => now(),
                'dicairkan_oleh' => $user->id,
                'dicairkan_at' => now(),
                'peringatan_saldo' => $peringatan,
                'catatan' => $catatan,
                'created_by' => $user->id,
            ]);

            PenyaluranItem::create([
                'penyaluran_id' => $penyaluran->id,
                'sumber_dana_id' => $sumber->id,
                'asnaf_id' => $wajibAsnaf ? $permohonan->asnaf_id : null,
                'bentuk_bantuan_id' => $bentuk->id,
                'jumlah' => $nominal,
                'created_by' => $user->id,
            ]);
        }

        $this->catatAudit(
            'terbit_dari_permohonan',
            Penyaluran::class,
            $penyaluran->id,
            $lama,
            $penyaluran->only(['nomor', 'status', 'permohonan_id', 'tanggal']),
            "Serah terima permohonan {$permohonan->nomor}",
        );

        return $penyaluran;
    }
}
