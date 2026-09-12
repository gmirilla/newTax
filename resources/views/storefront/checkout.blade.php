@extends('storefront.layout')

@section('title', 'Checkout — ' . $tenant->name)

@section('content')

<h1 class="text-xl font-bold text-gray-900 mb-6">Checkout</h1>

<div class="grid lg:grid-cols-5 gap-8">

    <div class="lg:col-span-3">
        <form method="POST" action="{{ route('storefront.checkout.submit', $tenant->slug) }}" class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                <input type="text" name="customer_name" value="{{ old('customer_name') }}" required
                       class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-gray-900 focus:border-gray-900">
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone <span class="text-red-500">*</span></label>
                    <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" required
                           class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-gray-900 focus:border-gray-900">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="customer_email" value="{{ old('customer_email') }}" required
                           class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-gray-900 focus:border-gray-900">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Address</label>
                <textarea name="delivery_address" rows="2"
                          class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-gray-900 focus:border-gray-900">{{ old('delivery_address') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Order Notes</label>
                <textarea name="notes" rows="2" placeholder="Anything the seller should know"
                          class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-gray-900 focus:border-gray-900">{{ old('notes') }}</textarea>
            </div>

            <div class="pt-2 flex flex-col sm:flex-row gap-3">
                <button type="submit"
                        style="background-color: {{ $tenant->accentColor() }}; color: {{ $tenant->accentTextColor() }};"
                        class="flex-1 px-6 py-3 rounded-lg text-sm font-semibold">
                    Submit Order
                </button>
                @if($tenant->storefront?->whatsapp_number)
                <button type="submit" formaction="{{ route('storefront.checkout.whatsapp', $tenant->slug) }}"
                        class="flex-1 px-6 py-3 rounded-lg text-sm font-semibold bg-green-500 text-white flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 004.74 1.21h.01c5.46 0 9.9-4.45 9.9-9.91C21.96 6.45 17.5 2 12.04 2zm0 18.12c-1.5 0-2.98-.4-4.27-1.16l-.31-.18-3.12.82.83-3.04-.2-.31a8.16 8.16 0 01-1.25-4.35c0-4.52 3.68-8.2 8.2-8.2 2.19 0 4.25.85 5.8 2.4a8.14 8.14 0 012.4 5.8c0 4.52-3.68 8.2-8.08 8.2z"/></svg>
                    Order via WhatsApp
                </button>
                @endif
            </div>
            <p class="text-xs text-gray-400 pt-1">
                We'll send this order to {{ $tenant->name }} for confirmation — no payment is collected here.
            </p>
        </form>
    </div>

    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <p class="text-sm font-semibold text-gray-900 mb-4">Order Summary</p>
            <div class="space-y-2 text-sm">
                @foreach($lines as $line)
                <div class="flex justify-between text-gray-600">
                    <span class="truncate pr-2">{{ rtrim(rtrim(number_format($line->quantity, 2), '0'), '.') }} × {{ $line->name }}</span>
                    <span class="flex-shrink-0">₦{{ number_format($line->total, 2) }}</span>
                </div>
                @endforeach
            </div>
            @php
                $subtotal = $lines->sum('subtotal');
                $vat      = $lines->sum('vat_amount');
                $total    = $lines->sum('total');
            @endphp
            <div class="space-y-1.5 pt-3 mt-3 border-t border-gray-100 text-sm">
                <div class="flex justify-between text-gray-500"><span>Subtotal</span><span>₦{{ number_format($subtotal, 2) }}</span></div>
                @if($vat > 0)
                <div class="flex justify-between text-gray-500"><span>VAT (7.5%)</span><span>₦{{ number_format($vat, 2) }}</span></div>
                @endif
                <div class="flex justify-between font-bold text-gray-900 pt-1.5 mt-1.5 border-t border-gray-100"><span>Total</span><span>₦{{ number_format($total, 2) }}</span></div>
            </div>
        </div>
    </div>

</div>

@endsection
