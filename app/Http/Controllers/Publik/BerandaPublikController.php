<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class BerandaPublikController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('publik/index', [
            'lembaga' => config('sisfozis.kop_surat'),
        ]);
    }
}
