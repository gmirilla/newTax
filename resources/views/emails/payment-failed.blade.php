<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="font-family: sans-serif; background: #f9fafb; margin: 0; padding: 32px 0;">
<div style="max-width: 560px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">

    <div style="background: #dc2626; padding: 24px 32px;">
        <p style="color: #fff; font-size: 20px; font-weight: 700; margin: 0;">AccountTaxNG</p>
    </div>

    <div style="padding: 32px;">
        <h1 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 8px;">Payment failed</h1>
        <p style="color: #6b7280; font-size: 15px; margin: 0 0 24px;">Hi {{ $tenant->name }},</p>

        <p style="color: #374151; font-size: 15px; line-height: 1.6; margin: 0 0 16px;">
            We were unable to charge your payment method for your
            <strong>{{ $tenant->plan?->name }}</strong> subscription renewal.
        </p>
        <p style="color: #374151; font-size: 15px; line-height: 1.6; margin: 0 0 24px;">
            Your account has been temporarily suspended. To restore access, please update your payment method
            or resubscribe from your billing page.
            @if($tenant->subscription_expires_at)
                You have until <strong>{{ $tenant->subscription_expires_at->addDays(7)->format('d M Y') }}</strong>
                before your account is downgraded to the Free plan.
            @endif
        </p>

        <a href="{{ route('billing') }}"
           style="display: inline-block; background: #dc2626; color: #fff; font-weight: 600; font-size: 14px; padding: 12px 24px; border-radius: 6px; text-decoration: none;">
            Update Payment Method →
        </a>

        <p style="color: #9ca3af; font-size: 13px; margin: 32px 0 0;">
            Need help? Contact us at <a href="mailto:hello@accounttaxng.com" style="color: #16a34a;">hello@accounttaxng.com</a>.
        </p>
    </div>
</div>
</body>
</html>
