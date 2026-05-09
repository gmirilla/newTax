<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class TenantFirsCredential extends Model
{
    protected $table = 'tenant_firs_credentials';

    protected $fillable = [
        'tenant_id',
        'service_id',
        'api_key',
        'secret_key',
        'public_key',
        'certificate',
        'is_active',
        'credentials_set_at',
    ];

    /**
     * Prevent sensitive fields from appearing in logs, error reports, or JSON serialisation.
     */
    protected $hidden = [
        'service_id',
        'api_key',
        'secret_key',
        'public_key',
        'certificate',
    ];

    /**
     * All credential fields use Laravel's built-in encrypted cast.
     * Encryption is applied transparently on write; decryption on read.
     * APP_KEY is used as the encryption key — never store raw values.
     */
    protected $casts = [
        'service_id'         => 'encrypted',
        'api_key'            => 'encrypted',
        'secret_key'         => 'encrypted',
        'public_key'         => 'encrypted',
        'certificate'        => 'encrypted',
        'is_active'          => 'boolean',
        'credentials_set_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /**
     * Scope: only active credential sets.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // ── Static helpers ────────────────────────────────────────────────────────

    /**
     * Retrieve the active credential set for a tenant.
     * Throws if none exist so callers never operate with null credentials.
     *
     * @throws RuntimeException
     */
    public static function forTenant(int $tenantId): static
    {
        $credential = static::where('tenant_id', $tenantId)
            ->active()
            ->first();

        throw_unless(
            $credential,
            RuntimeException::class,
            "No active NRS credentials found for tenant {$tenantId}. "
            . 'Please configure your NRS credentials under Settings → FIRS.'
        );

        return $credential;
    }
}
