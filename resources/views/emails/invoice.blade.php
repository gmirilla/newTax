<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="font-family: sans-serif; background: #f9fafb; margin: 0; padding: 32px 0;">
<div style="max-width: 560px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">

    <div style="background: #16a34a; padding: 24px 32px;">
        <p style="color: #fff; font-size: 20px; font-weight: 700; margin: 0;">{{ $invoice->tenant->name }}</p>
    </div>

    <div style="padding: 32px;">
        <h1 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 16px;">
            Invoice {{ $invoice->invoice_number }}
        </h1>

        <p style="color: #374151; font-size: 15px; line-height: 1.6; margin: 0 0 8px;">
            Dear {{ $invoice->customer->name }},
        </p>
        <p style="color: #374151; font-size: 15px; line-height: 1.6; margin: 0 0 24px;">
            Please find your invoice attached to this email. Here's a summary:
        </p>

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 24px; font-size: 14px;">
            <tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 10px 0; color: #6b7280;">Invoice Number</td>
                <td style="padding: 10px 0; font-weight: 600; text-align: right;">{{ $invoice->invoice_number }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 10px 0; color: #6b7280;">Invoice Date</td>
                <td style="padding: 10px 0; text-align: right;">{{ $invoice->invoice_date->format('d M Y') }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 10px 0; color: #6b7280;">Due Date</td>
                <td style="padding: 10px 0; font-weight: 600; text-align: right; {{ $invoice->isOverdue() ? 'color: #dc2626;' : '' }}">
                    {{ $invoice->due_date->format('d M Y') }}
                </td>
            </tr>
            <tr>
                <td style="padding: 10px 0; color: #6b7280;">Amount Due</td>
                <td style="padding: 10px 0; font-size: 18px; font-weight: 700; color: #16a34a; text-align: right;">
                    ₦{{ number_format($invoice->balance_due, 2) }}
                </td>
            </tr>
        </table>

        @if($invoice->notes)
        <p style="color: #374151; font-size: 14px; line-height: 1.6; margin: 0 0 24px; padding: 12px 16px; background: #f9fafb; border-left: 3px solid #16a34a; border-radius: 0 4px 4px 0;">
            {{ $invoice->notes }}
        </p>
        @endif

        <a href="{{ $invoice->publicUrl() }}"
           style="display: inline-block; background: #16a34a; color: #fff; font-weight: 600; font-size: 14px; padding: 12px 24px; border-radius: 6px; text-decoration: none;">
            View Invoice Online →
        </a>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 32px 0 24px;">

        <p style="color: #374151; font-size: 14px; line-height: 1.6; margin: 0 0 8px;">
            <strong>Payment Instructions</strong><br>
            Please use invoice number <strong>{{ $invoice->invoice_number }}</strong> as your payment reference.
        </p>

        @if($invoice->tenant->bank_name || $invoice->tenant->bank_account_number)
        <p style="color: #6b7280; font-size: 13px; line-height: 1.8; margin: 12px 0 0;">
            @if($invoice->tenant->bank_name)
                Bank: {{ $invoice->tenant->bank_name }}<br>
            @endif
            @if($invoice->tenant->bank_account_number)
                Account Number: {{ $invoice->tenant->bank_account_number }}<br>
            @endif
            @if($invoice->tenant->bank_account_name)
                Account Name: {{ $invoice->tenant->bank_account_name }}<br>
            @endif
        </p>
        @endif

        <p style="color: #9ca3af; font-size: 13px; margin: 32px 0 0;">
            Questions? Contact us at
            <a href="mailto:{{ $invoice->tenant->email }}" style="color: #16a34a;">{{ $invoice->tenant->email }}</a>
            @if($invoice->tenant->phone) or {{ $invoice->tenant->phone }} @endif.
        </p>
    </div>

    <div style="background: #f9fafb; padding: 16px 32px; border-top: 1px solid #e5e7eb;">
        <p style="color: #9ca3af; font-size: 12px; margin: 0; text-align: center;">
            Sent via <a href="{{ config('app.url') }}" style="color: #16a34a; text-decoration: none;">AccountTaxNG</a>
        </p>
    </div>
</div>
</body>
</html>
