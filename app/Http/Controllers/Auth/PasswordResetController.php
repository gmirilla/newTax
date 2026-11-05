<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TurnstileVerifier;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function __construct(
        private readonly TurnstileVerifier $turnstile,
    ) {}

    public function showRequestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        // Honeypot: a hidden "website" field only an automated submission would fill in.
        // Treat a failed Turnstile check the same way — never reveal bot detection to the caller.
        if ($request->filled('website') || !$this->turnstile->verify($request->input('cf-turnstile-response'), $request->ip())) {
            return back()->with('status', $this->genericSentMessage());
        }

        $request->validate(['email' => 'required|email']);

        // Always show the same message whether or not the email exists — never
        // let a reset-link request reveal which addresses have an account.
        Password::sendResetLink($request->only('email'));

        return back()->with('status', $this->genericSentMessage());
    }

    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()],
        ]);

        $status = Password::reset(
            $data,
            function ($user) use ($data) {
                $user->update(['password' => Hash::make($data['password'])]);
            }
        );

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))
                ->with('error', 'This password reset link is invalid or has expired. Please request a new one.');
        }

        return redirect()->route('login')
            ->with('success', 'Your password has been reset. Please log in.');
    }

    private function genericSentMessage(): string
    {
        return "If an account exists for that email, we've sent a link to reset the password. It expires in 60 minutes.";
    }
}
