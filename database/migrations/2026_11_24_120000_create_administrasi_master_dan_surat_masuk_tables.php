<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenis_surat', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 24)->unique();
            $table->string('nama', 128);
            $table->string('format_nomor', 128)->nullable();
            $table->boolean('pisah_urutan')->default(false);
            $table->text('keterangan')->nullable();
            $table->boolean('aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('template_surat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jenis_surat_id')->constrained('jenis_surat')->cascadeOnDelete();
            $table->string('nama', 160);
            $table->text('isi');
            $table->text('penutup_default')->nullable();
            $table->boolean('aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['jenis_surat_id', 'aktif']);
        });

        Schema::create('surat_masuk', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('nomor_agenda', 64)->unique();
            $table->string('nomor_surat', 128);
            $table->date('tanggal_surat');
            $table->date('tanggal_terima')->index();
            $table->string('pengirim', 200);
            $table->string('perihal', 255);
            $table->text('ringkasan')->nullable();
            $table->string('sifat', 24)->default('biasa')->index();
            $table->string('status_kode', 32)->default('diterima_arsip')->index();
            $table->foreignId('status_id')->nullable()->constrained('status_dokumen')->nullOnDelete();
            $table->foreignId('berkas_privat_id')->nullable()->constrained('berkas_privat')->nullOnDelete();
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('selesai_at')->nullable();
            $table->text('catatan_penutupan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status_kode', 'tanggal_terima']);
        });

        Schema::create('surat_masuk_status_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_masuk_id')->constrained('surat_masuk')->cascadeOnDelete();
            $table->string('status_kode', 32);
            $table->foreignId('status_id')->nullable()->constrained('status_dokumen')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('waktu');
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['surat_masuk_id', 'waktu']);
        });

        Schema::create('disposisi_surat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_masuk_id')->constrained('surat_masuk')->cascadeOnDelete();
            $table->foreignId('parent_disposisi_id')->nullable()->constrained('disposisi_surat')->nullOnDelete();
            $table->string('tahap', 24)->default('waka');
            $table->unsignedSmallInteger('urutan')->default(1);
            $table->foreignId('dari_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('kepada_user_id')->constrained('users')->restrictOnDelete();
            $table->string('kepada_peran', 48)->nullable();
            $table->text('instruksi');
            $table->date('tenggat')->nullable();
            $table->string('keputusan', 160)->nullable();
            $table->string('status', 24)->default('menunggu');
            $table->text('catatan_tindak_lanjut')->nullable();
            $table->foreignId('berkas_bukti_id')->nullable()->constrained('berkas_privat')->nullOnDelete();
            $table->timestamp('waktu');
            $table->timestamp('selesai_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['surat_masuk_id', 'status']);
            $table->index(['kepada_user_id', 'status']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "CREATE INDEX surat_masuk_fts_idx ON surat_masuk USING GIN (to_tsvector('indonesian', coalesce(nomor_agenda,'') || ' ' || coalesce(nomor_surat,'') || ' ' || coalesce(pengirim,'') || ' ' || coalesce(perihal,'') || ' ' || coalesce(ringkasan,'')))"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS surat_masuk_fts_idx');
        }

        Schema::dropIfExists('disposisi_surat');
        Schema::dropIfExists('surat_masuk_status_log');
        Schema::dropIfExists('surat_masuk');
        Schema::dropIfExists('template_surat');
        Schema::dropIfExists('jenis_surat');
    }
};
