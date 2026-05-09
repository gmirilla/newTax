<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="font-family: sans-serif; background: #f9fafb; margin: 0; padding: 32px 0;">
<div style="max-width: 560px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">

    <div style="background: #16a34a; padding: 24px 32px;">
        <p style="color: #fff; font-size: 20px; font-weight: 700; margin: 0;">AccountTaxNG</p>
    </div>

    <div style="padding: 32px;">
        @if($type === 'upgrade_proration')
        <h1 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 8px;">You've upgraded to {{ $plan->name }}</h1>
        @else
        <h1 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 8px;">Welcome to {{ $plan->name }}</h1>
        @endif

        <p style="color: #6b7280; font-size: 15px; margin: 0 0 24px;">Hi {{ $tenant->name }},</p>

        <p style="color: #374151; font-size: 15px; line-height: 1.6; margin: 0 0 16px;">
            Your <strong>{{ $plan->name }}</strong> subscription is now active.
            @if($tenant->subscription_expires_at)
                Your next renewal is on <strong>{{ $tenant->subscription_expires_at->format('d M Y') }}</strong>.
            @endif
        </p>

        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 16px; margin: 0 0 24px;">
            <p style="font-weight: 600; color: #166534; font-size: 14px; margin: 0 0 8px;">{{ $plan->name }} includes:</p>
            @php $limits = $plan->limits ?? []; @endphp
            <ul style="margin: 0; padding-left: 20px; color: #374151; font-size: 14px; line-height: 1.8;">
                @if(($limits['invoices_per_month'] ?? null) === null)
                <li>Unlimited invoices per month</li>
                @elseif(($limits['invoices_per_month'] ?? 0) > 0)
                <li>{{ $limits['invoices_per_month'] }} invoices per month</li>
                @endif
                @if(($limits['users'] ?? null) === null)
                <li>Unlimited team members</li>
                @elseif(($limits['users'] ?? 0) > 0)
                <li>Up to {{ $limits['users'] }} team members</li>
                @endif
                @if($limits['payroll'] ?? false)<li>Payroll & PAYE processing</li>@endif
                @if($limits['firs'] ?? false)<li>NRS e-Invoicing</li>@endif
            </ul>
        </div>

        <a href="{{ route('dashboard') }}"
           style="display: inline-block; background: #16a34a; color: #fff; font-weight: 600; font-size: 14px; padding: 12px 24px; border-radius: 6px; text-decoration: none;">
            Go to Dashboard →
        </a>

        <p style="color: #9ca3af; font-size: 13px; margin: 32px 0 0;">
            Questions? Contact us at <a href="mailto:hello@accounttaxng.com" style="color: #16a34a;">hello@accounttaxng.com</a>.
        </p>
    </div>
</div>
</body>
</html>
