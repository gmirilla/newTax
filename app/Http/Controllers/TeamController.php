<?php

namespace App\Http\Controllers;

use App\Mail\TeamInvitation;
use App\Models\User;
use App\Models\UserInvite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        $tenant  = $request->user()->tenant;
        $users   = $tenant->users()->orderBy('name')->get();

        $invites = UserInvite::where('tenant_id', $tenant->id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now()->subDays(7))  // include recently expired for resend
            ->with('inviter')
            ->latest()
            ->get();

        return view('team.index', compact('users', 'invites', 'tenant'));
    }

    public function invite(Request $request): RedirectResponse
    {
        $tenant = $request->user()->tenant;

        if (!$tenant->withinLimit('users')) {
            $limit = $tenant->plan?->limit('users') ?? 1;
            return back()->with('error', "Your plan allows up to {$limit} team members. Upgrade to add more.");
        }

        $data = $request->validate([
            'email' => 'required|email|max:200',
            'role'  => 'required|in:admin,accountant,staff',
        ]);

        if ($tenant->users()->where('email', $data['email'])->exists()) {
            return back()->with('error', 'That email address is already a member of your team.');
        }

        // Cancel any existing pending invite for this email
        UserInvite::where('tenant_id', $tenant->id)
            ->where('email', $data['email'])
            ->whereNull('accepted_at')
            ->delete();

        $invite = UserInvite::create([
            'tenant_id'  => $tenant->id,
            'email'      => $data['email'],
            'role'       => $data['role'],
            'token'      => Str::random(64),
            'invited_by' => $request->user()->id,
            'expires_at' => now()->addHours(72),
        ]);

        try {
            Mail::to($data['email'])->send(new TeamInvitation($invite, $tenant));
        } catch (\Throwable $e) {
            $invite->delete();
            return back()->with('error', 'Failed to send invitation email. Please try again.');
        }

        return back()->with('success', "Invitation sent to {$data['email']}.");
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $tenant = $request->user()->tenant;
        $this->guardTeamMember($user, $tenant);

        $data = $request->validate(['role' => 'required|in:admin,accountant,staff']);

        if ($user->role === 'admin' && $data['role'] !== 'admin') {
            if ($tenant->users()->where('role', 'admin')->count() <= 1) {
                return back()->with('error', 'Cannot change role — this is the last admin on the account.');
            }
        }

        $user->update(['role' => $data['role']]);

        return back()->with('success', "{$user->name}'s role updated to " . ucfirst($data['role']) . '.');
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        $tenant = $request->user()->tenant;
        $this->guardTeamMember($user, $tenant);

        if ($request->user()->id === $user->id) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => !$user->is_active]);
        $action = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "{$user->name} has been {$action}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $tenant = $request->user()->tenant;
        $this->guardTeamMember($user, $tenant);

        if ($request->user()->id === $user->id) {
            return back()->with('error', 'You cannot remove your own account.');
        }

        if ($user->role === 'admin' && $tenant->users()->where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Cannot remove — this is the last admin on the account.');
        }

        $user->delete();
        $tenant->invalidateLimitCache('users');

        return back()->with('success', "{$user->name} has been removed from your team.");
    }

    public function cancelInvite(Request $request, UserInvite $invite): RedirectResponse
    {
        if ($invite->tenant_id !== $request->user()->tenant->id) {
            abort(403);
        }

        $invite->delete();

        return back()->with('success', 'Invitation cancelled.');
    }

    public function resendInvite(Request $request, UserInvite $invite): RedirectResponse
    {
        $tenant = $request->user()->tenant;

        if ($invite->tenant_id !== $tenant->id) {
            abort(403);
        }

        if (! $tenant->withinLimit('users')) {
            $limit = $tenant->plan?->limit('users') ?? 1;
            return back()->with('error', "Your plan allows up to {$limit} team members. Upgrade to add more.");
        }

        // Refresh token and extend expiry
        $invite->update([
            'token'      => \Illuminate\Support\Str::random(64),
            'expires_at' => now()->addHours(72),
        ]);

        try {
            \Illuminate\Support\Facades\Mail::to($invite->email)
                ->send(new \App\Mail\TeamInvitation($invite, $tenant));
        } catch (\Throwable) {
            return back()->with('error', 'Failed to resend invitation email. Please try again.');
        }

        return back()->with('success', "Invitation resent to {$invite->email}. Link valid for 72 hours.");
    }

    public function updateModuleAccess(Request $request, User $user): RedirectResponse
    {
        $tenant = $request->user()->tenant;
        $this->guardTeamMember($user, $tenant);

        if ($request->user()->id === $user->id) {
            return back()->with('error', 'You cannot change your own module access.');
        }

        if ($user->isAdmin()) {
            return back()->with('error', 'Admins always have full access — module flags do not apply.');
        }

        $modules  = array_keys(\App\Models\User::MODULE_LIST);
        $incoming = $request->input('modules', []);

        $access = [];
        foreach ($modules as $key) {
            $access[$key] = in_array($key, (array) $incoming);
        }

        $user->update(['module_access' => $access]);

        return back()->with('success', "{$user->name}'s module access updated.");
    }

    private function guardTeamMember(User $user, \App\Models\Tenant $tenant): void
    {
        if ($user->tenant_id !== $tenant->id) {
            abort(403);
        }
    }
}
