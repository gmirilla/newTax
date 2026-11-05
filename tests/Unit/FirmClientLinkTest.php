<?php

namespace Tests\Unit;

use App\Models\FirmClientLink;
use PHPUnit\Framework\TestCase;

class FirmClientLinkTest extends TestCase
{
    public function test_is_active_is_true_only_for_active_status(): void
    {
        $link = new FirmClientLink(['status' => FirmClientLink::STATUS_ACTIVE]);
        $this->assertTrue($link->isActive());

        $link = new FirmClientLink(['status' => FirmClientLink::STATUS_PENDING_CLIENT]);
        $this->assertFalse($link->isActive());
    }

    public function test_is_revoked_is_true_only_for_revoked_status(): void
    {
        $link = new FirmClientLink(['status' => FirmClientLink::STATUS_REVOKED]);
        $this->assertTrue($link->isRevoked());

        $link = new FirmClientLink(['status' => FirmClientLink::STATUS_ACTIVE]);
        $this->assertFalse($link->isRevoked());
    }

    public function test_pending_approval_tenant_id_maps_pending_client_status_to_client_tenant(): void
    {
        $link = new FirmClientLink([
            'status' => FirmClientLink::STATUS_PENDING_CLIENT,
            'firm_tenant_id' => 1, 'client_tenant_id' => 2,
        ]);

        $this->assertSame(2, $link->pendingApprovalTenantId());
        $this->assertTrue($link->isPendingApprovalBy(2));
        $this->assertFalse($link->isPendingApprovalBy(1));
    }

    public function test_pending_approval_tenant_id_maps_pending_firm_status_to_firm_tenant(): void
    {
        $link = new FirmClientLink([
            'status' => FirmClientLink::STATUS_PENDING_FIRM,
            'firm_tenant_id' => 1, 'client_tenant_id' => 2,
        ]);

        $this->assertSame(1, $link->pendingApprovalTenantId());
        $this->assertTrue($link->isPendingApprovalBy(1));
        $this->assertFalse($link->isPendingApprovalBy(2));
    }

    public function test_pending_approval_tenant_id_is_null_for_active_or_revoked_links(): void
    {
        $link = new FirmClientLink(['status' => FirmClientLink::STATUS_ACTIVE, 'firm_tenant_id' => 1, 'client_tenant_id' => 2]);
        $this->assertNull($link->pendingApprovalTenantId());

        $link = new FirmClientLink(['status' => FirmClientLink::STATUS_REVOKED, 'firm_tenant_id' => 1, 'client_tenant_id' => 2]);
        $this->assertNull($link->pendingApprovalTenantId());
    }
}
