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
            ->where('expires_at', '>', now())
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

    private function guardTeamMember(User $user, $tenant): void
    {
        if ($user->tenant_id !== $tenant->id) {
            abort(403);
        }
    }
}
