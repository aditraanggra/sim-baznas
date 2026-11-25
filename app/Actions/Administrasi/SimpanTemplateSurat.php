<?php

namespace App\Actions\Administrasi;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\TemplateSurat;
use App\Services\Administrasi\AksesPersuratanService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SimpanTemplateSurat extends Action
{
    use MencatatAudit;

    public function __construct(private readonly AksesPersuratanService $akses) {}

    public function jalankan(array $data): TemplateSurat
    {
        $user = Auth::user();
        if (! $user || ! $this->akses->bolehKelolaSurat($user)) {
            throw new AuthorizationException('Anda tidak memiliki wewenang mengelola template surat.');
        }

        $id = ! empty($data['id']) ? (int) $data['id'] : null;

        $tervalidasi = validator($data, [
            'jenis_surat_id' => ['required', 'integer', 'exists:jenis_surat,id'],
            'nama' => ['required', 'string', 'max:160'],
            'isi' => ['required', 'string', 'max:20000'],
            'penutup_default' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        return DB::transaction(function () use ($id, $tervalidasi): TemplateSurat {
            $atribut = [
                'jenis_surat_id' => (int) $tervalidasi['jenis_surat_id'],
                'nama' => trim($tervalidasi['nama']),
                'isi' => trim($tervalidasi['isi']),
                'penutup_default' => ! empty($tervalidasi['penutup_default']) ? trim((string) $tervalidasi['penutup_default']) : null,
            ];

            if ($id) {
                $template = TemplateSurat::findOrFail($id);
                $lama = $template->only(['jenis_surat_id', 'nama', 'isi', 'penutup_default']);
                $template->update($atribut);
                $this->catatAudit('ubah', TemplateSurat::class, $template->id, $lama, $atribut);

                return $template->fresh();
            }

            $template = TemplateSurat::create($this->denganPembuat([...$atribut, 'aktif' => true]));
            $this->catatAudit('buat', TemplateSurat::class, $template->id, null, $atribut);

            return $template;
        });
    }
}
