<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserInvite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class InviteController extends Controller
{
    public function show(string $token): View|RedirectResponse
    {
        $invite = UserInvite::where('token', $token)->with('tenant')->first();

        if (!$invite || $invite->isAccepted() || $invite->isExpired()) {
            return redirect()->route('login')
                ->with('error', 'This invitation link is invalid or has expired. Ask your admin to send a new one.');
        }

        // If user is already logged in with a different account, log them out first
        if (auth()->check() && auth()->user()->email !== $invite->email) {
            auth()->logout();
        }

        return view('auth.accept-invite', compact('invite'));
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invite = UserInvite::where('token', $token)->with('tenant')->first();

        if (!$invite || $invite->isAccepted() || $invite->isExpired()) {
            return redirect()->route('login')
                ->with('error', 'This invitation link is invalid or has expired.');
        }

        $data = $request->validate([
            'name'     => 'required|string|max:150',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        // If this email already has an account (e.g. previously removed), reactivate it
        $user = User::where('email', $invite->email)->first();

        if ($user) {
            $user->update([
                'tenant_id' => $invite->tenant_id,
                'role'      => $invite->role,
                'is_active' => true,
                'name'      => $data['name'],
                'password'  => Hash::make($data['password']),
            ]);
        } else {
            $user = User::create([
                'tenant_id' => $invite->tenant_id,
                'name'      => $data['name'],
                'email'     => $invite->email,
                'password'  => Hash::make($data['password']),
                'role'      => $invite->role,
                'is_active' => true,
            ]);
        }

        $invite->update(['accepted_at' => now()]);

        $invite->tenant->invalidateLimitCache('users');

        auth()->login($user);

        return redirect()->route('dashboard')
            ->with('success', 'Welcome to ' . $invite->tenant->name . '! Your account is ready.');
    }
}
