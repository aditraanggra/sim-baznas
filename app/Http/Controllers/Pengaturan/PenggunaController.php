<?php

namespace App\Http\Controllers\Pengaturan;

use App\Actions\Pengguna\SimpanPengguna;
use App\Http\Controllers\Controller;
use App\Models\User;
use Database\Seeders\PeranSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PenggunaController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('pengaturan/pengguna', [
            'pengguna' => User::query()
                ->with('roles:id,name')
                ->orderBy('name')
                ->get()
                ->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'peran' => $u->roles->pluck('name')->all(),
                ]),
            'daftar_peran' => PeranSeeder::PERAN,
        ]);
    }

    public function simpan(Request $request, SimpanPengguna $action): RedirectResponse
    {
        $action->jalankan($request->all());

        return back()->with('sukses', 'Pengguna tersimpan.');
    }
}
