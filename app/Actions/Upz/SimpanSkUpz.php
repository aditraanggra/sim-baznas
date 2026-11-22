<?php

namespace App\Actions\Upz;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\SkUpz;
use App\Models\Upz;
use App\Services\BerkasPrivatService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SimpanSkUpz extends Action
{
    use MencatatAudit;

    public function __construct(private readonly BerkasPrivatService $berkasService) {}

    public function jalankan(array $data): SkUpz
    {
        $id = isset($data['id']) && $data['id'] !== '' ? (int) $data['id'] : null;

        $tervalidasi = validator($data, [
            'id' => ['nullable', 'integer', 'exists:sk_upz,id'],
            'upz_id' => ['required', 'integer', 'exists:upz,id'],
            'nomor' => ['required', 'string', 'max:128', Rule::unique('sk_upz', 'nomor')->ignore($id)],
            'tanggal_sk' => ['required', 'date'],
            'berlaku_mulai' => ['required', 'date'],
            'berlaku_sampai' => ['required', 'date', 'after_or_equal:berlaku_mulai'],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'berkas' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi, $data, $id): SkUpz {
            $upz = Upz::findOrFail($tervalidasi['upz_id']);
            $sk = $id ? SkUpz::findOrFail($id) : new SkUpz;
            $lama = $sk->exists ? $sk->toArray() : null;

            $berkasId = $sk->berkas_privat_id;
            if (isset($data['berkas']) && $data['berkas'] instanceof UploadedFile) {
                $berkas = $this->berkasService->unggah($data['berkas'], 'sk_upz', [
                    'entitas_tipe' => SkUpz::class,
                    'entitas_id' => $sk->id,
                    'bidang_kode' => 'umum',
                    'upz_id' => $upz->id,
                    'izinkan_publik_bertanda_tangan' => false,
                ]);
                $berkasId = $berkas->id;
            }

            $sk->fill([
                'upz_id' => $upz->id,
                'nomor' => trim((string) $tervalidasi['nomor']),
                'tanggal_sk' => $tervalidasi['tanggal_sk'],
                'berlaku_mulai' => $tervalidasi['berlaku_mulai'],
                'berlaku_sampai' => $tervalidasi['berlaku_sampai'],
                'berkas_privat_id' => $berkasId,
                'keterangan' => $tervalidasi['keterangan'] ?? null,
            ]);

            if (! $sk->exists) {
                $sk->created_by ??= auth()->id();
            }

            $sk->save();

            $upz->update(['nomor_sk' => $sk->nomor]);

            if (isset($berkas)) {
                $berkas->update(['entitas_id' => $sk->id]);
            }

            $this->catatAudit(
                $sk->wasRecentlyCreated ? 'buat' : 'ubah',
                SkUpz::class,
                $sk->id,
                $lama,
                $sk->fresh()->toArray(),
            );

            return $sk;
        });
    }
}
