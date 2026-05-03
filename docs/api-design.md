# API Design Guide

**Purpose:** Lay the groundwork for a mobile app (iOS/Android) that syncs with the existing Laravel backend. The API must be buildable incrementally alongside the web app without disrupting it.

---

## Current State

`routes/api.php` already exists with a partial foundation:
- Sanctum token auth (`auth:sanctum`) is wired up
- `HasApiTokens` is on the `User` model
- Invoice and Tax endpoints are stubbed (referencing `App\Http\Controllers\Api\*`)
- A token issue/revoke flow exists as inline closures

Three things need to be addressed before the API is production-ready:
1. The `TenantMiddleware` calls `view()->share()` — harmless for API but means a single middleware serves two concerns
2. No consistent JSON response envelope — responses are raw model/array dumps
3. No `App\Http\Resources\` classes — serialization is ad-hoc

---

## Principle 1 — Business Logic Stays in Service Classes

Controllers (both web and API) must be thin. All computation, validation logic, and data mutation goes through the existing Service layer.

**The rule:** if a method exists in a Service class, the controller calls it. Controllers never replicate logic.

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│  Web Controller │────▶│  Service Class  │────▶│  Model / DB     │
└─────────────────┘     └─────────────────┘     └─────────────────┘
┌─────────────────┐          ▲
│  API Controller │──────────┘
└─────────────────┘
```

Both the web `InvoiceController` and the API `Api\InvoiceController` call `InvoiceService::create()`. The service is the single source of truth.

**Existing services and their API relevance:**

| Service              | Used by mobile?       | Key methods                                      |
|----------------------|-----------------------|--------------------------------------------------|
| `InvoiceService`     | Yes — core feature    | `create`, `recordPayment`, `getDashboardSummary` |
| `VatService`         | Read-only display     | `computeMonthlyReturn`, `getDashboardSummary`    |
| `WhtService`         | Read-only display     | `generateMonthlySchedule`, `getPendingRemittance`|
| `CitService`         | Read-only display     | `compute`, `getDashboardSummary`                 |
| `ReportService`      | Dashboard only        | `getComplianceDashboard`                         |
| `BookkeepingService` | Read-only display     | `getProfitAndLoss`, `getBalanceSheet`            |
| `PayeService`        | Staff payslips only   | `generatePayslip`                                |
| `PaystackService`    | Not on mobile (yet)   | —                                                |

---

## Principle 2 — Separate API Tenant Middleware

The current `TenantMiddleware` is not suitable for API routes as-is because it calls `view()->share()`, which is a web concern. It also redirects to `superadmin.dashboard` — a redirect makes no sense for a JSON API consumer.

**Create `app/Http/Middleware/ApiTenantMiddleware.php`** as a lean version:

```php
public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();   // resolved by auth:sanctum before this runs

    if (!$user || !$user->tenant_id) {
        return response()->json(['message' => 'No tenant associated with this account.'], 403);
    }

    if (!$user->is_active) {
        return response()->json(['message' => 'Your account has been deactivated.'], 403);
    }

    $tenant = $user->load('tenant.plan')->tenant;

    if (!$tenant || !$tenant->is_active) {
        return response()->json(['message' => 'Company account is inactive.'], 403);
    }

    if (!$tenant->subscriptionActive()) {
        return response()->json(['message' => 'Subscription expired.'], 402);
    }

    app()->instance('currentTenant', $tenant);  // same binding — services use this

    return $next($request);
}
```

Register it as `'api.tenant'` in `bootstrap/app.php` alongside the existing `'tenant'` alias.

Update `routes/api.php` to use `api.tenant` instead of `tenant`:

```php
Route::prefix('v1')->middleware(['auth:sanctum', 'api.tenant'])->group(function () {
    // ...
});
```

The web routes keep using `tenant`. The two middlewares share the same `app()->instance('currentTenant')` binding so all Service classes work identically in both contexts.

**Tenant isolation guarantee:** Every API request resolves its tenant from `$request->user()->tenant_id` — the authenticated token bearer's own tenant. A mobile client cannot request data from a different tenant by changing a URL parameter; the tenant is always derived from the token, never from user input.

---

## Principle 3 — Consistent JSON Response Shape

All API responses — success and error — use the same envelope. This is what the mobile app parses.

### Success

```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "page": 1,
    "per_page": 25,
    "total": 143
  }
}
```

`meta` is only present on paginated list responses. Single-resource and action responses omit it.

### Error

