<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use Illuminate\View\View;

class ReferralReportController extends Controller
{
    public function index(): View
    {
        $referrals = Referral::with(['referrer:id,name,email', 'referee:id,name,email'])
            ->orderByDesc('created_at')
            ->paginate(50);

        $stats = [
            'total'     => Referral::count(),
            'pending'   => Referral::where('status', Referral::STATUS_PENDING)->count(),
            'rewarded'  => Referral::where('status', Referral::STATUS_REWARDED)->count(),
            'credit_ngn'=> Referral::where('status', Referral::STATUS_REWARDED)->sum('reward_ngn'),
        ];

        return view('superadmin.referrals.index', compact('referrals', 'stats'));
    }
}
