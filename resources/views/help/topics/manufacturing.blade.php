@extends('layouts.app')
@section('page-title', 'Manufacturing – Help')

@section('content')
<div class="max-w-3xl space-y-6">
    @include('help.partials.header', [
        'why' => 'If you make a product from raw materials (bread from flour, garments from fabric), this tracks the recipe, uses up the materials when you produce, and adds the finished goods to stock, so your inventory and costs stay accurate.',
        'who' => 'Users given Manufacturing access, on plans that include it. It works together with Inventory.',
    ])

    @component('help.partials.section', ['heading' => 'Bills of Materials (BOMs)'])
        <p>A BOM is the recipe for one product: which raw materials go in, and how much of each. Go to <strong>Manufacturing → Bills of Materials</strong>, define the product, and add a line for each raw material with the quantity required.</p>
    @endcomponent

    @component('help.partials.section', ['heading' => 'Production orders'])
        <ol class="list-decimal list-inside space-y-1 pl-2">
            <li>Go to <strong>Manufacturing → Production Orders</strong> and create a new order</li>
            <li>Choose the product and how many you want to make</li>
            <li>Start production. The order moves from draft to in production.</li>
            <li>Complete it. Raw materials are used up and finished goods are added to stock.</li>
        </ol>
    @endcomponent

    @component('help.partials.section', ['heading' => 'A worked example'])
        <p>Bola's Bakery defines a BOM for "Loaf of bread": 1 kg flour, 50 g yeast. It creates a production order for 200 loaves. When the order is completed, 200 kg of flour and 10 kg of yeast are deducted and 200 loaves appear in Finished Goods inventory.</p>
    @endcomponent

    @component('help.partials.section', ['heading' => 'Before you start'])
        <p>Set up your raw materials and finished products as items in <a class="text-green-700 underline" href="{{ route('help.show', 'inventory') }}">Inventory</a> first. Manufacturing uses those items.</p>
    @endcomponent

    @include('help.partials.footer')
</div>
@endsection
