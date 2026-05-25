@extends('superadmin.layout')
@section('page-title', 'Referral Report')

@section('content')
<div class="space-y-6">

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach([
            ['Total Referrals',   $stats['total'],                          'indigo'],
            ['Pending Payment',   $stats['pending'],                         'yellow'],
            ['Rewarded',          $stats['rewarded'],                        'green'],
            ['Credits Issued',    '₦' . number_format($stats['credit_ngn'], 2), 'purple'],
        ] as [$label, $value, $color])
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-500 uppercase tracking-wide">{{ $label }}</p>
            <p class="text-2xl font-bold text-{{ $color }}-600 mt-1">{{ $value }}</p>
        </div>
        @endforeach
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">All Referrals</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Referrer</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Referred</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Signed Up</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rewarded At</th>
                        <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase">Credit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($referrals as $ref)
                    <tr>
                        <td class="px-5 py-3">
                            <p class="text-sm font-medium text-gray-900">{{ $ref->referrer->name }}</p>
                            <p class="text-xs text-gray-400">{{ $ref->referrer->email }}</p>
                        </td>
                        <td class="px-5 py-3">
                            <p class="text-sm font-medium text-gray-900">{{ $ref->referee->name }}</p>
                            <p class="text-xs text-gray-400">{{ $ref->referee->email }}</p>
                        </td>
                        <td class="px-5 py-3 text-sm text-gray-500">{{ $ref->created_at->format('d M Y') }}</td>
                        <td class="px-5 py-3">
                            @php $colors = ['pending' => 'yellow', 'qualified' => 'blue', 'rewarded' => 'green']; @endphp
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                         bg-{{ $colors[$ref->status] ?? 'gray' }}-100
                                         text-{{ $colors[$ref->status] ?? 'gray' }}-700">
                                {{ ucfirst($ref->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-sm text-gray-500">
                            {{ $ref->rewarded_at?->format('d M Y') ?? '—' }}
                        </td>
                        <td class="px-5 py-3 text-right text-sm font-medium">
                            @if($ref->status === 'rewarded')
                                <span class="text-green-700">₦{{ number_format($ref->reward_ngn, 0) }}</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-sm text-gray-400">No referrals yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($referrals->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $referrals->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
