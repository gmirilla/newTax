<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceFirsSubmission extends Model
{
    protected $table = 'invoice_firs_submissions';

    protected $fillable = [
        'invoice_id',
        'tenant_id',
        'irn',
        'csid',
        'qr_code_data',
        'status',
        'firs_response',
        'attempts',
        'last_attempted_at',
    ];

    protected $casts = [
        'firs_response'    => 'array',
        'attempts'         => 'integer',
        'last_attempted_at' => 'datetime',
    ];

    /**
     * Valid forward-only status transitions.
     * A status may only move to a value that appears after it in this map.
     */
    public const TRANSITIONS = [
        'pending'    => ['validating', 'failed'],
        'validating' => ['validated', 'failed'],
        'validated'  => ['signing', 'failed'],
        'signing'    => ['signed', 'failed'],
        'signed'     => [],
        'failed'     => ['pending'], // allow retry from failed
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::TRANSITIONS[$this->status] ?? [], true);
    }

    public function transitionTo(string $newStatus): void
    {
        if (! $this->canTransitionTo($newStatus)) {
            throw new \RuntimeException(
                "Invalid NRS submission status transition: {$this->status} → {$newStatus}"
            );
        }

        $this->update([
            'status'           => $newStatus,
            'last_attempted_at' => now(),
        ]);
    }

    public function incrementAttempts(): void
    {
        $this->increment('attempts');
        $this->update(['last_attempted_at' => now()]);
    }
}
