@extends('layouts.app')
@section('page-title', 'Team & Users – Help')

@section('content')
<div class="max-w-3xl space-y-6">

    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('help.index') }}" class="hover:text-green-600">Help Center</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-800 font-medium">{{ $meta['title'] }}</span>
    </div>

    <div>
        <h1 class="text-xl font-bold text-gray-900">Team & Users</h1>
        <p class="text-sm text-gray-500 mt-1">Invite your accountant, staff members, and colleagues with the right level of access.</p>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">User Roles</h2>
        </div>
        <div class="p-5 text-sm text-gray-700">
            <table class="w-full text-xs border-collapse">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left p-2 border border-gray-200 font-semibold">Role</th>
                        <th class="text-left p-2 border border-gray-200 font-semibold">Access Level</th>
                        <th class="text-left p-2 border border-gray-200 font-semibold">Best For</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="p-2 border border-gray-200">
                            <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded font-bold">Admin</span>
                        </td>
                        <td class="p-2 border border-gray-200">Full access to everything including billing, settings, and team management</td>
                        <td class="p-2 border border-gray-200">Business owner or office manager</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="p-2 border border-gray-200">
                            <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded font-bold">Accountant</span>
                        </td>
                        <td class="p-2 border border-gray-200">All financial operations: invoices, expenses, payroll, reports. Cannot access billing or team management</td>
                        <td class="p-2 border border-gray-200">Your in-house or outsourced accountant</td>
                    </tr>
                    <tr>
                        <td class="p-2 border border-gray-200">
                            <span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded font-bold">Staff</span>
                        </td>
                        <td class="p-2 border border-gray-200">Access to the staff portal only — limited view, no financial editing</td>
                        <td class="p-2 border border-gray-200">General employees who need limited access</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Inviting a Team Member</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <ol class="list-decimal list-inside space-y-2 pl-2">
                <li>Go to <strong>Settings → Team Members</strong></li>
                <li>Click <strong>Invite User</strong></li>
                <li>Enter their email address and select a role</li>
                <li>Click <strong>Send Invite</strong></li>
            </ol>
            <p>The invited user receives an email with a link to set up their password. They join your business automatically — no separate registration needed.</p>
            <div class="bg-blue-50 border border-blue-200 rounded p-3 text-blue-800 text-xs">
                Only <strong>Admin</strong> users can invite and manage team members.
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Feature Access for Non-Admin Roles</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <p>For premium features like Inventory, Payroll, or Manufacturing, you can grant or restrict access per user — even within the same role.</p>
            <p>On the Team Members page, click the user's name and toggle access for each module:</p>
            <ul class="list-disc list-inside space-y-1 pl-2">
                <li>Inventory access</li>
                <li>Payroll access</li>
                <li>Manufacturing access</li>
                <li>Maintenance access</li>
            </ul>
            <p class="text-xs text-gray-500">A user can only access a feature if both (a) the plan allows it and (b) their user account has been granted access.</p>
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">Removing a Team Member</h2>
        </div>
        <div class="p-5 space-y-3 text-sm text-gray-700">
            <p>On the Team Members page, click the user and select <strong>Deactivate</strong>. This immediately revokes their access. Their past activity logs are preserved for audit purposes.</p>
            <p class="text-xs text-gray-500">You cannot deactivate the only Admin account — at least one Admin must remain active.</p>
        </div>
    </div>

    <a href="{{ route('help.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-green-600">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back to Help Center
    </a>

</div>
@endsection
