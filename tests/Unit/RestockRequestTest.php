<?php

namespace Tests\Unit;

use App\Models\RestockRequest;
use PHPUnit\Framework\TestCase;

class RestockRequestTest extends TestCase
{
    // ── totalCost ─────────────────────────────────────────────────────────────

    public function test_total_cost_multiplies_quantity_by_unit_cost(): void
    {
        $rr = new RestockRequest([
            'quantity_requested' => 10,
            'unit_cost'          => 500,
        ]);

        $this->assertEquals(5000.0, $rr->totalCost());
    }

    public function test_total_cost_rounds_to_two_decimal_places(): void
    {
        // unit_cost has a decimal:2 cast, so 333.333 is stored as "333.33".
        // 3 × 333.33 = 999.99 (already 2dp; round() is a no-op here but still required
        // for quantities with 3dp, e.g. 1.005 × 500 = 502.5 → round → 502.50).
        $rr = new RestockRequest([
            'quantity_requested' => 3,
            'unit_cost'          => 333.333,
        ]);

        $this->assertEquals(999.99, $rr->totalCost());
    }

    // ── canBeApproved ─────────────────────────────────────────────────────────

    public function test_can_be_approved_when_pending(): void
    {
        $rr = new RestockRequest(['status' => RestockRequest::STATUS_PENDING]);
        $this->assertTrue($rr->canBeApproved());
    }

    public function test_cannot_be_approved_when_already_approved(): void
    {
        $rr = new RestockRequest(['status' => RestockRequest::STATUS_APPROVED]);
        $this->assertFalse($rr->canBeApproved());
    }

    public function test_cannot_be_approved_when_received(): void
    {
        $rr = new RestockRequest(['status' => RestockRequest::STATUS_RECEIVED]);
        $this->assertFalse($rr->canBeApproved());
    }

    public function test_cannot_be_approved_when_rejected(): void
    {
        $rr = new RestockRequest(['status' => RestockRequest::STATUS_REJECTED]);
        $this->assertFalse($rr->canBeApproved());
    }

    // ── canBeReceived ─────────────────────────────────────────────────────────

    public function test_can_be_received_when_approved(): void
    {
        $rr = new RestockRequest(['status' => RestockRequest::STATUS_APPROVED]);
        $this->assertTrue($rr->canBeReceived());
    }

    public function test_cannot_be_received_when_pending(): void
    {
        $rr = new RestockRequest(['status' => RestockRequest::STATUS_PENDING]);
        $this->assertFalse($rr->canBeReceived());
    }

    public function test_cannot_be_received_when_already_received(): void
    {
        $rr = new RestockRequest(['status' => RestockRequest::STATUS_RECEIVED]);
        $this->assertFalse($rr->canBeReceived());
    }

    // ── canBeRejected ─────────────────────────────────────────────────────────

    public function test_can_be_rejected_when_pending(): void
    {
        $rr = new RestockRequest(['status' => RestockRequest::STATUS_PENDING]);
        $this->assertTrue($rr->canBeRejected());
    }

    public function test_can_be_rejected_when_approved(): void
    {
        $rr = new RestockRequest(['status' => RestockRequest::STATUS_APPROVED]);
        $this->assertTrue($rr->canBeRejected());
    }

    public function test_cannot_be_rejected_when_received(): void
    {
        $rr = new RestockRequest(['status' => RestockRequest::STATUS_RECEIVED]);
        $this->assertFalse($rr->canBeRejected());
    }

    // ── canBeCancelled ────────────────────────────────────────────────────────

    public function test_can_be_cancelled_when_pending(): void
    {
        $rr = new RestockRequest(['status' => RestockRequest::STATUS_PENDING]);
        $this->assertTrue($rr->canBeCancelled());
    }

    public function test_can_be_cancelled_when_approved(): void
    {
        $rr = new RestockRequest(['status' => RestockRequest::STATUS_APPROVED]);
        $this->assertTrue($rr->canBeCancelled());
    }

    public function test_cannot_be_cancelled_when_received(): void
    {
        $rr = new RestockRequest(['status' => RestockRequest::STATUS_RECEIVED]);
        $this->assertFalse($rr->canBeCancelled());
    }

    public function test_cannot_be_cancelled_when_rejected(): void
    {
        $rr = new RestockRequest(['status' => RestockRequest::STATUS_REJECTED]);
        $this->assertFalse($rr->canBeCancelled());
    }

    // ── canBePaid (non-relational short-circuits) ─────────────────────────────

    public function test_cannot_be_paid_when_not_received(): void
    {
        $rr = new RestockRequest(['status' => RestockRequest::STATUS_APPROVED, 'invoice_id' => 1]);
        $this->assertFalse($rr->canBePaid());
    }

    public function test_cannot_be_paid_when_no_invoice(): void
    {
        $rr = new RestockRequest(['status' => RestockRequest::STATUS_RECEIVED, 'invoice_id' => null]);
        $this->assertFalse($rr->canBePaid());
    }
}
