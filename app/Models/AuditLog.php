<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only: tidak ada update/delete (BR-11, PRD 12).
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'aksi', 'entitas_tipe', 'entitas_id',
        'nilai_lama', 'nilai_baru', 'alasan', 'ip', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'nilai_lama' => 'array',
            'nilai_baru' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('audit_logs bersifat append-only'));
        static::deleting(fn () => throw new \LogicException('audit_logs bersifat append-only'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
