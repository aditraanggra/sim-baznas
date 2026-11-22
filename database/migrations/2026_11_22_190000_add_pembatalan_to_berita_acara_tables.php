<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABEL = ['berita_acara_penerimaan', 'berita_acara_penyaluran'];

    public function up(): void
    {
        foreach (self::TABEL as $nama) {
            Schema::table($nama, function (Blueprint $table) {
                $table->text('alasan_batal')->nullable();
                $table->foreignId('dibatalkan_oleh')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('dibatalkan_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABEL as $nama) {
            Schema::table($nama, function (Blueprint $table) use ($nama) {
                $table->dropForeign($nama.'_dibatalkan_oleh_foreign');
                $table->dropColumn(['alasan_batal', 'dibatalkan_oleh', 'dibatalkan_at']);
            });
        }
    }
};
