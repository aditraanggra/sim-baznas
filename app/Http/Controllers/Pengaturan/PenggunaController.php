<?php

namespace App\Http\Controllers\Pengaturan;

use App\Actions\Pengguna\ResetSandiPengguna;
use App\Actions\Pengguna\SimpanPengguna;
use App\Actions\Pengguna\ToggleStatusPengguna;
use App\Http\Controllers\Controller;
use App\Models\User;
use Database\Seeders\PeranSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class PenggunaController extends Controller
{
    public function index(): Response
    {
        $daftarUpz = Schema::hasTable('upz')
            ? DB::table('upz')->whereNull('deleted_at')->where('aktif', true)->orderBy('nama')->get(['id', 'kode', 'nama'])
            : collect();

        return Inertia::render('pengaturan/pengguna', [
            'pengguna' => User::query()
                ->with('roles:id,name')
                ->orderBy('name')
                ->get()
                ->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'username' => $u->username,
                    'email' => $u->email,
                    'status' => $u->status ?? 'aktif',
                    'upz_id' => $u->upz_id,
                    'last_login_at' => $u->last_login_at?->toIso8601String(),
                    'two_factor_aktif' => $u->sudahAktif2Fa(),
                    'peran' => $u->roles->pluck('name')->all(),
                ]),
            'daftar_peran' => collect(PeranSeeder::PERAN_SISFOZIS)->map(fn (string $kode) => [
                'kode' => $kode,
                'nama' => config("sisfozis.peran.{$kode}.nama", $kode),
                'bidang' => config("sisfozis.peran.{$kode}.bidang_utama"),
            ])->values()->all(),
            'daftar_upz' => $daftarUpz->map(fn ($u) => ['id' => $u->id, 'kode' => $u->kode, 'nama' => $u->nama])->values()->all(),
        ]);
    }

    public function simpan(Request $request, SimpanPengguna $action): RedirectResponse
    {
        $action->jalankan($request->all());

        return back()->with('sukses', 'Data pengguna tersimpan.');
    }

    public function toggleStatus(Request $request, ToggleStatusPengguna $action): RedirectResponse
    {
        $action->jalankan($request->all());

        return back()->with('sukses', 'Status akun pengguna diperbarui.');
    }

    public function resetSandi(Request $request, ResetSandiPengguna $action): RedirectResponse
    {
        $action->jalankan($request->all());

        return back()->with('sukses', 'Kata sandi pengguna berhasil diatur ulang.');
    }
}
