<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'status',
        'upz_id',
        'last_login_at',
        'two_factor_secret',
        'two_factor_confirmed_at',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'upz_id' => 'integer',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
        ];
    }

    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class, 'user_id');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'user_id');
    }

    public function adalahAktif(): bool
    {
        return ($this->status ?? 'aktif') === 'aktif';
    }

    public function wajib2Fa(): bool
    {
        return $this->hasAnyRole(['admin', 'keuangan']);
    }

    public function sudahAktif2Fa(): bool
    {
        return $this->two_factor_confirmed_at !== null && ! empty($this->two_factor_secret);
    }

    public function adalahInternal(): bool
    {
        return $this->getRoleNames()->contains(fn (string $nama) => $nama !== 'upz');
    }

    public function adalahUpzEksternal(): bool
    {
        return $this->hasRole('upz') && ! $this->adalahInternal();
    }

    public function bolehAksiBidang(string $bidang, string $aksi = 'view'): bool
    {
        $matriks = config('sisfozis.matriks_peran', []);

        foreach ($this->getRoleNames() as $peran) {
            $aksiDiizinkan = $matriks[$peran][$bidang] ?? [];
            if (in_array($aksi, $aksiDiizinkan, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function kartuLauncher(): array
    {
        $bidang = collect(config('sisfozis.bidang', []))
            ->sortBy('urutan')
            ->keys()
            ->filter(fn (string $kode) => $this->bolehAksiBidang($kode, 'view'))
            ->values()
            ->all();

        return $bidang;
    }
}
