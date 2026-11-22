<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 64)->nullable()->unique()->after('name');
            $table->string('status', 16)->default('aktif')->index()->after('password');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->text('two_factor_secret')->nullable()->after('last_login_at');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_secret');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_confirmed_at');
        });

        // Isi username awal secara deterministik untuk akun yang sudah ada sebelum migrasi.
        DB::table('users')->whereNull('username')->orderBy('id')->chunkById(100, function ($users): void {
            foreach ($users as $user) {
                $prefix = strtolower(preg_replace('/[^a-zA-Z0-9_.]/', '', strstr((string) $user->email, '@', true) ?: 'user') ?? 'user');
                $prefix = substr($prefix !== '' ? $prefix : 'user', 0, 48);
                $kandidat = $prefix;
                if (DB::table('users')->where('username', $kandidat)->where('id', '!=', $user->id)->exists()) {
                    $kandidat = "{$prefix}_{$user->id}";
                }
                DB::table('users')->where('id', $user->id)->update(['username' => $kandidat]);
            }
        });

        Schema::create('login_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('identitas', 255);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('hasil', 32)->index();
            $table->timestamp('waktu')->useCurrent()->index();
        });

        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('jenis', 64);
            $table->string('judul', 160);
            $table->text('pesan');
            $table->string('tautan', 255)->nullable();
            $table->string('ref_tabel', 64)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->timestamp('dibaca_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'dibaca_at']);
            $table->index(['jenis', 'ref_tabel', 'ref_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
        Schema::dropIfExists('login_history');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropUnique(['username']);
            $table->dropColumn([
                'username',
                'status',
                'last_login_at',
                'two_factor_secret',
                'two_factor_confirmed_at',
                'two_factor_recovery_codes',
            ]);
        });
    }
};
