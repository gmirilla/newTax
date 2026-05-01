<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="font-family: sans-serif; background: #f9fafb; margin: 0; padding: 32px 0;">
<div style="max-width: 560px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">

    <div style="background: #16a34a; padding: 24px 32px;">
        <p style="color: #fff; font-size: 20px; font-weight: 700; margin: 0;">NaijaBooks</p>
    </div>

    <div style="padding: 32px;">
        <h1 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 8px;">
            You've been invited to join {{ $tenant->name }}
        </h1>
        <p style="color: #6b7280; font-size: 15px; margin: 0 0 24px;">
            {{ $invite->inviter?->name ?? 'An admin' }} has invited you to join
            <strong>{{ $tenant->name }}</strong> on NaijaBooks as
            <strong>{{ ucfirst($invite->role) }}</strong>.
        </p>

        <p style="color: #374151; font-size: 15px; line-height: 1.6; margin: 0 0 24px;">
            Click the button below to create your account. This invitation expires in
            <strong>72 hours</strong> ({{ $invite->expires_at->format('d M Y, g:i A') }}).
        </p>

        <a href="{{ route('invite.show', $invite->token) }}"
           style="display: inline-block; background: #16a34a; color: #fff; font-weight: 600; font-size: 14px; padding: 12px 24px; border-radius: 6px; text-decoration: none;">
            Accept Invitation →
        </a>

        <div style="margin-top: 24px; padding: 16px; background: #f9fafb; border-radius: 6px; border: 1px solid #e5e7eb;">
            <p style="font-size: 13px; color: #6b7280; margin: 0 0 4px; font-weight: 600;">Your role: {{ ucfirst($invite->role) }}</p>
            @if($invite->role === 'admin')
            <p style="font-size: 13px; color: #6b7280; margin: 0;">Full access — settings, billing, users, all financial data.</p>
            @elseif($invite->role === 'accountant')
            <p style="font-size: 13px; color: #6b7280; margin: 0;">Invoices, quotes, expenses, transactions, tax, and reports.</p>
            @else
            <p style="font-size: 13px; color: #6b7280; margin: 0;">View your payslips and submit expenses.</p>
            @endif
        </div>

        <p style="color: #9ca3af; font-size: 13px; margin: 32px 0 0;">
            If you didn't expect this invitation, you can safely ignore this email.
            Questions? Contact <a href="mailto:hello@naijabooks.ng" style="color: #16a34a;">hello@naijabooks.ng</a>.
        </p>
    </div>
</div>
</body>
</html>
