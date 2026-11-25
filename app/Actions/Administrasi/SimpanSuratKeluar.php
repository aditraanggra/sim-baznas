<?php

namespace App\Actions\Administrasi;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\StatusDokumen;
use App\Models\SuratKeluar;
use App\Services\Administrasi\AksesPersuratanService;
use App\Services\BerkasPrivatService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SimpanSuratKeluar extends Action
{
    use MencatatAudit;

    public function __construct(
        private readonly AksesPersuratanService $akses,
        private readonly BerkasPrivatService $berkasService,
    ) {}

    public function jalankan(array $data): SuratKeluar
    {
        $user = Auth::user();
        if (! $user || ! $this->akses->bolehKelolaSurat($user)) {
            throw new AuthorizationException('Anda tidak memiliki wewenang membuat atau mengubah draft surat keluar.');
        }

        $id = ! empty($data['id']) ? (int) $data['id'] : null;

        $tervalidasi = validator($data, [
            'jenis_surat_id' => ['required', 'integer', 'exists:jenis_surat,id'],
            'template_surat_id' => ['nullable', 'integer', 'exists:template_surat,id'],
            'surat_masuk_id' => ['nullable', 'integer', 'exists:surat_masuk,id'],
            'tanggal' => ['required', 'date'],
            'sifat' => ['required', 'in:biasa,penting,segera,rahasia'],
            'lampiran' => ['nullable', 'string', 'max:128'],
            'tujuan' => ['required', 'string', 'max:255'],
            'alamat_tujuan' => ['nullable', 'string', 'max:1000'],
            'perihal' => ['required', 'string', 'max:255'],
            'isi' => ['required', 'string', 'max:30000'],
            'data_placeholder' => ['nullable', 'array'],
            'tembusan' => ['nullable', 'string', 'max:1000'],
            'nama_penandatangan' => ['required', 'string', 'max:160'],
            'jabatan_penandatangan' => ['required', 'string', 'max:160'],
            'berkas_lampiran' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ])->validate();

        return DB::transaction(function () use ($id, $tervalidasi, $user): SuratKeluar {
            $statusDraft = StatusDokumen::where('kelompok', 'surat_keluar')->where('kode', 'draft')->first();

            $atribut = [
                'jenis_surat_id' => (int) $tervalidasi['jenis_surat_id'],
                'template_surat_id' => ! empty($tervalidasi['template_surat_id']) ? (int) $tervalidasi['template_surat_id'] : null,
                'surat_masuk_id' => ! empty($tervalidasi['surat_masuk_id']) ? (int) $tervalidasi['surat_masuk_id'] : null,
                'tanggal' => Carbon::parse($tervalidasi['tanggal'])->toDateString(),
                'sifat' => $tervalidasi['sifat'],
                'lampiran' => ! empty($tervalidasi['lampiran']) ? trim((string) $tervalidasi['lampiran']) : null,
                'tujuan' => trim($tervalidasi['tujuan']),
                'alamat_tujuan' => ! empty($tervalidasi['alamat_tujuan']) ? trim((string) $tervalidasi['alamat_tujuan']) : null,
                'perihal' => trim($tervalidasi['perihal']),
                'isi' => trim($tervalidasi['isi']),
                'data_placeholder' => $tervalidasi['data_placeholder'] ?? null,
                'tembusan' => ! empty($tervalidasi['tembusan']) ? trim((string) $tervalidasi['tembusan']) : null,
                'nama_penandatangan' => trim($tervalidasi['nama_penandatangan']),
                'jabatan_penandatangan' => trim($tervalidasi['jabatan_penandatangan']),
            ];

            if ($id) {
                $surat = SuratKeluar::lockForUpdate()->findOrFail($id);

                if ($surat->status_kode !== 'draft') {
                    throw ValidationException::withMessages([
                        'status' => 'Hanya surat keluar berstatus Draft yang dapat disunting.',
                    ]);
                }

                $lama = $surat->only(['jenis_surat_id', 'tujuan', 'perihal', 'sifat', 'tanggal']);

                if (($tervalidasi['berkas_lampiran'] ?? null) instanceof UploadedFile) {
                    $berkas = $this->berkasService->unggah($tervalidasi['berkas_lampiran'], 'surat-keluar', [
                        'entitas_tipe' => SuratKeluar::class,
                        'entitas_id' => $surat->id,
                        'bidang_kode' => 'umum',
                    ]);
                    $atribut['berkas_lampiran_id'] = $berkas->id;
                }

                $surat->update($atribut);
                $this->catatAudit('ubah', SuratKeluar::class, $surat->id, $lama, $atribut);

                return $surat->fresh();
            }

            $surat = SuratKeluar::create($this->denganPembuat([
                ...$atribut,
                'status_kode' => 'draft',
                'status_id' => $statusDraft?->id,
                'dibuat_oleh' => $user->id,
            ]));

            if (($tervalidasi['berkas_lampiran'] ?? null) instanceof UploadedFile) {
                $berkas = $this->berkasService->unggah($tervalidasi['berkas_lampiran'], 'surat-keluar', [
                    'entitas_tipe' => SuratKeluar::class,
                    'entitas_id' => $surat->id,
                    'bidang_kode' => 'umum',
                ]);
                $surat->update(['berkas_lampiran_id' => $berkas->id]);
            }

            $this->catatAudit('buat', SuratKeluar::class, $surat->id, null, [
                'jenis_surat_id' => $surat->jenis_surat_id,
                'tujuan' => $surat->tujuan,
                'perihal' => $surat->perihal,
                'status_kode' => 'draft',
            ]);

            return $surat->fresh();
        });
    }
}
