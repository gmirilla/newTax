<?php

namespace App\Support;

/**
 * Single source of truth for invoice / quote totals with a document-level discount.
 *
 *   discount = fixed ₦ amount, or % of subtotal (never more than the subtotal)
 *   net      = subtotal − discount
 *   VAT      = line VAT scaled by net/subtotal (VAT is charged on the discounted price)
 *   WHT      = wht_rate % of net
 *   total    = net + VAT − WHT
 *
 * $type === null means a legacy document: flat discount_amount, VAT and WHT on the
 * pre-discount subtotal (the behaviour before discount types existed).
 */
class DocumentTotals
{
    public const TYPE_FIXED   = 'fixed';
    public const TYPE_PERCENT = 'percent';

    /** "Discount" or "Discount (10%)" for display; safe to call with null/legacy values. */
    public static function label(?string $type, $value = 0): string
    {
        if ($type === self::TYPE_PERCENT) {
            $pct = rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
            return "Discount ({$pct}%)";
        }

        return 'Discount';
    }

    /**
     * @param  float  $subtotal  Sum of line subtotals (pre-discount, ex-VAT)
     * @param  float  $lineVat   Sum of line VAT on VAT-applicable lines (pre-discount)
     * @return array{discount: float, net: float, vat: float, wht: float, total: float}
     */
    public static function calculate(
        float $subtotal,
        float $lineVat,
        bool $vatApplicable,
        bool $whtApplicable,
        float $whtRate,
        ?string $type,
        float $value,
        float $legacyDiscount = 0.0,
    ): array {
        $subtotal = round($subtotal, 2);

        if ($type === null) {
            $discount = round($legacyDiscount, 2);
            $vat      = $vatApplicable ? round($lineVat, 2) : 0.0;
            $wht      = $whtApplicable ? round($subtotal * $whtRate / 100, 2) : 0.0;

            return [
                'discount' => $discount,
                'net'      => round($subtotal - $discount, 2),
                'vat'      => $vat,
                'wht'      => $wht,
                'total'    => round($subtotal - $discount + $vat - $wht, 2),
            ];
        }

        $value    = max(0.0, $value);
        $discount = $type === self::TYPE_PERCENT
            ? round($subtotal * min($value, 100) / 100, 2)
            : round(min($value, $subtotal), 2);

        $net   = round($subtotal - $discount, 2);
        $ratio = $subtotal > 0 ? $net / $subtotal : 1.0;
        $vat   = $vatApplicable ? round($lineVat * $ratio, 2) : 0.0;
        $wht   = $whtApplicable ? round($net * $whtRate / 100, 2) : 0.0;

        return [
            'discount' => $discount,
            'net'      => $net,
            'vat'      => $vat,
            'wht'      => $wht,
            'total'    => round($net + $vat - $wht, 2),
        ];
    }
}
