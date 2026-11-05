<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FirmClientLink extends Model
{
    public const STATUS_PENDING_CLIENT = 'pending_client_approval';
    public const STATUS_PENDING_FIRM   = 'pending_firm_approval';
    public const STATUS_ACTIVE         = 'active';
    public const STATUS_REVOKED        = 'revoked';

    protected $fillable = [
        'firm_tenant_id',
        'client_tenant_id',
        'status',
        'proxy_user_id',
        'requested_by_user_id',
        'approved_by_user_id',
        'approved_at',
        'revoked_by_user_id',
        'revoked_at',
        'token',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'revoked_at'  => 'datetime',
    ];

    public function firmTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'firm_tenant_id');
    }

    public function clientTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'client_tenant_id');
    }

    public function proxyUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proxy_user_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by_user_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isRevoked(): bool
    {
        return $this->status === self::STATUS_REVOKED;
    }

    /** Which tenant must act next to move this link out of a pending state. */
    public function pendingApprovalTenantId(): ?int
    {
        return match ($this->status) {
            self::STATUS_PENDING_CLIENT => $this->client_tenant_id,
            self::STATUS_PENDING_FIRM   => $this->firm_tenant_id,
            default                     => null,
        };
    }

    public function isPendingApprovalBy(int $tenantId): bool
    {
        return $this->pendingApprovalTenantId() === $tenantId;
    }
}
