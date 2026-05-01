<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="font-family: sans-serif; background: #f9fafb; margin: 0; padding: 32px 0;">
<div style="max-width: 560px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">

    <div style="background: #4b5563; padding: 24px 32px;">
        <p style="color: #fff; font-size: 20px; font-weight: 700; margin: 0;">NaijaBooks</p>
    </div>

    <div style="padding: 32px;">
        <h1 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 8px;">Subscription cancelled</h1>
        <p style="color: #6b7280; font-size: 15px; margin: 0 0 24px;">Hi {{ $tenant->name }},</p>

        <p style="color: #374151; font-size: 15px; line-height: 1.6; margin: 0 0 16px;">
            Your <strong>{{ $tenant->plan?->name }}</strong> subscription has been cancelled.
            You will keep full access until <strong>{{ $expiryDate }}</strong>, after which your
            account will move to the <strong>{{ $nextPlanName }}</strong> plan.
        </p>

        <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 16px; margin: 0 0 24px;">
            <p style="font-size: 14px; color: #6b7280; margin: 0;">
                Changed your mind? You can resubscribe at any time from your billing page and keep your data.
            </p>
        </div>

        <a href="{{ route('billing') }}"
           style="display: inline-block; background: #16a34a; color: #fff; font-weight: 600; font-size: 14px; padding: 12px 24px; border-radius: 6px; text-decoration: none;">
            View Billing Page →
        </a>

        <p style="color: #9ca3af; font-size: 13px; margin: 32px 0 0;">
            Questions? Contact us at <a href="mailto:hello@naijabooks.ng" style="color: #16a34a;">hello@naijabooks.ng</a>.
        </p>
    </div>
</div>
</body>
</html>
