<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #16a34a; color: white; padding: 20px 24px; border-radius: 8px 8px 0 0; }
        .body { border: 1px solid #e5e7eb; border-top: none; padding: 24px; border-radius: 0 0 8px 8px; }
        .highlight { background: #fef3c7; border: 1px solid #fcd34d; border-radius: 6px; padding: 12px 16px; margin: 16px 0; }
        .btn { display: inline-block; background: #16a34a; color: white; padding: 10px 24px; border-radius: 6px; text-decoration: none; font-weight: bold; }
        .footer { margin-top: 24px; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 16px; }
    </style>
</head>
<body>
    <div class="header">
        <h2 style="margin:0; font-size: 20px;">AccountTaxNG</h2>
        <p style="margin:4px 0 0; opacity: 0.85; font-size: 13px;">Nigerian SME Bookkeeping & Tax Compliance</p>
    </div>
    <div class="body">
        <p>Dear <strong>{{ $tenant->name }}</strong>,</p>

        @if($tenant->subscription_expires_at && $tenant->subscription_expires_at->isPast())
        <div class="highlight" style="background:#fee2e2; border-color:#fca5a5;">
            <strong style="color:#dc2626;">⚠ Your subscription has expired.</strong><br>
            Your <strong>{{ ucfirst($tenant->subscription_plan) }}</strong> plan expired on
            <strong>{{ $tenant->subscription_expires_at->format('d F Y') }}</strong>.
            Your data is safe, but access to premium features has been restricted.
        </div>
        @elseif($tenant->subscription_expires_at)
        <div class="highlight">
            <strong style="color:#d97706;">⏰ Your subscription is expiring soon.</strong><br>
            Your <strong>{{ ucfirst($tenant->subscription_plan) }}</strong> plan expires on
            <strong>{{ $tenant->subscription_expires_at->format('d F Y') }}</strong>
            ({{ $tenant->subscription_expires_at->diffForHumans() }}).
        </div>
        @else
        <div class="highlight">
            <strong style="color:#d97706;">💳 Subscription Reminder</strong><br>
            This is a reminder regarding your <strong>{{ ucfirst($tenant->subscription_plan) }}</strong> plan on AccountTaxNG.
        </div>
        @endif

        @if($customMessage)
        <p style="background:#f0fdf4; border-left: 3px solid #16a34a; padding: 12px 16px; border-radius: 0 4px 4px 0;">
            <em>{{ $customMessage }}</em>
        </p>
        @endif

        <p>To continue enjoying uninterrupted access to your bookkeeping records, VAT returns, payroll, and tax compliance tools, please renew your subscription.</p>

        <p>
            <strong>Account Details:</strong><br>
            Company: {{ $tenant->name }}<br>
            Email: {{ $tenant->email }}<br>
            Current Plan: {{ ucfirst($tenant->subscription_plan) }}<br>
            TIN: {{ $tenant->tin ?? 'Not set' }}
        </p>

        <p style="text-align:center; margin: 24px 0;">
            <a href="#" class="btn">Renew Subscription</a>
        </p>

        <p style="font-size: 13px; color: #6b7280;">
            If you have already made payment, please disregard this message or reply to this email with your payment confirmation.
        </p>
    </div>
    <div class="footer">
        AccountTaxNG — Nigerian SME Tax & Bookkeeping Platform<br>
        This is an automated notification. Please do not reply directly to this email.
    </div>
</body>
</html>
