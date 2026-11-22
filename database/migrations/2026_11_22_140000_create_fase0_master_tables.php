<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('upz_id')->nullable()->index();
        });

        Schema::create('bidang', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 32)->unique();
            $table->string('nama', 128);
            $table->string('warna', 16);
            $table->smallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('provinsi', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 16)->unique();
            $table->string('nama', 128);
            $table->boolean('aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('kabupaten', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provinsi_id')->constrained('provinsi')->restrictOnDelete();
            $table->string('kode', 16)->unique();
            $table->string('nama', 128);
            $table->boolean('aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('kecamatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kabupaten_id')->constrained('kabupaten')->restrictOnDelete();
            $table->string('kode', 16)->unique();
            $table->string('nama', 128);
            $table->boolean('aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('desa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kecamatan_id')->constrained('kecamatan')->restrictOnDelete();
            $table->string('kode', 24)->unique();
            $table->string('nama', 128);
            $table->boolean('aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('jenis_dana', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 16)->unique();
            $table->string('nama', 64)->unique();
            $table->boolean('wajib_asnaf')->default(false);
            $table->boolean('aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('dana_kelompok', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 24)->unique();
            $table->string('nama', 64)->unique();
            $table->boolean('aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sumber_dana', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 16)->unique();
            $table->string('nama', 128);
            $table->foreignId('jenis_dana_id')->constrained('jenis_dana')->restrictOnDelete();
            $table->foreignId('dana_kelompok_id')->constrained('dana_kelompok')->restrictOnDelete();
            $table->boolean('terikat')->default(false);
            $table->boolean('aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('asnaf', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 24)->unique();
            $table->string('nama', 64)->unique();
            $table->smallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('program', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 32)->unique();
            $table->string('nama', 128);
            $table->foreignId('bidang_id')->nullable()->constrained('bidang')->restrictOnDelete();
            $table->string('sub_bidang', 32)->nullable();
            $table->foreignId('asnaf_id')->nullable()->constrained('asnaf')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('program')->nullOnDelete();
            $table->boolean('aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('metode_bayar', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 24)->unique();
            $table->string('nama', 64);
            $table->boolean('butuh_rekening')->default(false);
            $table->boolean('aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rekening_bank', function (Blueprint $table) {
            $table->id();
            $table->string('bank', 64);
            $table->string('no_rekening', 64)->unique();
            $table->string('atas_nama', 128);
            $table->foreignId('jenis_dana_id')->nullable()->constrained('jenis_dana')->nullOnDelete();
            $table->unsignedBigInteger('coa_id')->nullable();
            $table->boolean('aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('jenis_dokumen', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 32)->unique();
            $table->string('nama', 128);
            $table->string('kelompok', 32)->default('umum');
            $table->boolean('wajib')->default(false);
            $table->boolean('aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('status_dokumen', function (Blueprint $table) {
            $table->id();
            $table->string('kelompok', 32);
            $table->string('kode', 32);
            $table->string('nama', 128);
            $table->smallInteger('urutan')->default(0);
            $table->string('warna', 16)->default('info');
            $table->boolean('tampil_publik')->default(false);
            $table->boolean('aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['kelompok', 'kode']);
        });

        Schema::create('bentuk_bantuan', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 24)->unique();
            $table->string('nama', 64);
            $table->boolean('aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('nomor_sequence', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_nomor', 64);
            $table->smallInteger('tahun');
            $table->unsignedBigInteger('nilai_terakhir')->default(0);
            $table->timestamps();
            $table->unique(['jenis_nomor', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomor_sequence');
        Schema::dropIfExists('bentuk_bantuan');
        Schema::dropIfExists('status_dokumen');
        Schema::dropIfExists('jenis_dokumen');
        Schema::dropIfExists('rekening_bank');
        Schema::dropIfExists('metode_bayar');
        Schema::dropIfExists('program');
        Schema::dropIfExists('asnaf');
        Schema::dropIfExists('sumber_dana');
        Schema::dropIfExists('dana_kelompok');
        Schema::dropIfExists('jenis_dana');
        Schema::dropIfExists('desa');
        Schema::dropIfExists('kecamatan');
        Schema::dropIfExists('kabupaten');
        Schema::dropIfExists('provinsi');
        Schema::dropIfExists('bidang');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['upz_id']);
            $table->dropColumn('upz_id');
        });
    }
};
