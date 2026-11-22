<?php

namespace App\Actions;

use Illuminate\Support\Facades\Auth;

/**
 * Action = satu use case yang mengubah data dan mencatat audit.
 * Controller tidak menulis langsung ke model bisnis.
 */
abstract class Action
{
    abstract public function jalankan(array $data): mixed;

    protected function denganPembuat(array $atribut, string $kolom = 'created_by'): array
    {
        $atribut[$kolom] ??= Auth::id();

        return $atribut;
    }
}
