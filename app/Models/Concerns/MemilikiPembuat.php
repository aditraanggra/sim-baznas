<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait MemilikiPembuat
{
    protected static function bootMemilikiPembuat(): void
    {
        static::creating(function (Model $model): void {
            if ($model->getAttribute('created_by') === null && Auth::id() !== null) {
                $model->setAttribute('created_by', Auth::id());
            }
        });
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
