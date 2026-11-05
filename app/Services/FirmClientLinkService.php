<?php

namespace App\Services;

use App\Mail\FirmLinkInvitation;
use App\Models\AuditLog;
use App\Models\FirmClientLink;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\FirmLinkRequestedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

class FirmClientLinkService
{
    /** Firm invites an already-independent client by its admin's email. The client must approve. */
    public function requestLinkByFirm(Tenant $firm, string $clientAdminEmail, User $actingFirmUser): FirmClientLink
    {
        $clientAdmin = User::where('email', $clientAdminEmail)->where('role', User::ROLE_ADMIN)->first();

        if (!$clientAdmin || !$clientAdmin->tenant_id) {
            throw new RuntimeException('No company admin was found with that email address.');
        }

        $this->guardCanLink($firm->id, $clientAdmin->tenant_id);

        $link = FirmClientLink::create([
            'firm_tenant_id'        => $firm->id,
            'client_tenant_id'      => $clientAdmin->tenant_id,
            'status'                => FirmClientLink::STATUS_PENDING_CLIENT,
            'requested_by_user_id'  => $actingFirmUser->id,
            'token'                 => Str::random(64),
        ]);

        try {
            Mail::to($clientAdmin->email)->send(new FirmLinkInvitation($link, $clientAdmin->email));
        } catch (\Throwable) {
            $link->delete();
            throw new RuntimeException('Failed to send the invitation email. Please try again.');
        }

        $this->audit('partner.link_requested', $firm->id, $actingFirmUser, $link, [
            'direction' => 'firm_to_client', 'client_admin_email' => $clientAdmin->email,
        ]);

        return $link;
    }

    /** Client requests an existing firm (identified by its partner code) to manage their books. The firm must approve. */
    public function requestLinkByClient(Tenant $client, string $partnerCode, User $actingClientUser): FirmClientLink
    {
        $firm = Tenant::where('partner_code', strtoupper(trim($partnerCode)))->first();

        if (!$firm) {
            throw new RuntimeException('No partner firm was found with that code.');
        }

        $this->guardCanLink($firm->id, $client->id);

        $link = FirmClientLink::create([
            'firm_tenant_id'       => $firm->id,
            'client_tenant_id'     => $client->id,
            'status'               => FirmClientLink::STATUS_PENDING_FIRM,
            'requested_by_user_id' => $actingClientUser->id,
        ]);

        $firmAdmins = User::where('tenant_id', $firm->id)
            ->where('role', User::ROLE_ADMIN)
            ->where('is_active', true)
            ->get();

        foreach ($firmAdmins as $admin) {
            $admin->notify(new FirmLinkRequestedNotification($link));
        }

        $this->audit('partner.link_requested', $client->id, $actingClientUser, $link, [
            'direction' => 'client_to_firm', 'firm_tenant_id' => $firm->id,
        ]);

        return $link;
    }

    /** Approve a pending link from whichever side it's currently awaiting. Activates it and provisions the proxy user. */
    public function approve(FirmClientLink $link, User $approver): FirmClientLink
    {
        if (!in_array($link->status, [FirmClientLink::STATUS_PENDING_CLIENT, FirmClientLink::STATUS_PENDING_FIRM], true)) {
            throw new RuntimeException('This request is no longer pending.');
        }

        DB::transaction(function () use ($link, $approver) {
            $link->update([
                'status'              => FirmClientLink::STATUS_ACTIVE,
                'approved_by_user_id' => $approver->id,
                'approved_at'         => now(),
            ]);

            $this->provisionProxyUser($link);
        });

        $this->audit('partner.link_approved', $link->client_tenant_id, $approver, $link, [
            'firm_tenant_id' => $link->firm_tenant_id,
        ]);

        return $link->refresh();
    }

    /** Create the one shared, non-human "proxy" user the firm impersonates into to access this client. */
    public function provisionProxyUser(FirmClientLink $link): User
    {
        $proxy = User::create([
            'tenant_id'         => $link->client_tenant_id,
            'name'              => "{$link->firmTenant->name} (Partner Access)",
            'email'             => "partner-link-{$link->id}@partner.accounttaxng.internal",
            'password'          => Hash::make(Str::random(40)),
            'role'              => User::ROLE_ACCOUNTANT,
            'is_active'         => true,
            'is_partner_proxy'  => true,
            'email_verified_at' => now(),
        ]);

        $link->update(['proxy_user_id' => $proxy->id]);

        return $proxy;
    }

