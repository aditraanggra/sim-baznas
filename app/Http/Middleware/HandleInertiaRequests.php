<?php

namespace App\Http\Middleware;

use App\Models\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            ...parent::share($request),
            'name' => config('app.name'),
            'tahun_aktif' => (int) $request->session()->get('tahun_aktif', date('Y')),
            'bidang_daftar' => array_values(config('sisfozis.bidang', [])),
            'auth' => [
                'user' => $user ? [
                    ...$user->only(['id', 'name', 'username', 'email', 'upz_id', 'status']),
                    'peran' => $user->getRoleNames()->all(),
                    'peran_label' => $user->getRoleNames()
                        ->map(fn (string $kode) => config("sisfozis.peran.{$kode}.nama", $kode))
                        ->values()
                        ->all(),
                    'kartu_launcher' => $user->kartuLauncher(),
                    'admin_sistem' => $user->hasRole('admin'),
                    'wajib_2fa' => $user->wajib2Fa(),
                    'two_factor_aktif' => $user->sudahAktif2Fa(),
                ] : null,
            ],
            'notifikasi' => fn () => $user && Schema::hasTable('notifikasi') ? [
                'belum_dibaca' => Notifikasi::query()->where('user_id', $user->id)->whereNull('dibaca_at')->count(),
                'daftar' => Notifikasi::query()
                    ->where('user_id', $user->id)
                    ->latest('id')
                    ->limit(10)
                    ->get()
                    ->map(fn (Notifikasi $n) => [
                        'id' => $n->id,
                        'jenis' => $n->jenis,
                        'judul' => $n->judul,
                        'pesan' => $n->pesan,
                        'tautan' => $n->tautan,
                        'dibaca' => $n->dibaca_at !== null,
                        'waktu' => $n->created_at?->diffForHumans(),
                    ])
                    ->all(),
            ] : ['belum_dibaca' => 0, 'daftar' => []],
            'flash' => [
                'sukses' => fn () => $request->session()->get('sukses'),
                'peringatan' => fn () => $request->session()->get('peringatan'),
            ],
        ]);
    }
}
