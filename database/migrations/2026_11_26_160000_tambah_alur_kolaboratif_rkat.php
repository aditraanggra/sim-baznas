<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rkat_versions', function (Blueprint $table) {
            $table->string('mode_penyusunan', 20)->default('manual')->after('sumber')->index();
            $table->bigInteger('target_pengumpulan')->nullable()->after('mode_penyusunan');
            $table->timestamp('target_ditetapkan_pada')->nullable()->after('target_pengumpulan');
            $table->foreignId('target_ditetapkan_oleh')->nullable()->after('target_ditetapkan_pada')->constrained('users')->nullOnDelete();
        });

        Schema::create('rkat_aturan_amil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rkat_version_id')->constrained('rkat_versions')->cascadeOnDelete();
            $table->foreignId('pos_anggaran_id')->constrained('pos_anggaran')->restrictOnDelete();
            $table->unsignedSmallInteger('urutan');
            $table->string('uraian', 255);
            $table->string('kelompok_penyaluran', 30);
            $table->unsignedSmallInteger('persen_bp');
            $table->unsignedSmallInteger('persen_maks_bp');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['rkat_version_id', 'pos_anggaran_id']);
        });

        Schema::create('rkat_tahap_bidang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rkat_version_id')->constrained('rkat_versions')->cascadeOnDelete();
            $table->string('bidang_kode', 20);
            $table->string('status', 20)->default('menunggu');
            $table->timestamp('diajukan_pada')->nullable();
            $table->foreignId('diajukan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_pada')->nullable();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan_verifikasi')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['rkat_version_id', 'bidang_kode']);
        });

        Schema::create('rkat_jenis_kegiatan', function (Blueprint $table) {
            $table->id();
            $table->string('bidang_kode', 20)->index();
            $table->string('kode', 40)->nullable();
            $table->string('nama', 200);
            $table->foreignId('pos_anggaran_id')->constrained('pos_anggaran')->restrictOnDelete();
            $table->foreignId('pos_program_id')->nullable()->constrained('pos_anggaran')->restrictOnDelete();
            $table->string('satuan_default', 50)->default('orang');
            $table->text('keterangan')->nullable();
            $table->boolean('aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('rkat_kegiatan_rencana', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rkat_version_id')->constrained('rkat_versions')->cascadeOnDelete();
            $table->string('bidang_kode', 20)->index();
            $table->foreignId('rkat_jenis_kegiatan_id')->constrained('rkat_jenis_kegiatan')->restrictOnDelete();
            $table->string('nama_kegiatan', 200);
            $table->foreignId('pos_anggaran_id')->constrained('pos_anggaran')->restrictOnDelete();
            $table->foreignId('pos_program_id')->nullable()->constrained('pos_anggaran')->restrictOnDelete();
            $table->bigInteger('jumlah');
            $table->string('satuan', 50);
            $table->unsignedInteger('frekuensi')->default(1);
            $table->bigInteger('besaran');
            $table->bigInteger('total');
            $table->text('keterangan')->nullable();
            $table->timestamp('diarsipkan_pada')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['rkat_version_id', 'bidang_kode']);
        });

        Schema::create('rkat_pelengkap_bidang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rkat_version_id')->constrained('rkat_versions')->cascadeOnDelete();
            $table->string('bidang_kode', 20);
            $table->foreignId('pos_anggaran_id')->constrained('pos_anggaran')->restrictOnDelete();
            $table->bigInteger('nilai')->default(0);
            $table->string('keterangan', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['rkat_version_id', 'pos_anggaran_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rkat_pelengkap_bidang');
        Schema::dropIfExists('rkat_kegiatan_rencana');
        Schema::dropIfExists('rkat_jenis_kegiatan');
        Schema::dropIfExists('rkat_tahap_bidang');
        Schema::dropIfExists('rkat_aturan_amil');

        Schema::table('rkat_versions', function (Blueprint $table) {
            $table->dropForeign(['target_ditetapkan_oleh']);
            $table->dropIndex(['mode_penyusunan']);
            $table->dropColumn([
                'mode_penyusunan',
                'target_pengumpulan',
                'target_ditetapkan_pada',
                'target_ditetapkan_oleh',
            ]);
        });
    }
};
