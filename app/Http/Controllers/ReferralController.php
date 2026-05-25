<?php

namespace App\Http\Controllers;

use App\Models\ReferralCreditLedger;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReferralController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $request->user()->tenant;

        $tenant->loadMissing('plan');

        // Ensure they have a referral code
        if (!$tenant->referral_code) {
            $tenant->generateReferralCode();
        }

        $referrals = $tenant->referralsMade()
            ->with('referee:id,name,email')
            ->orderByDesc('created_at')
            ->get();

        $ledger = ReferralCreditLedger::where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('referrals.index', compact('tenant', 'referrals', 'ledger'));
    }
}
