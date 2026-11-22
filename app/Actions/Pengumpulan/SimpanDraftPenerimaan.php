<?php

namespace App\Actions\Pengumpulan;

use App\Actions\Action;
use App\Models\BeritaAcaraPenerimaan;
use App\Models\Concerns\MencatatAudit;
use App\Models\MetodeBayar;
use App\Models\Penerimaan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SimpanDraftPenerimaan extends Action
{
    use MencatatAudit;

    public function jalankan(array $data): Penerimaan
    {
        $id = isset($data['id']) && $data['id'] !== '' ? (int) $data['id'] : null;

        $tervalidasi = validator($data, [
            'id' => ['nullable', 'integer', 'exists:penerimaan,id'],
            'tanggal' => ['required', 'date'],
            'muzakki_id' => ['required', 'integer', 'exists:muzakki,id'],
            'upz_id' => ['nullable', 'integer', 'exists:upz,id'],
            'metode_bayar_id' => ['required', 'integer', 'exists:metode_bayar,id'],
            'rekening_id' => ['nullable', 'integer', 'exists:rekening_bank,id'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sumber_dana_id' => ['required', 'integer', 'exists:sumber_dana,id'],
            'items.*.jumlah' => ['required', 'integer', 'min:1'],
            'items.*.keterangan' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $metode = MetodeBayar::findOrFail($tervalidasi['metode_bayar_id']);
        if ($metode->butuh_rekening && empty($tervalidasi['rekening_id'])) {
            throw ValidationException::withMessages([
                'rekening_id' => "Metode bayar {$metode->nama} wajib memilih rekening bank tujuan.",
            ]);
        }

        if (BeritaAcaraPenerimaan::whereDate('tanggal', $tervalidasi['tanggal'])->where('status', 'final')->exists()) {
            throw ValidationException::withMessages([
                'tanggal' => 'Tanggal ini sudah dikunci oleh Berita Acara Penerimaan final (Q-06).',
            ]);
        }

        return DB::transaction(function () use ($tervalidasi, $id): Penerimaan {
            $penerimaan = $id ? Penerimaan::lockForUpdate()->findOrFail($id) : new Penerimaan;

            if ($penerimaan->exists && $penerimaan->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' => 'Transaksi yang sudah final atau batal tidak dapat diubah.',
                ]);
            }

            $lama = $penerimaan->exists ? [
                ...$penerimaan->only(['tanggal', 'muzakki_id', 'upz_id', 'metode_bayar_id', 'rekening_id', 'status']),
                'total' => $penerimaan->items()->sum('jumlah'),
            ] : null;

            $penerimaan->fill([
                'tanggal' => $tervalidasi['tanggal'],
                'status' => 'draft',
                'muzakki_id' => $tervalidasi['muzakki_id'],
                'upz_id' => $tervalidasi['upz_id'] ?? null,
                'metode_bayar_id' => $tervalidasi['metode_bayar_id'],
                'rekening_id' => $tervalidasi['rekening_id'] ?? null,
                'catatan' => $tervalidasi['catatan'] ?? null,
                'petugas_id' => $penerimaan->petugas_id ?? Auth::id(),
            ]);

            if (! $penerimaan->exists) {
                $penerimaan->created_by ??= Auth::id();
            }

            $penerimaan->save();
            $penerimaan->items()->delete();

            foreach ($tervalidasi['items'] as $item) {
                $penerimaan->items()->create([
                    'sumber_dana_id' => $item['sumber_dana_id'],
                    'jumlah' => (int) $item['jumlah'],
                    'keterangan' => $item['keterangan'] ?? null,
                    'created_by' => Auth::id(),
                ]);
            }

            $penerimaan->load('items');

            $this->catatAudit(
                $penerimaan->wasRecentlyCreated ? 'buat_draft' : 'ubah_draft',
                Penerimaan::class,
                $penerimaan->id,
                $lama,
                [
                    ...$penerimaan->only(['tanggal', 'muzakki_id', 'upz_id', 'metode_bayar_id', 'rekening_id', 'status']),
                    'total' => $penerimaan->totalRupiah(),
                ],
            );

            return $penerimaan;
        });
    }
}
