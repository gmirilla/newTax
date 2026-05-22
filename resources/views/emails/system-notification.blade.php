<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="font-family: sans-serif; background: #f9fafb; margin: 0; padding: 32px 0;">
<div style="max-width: 560px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">

    @php
        $headerColor = match($notification->type) {
            'warning'  => '#d97706',
            'critical' => '#dc2626',
            'success'  => '#16a34a',
            default    => '#4338ca',
        };
    @endphp

    <div style="background: {{ $headerColor }}; padding: 24px 32px;">
        <p style="color: #fff; font-size: 20px; font-weight: 700; margin: 0;">AccountTaxNG</p>
    </div>

    <div style="padding: 32px;">
        <h1 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 8px;">
            {{ $notification->title }}
        </h1>
        <p style="color: #6b7280; font-size: 15px; margin: 0 0 24px;">Hi {{ $tenant->name }},</p>

        <div style="color: #374151; font-size: 15px; line-height: 1.7; margin: 0 0 24px; white-space: pre-line;">{{ $notification->message }}</div>

        @if($notification->expires_at)
        <p style="color: #6b7280; font-size: 13px; border-top: 1px solid #e5e7eb; padding-top: 16px; margin: 0;">
            This notification is valid until {{ $notification->expires_at->format('d M Y, H:i') }}.
        </p>
        @endif

        <p style="color: #9ca3af; font-size: 13px; margin: 24px 0 0;">
            Questions? Contact us at <a href="mailto:hello@accounttaxng.com" style="color: #4338ca;">hello@accounttaxng.com</a>.
        </p>
    </div>
</div>
</body>
</html>
