<?php

namespace App\Http\Controllers;

use App\Models\FirmClientLink;
use App\Services\FirmClientLinkService;
use App\Services\TenancyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FirmClientLinkController extends Controller
{
    public function __construct(
        private readonly FirmClientLinkService $linkService,
        private readonly TenancyService $tenancyService,
    ) {}

    /** Firm's "Clients" page. */
    public function indexAsFirm(Request $request): View
    {
        $firm = $request->user()->tenant;

        $links = $firm->firmLinksAsFirm()->with('clientTenant')->latest()->get();

        return view('partners.clients.index', [
            'firm'           => $firm,
            'activeLinks'    => $links->where('status', FirmClientLink::STATUS_ACTIVE),
            'pendingOutgoing'=> $links->where('status', FirmClientLink::STATUS_PENDING_CLIENT),
            'pendingIncoming'=> $links->where('status', FirmClientLink::STATUS_PENDING_FIRM),
        ]);
    }

    /** Client's "Manage Accounting Partners" page. */
    public function indexAsClient(Request $request): View
    {
        $client = $request->user()->tenant;

        $links = $client->firmLinksAsClient()->with('firmTenant')->latest()->get();

        return view('partners.manage.index', [
            'client'         => $client,
            'activeLinks'    => $links->where('status', FirmClientLink::STATUS_ACTIVE),
            'pendingOutgoing'=> $links->where('status', FirmClientLink::STATUS_PENDING_FIRM),
            'pendingIncoming'=> $links->where('status', FirmClientLink::STATUS_PENDING_CLIENT),
        ]);
    }

    public function requestByFirm(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => 'required|email']);

        try {
            $this->linkService->requestLinkByFirm($request->user()->tenant, $data['email'], $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Invitation sent to {$data['email']}.");
    }

    public function requestByClient(Request $request): RedirectResponse
    {
        $data = $request->validate(['partner_code' => 'required|string|max:20']);

        try {
            $this->linkService->requestLinkByClient($request->user()->tenant, $data['partner_code'], $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Request sent — the firm will need to approve it.');
    }

    public function approve(Request $request, FirmClientLink $link): RedirectResponse
    {
        $this->authorize('approve', $link);

        try {
            $this->linkService->approve($link, $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Partner access approved.');
    }

    public function revoke(Request $request, FirmClientLink $link): RedirectResponse
    {
        $this->authorize('revoke', $link);

        $this->linkService->revoke($link, $request->user());

        return back()->with('success', 'Partner access revoked.');
    }

    public function enter(Request $request, FirmClientLink $link): RedirectResponse
    {
        $this->authorize('enter', $link);

        $this->linkService->enterClientWorkspace($link, $request->user());

        return redirect()->route('dashboard')
            ->with('info', "You're now managing {$link->clientTenant->name}'s books. Use 'Exit Client Workspace' to return.");
    }

    public function exit(): RedirectResponse
    {
        $this->linkService->exitClientWorkspace();

        return redirect()->route('partners.clients.index')
            ->with('success', 'Returned to your firm.');
    }

    public function createClientForm(): View
    {
        return view('partners.clients.create');
    }

    public function createClient(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name'          => 'required|string|max:255',
            'company_email'         => 'required|email|unique:tenants,email',
            'admin_name'            => 'required|string|max:255',
            'admin_email'           => 'required|email|unique:users,email',
            'admin_password'        => 'required|min:8|confirmed',
        ]);

        $result = $this->tenancyService->registerClientForFirm(
            $request->user()->tenant,
            ['name' => $data['company_name'], 'email' => $data['company_email']],
            ['name' => $data['admin_name'], 'email' => $data['admin_email'], 'password' => $data['admin_password']],
            $request->user()
        );

        return redirect()->route('partners.clients.index')
            ->with('success', "{$result['tenant']->name} created and linked.");
    }
}
