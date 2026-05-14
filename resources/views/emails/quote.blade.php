<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="font-family: sans-serif; background: #f9fafb; margin: 0; padding: 32px 0;">
<div style="max-width: 560px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb;">

    <div style="background: #16a34a; padding: 24px 32px;">
        <p style="color: #fff; font-size: 20px; font-weight: 700; margin: 0;">{{ $quote->tenant->name }}</p>
    </div>

    <div style="padding: 32px;">
        <h1 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 16px;">
            Quotation {{ $quote->quote_number }}
        </h1>

        <p style="color: #374151; font-size: 15px; line-height: 1.6; margin: 0 0 8px;">
            Dear {{ $quote->customer->name }},
        </p>
        <p style="color: #374151; font-size: 15px; line-height: 1.6; margin: 0 0 24px;">
            Thank you for your interest. Please find our quotation attached. Here's a summary:
        </p>

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 24px; font-size: 14px;">
            <tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 10px 0; color: #6b7280;">Quote Number</td>
                <td style="padding: 10px 0; font-weight: 600; text-align: right;">{{ $quote->quote_number }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 10px 0; color: #6b7280;">Quote Date</td>
                <td style="padding: 10px 0; text-align: right;">{{ $quote->quote_date->format('d M Y') }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 10px 0; color: #6b7280;">Valid Until</td>
                <td style="padding: 10px 0; font-weight: 600; text-align: right; {{ $quote->expiry_date->isPast() ? 'color: #dc2626;' : '' }}">
                    {{ $quote->expiry_date->format('d M Y') }}
                </td>
            </tr>
            <tr>
                <td style="padding: 10px 0; color: #6b7280;">Total Amount</td>
                <td style="padding: 10px 0; font-size: 18px; font-weight: 700; color: #16a34a; text-align: right;">
                    ₦{{ number_format($quote->total_amount, 2) }}
                </td>
            </tr>
        </table>

        @if($quote->notes)
        <p style="color: #374151; font-size: 14px; line-height: 1.6; margin: 0 0 24px; padding: 12px 16px; background: #f9fafb; border-left: 3px solid #16a34a; border-radius: 0 4px 4px 0;">
            {{ $quote->notes }}
        </p>
        @endif

        <p style="color: #374151; font-size: 14px; line-height: 1.6; margin: 0 0 24px;">
            To accept this quotation or request any changes, please reply to this email or contact us directly.
        </p>

        <p style="color: #9ca3af; font-size: 13px; margin: 32px 0 0;">
            Questions? Contact us at
            <a href="mailto:{{ $quote->tenant->email }}" style="color: #16a34a;">{{ $quote->tenant->email }}</a>
            @if($quote->tenant->phone) or {{ $quote->tenant->phone }} @endif.
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
