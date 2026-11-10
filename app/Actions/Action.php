<?php

namespace App\Actions;

/**
 * Action = satu use case yang mengubah data dan mencatat audit.
 * Controller tidak menulis langsung ke model bisnis.
 */
abstract class Action
{
    abstract public function jalankan(array $data): mixed;
}
