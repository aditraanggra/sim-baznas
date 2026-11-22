<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mustahik', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 32)->unique();
            $table->string('jenis', 24)->default('perorangan')->index();
            $table->string('nama', 160)->index();
            $table->text('nik_encrypted')->nullable();
            $table->string('nik_hash', 64)->nullable()->unique();
            $table->string('nik_masked', 32)->nullable();
            $table->string('telepon', 32)->nullable();
            $table->string('telepon_norm', 24)->nullable()->index();
            $table->text('alamat')->nullable();
            $table->foreignId('kecamatan_id')->nullable()->constrained('kecamatan')->nullOnDelete();
            $table->foreignId('desa_id')->nullable()->constrained('desa')->nullOnDelete();
            $table->string('penanggung_jawab', 160)->nullable();
            $table->string('status_verifikasi', 24)->default('menunggu')->index();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('waktu_verifikasi')->nullable();
            $table->text('catatan_verifikasi')->nullable();
            $table->boolean('aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('mustahik_asnaf', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mustahik_id')->constrained('mustahik')->cascadeOnDelete();
            $table->foreignId('asnaf_id')->constrained('asnaf')->restrictOnDelete();
            $table->boolean('utama')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['mustahik_id', 'asnaf_id']);
        });

        Schema::create('mustahik_dokumen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mustahik_id')->constrained('mustahik')->cascadeOnDelete();
            $table->foreignId('jenis_dokumen_id')->constrained('jenis_dokumen')->restrictOnDelete();
            $table->foreignId('berkas_privat_id')->constrained('berkas_privat')->cascadeOnDelete();
            $table->string('keterangan', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('permohonan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 64)->unique();
            $table->string('kode_tracking', 24)->unique();
            $table->date('tanggal')->index();
            $table->foreignId('mustahik_id')->constrained('mustahik')->restrictOnDelete();
            $table->foreignId('program_id')->constrained('program')->restrictOnDelete();
            $table->string('sub_bidang', 24)->index();
            $table->foreignId('jenis_dana_id')->nullable()->constrained('jenis_dana')->nullOnDelete();
            $table->foreignId('asnaf_id')->nullable()->constrained('asnaf')->nullOnDelete();
            $table->bigInteger('nominal_diajukan');
            $table->bigInteger('nominal_disetujui')->nullable();
            $table->string('status_kode', 32)->default('diajukan')->index();
            $table->string('status_publik', 32)->default('diajukan')->index();
            $table->smallInteger('langkah_sekarang')->default(1);
            $table->foreignId('petugas_lapangan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kesiapan_keuangan', 24)->default('belum')->index();
            $table->foreignId('keuangan_diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('keuangan_siap_at')->nullable();
            $table->text('catatan_asesmen')->nullable();
            $table->text('uraian')->nullable();
            $table->text('alasan_tolak')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status_kode', 'tanggal']);
        });

        Schema::create('permohonan_dokumen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permohonan_id')->constrained('permohonan')->cascadeOnDelete();
            $table->foreignId('jenis_dokumen_id')->constrained('jenis_dokumen')->restrictOnDelete();
            $table->foreignId('berkas_privat_id')->constrained('berkas_privat')->cascadeOnDelete();
            $table->string('keterangan', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('disposisi', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 64)->unique();
            $table->foreignId('permohonan_id')->constrained('permohonan')->cascadeOnDelete();
            $table->smallInteger('langkah');
            $table->string('status_dari', 32)->nullable();
            $table->string('status_ke', 32);
            $table->foreignId('dari_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ke_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ke_peran', 48)->nullable()->index();
            $table->string('status', 24)->default('aktif')->index();
            $table->bigInteger('nominal_usulan')->nullable();
            $table->text('instruksi')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamp('waktu_tindak_lanjut')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['permohonan_id', 'langkah']);
            $table->index(['ke_peran', 'status']);
        });

        Schema::create('permohonan_status_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permohonan_id')->constrained('permohonan')->cascadeOnDelete();
            $table->string('status_kode', 32);
            $table->string('status_publik', 32);
            $table->string('judul_publik', 120);
            $table->text('keterangan_internal')->nullable();
            $table->text('keterangan_publik')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('waktu')->useCurrent();

            $table->index(['permohonan_id', 'waktu']);
        });

        Schema::create('berita_acara_penyaluran', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 64)->unique();
            $table->date('tanggal');
            $table->smallInteger('revisi_ke')->default(0);
            $table->string('status', 24)->default('draft');
            $table->bigInteger('total_rupiah')->default(0);
            $table->integer('jumlah_transaksi')->default(0);
            $table->foreignId('penanggung_jawab_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamp('final_at')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tanggal', 'status']);
        });

        Schema::create('penyaluran', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 64)->nullable()->unique();
            $table->date('tanggal')->index();
            $table->string('status', 24)->default('draft')->index();
            $table->foreignId('permohonan_id')->nullable()->constrained('permohonan')->nullOnDelete();
            $table->foreignId('mustahik_id')->constrained('mustahik')->restrictOnDelete();
            $table->foreignId('program_id')->constrained('program')->restrictOnDelete();
            $table->string('sub_bidang', 24)->index();
            $table->foreignId('metode_bayar_id')->constrained('metode_bayar')->restrictOnDelete();
            $table->foreignId('rekening_id')->nullable()->constrained('rekening_bank')->nullOnDelete();
            $table->foreignId('berita_acara_id')->nullable()->constrained('berita_acara_penyaluran')->nullOnDelete();
            $table->foreignId('petugas_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disetujui_at')->nullable();
            $table->foreignId('dicairkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dicairkan_at')->nullable();
            $table->foreignId('bukti_serah_terima_id')->nullable()->constrained('berkas_privat')->nullOnDelete();
            $table->text('peringatan_saldo')->nullable();
            $table->text('catatan')->nullable();
            $table->text('alasan_batal')->nullable();
            $table->foreignId('dibatalkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dibatalkan_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tanggal', 'status']);
            $table->index(['sub_bidang', 'status']);
            $table->index(['mustahik_id', 'status']);
        });

        Schema::create('penyaluran_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penyaluran_id')->constrained('penyaluran')->cascadeOnDelete();
            $table->foreignId('sumber_dana_id')->constrained('sumber_dana')->restrictOnDelete();
            $table->foreignId('asnaf_id')->nullable()->constrained('asnaf')->restrictOnDelete();
            $table->foreignId('bentuk_bantuan_id')->constrained('bentuk_bantuan')->restrictOnDelete();
            $table->string('uraian_barang', 255)->nullable();
            $table->decimal('kuantitas', 12, 2)->nullable();
            $table->string('satuan', 32)->nullable();
            $table->bigInteger('jumlah');
            $table->string('keterangan', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['penyaluran_id', 'sumber_dana_id']);
            $table->index('asnaf_id');
        });

        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_saldo_kelompok_dana AS
            WITH masuk AS (
                SELECT sd.dana_kelompok_id, COALESCE(SUM(pi.jumlah), 0)::bigint AS total_masuk
                FROM penerimaan_item pi
                JOIN penerimaan p ON p.id = pi.penerimaan_id AND p.status = 'final'
                JOIN sumber_dana sd ON sd.id = pi.sumber_dana_id
                GROUP BY sd.dana_kelompok_id
            ),
            keluar AS (
                SELECT sd.dana_kelompok_id,
                       COALESCE(SUM(CASE WHEN py.status = 'dicairkan' THEN pyi.jumlah ELSE 0 END), 0)::bigint AS total_keluar,
                       COALESCE(SUM(CASE WHEN py.status = 'disetujui' THEN pyi.jumlah ELSE 0 END), 0)::bigint AS total_komitmen
                FROM penyaluran_item pyi
                JOIN penyaluran py ON py.id = pyi.penyaluran_id AND py.status IN ('disetujui', 'dicairkan')
                JOIN sumber_dana sd ON sd.id = pyi.sumber_dana_id
                GROUP BY sd.dana_kelompok_id
            )
            SELECT dk.id AS dana_kelompok_id,
                   dk.kode,
                   dk.nama,
                   COALESCE(m.total_masuk, 0)::bigint AS total_masuk,
                   COALESCE(k.total_keluar, 0)::bigint AS total_keluar,
                   COALESCE(k.total_komitmen, 0)::bigint AS total_komitmen,
                   (COALESCE(m.total_masuk, 0) - COALESCE(k.total_keluar, 0) - COALESCE(k.total_komitmen, 0))::bigint AS saldo_tersedia
            FROM dana_kelompok dk
            LEFT JOIN masuk m ON m.dana_kelompok_id = dk.id
            LEFT JOIN keluar k ON k.dana_kelompok_id = dk.id
            WHERE dk.deleted_at IS NULL
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_saldo_kelompok_dana');

        Schema::dropIfExists('penyaluran_item');
        Schema::dropIfExists('penyaluran');
        Schema::dropIfExists('berita_acara_penyaluran');
        Schema::dropIfExists('permohonan_status_log');
        Schema::dropIfExists('disposisi');
        Schema::dropIfExists('permohonan_dokumen');
        Schema::dropIfExists('permohonan');
        Schema::dropIfExists('mustahik_dokumen');
        Schema::dropIfExists('mustahik_asnaf');
        Schema::dropIfExists('mustahik');
    }
};
