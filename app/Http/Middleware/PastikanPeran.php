<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PastikanPeran
{
    /** @param  string  ...$peran  daftar peran yang diizinkan */
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        $user = $request->user();

        abort_unless($user && $user->hasAnyRole($peran), 403, 'Anda tidak memiliki akses.');

        return $next($request);
    }
}
