<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahun_anggaran', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun')->unique();
            $table->string('status', 16)->default('draft')->index(); // draft, berjalan, tutup
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->timestamp('ditutup_pada')->nullable();
            $table->foreignId('ditutup_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('rkat_versions', function (Blueprint $table) {
            $table->foreignId('tahun_anggaran_id')->nullable()->after('tahun')->constrained('tahun_anggaran')->nullOnDelete();
        });

        Schema::create('pos_anggaran_pemetaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_anggaran_id')->unique()->constrained('pos_anggaran')->restrictOnDelete();
            $table->foreignId('bidang_id')->constrained('bidang')->restrictOnDelete();
            $table->string('sumber_transaksi', 32); // penerimaan, penyaluran, penerimaan_orang, penyaluran_orang, manual
            $table->foreignId('jenis_dana_id')->nullable()->constrained('jenis_dana')->nullOnDelete();
            $table->foreignId('sumber_dana_id')->nullable()->constrained('sumber_dana')->nullOnDelete();
            $table->foreignId('asnaf_id')->nullable()->constrained('asnaf')->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained('program')->nullOnDelete();
            $table->boolean('filter_via_upz')->nullable();
            $table->string('filter_jenis_muzakki', 24)->nullable();
            $table->string('nama_kegiatan', 255);
            $table->boolean('aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('realisasi_manual', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun')->index();
            $table->unsignedSmallInteger('bulan')->index(); // 1..12
            $table->foreignId('pos_anggaran_id')->constrained('pos_anggaran')->restrictOnDelete();
            $table->bigInteger('nilai');
            $table->string('sumber', 16)->default('manual'); // manual, impor
            $table->boolean('terkunci')->default(false);
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tahun', 'bulan', 'pos_anggaran_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('realisasi_manual');
        Schema::dropIfExists('pos_anggaran_pemetaan');

        Schema::table('rkat_versions', function (Blueprint $table) {
            $table->dropForeign(['tahun_anggaran_id']);
            $table->dropColumn('tahun_anggaran_id');
        });

        Schema::dropIfExists('tahun_anggaran');
    }
};