    /** Either side's admin can unilaterally end an active (or pending) link. */
    public function revoke(FirmClientLink $link, User $revoker): void
    {
        DB::transaction(function () use ($link, $revoker) {
            $link->update([
                'status'             => FirmClientLink::STATUS_REVOKED,
                'revoked_by_user_id' => $revoker->id,
                'revoked_at'         => now(),
            ]);

            $link->proxyUser?->update(['is_active' => false]);
        });

        $link->clientTenant->invalidateLimitCache('users');

        $this->audit('partner.link_revoked', $link->client_tenant_id, $revoker, $link, [
            'firm_tenant_id' => $link->firm_tenant_id,
        ]);
    }

    /** Token-entry counterpart to approve(), for the firm-to-client email-invite flow. */
    public function acceptInviteToken(string $token, User $acceptingAdmin): FirmClientLink
    {
        $link = FirmClientLink::where('token', $token)
            ->where('status', FirmClientLink::STATUS_PENDING_CLIENT)
            ->first();

        if (!$link) {
            throw new RuntimeException('This invitation is invalid or has already been handled.');
        }

        if ($acceptingAdmin->tenant_id !== $link->client_tenant_id || !$acceptingAdmin->isAdmin()) {
            throw new RuntimeException('You must be an admin of the invited company to approve this request.');
        }

        return $this->approve($link, $acceptingAdmin);
    }

    /** Firm staff member switches into the client's workspace as the shared proxy user. */
    public function enterClientWorkspace(FirmClientLink $link, User $firmUser): void
    {
        AuditLog::create([
            'tenant_id'      => $link->client_tenant_id,
            'user_id'        => $firmUser->id,
            'event'          => 'partner.workspace_entered',
            'auditable_type' => FirmClientLink::class,
            'auditable_id'   => $link->id,
            'old_values'     => [],
            'new_values'     => ['firm_tenant_id' => $link->firm_tenant_id],
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'url'            => request()->fullUrl(),
            'tags'           => 'partner',
        ]);

        session(['partner_origin_user_id' => $firmUser->id, 'partner_link_id' => $link->id]);
        auth()->login($link->proxyUser);
    }

    /** Restore the firm user's own identity after a client-workspace session. */
    public function exitClientWorkspace(): void
    {
        $originId = session()->pull('partner_origin_user_id');
        $linkId   = session()->pull('partner_link_id');

        if (!$originId) {
            return;
        }

        $currentTenant = auth()->user()?->tenant;

        if ($currentTenant) {
            AuditLog::create([
                'tenant_id'      => $currentTenant->id,
                'user_id'        => $originId,
                'event'          => 'partner.workspace_exited',
                'auditable_type' => FirmClientLink::class,
                'auditable_id'   => $linkId,
                'old_values'     => [],
                'new_values'     => ['client_tenant' => $currentTenant->name],
                'ip_address'     => request()->ip(),
                'user_agent'     => request()->userAgent(),
                'url'            => request()->fullUrl(),
                'tags'           => 'partner',
            ]);
        }

        auth()->loginUsingId($originId);
    }

    /** App-layer guard against duplicate/self links — DB enforces this on Postgres, this covers SQLite test envs too. */
    private function guardCanLink(int $firmTenantId, int $clientTenantId): void
    {
        if ($firmTenantId === $clientTenantId) {
            throw new RuntimeException('A company cannot be linked to itself as a partner firm.');
        }

        $exists = FirmClientLink::where('firm_tenant_id', $firmTenantId)
            ->where('client_tenant_id', $clientTenantId)
            ->where('status', '!=', FirmClientLink::STATUS_REVOKED)
            ->exists();

        if ($exists) {
            throw new RuntimeException('A link already exists between these two companies.');
        }
    }

    private function audit(string $event, int $tenantId, User $actor, FirmClientLink $link, array $newValues): void
    {
        AuditLog::create([
            'tenant_id'      => $tenantId,
            'user_id'        => $actor->id,
            'event'          => $event,
            'auditable_type' => FirmClientLink::class,
            'auditable_id'   => $link->id,
            'old_values'     => [],
            'new_values'     => $newValues,
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'url'            => request()->fullUrl(),
            'tags'           => 'partner',
        ]);
    }
}
