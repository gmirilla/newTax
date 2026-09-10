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
            New storefront order — {{ $order->order_number }}
        </h1>
        <p style="color: #6b7280; font-size: 15px; margin: 0 0 24px;">
            {{ $order->customer_name }} placed an order on your {{ $tenant->name }} storefront
            @if($order->channel === 'whatsapp') via WhatsApp @endif.
        </p>

        <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin-bottom: 20px;">
            <thead>
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <th style="text-align: left; padding: 6px 0; color: #6b7280; font-weight: 600;">Item</th>
                    <th style="text-align: right; padding: 6px 0; color: #6b7280; font-weight: 600;">Qty</th>
                    <th style="text-align: right; padding: 6px 0; color: #6b7280; font-weight: 600;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr style="border-bottom: 1px solid #f3f4f6;">
                    <td style="padding: 6px 0; color: #111827;">{{ $item->description }}</td>
                    <td style="padding: 6px 0; color: #111827; text-align: right;">{{ rtrim(rtrim($item->quantity, '0'), '.') }}</td>
                    <td style="padding: 6px 0; color: #111827; text-align: right;">₦{{ number_format((float) $item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <p style="font-size: 15px; font-weight: 700; color: #111827; margin: 0 0 24px; text-align: right;">
            Total: ₦{{ number_format((float) $order->total_amount, 2) }}
        </p>

        <div style="padding: 16px; background: #f9fafb; border-radius: 6px; border: 1px solid #e5e7eb; margin-bottom: 24px;">
            <p style="font-size: 13px; color: #6b7280; margin: 0 0 4px;"><strong>Customer:</strong> {{ $order->customer_name }}</p>
            <p style="font-size: 13px; color: #6b7280; margin: 0 0 4px;"><strong>Phone:</strong> {{ $order->customer_phone }}</p>
            <p style="font-size: 13px; color: #6b7280; margin: 0 0 4px;"><strong>Email:</strong> {{ $order->customer_email }}</p>
            @if($order->delivery_address)
            <p style="font-size: 13px; color: #6b7280; margin: 0;"><strong>Delivery address:</strong> {{ $order->delivery_address }}</p>
            @endif
        </div>

        <a href="{{ route('storefront.orders.show', $order) }}"
           style="display: inline-block; background: #16a34a; color: #fff; font-weight: 600; font-size: 14px; padding: 12px 24px; border-radius: 6px; text-decoration: none;">
            Review Order →
        </a>

        <p style="color: #9ca3af; font-size: 13px; margin: 32px 0 0;">
            Accept the order to create a draft sales order ready for you to fulfil, or reject it if you can't complete it.
        </p>
    </div>
</div>
</body>
</html>
