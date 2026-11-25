<?php

namespace App\Actions\Distribusi;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\Disposisi;
use App\Models\JenisDana;
use App\Models\Mustahik;
use App\Models\Permohonan;
use App\Models\PermohonanDokumen;
use App\Models\PermohonanStatusLog;
use App\Models\Program;
use App\Services\BerkasPrivatService;
use App\Services\NotifikasiService;
use App\Services\PenomoranService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BuatPermohonan extends Action
{
    use MencatatAudit;

    public function __construct(
        private readonly PenomoranService $penomoran,
        private readonly BerkasPrivatService $berkasService,
        private readonly NotifikasiService $notifikasi,
    ) {}

    public function jalankan(array $data): Permohonan
    {
        $tervalidasi = validator($data, [
            'tanggal' => ['nullable', 'date'],
            'mustahik_id' => ['required', 'integer', 'exists:mustahik,id'],
            'pemohon_sama_mustahik' => ['nullable', 'boolean'],
            'pemohon_nama' => ['nullable', 'string', 'max:160'],
            'pemohon_telepon' => ['nullable', 'string', 'max:32'],
            'pemohon_hubungan' => ['nullable', 'string', 'max:64'],
            'pemohon_alamat' => ['nullable', 'string', 'max:500'],
            'program_id' => ['required', 'integer', 'exists:program,id'],
            'jenis_dana_id' => ['nullable', 'integer', 'exists:jenis_dana,id'],
            'asnaf_id' => ['nullable', 'integer', 'exists:asnaf,id'],
            'nominal_diajukan' => ['required', 'integer', 'min:1'],
            'uraian' => ['nullable', 'string', 'max:2000'],
            'dokumen' => ['nullable', 'array'],
            'dokumen.*.jenis_dokumen_id' => ['required_with:dokumen', 'integer', 'exists:jenis_dokumen,id'],
            'dokumen.*.berkas' => ['required_with:dokumen', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'dokumen.*.keterangan' => ['nullable', 'string', 'max:255'],
        ])->validate();

        $mustahik = Mustahik::findOrFail($tervalidasi['mustahik_id']);
        if (! $mustahik->aktif) {
            throw ValidationException::withMessages([
                'mustahik_id' => 'Mustahik nonaktif tidak dapat mengajukan permohonan baru.',
            ]);
        }

        $sama = array_key_exists('pemohon_sama_mustahik', $tervalidasi)
            ? (bool) $tervalidasi['pemohon_sama_mustahik']
            : true;

        $pemohonNama = trim((string) ($tervalidasi['pemohon_nama'] ?? ''));
        $pemohonTelepon = trim((string) ($tervalidasi['pemohon_telepon'] ?? ''));
        $pemohonHubungan = trim((string) ($tervalidasi['pemohon_hubungan'] ?? ''));
        $pemohonAlamat = trim((string) ($tervalidasi['pemohon_alamat'] ?? ''));

        if (! $sama) {
            $galatPemohon = [];
            if ($pemohonNama === '') {
                $galatPemohon['pemohon_nama'] = 'Nama pemohon wajib diisi apabila berbeda dengan penerima manfaat (mustahik).';
            }
            if ($pemohonTelepon === '') {
                $galatPemohon['pemohon_telepon'] = 'Nomor telepon pemohon wajib diisi untuk keperluan konfirmasi dan pelacakan publik.';
            }
            if ($galatPemohon !== []) {
                throw ValidationException::withMessages($galatPemohon);
            }
        }

        if (! empty($tervalidasi['jenis_dana_id'])) {
            $jenisDana = JenisDana::findOrFail($tervalidasi['jenis_dana_id']);
            if ($jenisDana->wajib_asnaf && empty($tervalidasi['asnaf_id'])) {
                throw ValidationException::withMessages([
                    'asnaf_id' => 'Asnaf wajib dipilih untuk permohonan bersumber dana Zakat.',
                ]);
            }
            if (! $jenisDana->wajib_asnaf && ! empty($tervalidasi['asnaf_id'])) {
                throw ValidationException::withMessages([
                    'asnaf_id' => 'Asnaf tidak berlaku untuk jenis dana Infak, DSKL, atau CSR.',
                ]);
            }
        }

        $program = Program::findOrFail($tervalidasi['program_id']);
        $tanggalObj = ! empty($tervalidasi['tanggal']) ? Carbon::parse($tervalidasi['tanggal']) : Carbon::today();

        return DB::transaction(function () use ($tervalidasi, $program, $tanggalObj, $mustahik, $sama, $pemohonNama, $pemohonTelepon, $pemohonHubungan, $pemohonAlamat): Permohonan {
            do {
                $kodeTracking = 'TRK-'.$tanggalObj->format('ymd').'-'.strtoupper(Str::random(5));
            } while (Permohonan::where('kode_tracking', $kodeTracking)->exists());

            $permohonan = Permohonan::create([
                'nomor' => $this->penomoran->berikutnya('permohonan', $tanggalObj),
                'kode_tracking' => $kodeTracking,
                'tanggal' => $tanggalObj->toDateString(),
                'mustahik_id' => $mustahik->id,
                'pemohon_sama_mustahik' => $sama,
                'pemohon_nama' => $sama ? $mustahik->nama : $pemohonNama,
                'pemohon_telepon' => $sama ? $mustahik->telepon : $pemohonTelepon,
                'pemohon_hubungan' => $sama ? 'Diri Sendiri' : ($pemohonHubungan !== '' ? $pemohonHubungan : 'Perwakilan'),
                'pemohon_alamat' => $sama ? $mustahik->alamat : ($pemohonAlamat !== '' ? $pemohonAlamat : null),
                'program_id' => $program->id,
                'sub_bidang' => $program->sub_bidang ?: 'pendistribusian',
                'jenis_dana_id' => $tervalidasi['jenis_dana_id'] ?? null,
                'asnaf_id' => $tervalidasi['asnaf_id'] ?? null,
                'nominal_diajukan' => (int) $tervalidasi['nominal_diajukan'],
                'status_kode' => 'diajukan',
                'status_publik' => Permohonan::statusPublikUntuk('diajukan'),
                'langkah_sekarang' => 1,
                'kesiapan_keuangan' => 'belum',
                'uraian' => $tervalidasi['uraian'] ?? null,
                'created_by' => Auth::id(),
            ]);

            Disposisi::create([
                'nomor' => $this->penomoran->berikutnya('disposisi', $tanggalObj),
                'permohonan_id' => $permohonan->id,
                'langkah' => 1,
                'status_dari' => null,
                'status_ke' => 'diajukan',
                'dari_user_id' => Auth::id(),
                'ke_user_id' => Auth::id(),
                'ke_peran' => 'pelayanan_mustahik',
                'status' => 'aktif',
                'instruksi' => 'Verifikasi awal dan pengajuan disposisi ke Ketua.',
                'created_by' => Auth::id(),
            ]);

            PermohonanStatusLog::create([
                'permohonan_id' => $permohonan->id,
                'status_kode' => 'diajukan',
                'status_publik' => 'diajukan',
                'judul_publik' => Permohonan::judulPublikUntuk('diajukan'),
                'keterangan_internal' => 'Permohonan dicatat oleh Pelayanan Mustahik.',
                'keterangan_publik' => 'Permohonan bantuan telah diterima dan menunggu pemeriksaan awal.',
                'user_id' => Auth::id(),
                'waktu' => now(),
            ]);

            foreach ($tervalidasi['dokumen'] ?? [] as $dok) {
                /** @var UploadedFile $berkas */
                $berkas = $dok['berkas'];
                $berkasPrivat = $this->berkasService->unggah($berkas, 'permohonan', [
                    'entitas_tipe' => Permohonan::class,
                    'entitas_id' => $permohonan->id,
                    'bidang_kode' => 'distribusi',
                ]);

                PermohonanDokumen::create([
                    'permohonan_id' => $permohonan->id,
                    'jenis_dokumen_id' => (int) $dok['jenis_dokumen_id'],
                    'berkas_privat_id' => $berkasPrivat->id,
                    'keterangan' => $dok['keterangan'] ?? null,
                    'created_by' => Auth::id(),
                ]);
            }

            $this->catatAudit(
                'buat',
                Permohonan::class,
                $permohonan->id,
                null,
                $permohonan->only(['nomor', 'kode_tracking', 'mustahik_id', 'pemohon_sama_mustahik', 'pemohon_nama', 'program_id', 'nominal_diajukan', 'status_kode']),
            );

            $this->notifikasi->kirimKePeran(
                ['pelayanan_mustahik'],
                'permohonan_baru',
                "Permohonan Baru {$permohonan->nomor}",
                "Permohonan atas nama {$mustahik->nama} menunggu verifikasi dan penerusan ke Ketua.",
                "/distribusi/permohonan/{$permohonan->id}",
                Permohonan::class,
                $permohonan->id,
            );

            return $permohonan->fresh(['mustahik', 'program', 'disposisi', 'statusLog', 'dokumen.jenisDokumen']);
        });
    }
}
