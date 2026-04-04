<?php

use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\TaxController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Nigerian Tax SaaS REST API
|--------------------------------------------------------------------------
| All routes protected by Sanctum token authentication.
| Base URL: /api/v1
*/

Route::prefix('v1')->middleware(['auth:sanctum', 'tenant'])->group(function () {

    // ── Invoices ─────────────────────────────────────────────────────────────
    Route::prefix('invoices')->group(function () {
        Route::get('/',             [InvoiceController::class, 'index']);
        Route::post('/',            [InvoiceController::class, 'store']);
        Route::get('/summary',      [InvoiceController::class, 'summary']);
        Route::get('/{invoice}',    [InvoiceController::class, 'show']);
        Route::post('/{invoice}/payment', [InvoiceController::class, 'recordPayment']);
    });

    // ── Tax Engine ───────────────────────────────────────────────────────────
    Route::prefix('tax')->group(function () {
        Route::get('/compliance-dashboard', [TaxController::class, 'complianceDashboard']);
        Route::post('/vat/compute',         [TaxController::class, 'vatCompute']);
        Route::get('/vat/summary',          [TaxController::class, 'vatSummary']);
        Route::post('/wht/schedule',        [TaxController::class, 'whtSchedule']);
        Route::post('/cit/compute',         [TaxController::class, 'citCompute']);
    });

    // ── Auth Tokens ──────────────────────────────────────────────────────────
    Route::post('/auth/token', function (\Illuminate\Http\Request $request) {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if (!$user || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user]);
    })->withoutMiddleware(['auth:sanctum', 'tenant']);

    Route::delete('/auth/token', function (\Illuminate\Http\Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Token revoked.']);
    });
});
