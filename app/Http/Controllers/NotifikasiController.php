<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use App\Services\NotifikasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    public function tandaiDibaca(Request $request, Notifikasi $notifikasi, NotifikasiService $service): RedirectResponse
    {
        abort_unless((int) $notifikasi->user_id === (int) $request->user()->id, 403);

        $service->tandaiDibaca($notifikasi, $request->user());

        if ($notifikasi->tautan) {
            return redirect($notifikasi->tautan);
        }

        return back();
    }

    public function tandaiSemuaDibaca(Request $request, NotifikasiService $service): RedirectResponse
    {
        $service->tandaiSemuaDibaca($request->user());

        return back()->with('sukses', 'Semua notifikasi ditandai telah dibaca.');
    }
}
