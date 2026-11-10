<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rkat_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun')->index();
            $table->string('jenis', 10)->default('awal');
            $table->unsignedSmallInteger('nomor_perubahan')->default(0);
            $table->string('status', 12)->default('draft');
            $table->string('sumber', 8)->default('manual');
            $table->string('nomor_surat', 128)->nullable();
            $table->date('tanggal_surat')->nullable();
            $table->timestamp('disahkan_pada')->nullable();
            $table->foreignId('disahkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('disalin_dari_id')->nullable()->constrained('rkat_versions')->nullOnDelete();
            $table->boolean('arsip')->default(false);
            $table->timestamps();

            $table->unique(['tahun', 'jenis', 'nomor_perubahan']);
        });

        // BR-01: satu versi Disahkan per tahun, ditegakkan di DB.
        DB::statement("CREATE UNIQUE INDEX rkat_versions_satu_disahkan ON rkat_versions (tahun) WHERE status = 'disahkan'");

        Schema::create('rkat_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rkat_version_id')->constrained('rkat_versions')->restrictOnDelete();
            $table->foreignId('pos_anggaran_id')->nullable()->constrained('pos_anggaran')->restrictOnDelete();
            $table->string('kode_asal', 64);
            $table->string('nama_asal', 255)->nullable();
            $table->string('tipe_nilai', 16)->default('rupiah');
            $table->bigInteger('nilai');
            $table->timestamps();

            $table->unique(['rkat_version_id', 'kode_asal', 'tipe_nilai']);
            $table->index('pos_anggaran_id');
        });

        Schema::create('rkat_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rkat_version_id')->unique()->constrained('rkat_versions')->restrictOnDelete();
            $table->unsignedSmallInteger('batas_amil_zakat_bp')->nullable();
            $table->unsignedSmallInteger('batas_amil_infak_bp')->nullable();
            $table->unsignedSmallInteger('batas_amil_csr_bp')->nullable();
            $table->unsignedSmallInteger('batas_amil_dskl_bp')->nullable();
            $table->unsignedBigInteger('target_orang')->nullable();
            $table->unsignedBigInteger('target_pengentasan_ekstrem')->nullable();
            $table->unsignedBigInteger('target_pengentasan_garis')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('rkat_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rkat_version_id')->constrained('rkat_versions')->restrictOnDelete();
            $table->string('jenis', 24);
            $table->string('nama_berkas', 255);
            $table->string('path', 255);
            $table->string('mime', 64);
            $table->unsignedInteger('ukuran');
            $table->foreignId('diunggah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diarsipkan_pada')->nullable();
            $table->timestamps();

            $table->index(['rkat_version_id', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rkat_documents');
        Schema::dropIfExists('rkat_notes');
        Schema::dropIfExists('rkat_values');
        DB::statement('DROP INDEX IF EXISTS rkat_versions_satu_disahkan');
        Schema::dropIfExists('rkat_versions');
    }
};
