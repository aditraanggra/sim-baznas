<?php

namespace App\Actions\Pengaturan;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\TautanAkses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BuatTautanAkses extends Action
{
    use MencatatAudit;

    /**
     * @return array{tautan: TautanAkses, token_mentah: string}
     */
    public function jalankan(array $data): array
    {
        $user = Auth::user();
        if (! $user || ! $user->hasRole('admin')) {
            throw new AuthorizationException('Hanya Administrator Sistem yang dapat menerbitkan tautan akses khusus.');
        }

        $tervalidasi = validator($data, [
            'label' => ['required', 'string', 'max:160'],
            'jenis' => ['required', Rule::in(TautanAkses::DAFTAR_JENIS)],
            'kadaluarsa_at' => ['nullable', 'date', 'after:now'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ], [
            'kadaluarsa_at.after' => 'Tanggal kedaluwarsa harus lebih besar dari waktu sekarang.',
        ])->validate();

        return DB::transaction(function () use ($tervalidasi, $user): array {
            $tokenMentah = bin2hex(random_bytes(32));

            $tautan = TautanAkses::create([
                'label' => $tervalidasi['label'],
                'jenis' => $tervalidasi['jenis'],
                'token_hash' => hash('sha256', $tokenMentah),
                'token_prefix' => substr($tokenMentah, 0, 8),
                'kadaluarsa_at' => $tervalidasi['kadaluarsa_at'] ?? null,
                'catatan' => $tervalidasi['catatan'] ?? null,
                'dibuat_oleh' => $user->id,
            ]);

            $this->catatAudit(
                'buat',
                TautanAkses::class,
                $tautan->id,
                null,
                [
                    'label' => $tautan->label,
                    'jenis' => $tautan->jenis,
                    'token_prefix' => $tautan->token_prefix,
                    'kadaluarsa_at' => $tautan->kadaluarsa_at?->toIso8601String(),
                ],
            );

            return [
                'tautan' => $tautan,
                'token_mentah' => $tokenMentah,
            ];
        });
    }
}
