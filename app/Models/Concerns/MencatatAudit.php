<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Semua perubahan data bisnis lewat Action yang memanggil catatAudit().
 */
trait MencatatAudit
{
    protected function catatAudit(
        string $aksi,
        string $entitasTipe,
        int $entitasId,
        ?array $nilaiLama = null,
        ?array $nilaiBaru = null,
        ?string $alasan = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'aksi' => $aksi,
            'entitas_tipe' => $entitasTipe,
            'entitas_id' => $entitasId,
            'nilai_lama' => $nilaiLama,
            'nilai_baru' => $nilaiBaru,
            'alasan' => $alasan,
            'ip' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 255),
        ]);
    }
}
