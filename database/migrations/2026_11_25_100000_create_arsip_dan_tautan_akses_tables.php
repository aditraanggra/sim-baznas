<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_dokumen', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 32)->unique();
            $table->string('nama', 128)->unique();
            $table->smallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('dokumen_arsip', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('kategori_dokumen_id')->constrained('kategori_dokumen');
            $table->foreignId('bidang_id')->nullable()->constrained('bidang')->nullOnDelete();
            $table->smallInteger('tahun')->index();
            $table->string('nomor', 120)->nullable();
            $table->string('judul', 255);
            $table->unsignedSmallInteger('versi')->default(1);
            $table->foreignId('induk_versi_id')->nullable()->constrained('dokumen_arsip')->nullOnDelete();
            $table->date('tanggal_pengesahan')->nullable();
            $table->text('ringkasan')->nullable();
            $table->foreignId('berkas_privat_id')->constrained('berkas_privat');
            $table->string('status', 24)->default('draft')->index();
            $table->foreignId('diunggah_oleh')->constrained('users');
            $table->foreignId('disahkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disahkan_at')->nullable();
            $table->foreignId('dicabut_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dicabut_at')->nullable();
            $table->text('alasan_cabut')->nullable();
            $table->timestamps();

            $table->index(['status', 'tahun', 'kategori_dokumen_id']);
        });

        Schema::create('tautan_akses', function (Blueprint $table) {
            $table->id();
            $table->string('label', 160);
            $table->string('jenis', 32);
            $table->string('token_hash', 64)->unique();
            $table->string('token_prefix', 12);
            $table->timestamp('kadaluarsa_at')->nullable();
            $table->timestamp('dicabut_at')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->foreignId('dicabut_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('terakhir_diakses_at')->nullable();
            $table->unsignedBigInteger('jumlah_akses')->default(0);
            $table->timestamps();

            $table->index(['jenis', 'dicabut_at']);
        });

        Schema::create('akses_dokumen_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dokumen_arsip_id')->nullable()->constrained('dokumen_arsip')->cascadeOnDelete();
            $table->foreignId('tautan_akses_id')->nullable()->constrained('tautan_akses')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('konteks', 32)->default('arsip');
            $table->string('aksi', 24);
            $table->smallInteger('tahun_filter')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('waktu')->useCurrent();

            $table->index(['dokumen_arsip_id', 'waktu']);
            $table->index(['tautan_akses_id', 'waktu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akses_dokumen_log');
        Schema::dropIfExists('tautan_akses');
        Schema::dropIfExists('dokumen_arsip');
        Schema::dropIfExists('kategori_dokumen');
    }
};
