<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('berkas_privat', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('kelompok', 64)->index();
            $table->string('entitas_tipe', 128)->nullable();
            $table->unsignedBigInteger('entitas_id')->nullable();
            $table->string('disk', 32)->default('private');
            $table->string('path', 512);
            $table->string('nama_asli', 255);
            $table->string('mime', 128);
            $table->unsignedBigInteger('ukuran');
            $table->string('checksum_sha256', 64)->nullable();
            $table->string('bidang_kode', 32)->nullable();
            $table->unsignedBigInteger('upz_id')->nullable()->index();
            $table->boolean('izinkan_publik_bertanda_tangan')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['entitas_tipe', 'entitas_id']);
        });

        Schema::create('berkas_unduh_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('berkas_privat_id')->constrained('berkas_privat')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aksi', 24)->default('unduh');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('waktu')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berkas_unduh_log');
        Schema::dropIfExists('berkas_privat');
    }
};
