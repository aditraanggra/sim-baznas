<?php

namespace App\Services\Rkat;

use App\Models\PosAnggaran;
use App\Models\RkatNote;
use App\Models\RkatValue;
use App\Models\RkatVersion;
use Illuminate\Support\Facades\DB;

/**
 * Menyinkronkan seluruh pos daun rkat_values (L2-L7) dan batas rkat_notes
 * dari rincian kegiatan 3 bidang secara otomatis untuk versi RKAT kolaboratif.
 * Tanpa hard delete RkatValue (BR-11): pos tanpa kegiatan bernilai 0.
 */
final class SinkronisasiNilaiRkatService
{
    public static function sinkronkan(RkatVersion $versi): void
    {
        if (! $versi->isKolaboratif()) {
            return;
        }

        DB::transaction(function () use ($versi): void {
            $ringkasan = AlokasiRkatService::ringkasan($versi);
            $kegiatan = $versi->kegiatanRencana()->aktif()->with(['posAnggaran:id,kode', 'posProgram:id,kode'])->get();
            $pelengkap = $versi->pelengkapBidang()->with('posAnggaran:id,kode')->get();
            $programKeMustahik = config('rkat_amil.program_ke_mustahik_l5', []);

            $daunPos = PosAnggaran::query()
                ->where('agregasi', 'input_langsung')
                ->where(fn ($q) => $q->where('aktif', true)->orWhereIn('id', $versi->nilai()->pluck('pos_anggaran_id')->filter()))
                ->get()
                ->keyBy('kode');

            $nilaiPerKode = [];
            foreach ($daunPos as $kode => $pos) {
                $nilaiPerKode[$kode] = 0;
            }

            // L2 Neraca dari Pengumpulan
            foreach ($kegiatan->where('bidang_kode', 'pengumpulan') as $item) {
                $kode = $item->posAnggaran?->kode;
                if ($kode !== null) {
                    $nilaiPerKode[$kode] = ($nilaiPerKode[$kode] ?? 0) + (int) $item->total;
                }
            }

            // L3 Amil & L6 Amil
            foreach ($ringkasan['pengumpulan']['hak_amil_l3'] as $kode => $nilai) {
                $nilaiPerKode[$kode] = (int) $nilai;
            }
            foreach ($ringkasan['pengumpulan']['hak_amil_l6'] as $kode => $nilai) {
                $nilaiPerKode[$kode] = (int) $nilai;
            }

            // L5/1 Muzakki/Munfiq/Donatur
            foreach ($ringkasan['pengumpulan']['orang_l5'] as $kode => $nilai) {
                $nilaiPerKode[$kode] = (int) $nilai;
            }

            // L3 Non-Amil, L4 Program, dan L5/2 Mustahik dari Pendistribusian & Pendayagunaan
            foreach ($kegiatan->where('bidang_kode', 'distribusi') as $item) {
                $kodeL3 = $item->posAnggaran?->kode;
                if ($kodeL3 !== null) {
                    $nilaiPerKode[$kodeL3] = ($nilaiPerKode[$kodeL3] ?? 0) + (int) $item->total;
                }

                $kodeL4 = $item->posProgram?->kode;
                if ($kodeL4 !== null) {
                    $nilaiPerKode[$kodeL4] = ($nilaiPerKode[$kodeL4] ?? 0) + (int) $item->total;

                    $kodeL5Mustahik = $programKeMustahik[$kodeL4] ?? null;
                    if ($kodeL5Mustahik !== null) {
                        $nilaiPerKode[$kodeL5Mustahik] = ($nilaiPerKode[$kodeL5Mustahik] ?? 0) + (int) $item->jumlah;
                    }
                }
            }

            // L7 Penggunaan Operasional dari SAU
            foreach ($kegiatan->where('bidang_kode', 'umum') as $item) {
                $kodeL7 = $item->posAnggaran?->kode;
                if ($kodeL7 !== null) {
                    $nilaiPerKode[$kodeL7] = ($nilaiPerKode[$kodeL7] ?? 0) + (int) $item->total;
                }
            }

            // Pos pelengkap non-kegiatan (L2/LN, L5/3, L6 Hibah & Saldo Awal)
            foreach ($pelengkap as $item) {
                $kode = $item->posAnggaran?->kode;
                if ($kode !== null) {
                    $nilaiPerKode[$kode] = (int) $item->nilai;
                }
            }

            foreach ($daunPos as $kode => $pos) {
                RkatValue::updateOrCreate(
                    [
                        'rkat_version_id' => $versi->id,
                        'kode_asal' => $pos->kode,
                        'tipe_nilai' => $pos->tipe_nilai,
                    ],
                    [
                        'pos_anggaran_id' => $pos->id,
                        'nama_asal' => $pos->nama,
                        'nilai' => (int) ($nilaiPerKode[$kode] ?? 0),
                    ],
                );
            }

            // Sinkronkan batas hak amil ke RkatNote agar VAL-6 dan laporan tetap akurat
            $metaBaris = collect(config('rkat_amil.baris', []))->keyBy('kode_pos');
            $aturan = $versi->aturanAmil()->with('posAnggaran:id,kode')->get();
            $batasJenis = ['zakat' => 1250, 'infak' => 2000, 'csr' => 2000, 'dskl' => 2000];
            $terisiBatas = [];

            foreach ($aturan as $baris) {
                $kodePos = $baris->posAnggaran?->kode ?? '';
                $jenisVal = $metaBaris->get($kodePos)['jenis_dana_val'] ?? null;
                if ($jenisVal !== null) {
                    $terisiBatas[$jenisVal] = max($terisiBatas[$jenisVal] ?? 0, (int) $baris->persen_maks_bp);
                }
            }

            $totalMustahik = (int) (($nilaiPerKode['L5/2.1'] ?? 0)
                + ($nilaiPerKode['L5/2.2'] ?? 0)
                + ($nilaiPerKode['L5/2.3'] ?? 0)
                + ($nilaiPerKode['L5/2.4'] ?? 0)
                + ($nilaiPerKode['L5/2.5'] ?? 0));

            RkatNote::updateOrCreate(
                ['rkat_version_id' => $versi->id],
                [
                    'batas_amil_zakat_bp' => $terisiBatas['zakat'] ?? $batasJenis['zakat'],
                    'batas_amil_infak_bp' => $terisiBatas['infak'] ?? $batasJenis['infak'],
                    'batas_amil_csr_bp' => $terisiBatas['csr'] ?? $batasJenis['csr'],
                    'batas_amil_dskl_bp' => $terisiBatas['dskl'] ?? $batasJenis['dskl'],
                    'target_orang' => $totalMustahik > 0 ? $totalMustahik : null,
                    'target_pengentasan_ekstrem' => ($nilaiPerKode['L5/3.1'] ?? 0) > 0 ? $nilaiPerKode['L5/3.1'] : null,
                    'target_pengentasan_garis' => ($nilaiPerKode['L5/3.2'] ?? 0) > 0 ? $nilaiPerKode['L5/3.2'] : null,
                ],
            );
        });
    }
}
