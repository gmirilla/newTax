@extends('layouts.app')
@section('page-title', 'Maintenance – Help')

@section('content')
<div class="max-w-3xl space-y-6">
    @include('help.partials.header', [
        'why' => 'If your business depends on machines, vehicles or equipment, this keeps track of them: what you own, when each needs servicing, what broke, and what the repair cost. That means fewer surprise breakdowns and a clear cost history per asset.',
        'who' => 'Users given Maintenance access, on plans that include it.',
    ])

    @component('help.partials.section', ['heading' => 'The pieces'])
        <ul class="list-disc list-inside space-y-1 pl-2">
            <li><strong>Assets</strong>: each machine or vehicle you maintain.</li>
            <li><strong>PM Schedules</strong>: preventive maintenance, such as "service the generator every 3 months".</li>
            <li><strong>Breakdowns</strong>: report something that has stopped working.</li>
            <li><strong>Work Orders</strong>: the job to fix or service an asset, including the spare parts used.</li>
            <li><strong>Overview</strong>: asset health, overdue work and open breakdowns at a glance.</li>
        </ul>
    @endcomponent

    @component('help.partials.section', ['heading' => 'A worked example'])
        <p>Bola's Bakery has a baking oven. Set up an <strong>asset</strong> for the oven and a <strong>PM schedule</strong> to clean and inspect it monthly. When the schedule falls due a work order is raised. One day the oven stops heating, so you report a <strong>breakdown</strong>. A <strong>work order</strong> is opened, the technician records the heating element used as a part, and the job is closed. The oven's history now shows what failed and what it cost.</p>
    @endcomponent

    @component('help.partials.section', ['heading' => 'Getting started'])
        <ol class="list-decimal list-inside space-y-1 pl-2">
            <li>Go to <strong>Maintenance → Assets</strong> and add your equipment</li>
            <li>Add <strong>PM Schedules</strong> for anything that needs regular servicing</li>
            <li>Use <strong>Breakdowns</strong> when something fails, and <strong>Work Orders</strong> to track the repair</li>
        </ol>
        <p class="text-xs text-gray-500">Parts added to a work order come from your inventory, so keep spare parts in <a class="text-green-700 underline" href="{{ route('help.show', 'inventory') }}">Inventory</a>.</p>
    @endcomponent

    @include('help.partials.footer')
</div>
@endsection
