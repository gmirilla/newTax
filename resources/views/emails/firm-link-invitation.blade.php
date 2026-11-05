<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="font-family: sans-serif; background: #f9fafb; margin: 0; padding: 32px 0;">
<div style="max-width: 560px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">

    <div style="background: #16a34a; padding: 24px 32px;">
        <p style="color: #fff; font-size: 20px; font-weight: 700; margin: 0;">AccountTaxNG</p>
    </div>

    <div style="padding: 32px;">
        <h1 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 8px;">
            {{ $link->firmTenant->name }} wants to manage your books
        </h1>
        <p style="color: #6b7280; font-size: 15px; margin: 0 0 24px;">
            <strong>{{ $link->firmTenant->name }}</strong> has requested bookkeeping access to your
            AccountTaxNG account. If you approve, they'll be able to work on your invoices, expenses,
            and financial reports — they will <strong>not</strong> have access to billing, your team,
            or company settings.
        </p>

        <a href="{{ route('partner-invite.show', $link->token) }}"
           style="display: inline-block; background: #16a34a; color: #fff; font-weight: 600; font-size: 14px; padding: 12px 24px; border-radius: 6px; text-decoration: none;">
            Review Request →
        </a>

        <p style="color: #9ca3af; font-size: 13px; margin: 32px 0 0;">
            If you weren't expecting this, you can safely ignore this email — nothing changes until
            you approve. Questions? Contact <a href="mailto:hello@accounttaxng.com" style="color: #16a34a;">hello@accounttaxng.com</a>.
        </p>
    </div>
</div>
</body>
</html>
