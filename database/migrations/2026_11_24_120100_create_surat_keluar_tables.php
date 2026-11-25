<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_keluar', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('nomor', 64)->nullable()->unique();
            $table->foreignId('jenis_surat_id')->constrained('jenis_surat')->restrictOnDelete();
            $table->foreignId('template_surat_id')->nullable()->constrained('template_surat')->nullOnDelete();
            $table->foreignId('surat_masuk_id')->nullable()->constrained('surat_masuk')->nullOnDelete();
            $table->date('tanggal')->index();
            $table->string('sifat', 24)->default('biasa')->index();
            $table->string('lampiran', 128)->nullable();
            $table->string('tujuan', 255);
            $table->text('alamat_tujuan')->nullable();
            $table->string('perihal', 255);
            $table->text('isi');
            $table->jsonb('data_placeholder')->nullable();
            $table->text('tembusan')->nullable();
            $table->string('nama_penandatangan', 160);
            $table->string('jabatan_penandatangan', 160);
            $table->string('status_kode', 32)->default('draft')->index();
            $table->foreignId('status_id')->nullable()->constrained('status_dokumen')->nullOnDelete();
            $table->foreignId('berkas_lampiran_id')->nullable()->constrained('berkas_privat')->nullOnDelete();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diajukan_at')->nullable();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disetujui_at')->nullable();
            $table->text('catatan_revisi')->nullable();
            $table->foreignId('dibatalkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dibatalkan_at')->nullable();
            $table->text('alasan_batal')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['jenis_surat_id', 'status_kode']);
            $table->index(['status_kode', 'tanggal']);
        });

        Schema::create('surat_keluar_cetak_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_keluar_id')->constrained('surat_keluar')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('waktu')->useCurrent();
            $table->string('ip_address', 45)->nullable();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "CREATE INDEX surat_keluar_fts_idx ON surat_keluar USING GIN (to_tsvector('indonesian', coalesce(nomor,'') || ' ' || coalesce(tujuan,'') || ' ' || coalesce(perihal,'') || ' ' || coalesce(isi,'')))"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS surat_keluar_fts_idx');
        }

        Schema::dropIfExists('surat_keluar_cetak_log');
        Schema::dropIfExists('surat_keluar');
    }
};
