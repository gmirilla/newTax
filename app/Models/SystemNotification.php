<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemNotification extends Model
{
    protected $fillable = [
        'title', 'message', 'type', 'target_type', 'target_ids',
        'status', 'send_at', 'expires_at', 'created_by',
    ];

    protected $casts = [
        'target_ids' => 'array',
        'send_at'    => 'datetime',
        'expires_at' => 'datetime',
    ];

    const TYPE_INFO     = 'info';
    const TYPE_WARNING  = 'warning';
    const TYPE_CRITICAL = 'critical';
    const TYPE_SUCCESS  = 'success';

    const TARGET_ALL      = 'all';
    const TARGET_PLAN     = 'plan';
    const TARGET_SPECIFIC = 'specific';

    const STATUS_DRAFT = 'draft';
    const STATUS_SENT  = 'sent';

    const TYPE_COLORS = [
        'info'     => ['bg' => 'blue',   'icon' => 'ℹ️'],
        'warning'  => ['bg' => 'amber',  'icon' => '⚠️'],
        'critical' => ['bg' => 'red',    'icon' => '🚨'],
        'success'  => ['bg' => 'green',  'icon' => '✅'],
    ];

    public function reads(): HasMany
    {
        return $this->hasMany(SystemNotificationRead::class, 'notification_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Active = sent and not expired. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SENT)
            ->where(fn($q) => $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()));
    }

    /** All active notifications visible to the given tenant/user (unread). */
    public static function forUser(int $userId, int $tenantId, ?int $planId): \Illuminate\Database\Eloquent\Collection
    {
        $readIds = SystemNotificationRead::where('user_id', $userId)
            ->pluck('notification_id');

        return static::active()
            ->whereNotIn('id', $readIds)
            ->where(function ($q) use ($tenantId, $planId) {
                $q->where('target_type', self::TARGET_ALL);

                if ($planId) {
                    $q->orWhere(fn($q2) => $q2
                        ->where('target_type', self::TARGET_PLAN)
                        ->whereJsonContains('target_ids', $planId));
                }

                $q->orWhere(fn($q2) => $q2
                    ->where('target_type', self::TARGET_SPECIFIC)
                    ->whereJsonContains('target_ids', $tenantId));
            })
            ->orderByDesc('send_at')
            ->get();
    }

    public function typeColor(): string
    {
        return self::TYPE_COLORS[$this->type]['bg'] ?? 'blue';
    }

    public function readCount(): int
    {
        return $this->reads()->count();
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
