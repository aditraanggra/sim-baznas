<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenis_upz', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 32)->unique();
            $table->string('nama', 128);
            $table->boolean('aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('upz', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 32)->unique();
            $table->string('nama', 160);
            $table->foreignId('jenis_upz_id')->constrained('jenis_upz')->restrictOnDelete();
            $table->foreignId('desa_id')->constrained('desa')->restrictOnDelete();
            $table->string('ketua', 128);
            $table->string('sekretaris', 128)->nullable();
            $table->string('bendahara', 128)->nullable();
            $table->text('alamat');
            $table->string('pic_nama', 128);
            $table->string('pic_telepon', 32);
            $table->boolean('aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['desa_id', 'aktif']);
        });

        Schema::create('sk_upz', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upz_id')->constrained('upz')->restrictOnDelete();
            $table->string('nomor', 128)->unique();
            $table->date('tanggal_sk');
            $table->date('berlaku_mulai');
            $table->date('berlaku_sampai');
            $table->foreignId('berkas_privat_id')->nullable()->constrained('berkas_privat')->nullOnDelete();
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['upz_id', 'berlaku_sampai']);
        });

        DB::statement("
            CREATE OR REPLACE VIEW v_sk_upz_status AS
            SELECT
                s.id,
                s.upz_id,
                s.nomor,
                s.tanggal_sk,
                s.berlaku_mulai,
                s.berlaku_sampai,
                s.berkas_privat_id,
                (s.berlaku_sampai - CURRENT_DATE) AS sisa_hari,
                CASE
                    WHEN s.berlaku_sampai < CURRENT_DATE THEN 'habis'
                    WHEN (s.berlaku_sampai - CURRENT_DATE) <= 90 THEN 'segera_habis'
                    ELSE 'aktif'
                END AS status_berlaku
            FROM sk_upz s
            WHERE s.deleted_at IS NULL
        ");

        Schema::create('muzakki', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 32)->unique();
            $table->string('jenis', 24)->default('perorangan')->index();
            $table->string('nama', 160)->index();
            $table->text('nik_encrypted')->nullable();
            $table->string('nik_hash', 64)->nullable()->unique();
            $table->string('nik_masked', 32)->nullable();
            $table->string('npwp', 32)->nullable();
            $table->string('telepon', 32)->nullable();
            $table->string('telepon_norm', 24)->nullable()->index();
            $table->string('email', 160)->nullable();
            $table->text('alamat')->nullable();
            $table->foreignId('kecamatan_id')->nullable()->constrained('kecamatan')->nullOnDelete();
            $table->foreignId('desa_id')->nullable()->constrained('desa')->nullOnDelete();
            $table->boolean('aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('berita_acara_penerimaan', function (Blueprint $table) {
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

        Schema::create('penerimaan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 64)->nullable()->unique();
            $table->date('tanggal')->index();
            $table->string('status', 24)->default('draft')->index();
            $table->foreignId('muzakki_id')->constrained('muzakki')->restrictOnDelete();
            $table->foreignId('upz_id')->nullable()->constrained('upz')->nullOnDelete();
            $table->foreignId('metode_bayar_id')->constrained('metode_bayar')->restrictOnDelete();
            $table->foreignId('rekening_id')->nullable()->constrained('rekening_bank')->nullOnDelete();
            $table->foreignId('berita_acara_id')->nullable()->constrained('berita_acara_penerimaan')->nullOnDelete();
            $table->foreignId('petugas_id')->constrained('users')->restrictOnDelete();
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tanggal', 'status']);
            $table->index(['upz_id', 'status']);
        });

        Schema::create('penerimaan_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penerimaan_id')->constrained('penerimaan')->cascadeOnDelete();
            $table->foreignId('sumber_dana_id')->constrained('sumber_dana')->restrictOnDelete();
            $table->bigInteger('jumlah');
            $table->string('keterangan', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['penerimaan_id', 'sumber_dana_id']);
        });

        Schema::create('kwitansi', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 64)->unique();
            $table->uuid('token_verifikasi')->unique();
            $table->foreignId('penerimaan_id')->unique()->constrained('penerimaan')->restrictOnDelete();
            $table->date('tanggal_terbit');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('kwitansi_cetak_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kwitansi_id')->constrained('kwitansi')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->smallInteger('cetak_ke');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('waktu')->useCurrent();
        });

        Schema::create('pembatalan_penerimaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penerimaan_id')->unique()->constrained('penerimaan')->restrictOnDelete();
            $table->text('alasan');
            $table->string('status', 24)->default('menunggu')->index();
            $table->foreignId('diminta_oleh')->constrained('users')->restrictOnDelete();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan_penyetuju')->nullable();
            $table->timestamp('waktu_diminta')->useCurrent();
            $table->timestamp('waktu_disetujui')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_sk_upz_status');

        Schema::dropIfExists('pembatalan_penerimaan');
        Schema::dropIfExists('kwitansi_cetak_log');
        Schema::dropIfExists('kwitansi');
        Schema::dropIfExists('penerimaan_item');
        Schema::dropIfExists('penerimaan');
        Schema::dropIfExists('berita_acara_penerimaan');
        Schema::dropIfExists('muzakki');
        Schema::dropIfExists('sk_upz');
        Schema::dropIfExists('upz');
        Schema::dropIfExists('jenis_upz');
    }
};
