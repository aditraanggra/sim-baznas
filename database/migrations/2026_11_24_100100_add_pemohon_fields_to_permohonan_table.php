<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permohonan', function (Blueprint $table) {
            $table->boolean('pemohon_sama_mustahik')->default(true)->after('mustahik_id');
            $table->string('pemohon_nama', 160)->nullable()->after('pemohon_sama_mustahik');
            $table->string('pemohon_telepon', 32)->nullable()->after('pemohon_nama');
            $table->string('pemohon_hubungan', 64)->nullable()->after('pemohon_telepon');
            $table->text('pemohon_alamat')->nullable()->after('pemohon_hubungan');
        });
    }

    public function down(): void
    {
        Schema::table('permohonan', function (Blueprint $table) {
            $table->dropColumn([
                'pemohon_sama_mustahik',
                'pemohon_nama',
                'pemohon_telepon',
                'pemohon_hubungan',
                'pemohon_alamat',
            ]);
        });
    }
};
