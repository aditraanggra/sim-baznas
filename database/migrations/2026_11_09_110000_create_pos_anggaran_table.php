<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_anggaran', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 32);
            $table->string('tipe_nilai', 16)->default('rupiah');
            $table->string('nama', 255);
            $table->foreignId('induk_id')->nullable()->constrained('pos_anggaran')->nullOnDelete();
            $table->string('path', 255)->index();
            $table->string('lampiran', 2)->index();
            $table->unsignedSmallInteger('tingkat');
            $table->string('agregasi', 16)->default('input_langsung');
            $table->string('sifat_waktu', 8)->default('aliran');
            $table->string('rumus', 255)->nullable();
            $table->unsignedSmallInteger('berlaku_dari_tahun');
            $table->unsignedSmallInteger('berlaku_sampai_tahun')->nullable();
            $table->boolean('aktif')->default(true);
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();

            $table->unique(['kode', 'tipe_nilai']);
            $table->index(['lampiran', 'urutan']);
        });

        Schema::create('pos_alias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_anggaran_id')->constrained('pos_anggaran')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->string('kode_alias', 64);
            $table->string('nama_alias', 255)->nullable();
            $table->timestamps();

            $table->unique(['tahun', 'kode_alias']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_alias');
        Schema::dropIfExists('pos_anggaran');
    }
};
