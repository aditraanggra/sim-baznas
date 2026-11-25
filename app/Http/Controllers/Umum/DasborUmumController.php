<?php

namespace App\Http\Controllers\Umum;

use App\Http\Controllers\Controller;
use App\Models\DisposisiSurat;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\Upz;
use App\Services\Administrasi\AksesPersuratanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DasborUmumController extends Controller
{
    public function index(Request $request, AksesPersuratanService $akses): Response
    {
        $user = $request->user();
        $adalahUpz = $user->adalahUpzEksternal();

        if ($adalahUpz) {
            $upz = Upz::query()->untukPengguna($user)->with('jenisUpz:id,nama')->first();
            $skStatus = $upz
                ? DB::table('v_sk_upz_status')->where('upz_id', $upz->id)->orderByDesc('berlaku_sampai')->first()
                : null;

            return Inertia::render('umum/dasbor', [
                'bidang' => config('sisfozis.bidang.umum'),
                'adalah_upz' => true,
                'upz_ringkas' => $upz ? [
                    'id' => $upz->id,
                    'nama' => $upz->nama,
                    'jenis' => $upz->jenisUpz?->nama,
                    'sk_nomor' => $skStatus?->nomor ?? $upz->nomor_sk,
                    'sk_status' => $skStatus?->status_berlaku ?? 'belum_ada',
                    'sk_sisa_hari' => $skStatus ? (int) $skStatus->sisa_hari : null,
                ] : null,
                'ringkasan' => null,
                'disposisi_menunggu' => [],
                'surat_keluar_menunggu' => [],
                'sk_segera_habis' => [],
                'akses' => [
                    'boleh_kelola_surat' => false,
                    'boleh_setujui_keluar' => false,
                ],
            ]);
        }

        $masukQuery = $akses->terapkanCakupanSuratMasuk(SuratMasuk::query(), $user);
        $keluarQuery = $akses->terapkanCakupanSuratKeluar(SuratKeluar::query(), $user);

        $suratMasukAktif = (clone $masukQuery)->where('status_kode', '!=', 'selesai')->count();
        $disposisiSayaCount = DisposisiSurat::query()
            ->where('kepada_user_id', $user->id)
            ->where('status', 'menunggu')
            ->count();
        $suratKeluarDraftAtauMenunggu = (clone $keluarQuery)
            ->whereIn('status_kode', ['draft', 'menunggu_persetujuan'])
            ->count();
        $skSegeraHabisQuery = DB::table('v_sk_upz_status as v')
            ->join('upz as u', 'u.id', '=', 'v.upz_id')
            ->whereNull('u.deleted_at')
            ->where('u.aktif', true)
            ->whereIn('v.status_berlaku', ['segera_habis', 'habis']);

        $skBermasalahCount = (clone $skSegeraHabisQuery)->count();

        $disposisiMenunggu = DisposisiSurat::query()
            ->with(['suratMasuk:id,nomor_agenda,pengirim,perihal,sifat,tanggal_terima', 'dariUser:id,name'])
            ->where('kepada_user_id', $user->id)
            ->where('status', 'menunggu')
            ->orderBy('tenggat')
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(fn (DisposisiSurat $d) => [
                'id' => $d->id,
                'surat_masuk_id' => $d->surat_masuk_id,
                'nomor_agenda' => $d->suratMasuk?->nomor_agenda ?? '-',
                'pengirim' => $d->suratMasuk?->pengirim ?? '-',
                'perihal' => $d->suratMasuk?->perihal ?? '-',
                'sifat' => $d->suratMasuk?->sifat ?? 'biasa',
                'dari' => $d->dariUser?->name ?? '-',
                'instruksi' => $d->instruksi,
                'tenggat' => $d->tenggat?->format('d/m/Y'),
            ])
            ->all();

        $suratKeluarMenunggu = $akses->bolehSetujuiSuratKeluar($user)
            ? $akses->terapkanCakupanSuratKeluar(SuratKeluar::query(), $user)
                ->with(['jenisSurat:id,kode,nama', 'pembuat:id,name'])
                ->where('status_kode', 'menunggu_persetujuan')
                ->orderByDesc('diajukan_at')
                ->limit(6)
                ->get()
                ->map(fn (SuratKeluar $sk) => [
                    'id' => $sk->id,
                    'jenis' => $sk->jenisSurat?->nama ?? '-',
                    'tujuan' => $sk->tujuan,
                    'perihal' => $sk->perihal,
                    'tanggal' => $sk->tanggal?->format('d/m/Y'),
                    'pembuat' => $sk->pembuat?->name ?? '-',
                ])
                ->all()
            : [];

        $skSegeraHabis = (clone $skSegeraHabisQuery)
            ->orderBy('v.sisa_hari')
            ->limit(5)
            ->get(['u.id as upz_id', 'u.nama', 'v.nomor', 'v.berlaku_sampai', 'v.sisa_hari', 'v.status_berlaku'])
            ->map(fn ($r) => [
                'upz_id' => (int) $r->upz_id,
                'nama' => $r->nama,
                'nomor' => $r->nomor,
                'berlaku_sampai' => $r->berlaku_sampai,
                'sisa_hari' => (int) $r->sisa_hari,
                'status_berlaku' => $r->status_berlaku,
            ])
            ->all();

        return Inertia::render('umum/dasbor', [
            'bidang' => config('sisfozis.bidang.umum'),
            'adalah_upz' => false,
            'upz_ringkas' => null,
            'ringkasan' => [
                'surat_masuk_aktif' => $suratMasukAktif,
                'disposisi_menunggu_saya' => $disposisiSayaCount,
                'surat_keluar_proses' => $suratKeluarDraftAtauMenunggu,
                'sk_upz_perlu_tindakan' => $skBermasalahCount,
            ],
            'disposisi_menunggu' => $disposisiMenunggu,
            'surat_keluar_menunggu' => $suratKeluarMenunggu,
            'sk_segera_habis' => $skSegeraHabis,
            'akses' => [
                'boleh_kelola_surat' => $akses->bolehKelolaSurat($user),
                'boleh_setujui_keluar' => $akses->bolehSetujuiSuratKeluar($user),
            ],
        ]);
    }
}
