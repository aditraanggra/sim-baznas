<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PastikanDuaFaktor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('sisfozis.aktifkan_2fa_login', false) !== true) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($request->routeIs('two-factor.*', 'logout', 'berkas.privat.unduh')) {
            return $next($request);
        }

        $wajibTerapkan = $request->session()->get('2fa:wajib_setup') === true
            || config('sisfozis.paksa_2fa_middleware', false) === true;

        if ($wajibTerapkan && $user->wajib2Fa() && ! $user->sudahAktif2Fa()) {
            return redirect()->route('two-factor.setup');
        }

        return $next($request);
    }
}
