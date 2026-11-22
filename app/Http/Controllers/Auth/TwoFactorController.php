<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Pengguna\KelolaDuaFaktor;
use App\Http\Controllers\Controller;
use App\Models\LoginHistory;
use App\Models\User;
use App\Services\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorController extends Controller
{
    public function tampilChallenge(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has('2fa:user:id')) {
            return redirect()->route('login');
        }

        return Inertia::render('auth/two-factor-challenge');
    }

    public function verifikasiChallenge(Request $request, TotpService $totp): RedirectResponse
    {
        $userId = $request->session()->get('2fa:user:id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (! $user || ! $user->adalahAktif() || ! $user->sudahAktif2Fa()) {
            $request->session()->forget(['2fa:user:id', '2fa:remember', '2fa:identitas']);

            return redirect()->route('login');
        }

        $validated = $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $identitas = (string) $request->session()->get('2fa:identitas', $user->email);
        $valid = false;

        if (! empty($validated['code'])) {
            $valid = $totp->verifikasi((string) $user->two_factor_secret, (string) $validated['code']);
        } elseif (! empty($validated['recovery_code'])) {
            $kodeMasuk = strtoupper(trim((string) $validated['recovery_code']));
            $daftarKode = $user->two_factor_recovery_codes ?? [];
            $indeks = array_search($kodeMasuk, $daftarKode, true);

            if ($indeks !== false) {
                $valid = true;
                unset($daftarKode[$indeks]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($daftarKode)])->save();
            }
        }

        if (! $valid) {
            LoginHistory::create([
                'user_id' => $user->id,
                'identitas' => mb_substr($identitas, 0, 255),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'hasil' => 'gagal_2fa',
                'waktu' => now(),
            ]);

            throw ValidationException::withMessages([
                'code' => 'Kode autentikator atau kode pemulihan tidak valid.',
            ]);
        }

        $remember = (bool) $request->session()->pull('2fa:remember', false);
        $request->session()->forget(['2fa:user:id', '2fa:identitas', '2fa:wajib_setup']);

        Auth::login($user, $remember);
        $user->forceFill(['last_login_at' => now()])->save();

        LoginHistory::create([
            'user_id' => $user->id,
            'identitas' => mb_substr($identitas, 0, 255),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'hasil' => 'sukses',
            'waktu' => now(),
        ]);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function tampilSetup(Request $request, TotpService $totp): Response
    {
        /** @var User $user */
        $user = $request->user();
        $aktif = $user->sudahAktif2Fa();

        $secret = $aktif
            ? (string) $user->two_factor_secret
            : (string) ($request->session()->get('2fa:pending_secret') ?? tap($totp->buatSecret(), fn (string $s) => $request->session()->put('2fa:pending_secret', $s)));

        return Inertia::render('settings/two-factor', [
            'aktif' => $aktif,
            'wajib' => $user->wajib2Fa(),
            'secret' => $aktif ? null : $secret,
            'qr_data_uri' => $aktif ? null : $totp->qrDataUri($user->username ?: $user->email, $secret),
            'recovery_codes' => $aktif ? ($user->two_factor_recovery_codes ?? []) : [],
        ]);
    }

    public function aktifkan(Request $request, KelolaDuaFaktor $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $secret = (string) ($request->input('secret') ?: $request->session()->get('2fa:pending_secret'));

        $action->jalankan([
            'user_id' => $user->id,
            'aksi' => 'aktifkan',
            'secret' => $secret,
            'code' => $request->input('code'),
        ]);

        $request->session()->forget(['2fa:pending_secret', '2fa:wajib_setup']);

        return redirect()->route('two-factor.setup')->with('sukses', 'Autentikasi dua faktor (2FA) berhasil diaktifkan.');
    }

    public function nonaktifkan(Request $request, KelolaDuaFaktor $action): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $action->jalankan([
            'user_id' => $user->id,
            'aksi' => 'nonaktifkan',
        ]);

        return back()->with('sukses', 'Autentikasi dua faktor dinonaktifkan.');
    }
}
