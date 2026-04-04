<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable audit trail of every FIRS API call.
 * Never update or soft-delete records — only append.
 */
class FirsApiLog extends Model
{
    protected $table = 'firs_api_logs';

    // Immutable — disable updated_at
    public const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'endpoint',
        'request_payload',
        'response_status',
        'response_body',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_body'   => 'array',
        'response_status' => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    // ── Factory helper ────────────────────────────────────────────────────────

    public static function record(
        int $tenantId,
        ?int $invoiceId,
        string $endpoint,
        ?array $requestPayload,
        ?int $responseStatus,
        ?array $responseBody,
    ): self {
        return self::create([
            'tenant_id'        => $tenantId,
            'invoice_id'       => $invoiceId,
            'endpoint'         => $endpoint,
            'request_payload'  => $requestPayload,
            'response_status'  => $responseStatus,
            'response_body'    => $responseBody,
        ]);
    }
}
