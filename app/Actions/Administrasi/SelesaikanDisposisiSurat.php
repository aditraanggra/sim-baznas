<?php

namespace App\Actions\Administrasi;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\DisposisiSurat;
use App\Models\StatusDokumen;
use App\Models\SuratMasuk;
use App\Models\SuratMasukStatusLog;
use App\Services\Administrasi\AksesPersuratanService;
use App\Services\BerkasPrivatService;
use App\Services\NotifikasiService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SelesaikanDisposisiSurat extends Action
{
    use MencatatAudit;

    public function __construct(
        private readonly AksesPersuratanService $akses,
        private readonly BerkasPrivatService $berkasService,
        private readonly NotifikasiService $notifikasi,
    ) {}

    public function jalankan(array $data): DisposisiSurat
    {
        $user = Auth::user();
        if (! $user) {
            throw new AuthorizationException('Anda harus login untuk menyelesaikan disposisi.');
        }

        $this->akses->pastikanBukanUpz($user);

        $tervalidasi = validator($data, [
            'disposisi_id' => ['required', 'integer', 'exists:disposisi_surat,id'],
            'catatan_tindak_lanjut' => ['required', 'string', 'max:2000'],
            'keputusan' => ['nullable', 'string', 'max:160'],
            'berkas_bukti' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi, $user): DisposisiSurat {
            $disposisi = DisposisiSurat::lockForUpdate()->findOrFail((int) $tervalidasi['disposisi_id']);
            $surat = SuratMasuk::lockForUpdate()->findOrFail($disposisi->surat_masuk_id);

            if ((int) $disposisi->kepada_user_id !== (int) $user->id && ! $user->hasRole('admin')) {
                throw new AuthorizationException('Anda tidak berhak menyelesaikan disposisi milik pengguna lain.');
            }

            if ($disposisi->status !== 'menunggu') {
                throw ValidationException::withMessages([
                    'status' => 'Cabang disposisi ini sudah selesai atau sudah diteruskan.',
                ]);
            }

            $berkasBuktiId = $disposisi->berkas_bukti_id;
            if (($tervalidasi['berkas_bukti'] ?? null) instanceof UploadedFile) {
                $berkas = $this->berkasService->unggah($tervalidasi['berkas_bukti'], 'disposisi-bukti', [
                    'entitas_tipe' => DisposisiSurat::class,
                    'entitas_id' => $disposisi->id,
                    'bidang_kode' => 'umum',
                ]);
                $berkasBuktiId = $berkas->id;
            }

            $lama = $disposisi->only(['status', 'catatan_tindak_lanjut']);
            $disposisi->update([
                'status' => 'selesai',
                'catatan_tindak_lanjut' => trim($tervalidasi['catatan_tindak_lanjut']),
                'keputusan' => ! empty($tervalidasi['keputusan']) ? trim((string) $tervalidasi['keputusan']) : ($disposisi->keputusan ?? 'Selesai ditindaklanjuti'),
                'berkas_bukti_id' => $berkasBuktiId,
                'selesai_at' => now(),
            ]);

            $sisaMenunggu = DisposisiSurat::query()
                ->where('surat_masuk_id', $surat->id)
                ->where('status', 'menunggu')
                ->count();

            $statusSelesaiDok = StatusDokumen::where('kelompok', 'surat_masuk')->where('kode', 'selesai')->first();

            if ($sisaMenunggu === 0 && $surat->status_kode !== 'selesai') {
                $surat->update([
                    'status_kode' => 'selesai',
                    'status_id' => $statusSelesaiDok?->id,
                    'selesai_at' => now(),
                    'catatan_penutupan' => 'Seluruh cabang disposisi telah selesai ditindaklanjuti.',
                ]);

                SuratMasukStatusLog::create($this->denganPembuat([
                    'surat_masuk_id' => $surat->id,
                    'status_kode' => 'selesai',
                    'status_id' => $statusSelesaiDok?->id,
                    'user_id' => $user->id,
                    'waktu' => now(),
                    'catatan' => 'Seluruh cabang disposisi selesai. Terakhir diselesaikan oleh '.$user->name.': '.trim($tervalidasi['catatan_tindak_lanjut']),
                ]));

                if ($surat->dicatat_oleh && (int) $surat->dicatat_oleh !== (int) $user->id) {
                    $this->notifikasi->kirimKeUser(
                        $surat->dicatat_oleh,
                        'surat_masuk_selesai',
                        'Tindak Lanjut Surat Masuk Selesai',
                        "Agenda {$surat->nomor_agenda} ({$surat->perihal}) telah selesai ditindaklanjuti.",
                        route('umum.surat.masuk.show', $surat->id, false),
                        SuratMasuk::class,
                        $surat->id,
                    );
                }
            } else {
                SuratMasukStatusLog::create($this->denganPembuat([
                    'surat_masuk_id' => $surat->id,
                    'status_kode' => $surat->status_kode,
                    'status_id' => $surat->status_id,
                    'user_id' => $user->id,
                    'waktu' => now(),
                    'catatan' => "Cabang disposisi {$user->name} selesai: ".trim($tervalidasi['catatan_tindak_lanjut']),
                ]));
            }

            $this->catatAudit(
                'selesaikan_disposisi',
                DisposisiSurat::class,
                $disposisi->id,
                $lama,
                ['status' => 'selesai', 'catatan_tindak_lanjut' => trim($tervalidasi['catatan_tindak_lanjut'])],
            );

            return $disposisi->fresh();
        });
    }
}
