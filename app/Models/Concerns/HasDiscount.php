<?php

namespace App\Models\Concerns;

use App\Support\DocumentTotals;

/**
 * Shared by Invoice and Quote. Expects discount_type, discount_value and
 * discount_amount columns; discount_amount is always the computed ₦ value.
 */
trait HasDiscount
{
    /** Totals for the given line figures using this document's discount settings. */
    protected function computeTotals(float $subtotal, float $lineVat): array
    {
        return DocumentTotals::calculate(
            $subtotal,
            $lineVat,
            (bool) $this->vat_applicable,
            (bool) $this->wht_applicable,
            (float) $this->wht_rate,
            $this->discount_type,
            (float) $this->discount_value,
            (float) $this->discount_amount,
        );
    }

    public function hasDiscount(): bool
    {
        return (float) $this->discount_amount > 0;
    }

    /** "Discount" or "Discount (10%)" for display. */
    public function discountLabel(): string
    {
        return DocumentTotals::label($this->discount_type, $this->discount_value);
    }
}
