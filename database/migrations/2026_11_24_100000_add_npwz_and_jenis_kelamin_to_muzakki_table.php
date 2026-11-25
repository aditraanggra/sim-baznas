<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('muzakki', function (Blueprint $table) {
            $table->string('npwz', 32)->nullable()->unique()->after('kode');
            $table->string('jenis_kelamin', 16)->nullable()->after('nama');
        });
    }

    public function down(): void
    {
        Schema::table('muzakki', function (Blueprint $table) {
            $table->dropUnique(['npwz']);
            $table->dropColumn(['npwz', 'jenis_kelamin']);
        });
    }
};
