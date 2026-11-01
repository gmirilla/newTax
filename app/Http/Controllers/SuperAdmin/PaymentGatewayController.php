<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PlatformPaymentCredential;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentGatewayController extends Controller
{
    public function index(): View
    {
        $credential = PlatformPaymentCredential::where('provider', 'paystack')->first();

        return view('superadmin.payment-gateway.index', [
            'hasSecretKey'      => (bool) $credential?->secret_key,
            'hasPublicKey'      => (bool) $credential?->public_key,
            'credentialsSetAt'  => $credential?->credentials_set_at,
            'updatedBy'         => $credential?->updatedByUser,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'secret_key' => 'nullable|string|max:500',
            'public_key' => 'nullable|string|max:500',
        ]);

        $credential = PlatformPaymentCredential::firstOrNew(['provider' => 'paystack']);

        if (!$credential->exists && blank($validated['secret_key'])) {
            return back()->withErrors([
                'secret_key' => 'Secret key is required to set up Paystack.',
            ]);
        }

        $old = [
            'has_secret_key' => (bool) $credential->secret_key,
            'has_public_key' => (bool) $credential->public_key,
        ];

        DB::transaction(function () use ($credential, $validated, $request) {
            if (filled($validated['secret_key'])) {
                $credential->secret_key = $validated['secret_key'];
            }

            if (filled($validated['public_key'])) {
                $credential->public_key = $validated['public_key'];
            }

            $credential->credentials_set_at = now();
            $credential->updated_by_user_id = $request->user()->id;
            $credential->save();
        });

        PlatformPaymentCredential::forgetCache('paystack');

        AuditLog::create([
            'tenant_id'      => null,
            'user_id'        => auth()->id(),
            'event'          => 'superadmin.paystack_credentials_updated',
            'auditable_type' => PlatformPaymentCredential::class,
            'auditable_id'   => $credential->id,
            'old_values'     => $old,
            'new_values'     => [
                'has_secret_key'   => (bool) $credential->secret_key,
                'has_public_key'   => (bool) $credential->public_key,
                'secret_key_last4' => $credential->secret_key ? substr($credential->secret_key, -4) : null,
            ],
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'url'            => request()->fullUrl(),
            'tags'           => 'superadmin,payment-gateway',
        ]);

        return redirect()->route('superadmin.payment-gateway.index')
            ->with('success', 'Paystack credentials updated.');
    }
}
