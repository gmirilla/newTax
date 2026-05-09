<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class MarketingController extends Controller
{
    public function home()
    {
        $plans = Plan::where('is_active', true)
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->limit(3)
            ->get();

        return view('marketing.home', compact('plans'));
    }

    public function features()
    {
        return view('marketing.features');
    }

    public function pricing()
    {
        $plans = Plan::where('is_active', true)
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->get();

        return view('marketing.pricing', compact('plans'));
    }

    public function about()
    {
        return view('marketing.about');
    }

    public function contact()
    {
        return view('marketing.contact');
    }

    public function contactSubmit(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'phone'   => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'subject' => 'required|string|in:general,demo,support,billing,partnership',
            'message' => 'required|string|max:3000',
        ]);

        return back()->with('success', 'Thank you for reaching out. Our team will respond within one business day.');
    }
}
