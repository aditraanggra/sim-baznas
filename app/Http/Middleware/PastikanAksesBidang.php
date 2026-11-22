<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PastikanAksesBidang
{
    public function handle(Request $request, Closure $next, string $bidang, string $aksi = 'view'): Response
    {
        $user = $request->user();

        abort_unless(
            $user && $user->bolehAksiBidang($bidang, $aksi),
            403,
            'Anda tidak memiliki akses ke bidang ini.',
        );

        return $next($request);
    }
}
