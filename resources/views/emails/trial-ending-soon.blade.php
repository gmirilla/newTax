<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="font-family: sans-serif; background: #f9fafb; margin: 0; padding: 32px 0;">
<div style="max-width: 560px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">

    <div style="background: #16a34a; padding: 24px 32px;">
        <p style="color: #fff; font-size: 20px; font-weight: 700; margin: 0;">NaijaBooks</p>
    </div>

    <div style="padding: 32px;">
        <h1 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 8px;">Your trial ends in 3 days</h1>
        <p style="color: #6b7280; font-size: 15px; margin: 0 0 24px;">
            Hi {{ $tenant->name }},
        </p>
        <p style="color: #374151; font-size: 15px; line-height: 1.6; margin: 0 0 16px;">
            Your free trial of <strong>{{ $tenant->plan?->name ?? 'NaijaBooks' }}</strong> expires on
            <strong>{{ $tenant->trial_ends_at?->format('d M Y') }}</strong>.
        </p>
        <p style="color: #374151; font-size: 15px; line-height: 1.6; margin: 0 0 24px;">
            After your trial ends your account will move to the Free plan (5 invoices/month, 1 user). To keep full access — including payroll, unlimited invoices, and FIRS e-Invoicing — upgrade before your trial expires.
        </p>

        <a href="{{ route('billing') }}"
           style="display: inline-block; background: #16a34a; color: #fff; font-weight: 600; font-size: 14px; padding: 12px 24px; border-radius: 6px; text-decoration: none;">
            Upgrade Now →
        </a>

        <p style="color: #9ca3af; font-size: 13px; margin: 32px 0 0;">
            If you have questions, reply to this email or contact us at
            <a href="mailto:hello@naijabooks.ng" style="color: #16a34a;">hello@naijabooks.ng</a>.
        </p>
    </div>
</div>
</body>
</html>
