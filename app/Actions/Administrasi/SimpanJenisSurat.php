<?php

namespace App\Actions\Administrasi;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\JenisSurat;
use App\Services\Administrasi\AksesPersuratanService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SimpanJenisSurat extends Action
{
    use MencatatAudit;

    public function __construct(private readonly AksesPersuratanService $akses) {}

    public function jalankan(array $data): JenisSurat
    {
        $user = Auth::user();
        if (! $user || ! $this->akses->bolehKelolaSurat($user)) {
            throw new AuthorizationException('Anda tidak memiliki wewenang mengelola jenis surat.');
        }

        $id = ! empty($data['id']) ? (int) $data['id'] : null;

        $tervalidasi = validator($data, [
            'kode' => ['required', 'string', 'max:24', Rule::unique('jenis_surat', 'kode')->ignore($id)],
            'nama' => ['required', 'string', 'max:128'],
            'format_nomor' => ['nullable', 'string', 'max:128'],
            'pisah_urutan' => ['nullable', 'boolean'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ])->validate();

        return DB::transaction(function () use ($id, $tervalidasi): JenisSurat {
            $kode = strtoupper(trim($tervalidasi['kode']));
            $atribut = [
                'kode' => $kode,
                'nama' => trim($tervalidasi['nama']),
                'format_nomor' => ! empty($tervalidasi['format_nomor']) ? trim((string) $tervalidasi['format_nomor']) : null,
                'pisah_urutan' => (bool) ($tervalidasi['pisah_urutan'] ?? false),
                'keterangan' => $tervalidasi['keterangan'] ?? null,
            ];

            if ($id) {
                $jenis = JenisSurat::findOrFail($id);
                $lama = $jenis->only(['kode', 'nama', 'format_nomor', 'pisah_urutan', 'keterangan']);
                $jenis->update($atribut);
                $this->catatAudit('ubah', JenisSurat::class, $jenis->id, $lama, $atribut);

                return $jenis->fresh();
            }

            $jenis = JenisSurat::create($this->denganPembuat([...$atribut, 'aktif' => true]));
            $this->catatAudit('buat', JenisSurat::class, $jenis->id, null, $atribut);

            return $jenis;
        });
    }
}
