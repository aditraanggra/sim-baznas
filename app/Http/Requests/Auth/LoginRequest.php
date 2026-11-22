<?php

namespace App\Http\Requests\Auth;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $identitas = trim((string) ($this->input('identitas') ?: $this->input('email')));

        $this->merge([
            'identitas' => $identitas,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'identitas' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    public function identitas(): string
    {
        return trim((string) ($this->input('identitas') ?: $this->input('email')));
    }

    /**
     * Memverifikasi kredensial tanpa membocorkan apakah akun ada atau tidak.
     *
     * @throws ValidationException
     */
    public function verifikasiKredensial(): User
    {
        $this->ensureIsNotRateLimited();

        $identitas = $this->identitas();
        $identitasLower = Str::lower($identitas);

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$identitasLower])
            ->orWhereRaw('LOWER(username) = ?', [$identitasLower])
            ->first();

        if (! $user || ! Hash::check((string) $this->input('password'), $user->password)) {
            RateLimiter::hit($this->throttleKey());
            $this->catatRiwayat($user?->id, $identitas, 'gagal_kredensial');

            throw ValidationException::withMessages([
                'identitas' => __('auth.failed'),
                'email' => __('auth.failed'),
            ]);
        }

        if (! $user->adalahAktif()) {
            RateLimiter::hit($this->throttleKey());
            $this->catatRiwayat($user->id, $identitas, 'gagal_nonaktif');

            // Pesan disamakan agar tidak membocorkan status atau keberadaan akun.
            throw ValidationException::withMessages([
                'identitas' => __('auth.failed'),
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        return $user;
    }

    /**
     * Kompatibilitas mundur jika dipanggil langsung.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $user = $this->verifikasiKredensial();
        Auth::login($user, $this->boolean('remember'));
        $user->forceFill(['last_login_at' => now()])->save();
        $this->catatRiwayat($user->id, $this->identitas(), 'sukses');
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $this->catatRiwayat(null, $this->identitas(), 'terkunci');

        $seconds = RateLimiter::availableIn($this->throttleKey());

        $pesan = __('auth.throttle', [
            'seconds' => $seconds,
            'minutes' => ceil($seconds / 60),
        ]);

        throw ValidationException::withMessages([
            'identitas' => $pesan,
            'email' => $pesan,
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->identitas())).'|'.$this->ip();
    }

    private function catatRiwayat(?int $userId, string $identitas, string $hasil): void
    {
        LoginHistory::create([
            'user_id' => $userId,
            'identitas' => mb_substr($identitas, 0, 255),
            'ip_address' => $this->ip(),
            'user_agent' => substr((string) $this->userAgent(), 0, 255),
            'hasil' => $hasil,
            'waktu' => now(),
        ]);
    }
}
