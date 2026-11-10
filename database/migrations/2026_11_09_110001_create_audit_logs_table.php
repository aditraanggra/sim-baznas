<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aksi', 32);
            $table->string('entitas_tipe', 64)->index();
            $table->unsignedBigInteger('entitas_id')->index();
            $table->jsonb('nilai_lama')->nullable();
            $table->jsonb('nilai_baru')->nullable();
            $table->text('alasan')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('import_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('jenis', 16);
            $table->string('nama_berkas', 255);
            $table->string('path', 255);
            $table->string('status', 16)->default('menunggu')->index();
            $table->unsignedInteger('total_baris')->default(0);
            $table->unsignedInteger('baris_sukses')->default(0);
            $table->unsignedInteger('baris_gagal')->default(0);
            $table->jsonb('ringkasan')->nullable();
            $table->timestamp('mulai_pada')->nullable();
            $table->timestamp('selesai_pada')->nullable();
            $table->timestamps();
        });

        Schema::create('import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_job_id')->nullable()->constrained('import_jobs')->cascadeOnDelete();
            $table->string('jenis', 16)->index();
            $table->unsignedInteger('nomor_baris');
            $table->string('kunci', 128)->nullable()->index();
            $table->string('status', 16)->default('valid');
            $table->jsonb('galat')->nullable();
            $table->jsonb('data')->nullable();
            $table->timestamps();

            // Kunci alami: impor ulang menimpa baris yang sama (idempoten).
            $table->unique(['jenis', 'kunci']);
            $table->unique(['import_job_id', 'nomor_baris']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_rows');
        Schema::dropIfExists('import_jobs');
        Schema::dropIfExists('audit_logs');
    }
};
