<?php

namespace App\Actions\Distribusi;

use App\Actions\Action;
use App\Models\BeritaAcaraPenyaluran;
use App\Models\Concerns\MencatatAudit;
use App\Models\Penyaluran;
use App\Models\User;
use App\Services\BerkasPrivatService;
use App\Services\SaldoDanaService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CairkanPenyaluran extends Action
{
    use MencatatAudit;

    public function __construct(
        private readonly SaldoDanaService $saldoService,
        private readonly BerkasPrivatService $berkasService,
    ) {}

    public function jalankan(array $data): Penyaluran
    {
        $tervalidasi = validator($data, [
            'id' => ['required', 'integer', 'exists:penyaluran,id'],
            'bukti_serah_terima' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ])->validate();

        $user = Auth::user();
        if (! $user instanceof User || ! $user->hasAnyRole([
            'pelayanan_mustahik',
            'staf_pendistribusian',
            'staf_pendayagunaan',
            'kabag_pendis_pendag',
            'keuangan',
            'admin',
        ])) {
            throw new AuthorizationException('Anda tidak berwenang mencairkan penyaluran.');
        }

        return DB::transaction(function () use ($tervalidasi, $user): Penyaluran {
            $penyaluran = Penyaluran::with('items')->lockForUpdate()->findOrFail($tervalidasi['id']);

            if ($penyaluran->status !== 'disetujui') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya penyaluran berstatus disetujui yang dapat dicairkan.',
                ]);
            }

            $terkunciBa = BeritaAcaraPenyaluran::query()
                ->whereDate('tanggal', $penyaluran->tanggal)
                ->where('status', 'final')
                ->exists();

            if ($terkunciBa) {
                throw ValidationException::withMessages([
                    'tanggal' => 'Tanggal ini sudah dikunci oleh Berita Acara Penyaluran Harian final.',
                ]);
            }

            $itemArray = $penyaluran->items->map(fn ($i) => [
                'sumber_dana_id' => $i->sumber_dana_id,
                'jumlah' => $i->jumlah,
            ])->all();

            $hasilSaldo = $this->saldoService->periksaKecukupan($itemArray, $penyaluran->id);
            $peringatanSaldo = ! $hasilSaldo['cukup'] ? implode(' ', $hasilSaldo['peringatan']) : null;

            $buktiId = $penyaluran->bukti_serah_terima_id;
            if (! empty($tervalidasi['bukti_serah_terima']) && $tervalidasi['bukti_serah_terima'] instanceof UploadedFile) {
                $berkas = $this->berkasService->unggah($tervalidasi['bukti_serah_terima'], 'penyaluran', [
                    'entitas_tipe' => Penyaluran::class,
                    'entitas_id' => $penyaluran->id,
                    'bidang_kode' => 'distribusi',
                ]);
                $buktiId = $berkas->id;
            }

            $lama = $penyaluran->only(['status', 'dicairkan_oleh', 'bukti_serah_terima_id']);

            $penyaluran->forceFill([
                'status' => 'dicairkan',
                'dicairkan_oleh' => $user->id,
                'dicairkan_at' => now(),
                'bukti_serah_terima_id' => $buktiId,
                'peringatan_saldo' => $peringatanSaldo,
                'catatan' => $tervalidasi['catatan'] ?? $penyaluran->catatan,
            ])->save();

            $this->catatAudit(
                'cairkan',
                Penyaluran::class,
                $penyaluran->id,
                $lama,
                $penyaluran->only(['status', 'dicairkan_oleh', 'bukti_serah_terima_id', 'peringatan_saldo']),
                $tervalidasi['catatan'] ?? null,
            );

            return $penyaluran->fresh(['items.sumberDana', 'items.asnaf', 'items.bentukBantuan', 'mustahik', 'program']);
        });
    }
}
