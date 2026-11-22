<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('upz', function (Blueprint $table) {
            $table->string('nomor_sk', 128)->nullable()->after('nama');
        });
    }

    public function down(): void
    {
        Schema::table('upz', function (Blueprint $table) {
            $table->dropColumn('nomor_sk');
        });
    }
};
