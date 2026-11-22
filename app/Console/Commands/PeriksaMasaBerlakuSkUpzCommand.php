<?php

namespace App\Console\Commands;

use App\Models\Notifikasi;
use App\Models\SkUpz;
use App\Models\User;
use App\Services\NotifikasiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PeriksaMasaBerlakuSkUpzCommand extends Command
{
    protected $signature = 'upz:peringatan-sk';

    protected $description = 'Mengirim notifikasi in-app untuk SK UPZ yang mendekati habis (H-90, H-60, H-30).';

    public function handle(NotifikasiService $notifikasiService): int
    {
        $barisSk = DB::table('v_sk_upz_status as v')
            ->join('upz', 'upz.id', '=', 'v.upz_id')
            ->whereNull('upz.deleted_at')
            ->where('upz.aktif', true)
            ->whereBetween('v.sisa_hari', [0, 90])
            ->select([
                'v.id',
                'v.upz_id',
                'v.nomor',
                'v.berlaku_sampai',
                'v.sisa_hari',
                'upz.nama as upz_nama',
            ])
            ->get();

        $totalTerkirim = 0;

        foreach ($barisSk as $sk) {
            $sisa = (int) $sk->sisa_hari;
            $ambang = match (true) {
                $sisa <= 30 => 'h_30',
                $sisa <= 60 => 'h_60',
                default => 'h_90',
            };
            $labelAmbang = strtoupper(str_replace('_', '-', $ambang));
            $jenis = "sk_upz_{$ambang}";

            $penerimaInternal = User::query()
                ->where('status', 'aktif')
                ->role(['admin_arsip', 'kabag_sau', 'kabag_pengumpulan'])
                ->get(['id']);

            $penerimaUpz = User::query()
                ->where('status', 'aktif')
                ->where('upz_id', $sk->upz_id)
                ->role('upz')
                ->get(['id']);

            $semuaPenerima = $penerimaInternal->merge($penerimaUpz)->unique('id');

            foreach ($semuaPenerima as $user) {
                $sudahAda = Notifikasi::query()
                    ->where('user_id', $user->id)
                    ->where('jenis', $jenis)
                    ->where('ref_tabel', SkUpz::class)
                    ->where('ref_id', $sk->id)
                    ->exists();

                if ($sudahAda) {
                    continue;
                }

                $notifikasiService->kirimKeUser(
                    $user->id,
                    $jenis,
                    "SK UPZ {$sk->upz_nama} Segera Habis ({$labelAmbang})",
                    "SK nomor {$sk->nomor} berlaku sampai {$sk->berlaku_sampai} (sisa {$sisa} hari).",
                    "/umum/upz/{$sk->upz_id}",
                    SkUpz::class,
                    (int) $sk->id,
                );
                $totalTerkirim++;
            }
        }

        $this->info("Selesai memeriksa SK UPZ. Notifikasi terkirim: {$totalTerkirim}.");

        return self::SUCCESS;
    }
}
