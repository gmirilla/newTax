@extends('layouts.app')
@section('page-title', 'Inventory – Help')

@section('content')
<div class="max-w-3xl space-y-6">

    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('help.index') }}" class="hover:text-green-600">Help Center</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-800 font-medium">{{ $meta['title'] }}</span>
    </div>

    <div>
        <h1 class="text-xl font-bold text-gray-900">Inventory</h1>
        <p class="text-sm text-gray-500 mt-1">Track stock levels, manage sales orders, and analyse inventory performance.</p>
    </div>

    @unless(auth()->user()->tenant->planAllows('inventory'))
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800 flex items-start gap-3">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        <div>
            <strong>Inventory is a Pro feature.</strong> Upgrade your plan to access inventory management.
            <a href="{{ route('billing') }}" class="underline ml-1">View Plans</a>
        </div>
    </div>
    @endunless

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Adding Inventory Items</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <ol class="list-decimal list-inside space-y-2 pl-2">
                <li>Go to <strong>Inventory → Items</strong> and click <strong>New Item</strong></li>
                <li>Enter the item name, SKU, and category</li>
                <li>Set the <strong>Unit Cost</strong> (what you paid) and <strong>Selling Price</strong></li>
                <li>Enter the <strong>Opening Stock</strong> — the quantity on hand when you started</li>
                <li>Set the <strong>Reorder Level</strong> — the quantity that triggers a low-stock alert</li>
                <li>Click <strong>Save</strong></li>
            </ol>
            <div class="bg-blue-50 border border-blue-200 rounded p-3 text-blue-800 text-xs">
                The opening stock value (quantity × unit cost) is automatically posted to your Inventory GL account and Owner's Equity, keeping your Balance Sheet balanced.
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Stock Movements</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <p>Every change to stock quantity is recorded as a movement. Types of movements:</p>
            <table class="w-full text-xs border-collapse">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left p-2 border border-gray-200 font-semibold">Type</th>
                        <th class="text-left p-2 border border-gray-200 font-semibold">When it happens</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td class="p-2 border border-gray-200 font-medium text-green-700">Opening</td><td class="p-2 border border-gray-200">Initial stock when item is first added</td></tr>
                    <tr class="bg-gray-50"><td class="p-2 border border-gray-200 font-medium text-blue-700">Restock</td><td class="p-2 border border-gray-200">You received more stock (purchase)</td></tr>
                    <tr><td class="p-2 border border-gray-200 font-medium text-red-700">Sale</td><td class="p-2 border border-gray-200">Sold via a confirmed Sales Order</td></tr>
                    <tr class="bg-gray-50"><td class="p-2 border border-gray-200 font-medium text-orange-700">Adjustment</td><td class="p-2 border border-gray-200">Manual correction (e.g. stock count variance)</td></tr>
                    <tr><td class="p-2 border border-gray-200 font-medium text-purple-700">Production</td><td class="p-2 border border-gray-200">Used in a manufacturing production order</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Sales Orders</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <ol class="list-decimal list-inside space-y-2 pl-2">
                <li>Go to <strong>Inventory → Sales Orders</strong> and click <strong>New Order</strong></li>
                <li>Select or enter a customer</li>
                <li>Add items — the system checks available stock in real time</li>
                <li>Click <strong>Save as Draft</strong> to hold, or <strong>Confirm Order</strong> to process</li>
                <li>Confirming the order: deducts stock, creates an invoice, and generates journal entries</li>
                <li>Download the invoice PDF directly from the confirmed order — no need to go to Invoices separately</li>
            </ol>
            <div class="bg-green-50 border border-green-200 rounded p-3 text-green-800 text-xs">
                Confirming an order is the trigger for all accounting: stock is reduced, revenue is recognised, and the GL is updated automatically.
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Restock Requests</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <p>When stock falls to or below the reorder level, NaijaBooks flags the item. Go to <strong>Inventory → Restock Requests</strong> to:</p>
            <ul class="list-disc list-inside space-y-1 pl-2">
                <li>Review pending restock requests</li>
                <li>Confirm a restock delivery — entering the quantity received and unit cost</li>
                <li>The system posts the stock increase and updates the GL</li>
            </ul>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-800">Advanced Inventory Reports</h2>
                <span class="text-xs px-2 py-0.5 bg-orange-100 text-orange-700 rounded-full font-bold uppercase">Business Plan</span>
            </div>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <p>Under <strong>Reports → Inventory → Advanced</strong>:</p>
            <div class="space-y-3">
                <div>
                    <p class="font-medium">Slow Moving</p>
                    <p class="text-xs text-gray-500">Items with low sales velocity. Identifies dead stock (zero sales) and items selling less than 0.1 units/day.</p>
                </div>
                <div>
                    <p class="font-medium">Fast Moving</p>
                    <p class="text-xs text-gray-500">Your best-selling items by volume and revenue. Highlights top 3 performers for priority restocking.</p>
                </div>
                <div>
                    <p class="font-medium">Reorder Analysis</p>
                    <p class="text-xs text-gray-500">Prioritised list showing which items need reordering urgently, based on days of stock remaining vs. average daily demand.</p>
                </div>
            </div>
            <p class="text-xs text-gray-500">All three reports are downloadable as PDF and Excel.</p>
        </div>
    </div>

    <a href="{{ route('help.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-green-600">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back to Help Center
    </a>

</div>
@endsection
