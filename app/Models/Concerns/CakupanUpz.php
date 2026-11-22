<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait CakupanUpz
{
    public function scopeUntukPengguna(Builder $query, ?User $user = null, string $kolomUpz = 'upz_id'): Builder
    {
        $user ??= Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->adalahUpzEksternal()) {
            if ($user->upz_id === null) {
                return $query->whereRaw('1 = 0');
            }

            return $query->where($query->qualifyColumn($kolomUpz), $user->upz_id);
        }

        return $query;
    }
}
