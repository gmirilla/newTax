<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TenancyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        private readonly TenancyService $tenancyService
    ) {}

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            if ($user->isSuperAdmin()) {
                return redirect()->intended(route('superadmin.dashboard'));
            }

            $this->tenancyService->setCurrentTenant($user);

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $request->validate([
            'company_name'    => 'required|string|max:255',
            'company_email'   => 'required|email|unique:tenants,email',
            'tin'             => 'nullable|string|max:20',
            'annual_turnover' => 'nullable|numeric|min:0',
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|unique:users,email',
            'password'        => 'required|min:8|confirmed',
        ]);

        $result = $this->tenancyService->registerTenant(
            [
                'name'            => $request->company_name,
                'email'           => $request->company_email,
                'phone'           => $request->company_phone,
                'state'           => $request->state,
                'tin'             => $request->tin,
                'annual_turnover' => $request->annual_turnover ?? 0,
                'business_type'   => $request->business_type ?? 'limited_liability',
            ],
            [
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => $request->password,
            ]
        );

        Auth::login($result['admin']);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', "Welcome to NaijaBooks! Your account for {$result['tenant']->name} is ready.");
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
