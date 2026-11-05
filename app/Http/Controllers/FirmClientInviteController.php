<?php

namespace App\Http\Controllers;

use App\Models\FirmClientLink;
use App\Services\FirmClientLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FirmClientInviteController extends Controller
{
    public function __construct(
        private readonly FirmClientLinkService $linkService,
    ) {}

    public function show(string $token): View|RedirectResponse
    {
        $link = FirmClientLink::where('token', $token)
            ->where('status', FirmClientLink::STATUS_PENDING_CLIENT)
            ->with('firmTenant')
            ->first();

        if (!$link) {
            return redirect()->route('login')
                ->with('error', 'This invitation is invalid or has already been handled.');
        }

        if (!auth()->check()) {
            return redirect()->route('login')->with('info', 'Please log in as a company admin to review this request.');
        }

        return view('auth.accept-partner-invite', compact('link'));
    }

    public function accept(string $token): RedirectResponse
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        try {
            $link = $this->linkService->acceptInviteToken($token, auth()->user());
        } catch (\RuntimeException $e) {
            return redirect()->route('partners.manage.index')->with('error', $e->getMessage());
        }

        return redirect()->route('partners.manage.index')
            ->with('success', "{$link->firmTenant->name} now has bookkeeping access to your account.");
    }
}
