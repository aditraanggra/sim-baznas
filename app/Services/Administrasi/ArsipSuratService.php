<?php

namespace App\Services\Administrasi;

use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ArsipSuratService
{
    public function __construct(private readonly AksesPersuratanService $akses) {}

    /**
     * @param  array<string, mixed>  $filter
     * @return array{
     *     items: Collection<int, array<string, mixed>>,
     *     ringkasan: array{total: int, masuk: int, keluar: int}
     * }
     */
    public function cari(User $user, array $filter, int $batas = 300): array
    {
        $this->akses->pastikanBukanUpz($user);

        $q = trim((string) ($filter['q'] ?? ''));
        $tipe = (string) ($filter['tipe'] ?? 'semua');
        $sifat = trim((string) ($filter['sifat'] ?? ''));
        $jenisSuratId = ! empty($filter['jenis_surat_id']) ? (int) $filter['jenis_surat_id'] : null;
        $status = trim((string) ($filter['status'] ?? ''));
        $dari = trim((string) ($filter['dari'] ?? ''));
        $sampai = trim((string) ($filter['sampai'] ?? ''));

        $hasil = collect();

        if ($tipe === 'semua' || $tipe === 'masuk') {
            $queryMasuk = SuratMasuk::query()
                ->with(['pencatat:id,name', 'berkasPrivat:id,uuid,nama_asli'])
                ->when($sifat !== '', fn (Builder $b) => $b->where('sifat', $sifat))
                ->when($status !== '', fn (Builder $b) => $b->where('status_kode', $status))
                ->when($dari !== '', fn (Builder $b) => $b->whereDate('tanggal_terima', '>=', $dari))
                ->when($sampai !== '', fn (Builder $b) => $b->whereDate('tanggal_terima', '<=', $sampai));

            $this->akses->terapkanCakupanSuratMasuk($queryMasuk, $user);

            if ($q !== '') {
                $queryMasuk->where(function (Builder $sub) use ($q): void {
                    if ($sub->getConnection()->getDriverName() === 'pgsql') {
                        $sub->whereRaw(
                            "to_tsvector('indonesian', coalesce(nomor_agenda,'') || ' ' || coalesce(nomor_surat,'') || ' ' || coalesce(pengirim,'') || ' ' || coalesce(perihal,'') || ' ' || coalesce(ringkasan,'')) @@ plainto_tsquery('indonesian', ?)",
                            [$q],
                        )->orWhere('nomor_agenda', 'ilike', "%{$q}%")
                            ->orWhere('nomor_surat', 'ilike', "%{$q}%")
                            ->orWhere('pengirim', 'ilike', "%{$q}%")
                            ->orWhere('perihal', 'ilike', "%{$q}%");
                    } else {
                        $sub->where('nomor_agenda', 'like', "%{$q}%")
                            ->orWhere('nomor_surat', 'like', "%{$q}%")
                            ->orWhere('pengirim', 'like', "%{$q}%")
                            ->orWhere('perihal', 'like', "%{$q}%");
                    }
                });
            }

            $queryMasuk->orderByDesc('tanggal_terima')->orderByDesc('id')->limit($batas)->get()->each(function (SuratMasuk $m) use (&$hasil): void {
                $hasil->push([
                    'uid' => "masuk-{$m->id}",
                    'id' => $m->id,
                    'tipe' => 'masuk',
                    'tipe_label' => 'Surat Masuk',
                    'nomor' => $m->nomor_agenda,
                    'nomor_referensi' => $m->nomor_surat,
                    'tanggal' => $m->tanggal_terima?->format('Y-m-d'),
                    'tanggal_label' => $m->tanggal_terima?->format('d/m/Y') ?? '-',
                    'pihak' => $m->pengirim,
                    'perihal' => $m->perihal,
                    'jenis_surat' => 'Surat Masuk',
                    'sifat' => $m->sifat,
                    'status_kode' => $m->status_kode,
                    'status_label' => SuratMasuk::labelStatus($m->status_kode),
                    'url' => route('umum.surat.masuk.show', $m->id),
                ]);
            });
        }

        if ($tipe === 'semua' || $tipe === 'keluar') {
            $queryKeluar = SuratKeluar::query()
                ->with(['jenisSurat:id,kode,nama'])
                ->when($sifat !== '', fn (Builder $b) => $b->where('sifat', $sifat))
                ->when($jenisSuratId !== null, fn (Builder $b) => $b->where('jenis_surat_id', $jenisSuratId))
                ->when($status !== '', fn (Builder $b) => $b->where('status_kode', $status))
                ->when($dari !== '', fn (Builder $b) => $b->whereDate('tanggal', '>=', $dari))
                ->when($sampai !== '', fn (Builder $b) => $b->whereDate('tanggal', '<=', $sampai));

            $this->akses->terapkanCakupanSuratKeluar($queryKeluar, $user);

            if ($q !== '') {
                $queryKeluar->where(function (Builder $sub) use ($q): void {
                    if ($sub->getConnection()->getDriverName() === 'pgsql') {
                        $sub->whereRaw(
                            "to_tsvector('indonesian', coalesce(nomor,'') || ' ' || coalesce(tujuan,'') || ' ' || coalesce(perihal,'') || ' ' || coalesce(isi,'')) @@ plainto_tsquery('indonesian', ?)",
                            [$q],
                        )->orWhere('nomor', 'ilike', "%{$q}%")
                            ->orWhere('tujuan', 'ilike', "%{$q}%")
                            ->orWhere('perihal', 'ilike', "%{$q}%");
                    } else {
                        $sub->where('nomor', 'like', "%{$q}%")
                            ->orWhere('tujuan', 'like', "%{$q}%")
                            ->orWhere('perihal', 'like', "%{$q}%");
                    }
                });
            }

            $queryKeluar->orderByDesc('tanggal')->orderByDesc('id')->limit($batas)->get()->each(function (SuratKeluar $k) use (&$hasil): void {
                $hasil->push([
                    'uid' => "keluar-{$k->id}",
                    'id' => $k->id,
                    'tipe' => 'keluar',
                    'tipe_label' => 'Surat Keluar',
                    'nomor' => $k->nomor ?? '(Draft)',
                    'nomor_referensi' => $k->jenisSurat?->kode ?? '-',
                    'tanggal' => $k->tanggal?->format('Y-m-d'),
                    'tanggal_label' => $k->tanggal?->format('d/m/Y') ?? '-',
                    'pihak' => $k->tujuan,
                    'perihal' => $k->perihal,
                    'jenis_surat' => $k->jenisSurat?->nama ?? '-',
                    'sifat' => $k->sifat,
                    'status_kode' => $k->status_kode,
                    'status_label' => SuratKeluar::labelStatus($k->status_kode),
                    'url' => route('umum.surat.keluar.show', $k->id),
                ]);
            });
        }

        $terurut = $hasil
            ->sortByDesc(fn (array $item) => [$item['tanggal'] ?? '', $item['id']])
            ->take($batas)
            ->values();

        return [
            'items' => $terurut,
            'ringkasan' => [
                'total' => $terurut->count(),
                'masuk' => $terurut->where('tipe', 'masuk')->count(),
                'keluar' => $terurut->where('tipe', 'keluar')->count(),
            ],
        ];
    }
}
