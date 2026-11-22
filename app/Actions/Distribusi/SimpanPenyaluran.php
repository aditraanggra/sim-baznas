<?php

namespace App\Actions\Distribusi;

use App\Actions\Action;
use App\Models\BentukBantuan;
use App\Models\BeritaAcaraPenyaluran;
use App\Models\Concerns\MencatatAudit;
use App\Models\MetodeBayar;
use App\Models\Penyaluran;
use App\Models\PenyaluranItem;
use App\Models\Program;
use App\Models\SumberDana;
use App\Services\SaldoDanaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SimpanPenyaluran extends Action
{
    use MencatatAudit;

    public function __construct(private readonly SaldoDanaService $saldoService) {}

    public function jalankan(array $data): Penyaluran
    {
        $id = isset($data['id']) && $data['id'] !== '' ? (int) $data['id'] : null;

        $tervalidasi = validator($data, [
            'id' => ['nullable', 'integer', 'exists:penyaluran,id'],
            'tanggal' => ['required', 'date'],
            'permohonan_id' => ['nullable', 'integer', 'exists:permohonan,id'],
            'mustahik_id' => ['required', 'integer', 'exists:mustahik,id'],
            'program_id' => ['required', 'integer', 'exists:program,id'],
            'metode_bayar_id' => ['required', 'integer', 'exists:metode_bayar,id'],
            'rekening_id' => ['nullable', 'integer', 'exists:rekening_bank,id'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sumber_dana_id' => ['required', 'integer', 'exists:sumber_dana,id'],
            'items.*.asnaf_id' => ['nullable', 'integer', 'exists:asnaf,id'],
            'items.*.bentuk_bantuan_id' => ['required', 'integer', 'exists:bentuk_bantuan,id'],
            'items.*.uraian_barang' => ['nullable', 'string', 'max:255'],
            'items.*.kuantitas' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.satuan' => ['nullable', 'string', 'max:32'],
            'items.*.jumlah' => ['required', 'integer', 'min:1'],
            'items.*.keterangan' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $metode = MetodeBayar::findOrFail($tervalidasi['metode_bayar_id']);
        if ($metode->butuh_rekening && empty($tervalidasi['rekening_id'])) {
            throw ValidationException::withMessages([
                'rekening_id' => "Metode bayar {$metode->nama} wajib memilih rekening bank.",
            ]);
        }

        $terkunciBa = BeritaAcaraPenyaluran::query()
            ->whereDate('tanggal', $tervalidasi['tanggal'])
            ->where('status', 'final')
            ->exists();

        if ($terkunciBa) {
            throw ValidationException::withMessages([
                'tanggal' => 'Tanggal ini sudah dikunci oleh Berita Acara Penyaluran Harian final.',
            ]);
        }

        $sumberMap = SumberDana::with('jenisDana')
            ->whereIn('id', collect($tervalidasi['items'])->pluck('sumber_dana_id')->unique()->all())
            ->get()
            ->keyBy('id');

        $bentukMap = BentukBantuan::query()
            ->whereIn('id', collect($tervalidasi['items'])->pluck('bentuk_bantuan_id')->unique()->all())
            ->get()
            ->keyBy('id');

        foreach ($tervalidasi['items'] as $idx => $item) {
            $sumber = $sumberMap->get((int) $item['sumber_dana_id']);
            $wajibAsnaf = (bool) ($sumber?->jenisDana?->wajib_asnaf);
            $asnafTerisi = ! empty($item['asnaf_id']);

            if ($wajibAsnaf && ! $asnafTerisi) {
                throw ValidationException::withMessages([
                    "items.{$idx}.asnaf_id" => 'Asnaf wajib diisi untuk penyaluran bersumber dana Zakat.',
                ]);
            }
            if (! $wajibAsnaf && $asnafTerisi) {
                throw ValidationException::withMessages([
                    "items.{$idx}.asnaf_id" => 'Asnaf hanya berlaku untuk dana Zakat (Infak/DSKL/CSR tanpa asnaf).',
                ]);
            }

            $bentuk = $bentukMap->get((int) $item['bentuk_bantuan_id']);
            if ($bentuk?->kode === 'BARANG' && trim((string) ($item['uraian_barang'] ?? '')) === '') {
                throw ValidationException::withMessages([
                    "items.{$idx}.uraian_barang" => 'Uraian barang wajib diisi untuk bantuan berbentuk barang beserta taksiran rupiahnya.',
                ]);
            }
        }

        $program = Program::findOrFail($tervalidasi['program_id']);
        $hasilSaldo = $this->saldoService->periksaKecukupan($tervalidasi['items']);
        $peringatanSaldo = ! $hasilSaldo['cukup'] ? implode(' ', $hasilSaldo['peringatan']) : null;

        return DB::transaction(function () use ($id, $tervalidasi, $program, $metode, $peringatanSaldo): Penyaluran {
            $penyaluran = $id ? Penyaluran::lockForUpdate()->findOrFail($id) : new Penyaluran;

            if ($penyaluran->exists && $penyaluran->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' => 'Hanya draft penyaluran yang dapat diubah.',
                ]);
            }

            $lama = $penyaluran->exists ? $penyaluran->only(['tanggal', 'mustahik_id', 'program_id', 'status']) : null;

            $penyaluran->fill([
                'tanggal' => $tervalidasi['tanggal'],
                'status' => 'draft',
                'permohonan_id' => $tervalidasi['permohonan_id'] ?? null,
                'mustahik_id' => $tervalidasi['mustahik_id'],
                'program_id' => $program->id,
                'sub_bidang' => $program->sub_bidang ?: 'pendistribusian',
                'metode_bayar_id' => $metode->id,
                'rekening_id' => $metode->butuh_rekening ? ($tervalidasi['rekening_id'] ?? null) : null,
                'petugas_id' => $penyaluran->petugas_id ?: Auth::id(),
                'peringatan_saldo' => $peringatanSaldo,
                'catatan' => $tervalidasi['catatan'] ?? null,
            ]);
            $penyaluran->save();

            PenyaluranItem::where('penyaluran_id', $penyaluran->id)->delete();

            foreach ($tervalidasi['items'] as $item) {
                PenyaluranItem::create([
                    'penyaluran_id' => $penyaluran->id,
                    'sumber_dana_id' => (int) $item['sumber_dana_id'],
                    'asnaf_id' => ! empty($item['asnaf_id']) ? (int) $item['asnaf_id'] : null,
                    'bentuk_bantuan_id' => (int) $item['bentuk_bantuan_id'],
                    'uraian_barang' => $item['uraian_barang'] ?? null,
                    'kuantitas' => $item['kuantitas'] ?? null,
                    'satuan' => $item['satuan'] ?? null,
                    'jumlah' => (int) $item['jumlah'],
                    'keterangan' => $item['keterangan'] ?? null,
                    'created_by' => Auth::id(),
                ]);
            }

            $this->catatAudit(
                $id ? 'ubah_draft' : 'buat_draft',
                Penyaluran::class,
                $penyaluran->id,
                $lama,
                [
                    'tanggal' => $penyaluran->tanggal?->format('Y-m-d'),
                    'mustahik_id' => $penyaluran->mustahik_id,
                    'program_id' => $penyaluran->program_id,
                    'total' => $penyaluran->totalRupiah(),
                    'peringatan_saldo' => $peringatanSaldo,
                ],
            );

            return $penyaluran->fresh(['items.sumberDana', 'items.asnaf', 'items.bentukBantuan', 'mustahik', 'program']);
        });
    }
}
