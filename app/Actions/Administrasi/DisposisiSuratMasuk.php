<?php

namespace App\Actions\Administrasi;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\DisposisiSurat;
use App\Models\StatusDokumen;
use App\Models\SuratMasuk;
use App\Models\SuratMasukStatusLog;
use App\Models\User;
use App\Services\Administrasi\AksesPersuratanService;
use App\Services\NotifikasiService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DisposisiSuratMasuk extends Action
{
    use MencatatAudit;

    public function __construct(
        private readonly AksesPersuratanService $akses,
        private readonly NotifikasiService $notifikasi,
    ) {}

    public function jalankan(array $data): SuratMasuk
    {
        $user = Auth::user();
        if (! $user) {
            throw new AuthorizationException('Anda harus login untuk mendisposisikan surat.');
        }

        $this->akses->pastikanBukanUpz($user);

        $tervalidasi = validator($data, [
            'surat_masuk_id' => ['required', 'integer', 'exists:surat_masuk,id'],
            'parent_disposisi_id' => ['nullable', 'integer', 'exists:disposisi_surat,id'],
            'kepada_user_ids' => ['required', 'array', 'min:1'],
            'kepada_user_ids.*' => ['required', 'integer', 'distinct', 'exists:users,id'],
            'instruksi' => ['required', 'string', 'max:2000'],
            'tenggat' => ['nullable', 'date'],
            'keputusan' => ['nullable', 'string', 'max:160'],
        ])->validate();

        $penerimaList = User::query()
            ->whereIn('id', $tervalidasi['kepada_user_ids'])
            ->where('status', 'aktif')
            ->get();

        if ($penerimaList->count() !== count($tervalidasi['kepada_user_ids'])) {
            throw ValidationException::withMessages([
                'kepada_user_ids' => 'Semua penerima disposisi harus merupakan pengguna aktif.',
            ]);
        }

        foreach ($penerimaList as $penerima) {
            if ($penerima->adalahUpzEksternal()) {
                throw ValidationException::withMessages([
                    'kepada_user_ids' => 'Akun UPZ eksternal tidak dapat menerima disposisi surat internal.',
                ]);
            }
        }

        return DB::transaction(function () use ($tervalidasi, $user, $penerimaList): SuratMasuk {
            $surat = SuratMasuk::lockForUpdate()->findOrFail((int) $tervalidasi['surat_masuk_id']);

            if ($surat->status_kode === 'selesai') {
                throw ValidationException::withMessages([
                    'status' => 'Surat masuk yang sudah selesai tidak dapat didisposisikan ulang.',
                ]);
            }

            $parentId = ! empty($tervalidasi['parent_disposisi_id']) ? (int) $tervalidasi['parent_disposisi_id'] : null;

            if ($parentId !== null) {
                $parent = DisposisiSurat::lockForUpdate()->findOrFail($parentId);

                if ((int) $parent->surat_masuk_id !== (int) $surat->id) {
                    throw ValidationException::withMessages([
                        'parent_disposisi_id' => 'Disposisi induk tidak sesuai dengan surat masuk.',
                    ]);
                }

                if ((int) $parent->kepada_user_id !== (int) $user->id && ! $user->hasRole('admin')) {
                    throw new AuthorizationException('Anda hanya berhak meneruskan disposisi yang ditujukan kepada Anda.');
                }

                if ($parent->status !== 'menunggu') {
                    throw ValidationException::withMessages([
                        'parent_disposisi_id' => 'Cabang disposisi ini sudah ditindaklanjuti.',
                    ]);
                }

                $parent->update([
                    'status' => 'diteruskan',
                    'keputusan' => $tervalidasi['keputusan'] ?? 'Diteruskan ke pelaksana',
                    'selesai_at' => now(),
                ]);

                $urutanBerikut = ((int) DisposisiSurat::where('surat_masuk_id', $surat->id)->max('urutan')) + 1;
                foreach ($penerimaList as $idx => $penerima) {
                    DisposisiSurat::create($this->denganPembuat([
                        'surat_masuk_id' => $surat->id,
                        'parent_disposisi_id' => $parent->id,
                        'tahap' => 'bawahan',
                        'urutan' => $urutanBerikut + $idx,
                        'dari_user_id' => $user->id,
                        'kepada_user_id' => $penerima->id,
                        'kepada_peran' => $penerima->getRoleNames()->first(),
                        'instruksi' => trim($tervalidasi['instruksi']),
                        'tenggat' => $tervalidasi['tenggat'] ?? null,
                        'keputusan' => $tervalidasi['keputusan'] ?? null,
                        'status' => 'menunggu',
                        'waktu' => now(),
                    ]));

                    $this->notifikasi->kirimKeUser(
                        $penerima->id,
                        'disposisi_surat_masuk',
                        'Disposisi Surat Masuk',
                        "Agenda {$surat->nomor_agenda} ({$surat->perihal}): ".trim($tervalidasi['instruksi']),
                        route('umum.surat.masuk.show', $surat->id, false),
                        SuratMasuk::class,
                        $surat->id,
                    );
                }

                $lama = ['status_kode' => $surat->status_kode];
                $statusDok = StatusDokumen::where('kelompok', 'surat_masuk')->where('kode', 'tindak_lanjut')->first();
                if ($surat->status_kode !== 'tindak_lanjut') {
                    $surat->update([
                        'status_kode' => 'tindak_lanjut',
                        'status_id' => $statusDok?->id,
                    ]);
                }

                SuratMasukStatusLog::create($this->denganPembuat([
                    'surat_masuk_id' => $surat->id,
                    'status_kode' => 'tindak_lanjut',
                    'status_id' => $statusDok?->id,
                    'user_id' => $user->id,
                    'waktu' => now(),
                    'catatan' => 'Disposisi diteruskan ke '.$penerimaList->pluck('name')->implode(', ').': '.trim($tervalidasi['instruksi']),
                ]));

                $this->catatAudit(
                    'disposisi_teruskan',
                    SuratMasuk::class,
                    $surat->id,
                    $lama,
                    ['status_kode' => 'tindak_lanjut', 'penerima' => $penerimaList->pluck('name')->all()],
                    trim($tervalidasi['instruksi']),
                );

                return $surat->fresh();
            }

            // Jalur Ketua (K9): disposisi dari Ketua ke 1..N Waka atau langsung ke bawahan
            if (! $this->akses->bolehDisposisiDariKetua($user)) {
                throw new AuthorizationException('Hanya Ketua yang berhak melakukan disposisi tingkat pimpinan.');
            }

            if (! in_array($surat->status_kode, ['diteruskan_ketua', 'disposisi_waka', 'tindak_lanjut'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Surat harus diteruskan ke Ketua terlebih dahulu sebelum didisposisikan.',
                ]);
            }

            $adaWaka = false;
            $urutanBerikut = ((int) DisposisiSurat::where('surat_masuk_id', $surat->id)->max('urutan')) + 1;

            foreach ($penerimaList as $idx => $penerima) {
                $adalahWaka = $penerima->hasAnyRole(['waka_1', 'waka_2', 'waka_3', 'waka_4']);
                if ($adalahWaka) {
                    $adaWaka = true;
                }

                DisposisiSurat::create($this->denganPembuat([
                    'surat_masuk_id' => $surat->id,
                    'parent_disposisi_id' => null,
                    'tahap' => $adalahWaka ? 'waka' : 'bawahan',
                    'urutan' => $urutanBerikut + $idx,
                    'dari_user_id' => $user->id,
                    'kepada_user_id' => $penerima->id,
                    'kepada_peran' => $penerima->getRoleNames()->first(),
                    'instruksi' => trim($tervalidasi['instruksi']),
                    'tenggat' => $tervalidasi['tenggat'] ?? null,
                    'keputusan' => $tervalidasi['keputusan'] ?? null,
                    'status' => 'menunggu',
                    'waktu' => now(),
                ]));

                $this->notifikasi->kirimKeUser(
                    $penerima->id,
                    'disposisi_surat_masuk',
                    'Disposisi Surat dari Ketua',
                    "Agenda {$surat->nomor_agenda} ({$surat->perihal}): ".trim($tervalidasi['instruksi']),
                    route('umum.surat.masuk.show', $surat->id, false),
                    SuratMasuk::class,
                    $surat->id,
                );
            }

            $lama = ['status_kode' => $surat->status_kode];
            $statusTarget = $surat->status_kode === 'tindak_lanjut'
                ? 'tindak_lanjut'
                : ($adaWaka ? 'disposisi_waka' : 'tindak_lanjut');

            $statusDok = StatusDokumen::where('kelompok', 'surat_masuk')->where('kode', $statusTarget)->first();

            $surat->update([
                'status_kode' => $statusTarget,
                'status_id' => $statusDok?->id,
            ]);

            SuratMasukStatusLog::create($this->denganPembuat([
                'surat_masuk_id' => $surat->id,
                'status_kode' => $statusTarget,
                'status_id' => $statusDok?->id,
                'user_id' => $user->id,
                'waktu' => now(),
                'catatan' => 'Disposisi Ketua kepada '.$penerimaList->pluck('name')->implode(', ').': '.trim($tervalidasi['instruksi']),
            ]));

            $this->catatAudit(
                'disposisi_ketua',
                SuratMasuk::class,
                $surat->id,
                $lama,
                ['status_kode' => $statusTarget, 'penerima' => $penerimaList->pluck('name')->all()],
                trim($tervalidasi['instruksi']),
            );

            return $surat->fresh();
        });
    }
}