```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

`errors` is only present on validation failures (422). All other errors use `message` only.

### Implementation — base API controller

Create `app/Http/Controllers/Api/ApiController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiController extends Controller
{
    protected function success(mixed $data, int $status = 200): JsonResponse
    {
        $payload = ['success' => true, 'data' => $data];

        if ($data instanceof LengthAwarePaginator) {
            $payload['meta'] = [
                'page'     => $data->currentPage(),
                'per_page' => $data->perPage(),
                'total'    => $data->total(),
            ];
            $payload['data'] = $data->items();
        }

        return response()->json($payload, $status);
    }

    protected function error(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        $payload = ['success' => false, 'message' => $message];

        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
```

All API controllers extend `ApiController`, never the base `Controller` directly.

### Validation errors — register in bootstrap/app.php

```php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (ValidationException $e, Request $request) {
        if ($request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors'  => $e->errors(),
            ], 422);
        }
    });

    $exceptions->render(function (AuthenticationException $e, Request $request) {
        if ($request->is('api/*')) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }
    });
})
```

---

## API Resource Classes

Never return raw Eloquent models from API controllers — model attributes change and break mobile clients. Use `JsonResource` classes in `app/Http/Resources/` to define stable contracts.

```
app/Http/Resources/
├── InvoiceResource.php
├── InvoiceCollection.php
├── QuoteResource.php
├── CustomerResource.php
├── VendorResource.php
├── TransactionResource.php
├── ExpenseResource.php
├── EmployeeResource.php
├── PayrollItemResource.php   (payslip)
└── TaxSummaryResource.php
```

Example:

```php
// app/Http/Resources/InvoiceResource.php
class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'number'         => $this->invoice_number,
            'status'         => $this->status,
            'issue_date'     => $this->issue_date->toDateString(),
            'due_date'       => $this->due_date->toDateString(),
            'subtotal'       => (float) $this->subtotal,
            'vat_amount'     => (float) $this->vat_amount,
            'total'          => (float) $this->total_amount,
            'amount_paid'    => (float) $this->amount_paid,
            'balance_due'    => (float) $this->balance_due,
            'customer'       => [
                'id'   => $this->customer?->id,
                'name' => $this->customer?->name,
            ],
            'updated_at'     => $this->updated_at->toIso8601String(),
        ];
    }
}
```

The `updated_at` field is required on every resource — the mobile sync engine uses it.

---

## Planned API Endpoints

Base URL: `/api/v1` — all require `Authorization: Bearer {token}`.

### Authentication

| Method | Endpoint         | Description                          |
|--------|------------------|--------------------------------------|
| POST   | `/auth/token`    | Issue token (email + password)       |
| DELETE | `/auth/token`    | Revoke current token                 |
| GET    | `/auth/me`       | Authenticated user + tenant info     |

### Dashboard

| Method | Endpoint          | Description                          |
|--------|-------------------|--------------------------------------|
| GET    | `/dashboard`      | Compliance dashboard summary         |

### Invoices

| Method | Endpoint                      | Description              |
|--------|-------------------------------|--------------------------|
| GET    | `/invoices`                   | Paginated list           |
| POST   | `/invoices`                   | Create invoice           |
| GET    | `/invoices/{id}`              | Single invoice           |
| PUT    | `/invoices/{id}`              | Update invoice           |
| POST   | `/invoices/{id}/payment`      | Record payment           |
| POST   | `/invoices/{id}/void`         | Void invoice             |
| GET    | `/invoices/summary`           | Counts + totals          |

### Quotes

| Method | Endpoint                      | Description              |
|--------|-------------------------------|--------------------------|
| GET    | `/quotes`                     | Paginated list           |
| POST   | `/quotes`                     | Create quote             |
| GET    | `/quotes/{id}`                | Single quote             |
| PUT    | `/quotes/{id}`                | Update quote             |
| POST   | `/quotes/{id}/accept`         | Accept quote             |
| POST   | `/quotes/{id}/decline`        | Decline quote            |

### Customers & Vendors

| Method | Endpoint           | Description              |
|--------|--------------------|--------------------------|
| GET    | `/customers`       | List customers           |
| POST   | `/customers`       | Create customer          |
| GET    | `/customers/{id}`  | Single customer          |
| PUT    | `/customers/{id}`  | Update customer          |
| GET    | `/vendors`         | List vendors             |
| POST   | `/vendors`         | Create vendor            |
| GET    | `/vendors/{id}`    | Single vendor            |

### Transactions & Expenses

| Method | Endpoint                      | Description              |
|--------|-------------------------------|--------------------------|
| GET    | `/transactions`               | Paginated list           |
| POST   | `/transactions`               | Record transaction       |
| GET    | `/transactions/{id}`          | Single transaction       |
| GET    | `/expenses`                   | Paginated list           |
| POST   | `/expenses`                   | Submit expense           |
| GET    | `/expenses/{id}`              | Single expense           |
| PUT    | `/expenses/{id}`              | Update expense           |

### Tax

| Method | Endpoint                      | Description              |
|--------|-------------------------------|--------------------------|
| GET    | `/tax/dashboard`              | Full compliance summary  |
| GET    | `/tax/vat`                    | VAT returns list         |
| GET    | `/tax/vat/compute`            | Compute current period   |
| GET    | `/tax/wht`                    | WHT records list         |
| GET    | `/tax/cit`                    | CIT records list         |

### Payroll (staff-facing)

| Method | Endpoint                      | Description              |
|--------|-------------------------------|--------------------------|
| GET    | `/payroll/my-payslips`        | Authenticated user's payslips (staff) |
| GET    | `/payroll/my-payslips/{id}`   | Single payslip           |

### Sync

| Method | Endpoint          | Description                                               |
|--------|-------------------|-----------------------------------------------------------|
| GET    | `/sync`           | Pull all changed records since `?since=ISO8601_timestamp` |

The `/sync` endpoint is the mobile offline engine. It returns a single payload:

```json
{
  "success": true,
  "data": {
    "invoices":     [...],
    "quotes":       [...],
    "customers":    [...],
    "vendors":      [...],
    "expenses":     [...],
    "transactions": [...],
    "deleted_ids":  { "invoices": [12, 45], "quotes": [3] }
  },
  "meta": {
    "synced_at": "2026-05-03T10:00:00Z"
  }
}
```

The `?since` parameter filters every collection to `updated_at > since`. On first install, `since` is omitted and the full dataset is returned.

---

## Offline Sync Strategy

### Conflict Resolution

Use **last-write-wins** on `updated_at`. When a device pushes a queued mutation:

1. Client sends the record with the `updated_at` it last saw (its local version)
2. Server compares: if `server.updated_at > client.updated_at`, the server version wins and is returned in the response
3. If `server.updated_at <= client.updated_at`, the update is applied

This covers the vast majority of SME usage patterns where a single user edits records, and conflict rates are extremely low.

### Mutation Queue

The mobile app queues write operations locally (SQLite) when offline. On reconnect:

1. Process the queue in order (FIFO)
2. For each item, attempt the API call
3. On success — remove from queue, update local record with server response
4. On 409 Conflict — server version wins, update local record, discard queued mutation
5. On 4xx (validation) — surface to user, keep in queue for correction
6. On 5xx or network failure — retry with exponential backoff

### What to sync to mobile

Not all data needs to be available offline. Tier by priority:

| Resource              | Offline read | Offline write |
|-----------------------|-------------|---------------|
| Invoices              | Yes          | Yes (create, record payment) |
| Quotes                | Yes          | Yes (create)  |
| Customers / Vendors   | Yes          | Yes (create)  |
| Expenses              | Yes          | Yes (submit)  |
| Transactions          | Yes          | Read-only     |
| Tax returns           | Yes          | No — file online only |
| Payroll / payslips    | Yes          | No            |
| Reports               | No           | No            |
| Settings / Billing    | No           | No            |

---

## Auth Token Improvements

The current inline closure in `routes/api.php` needs to move to a proper controller and be hardened:

```php
// Improvements needed:
// 1. Move to App\Http\Controllers\Api\AuthController
// 2. Rate-limit: throttle:5,1 (5 attempts per minute)
// 3. Check is_active before issuing token
// 4. Check tenant subscription before issuing token
// 5. Token name should identify the device: 'mobile-ios', 'mobile-android'
// 6. Return user + tenant info (mobile needs it for initial setup)
// 7. Support token expiry (createToken with expiration)
```

---

## File Structure

```
app/Http/Controllers/Api/
├── ApiController.php          ← base class (success/error helpers)
├── AuthController.php         ← token issue/revoke/me
├── DashboardController.php
├── InvoiceController.php      ← already stubbed in routes/api.php
├── QuoteController.php
├── CustomerController.php
├── VendorController.php
├── TransactionController.php
├── ExpenseController.php
├── TaxController.php          ← already stubbed in routes/api.php
├── PayrollController.php      ← staff payslips only
└── SyncController.php         ← /sync endpoint

app/Http/Resources/
├── InvoiceResource.php
├── QuoteResource.php
├── CustomerResource.php
├── VendorResource.php
├── TransactionResource.php
├── ExpenseResource.php
├── PayrollItemResource.php
└── TaxSummaryResource.php

app/Http/Middleware/
├── TenantMiddleware.php       ← web only (keep as-is)
└── ApiTenantMiddleware.php    ← api only (new)
```

---

## Implementation Order

Build in this sequence — each step is independently useful even if the mobile app isn't started yet:

1. **`ApiTenantMiddleware`** — decouple API tenant resolution from web middleware
2. **`ApiController` base class** — establish the response envelope
3. **Exception handler wiring** — consistent 422/401 JSON responses
4. **`AuthController`** — move inline token logic, harden it
5. **Resource classes** — `InvoiceResource`, `CustomerResource`, `VendorResource` first
6. **`Api\InvoiceController`** — highest mobile priority, calls `InvoiceService`
7. **`Api\CustomerController`**, **`Api\VendorController`** — needed for invoice creation
8. **`Api\ExpenseController`** — second most-used on mobile
9. **`Api\TaxController`** — read-only, straightforward
10. **`SyncController`** — build last, after individual endpoints are stable
