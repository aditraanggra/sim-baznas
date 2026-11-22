<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LoginHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $request->verifikasiKredensial();
        $aktifkan2FaLogin = config('sisfozis.aktifkan_2fa_login', false) === true;

        if ($aktifkan2FaLogin && $user->sudahAktif2Fa()) {
            $request->session()->put([
                '2fa:user:id' => $user->id,
                '2fa:remember' => $request->boolean('remember'),
                '2fa:identitas' => $request->identitas(),
            ]);

            LoginHistory::create([
                'user_id' => $user->id,
                'identitas' => mb_substr($request->identitas(), 0, 255),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'hasil' => 'menunggu_2fa',
                'waktu' => now(),
            ]);

            return redirect()->route('two-factor.challenge');
        }

        Auth::login($user, $request->boolean('remember'));
        $user->forceFill(['last_login_at' => now()])->save();

        LoginHistory::create([
            'user_id' => $user->id,
            'identitas' => mb_substr($request->identitas(), 0, 255),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'hasil' => 'sukses',
            'waktu' => now(),
        ]);

        $request->session()->regenerate();

        if ($aktifkan2FaLogin && $user->wajib2Fa()) {
            $request->session()->put('2fa:wajib_setup', true);

            return redirect()->route('two-factor.setup');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
