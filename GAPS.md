# AccountTaxNG — Known Gaps & Follow-Through Tracker

Updated: 2026-09-12 (reviewed against current codebase — see changes below).
Tick items off as each is completed.

Phase 3 (Trial Flow) completed 2026-04-30.

**2026-09-12 review — what changed since 2026-05-21:** two items below were
confirmed resolved and checked off (Audit log viewer, PDF logo fallback); the
"Uncommitted Work" section is now obsolete (everything it described is long
since committed and covered by the completed phases below) and marked as
such rather than deleted. Every other open item was re-checked against the
current code and is still accurate/open — see the note on each. This review
did not cover the Online Storefront feature or its own follow-ups (categories,
services, VAT toggle, image limits, search, discovery) — those are tracked
separately in [TODO.md](TODO.md), which post-dates this file's last update.

---

## Critical Bugs

- [x] **InvoiceController: missing `use` for Excel facade** — fixed 2026-04-30. Added `use Maatwebsite\Excel\Facades\Excel;` to [InvoiceController.php](app/Http/Controllers/InvoiceController.php).
- [x] **Invoice edit form couldn't save — wrong field name** — fixed 2026-11-02.
  [resources/views/invoices/edit.blade.php](resources/views/invoices/edit.blade.php)'s
  date input was named `issue_date` and read `$invoice->issue_date` (an
  attribute that doesn't exist on the model), while `InvoiceRequest`
  validates `invoice_date` (the real column). Every edit submission failed
  with "The invoice date field is required." — reported live by a user.
  The create form already used the correct field name; this was drift
  specific to the edit view. Renamed to `invoice_date` in both the input
  name and the value binding.
  - **Found along the way**: all 6 tests in
    [tests/Feature/InvoiceTest.php](tests/Feature/InvoiceTest.php) used the
    old `/** @test */` docblock annotation, which PHPUnit 12 (installed
    here) no longer recognises — confirmed via `--filter`, it reported "No
    tests found." Every one had been silently dead, which is exactly how
    this bug shipped untested. Renamed all 6 to `test_`-prefixed methods
    (all now genuinely pass) and added a new HTTP-level regression test
    (`test_editing_an_invoice_via_the_edit_form_succeeds`) that submits the
    edit form's exact field set and would have caught this bug immediately.
  - **Same dead-annotation pattern also found, not yet fixed, in
    [tests/Feature/TaxComplianceTest.php](tests/Feature/TaxComplianceTest.php)** —
    a newly-discovered instance alongside the three already documented in
    [TODO.md](TODO.md) (`CitCalculationTest`, `PayeCalculationTest`,
    `WhtCalculationTest`). Worth a dedicated pass to sweep the whole suite
    for this pattern rather than fixing one file at a time as bugs surface it.

---

## Uncommitted Work — ✅ obsolete (superseded 2026-09-12)

This section described a since-committed working state as of 2026-05-21;
every item it lists (public invoice links, VAT/WHT reactivity, preview
modals, DB-driven plans, subscription helpers, plan gating) is confirmed
present in the current codebase and covered by the completed phases below.
Kept for history rather than deleted.

<details>
<summary>Original note (2026-05-21)</summary>

Everything since commit `251f0fb` (Added Company Logo support) is unstaged. A single commit covers:
- Public invoice UUID links (`PublicInvoiceController`, `Invoice::public_token`, migrations)
- VAT/WHT reactivity fix on create forms
- Preview modal on invoice & quote create forms
- Phase 1: DB-driven plans (`Plan` model, migrations, `PlanSeeder`, SuperAdmin plan CRUD)
- Phase 1: `Tenant` subscription helpers (`planAllows`, `withinLimit`, `isOnTrial`, etc.)
- Phase 2: `RequiresPlan` middleware, route gating, billing page, sidebar locked states

</details>

---

## Monetisation Phases

### Phase 3 — Trial Flow ✅ DONE (2026-04-30)
- [x] On registration `TenancyService` finds the first active plan with `trial_days > 0` (Growth) and calls `assignPlan()` with `status='trialing'` and `trial_ends_at`
- [x] `Tenant::assignPlan()` extended to accept optional `$trialEndsAt` parameter
- [x] `Tenant::planAllows()` now gates on `subscriptionActive()` — expired trial loses Growth features immediately
- [x] `Tenant::withinLimit()` falls back to `Plan::LIMIT_DEFAULTS` when subscription inactive (Free-level limits enforced during expired-trial window)
- [x] Trial banners in layout: blue (active, >3 days), amber (≤3 days), red (expired) — each with upgrade CTA
- [x] Impersonation exit banner added (orange strip with Exit button)
- [x] `subscriptions:downgrade-expired-trials` artisan command — bulk-downgrades `trialing` tenants past `trial_ends_at` to Free plan
- [x] Scheduled daily at 00:15 in `routes/console.php`

### Phase 4 — Paystack Integration ✅ DONE (2026-04-30)
- [x] No external package needed — uses Laravel HTTP client (`Http::withToken`) against `https://api.paystack.co`
- [x] `config/paystack.php` reads `PAYSTACK_SECRET_KEY` / `PAYSTACK_PUBLIC_KEY` from env (both already present in `.env.example`)
- [x] `PaystackService` — `initializeTransaction()`, `verifyTransaction()`, `getSubscription()`, `disableSubscription()`
- [x] `BillingController::checkout(Plan $plan)` — validates plan, builds payload, redirects to Paystack `authorization_url`; attaches `plan` code for recurring subscriptions if `paystack_plan_code` is set
- [x] `BillingController::callback()` — verifies transaction, checks `metadata.tenant_id` against authenticated user, activates plan with 31-day expiry, persists Paystack customer/subscription codes
- [x] `paystack_plan_code` column added to `plans` table (migration `2026_04_30_100000`)
- [x] `Plan::$fillable` and SuperAdmin plan form/controller updated to expose `paystack_plan_code`
- [x] Upgrade CTAs in `billing/index.blade.php` now live links to `billing.checkout`; "Contact to Downgrade" shown for Free plan when not current

### Phase 5 — Webhook Handler ✅ DONE (2026-04-30)
- [x] `POST /webhooks/paystack` — outside auth middleware, CSRF-excluded in `bootstrap/app.php`
- [x] HMAC-SHA512 signature verification (`x-paystack-signature` header vs `hash_hmac('sha512', rawBody, secretKey)`)
- [x] `webhook_events` table — stores every incoming event with `status` (processing/processed/failed), `event_id` for idempotency, full JSON payload
- [x] Compound unique on `(source, event_id)` prevents duplicate row creation
- [x] `charge.success` — activates/extends plan; expiry set to `max(now, current_expiry) + 31 days` (handles both first payment and renewals)
- [x] `subscription.create` — stores `paystack_subscription_code` on tenant
- [x] `subscription.disable` + `subscription.not_renew` — downgrades to Free plan, sets `cancelled`
- [x] `invoice.payment_failed` — sets `subscription_status = suspended` (grace window before cancellation)
- [x] `invoice.update` (status=success) — treated same as `charge.success` (renewal confirmation)
- [x] Always returns 200 on processing errors (prevents Paystack retry loops); errors logged to `Log::error`
- [x] `resolveTenant()` — prefers `metadata.tenant_id` (checkout-set), falls back to `paystack_customer_id` (renewal events)

### Phase 6 — Full Billing UI ✅ DONE (2026-04-30)
- [x] Payment history table on `/billing` (from `subscription_payments` table, last 12 payments)
- [x] Cancel subscription flow — Alpine.js modal with confirmation, POST to `billing.cancel`
- [x] Upgrade/downgrade between paid plans — hybrid model: upgrades immediate with proration, downgrades end-of-cycle via `next_plan_id`
- [x] Pending plan change notice banner with "Keep current plan" escape hatch
- [x] Context-aware CTAs per plan (Upgrade / Downgrade / Pending / Current / Contact Sales)
- [ ] Receipt download / email — deferred to Phase 7

### Phase 7 — Hardening ✅ DONE (2026-05-01)
- [x] Grace period: 7-day window after `subscription_expires_at` — computed via `isInGracePeriod()` / `graceDaysLeft()` helpers; `subscriptionActive()` extended +7 days; nightly job uses `now()->subDays(7)` threshold; orange banner in app layout and billing page
- [x] Email notifications: `TrialEndingSoon` (sent by nightly job, 3 days before expiry), `SubscriptionActivated` (BillingController::callback), `PaymentFailed` (webhook handler), `SubscriptionCancelled` (cancel + downgrade actions) — all in `app/Mail/`, views in `resources/views/emails/`
- [x] SuperAdmin billing controls: dashboard stats updated to use `Plan::withCount('tenants')` by plan_id; `extendTrial` endpoint (`POST /superadmin/companies/{tenant}/extend-trial`) with modal; `grace` added as valid status option in subscription update form; grace badge shown in company card
- [x] Cache `withinLimit` counts: `Cache::remember($key, 300, ...)` for all resource counts; `invalidateLimitCache('invoices_per_month')` called in `InvoiceController::store()` after creation

---

## Proposed Future Features

> Status key: 💡 Proposed · 🔍 Needs design · 🏗 Ready to build · ⏸ Deferred

---

### Multi-Site / Multi-Branch Support 💡
**What:** A single tenant (company) operates multiple physical locations or branches. Each site has its own inventory, staff, and invoices while sharing one subscription, one chart of accounts, and consolidated financials.

**Design summary (agreed 2026-05-20):**
- Add `sites` table with `(tenant_id, name, code, address, is_active)`
- Add nullable `site_id` to: `users`, `inventory_items`, `invoices`, `employees`, `journal_entries` — null = company-wide
- `SiteScope` as an opt-in scope (not global) activated via `app('currentSite')` — tenant scope remains outermost isolation boundary
- User access: nullable `site_id` on user; null = sees all sites (admin); non-null = scoped to that site only
- Plan limits count **across all sites** for the tenant, never per-site
- Inter-site stock transfers need a dedicated `stock_transfers` model (both `from_site_id` + `to_site_id`) to avoid double-counting costs in consolidated reports
- GL entries must carry `site_id` to support per-site P&L drill-down

**Implementation order:** sites table → site_id on users + inventory + invoices → SiteScope middleware → consolidated reporting baseline → per-site GL posting

**Risk:** Forgetting `site_id` on a new model creates silent cross-site data leakage. Requires a checklist item in the dev process once adopted.

---

### Enterprise Platform Invoice Emails 🔍
**What:** When a superadmin marks a platform invoice as "Sent", it should email the invoice PDF to the tenant's admin.

**Current state:** `PlatformInvoiceController::send()` has a `// TODO: dispatch SendPlatformInvoiceEmail job` comment. The mark-sent action works but no email is dispatched.

**Needed:** `SendPlatformInvoiceEmail` queued Mailable (attach PDF via DomPDF), dispatched from `PlatformInvoiceController::send()`. Follow the same pattern as `SendInvoiceEmail`.

---

### API Access (Public API for Tenants) 🔍
**What:** Tenants on plans with `api_access = true` can generate API keys and access company data programmatically (invoices, customers, transactions).

**Current state (re-checked 2026-09-12):** `routes/api.php` has grown further
than last noted — real, working endpoints exist today: `POST /auth/token` /
`DELETE /auth/token` (email+password → Sanctum personal access token, not
tenant-generated API keys), Invoices (`index`/`store`/`summary`/`show`/`recordPayment`),
and a Tax Engine group (`compliance-dashboard`, `vat/compute`, `vat/summary`,
`wht/schedule`, `cit/compute`) — all behind `auth:sanctum` + `tenant`
middleware. Still missing exactly what was true before: **no `plan:api_access`
gate anywhere in this file**, no `api_keys` table, no tenant-facing key
generation UI, no per-key rate limiting. The auth model today is
"log in with your normal password to get a token," not API keys — worth
deciding whether that's actually the intended tenant-facing auth model going
forward, or whether real API keys are still wanted.

**Needed:** `api_keys` table, key generation UI in Settings, `ApiKeyMiddleware`
to authenticate bearer tokens (or decide the existing Sanctum-token approach
is the permanent design and just add the plan gate + rate limiting to it),
`plan:api_access` applied to `routes/api.php`, rate limiting per key/token.

---

### Quote Public Links ✅ RESOLVED (2026-11-01)
See the "Public Links" section below for the full writeup.

---

### Vendor Quick-Create in Invoice / Quote Forms ✅ RESOLVED (2026-09-13)
**What was actually true:** invoices/quotes are customer-facing sales documents (`customer_id`, no `vendor_id` concept) — there's no business case for selecting a vendor there. Vendor quick-create already worked correctly where vendors are actually used: the Expense form. Confirmed with the user; this part of the gap was a mislabeling, not a real gap, and was intentionally left alone.

**What was built instead:** full vendor management, which was completely missing (only `quickStore` existed — no index/edit/update/destroy, no views, no policy):
- `VendorPolicy` (`viewAny`/`update` → accountant+, `delete` → admin only)
- `VendorController@index/edit/update/destroy` + routes under the existing `role:admin,accountant` group
- `resources/views/vendors/index.blade.php` (list + the same quick-create modal, reused) and `edit.blade.php` (full field editor: contact info, vendor type, WHT rate/exemption + reason, active toggle)
- Delete is blocked (guard-rail, mirrors `InventoryCategoryController`) when a vendor has existing expenses or WHT records — deactivate instead
- "Vendors" nav link added under Sales & Finance
- `tests/Feature/VendorManagementTest.php` (7 tests)

---

### Accountant / Partner Programme ✅ BUILT (2026-11-05, v1 — access/management only)
**What was built:** an accounting firm (its own `Tenant`, normal signup, own
staff/admin) can manage multiple client companies from one login. Both
onboarding paths are supported: the firm creates a brand-new client from
scratch (auto-linked, no approval needed), and an already-independent
tenant and a firm can mutually link — either side can initiate (firm
invites a client by the client admin's email; client invites a firm by its
`partner_code`), and the other side approves.

**How it works:** reuses the existing superadmin-impersonation mechanism
(full identity substitution via `auth()->login()`/`auth()->loginUsingId()`)
instead of rebuilding tenant-scoping. A firm staff member "enters" a client
workspace by logging into a shared, auto-provisioned `role=accountant`
proxy user created inside that client tenant (`is_partner_proxy=true`,
excluded from the client's `withinLimit('users')` seat count). Because
Billing/Team/Company-Settings routes were already `role:admin`-only, giving
the firm a `role=accountant` identity inside the client produces exactly
"bookkeeping-only access" with zero new permission logic. New
`audit_logs.acting_on_behalf_of_user_id` column (auto-filled from
`session('partner_origin_user_id')` via a model hook) preserves per-firm-
employee traceability even though the proxy identity is shared.

**New:** `firm_client_links` table, `App\Models\FirmClientLink`,
`App\Services\FirmClientLinkService`, `App\Policies\FirmClientLinkPolicy`,
`FirmClientLinkController` + `FirmClientInviteController`, the
`partners.*` routes, `resources/views/partners/*`. Tests across
`tests/Unit/FirmClientLinkTest.php` and six `tests/Feature/FirmClientLink*Test.php` /
`TenancyServiceRegisterClientForFirmTest.php` / `TenantWithinLimitExcludesPartnerProxyTest.php` files.

**Explicitly deferred (not built in v1):**
- Consolidated/partner billing (one invoice covering several clients, a
  partner discount) — each client tenant keeps paying for its own plan,
  completely unchanged. `Plan`, `Tenant::assignPlan()`, `EnterpriseAgreement`,
  `PlatformInvoice` were not touched.
- A `partner_access`-style plan-gating flag — available to every plan in v1.
- Per-individual-firm-staff-member proxy identities — one shared proxy per
  link; `acting_on_behalf_of_user_id` already gives per-employee audit
  traceability without needing separate identities.
- Fixing the pre-existing `BankAccount` global-scope outlier (reads
  `auth()->user()->tenant_id` instead of `app('currentTenant')`, unlike the
  other 27 tenant-scoped models) — irrelevant to this feature specifically
  since full identity substitution keeps both always in sync, but still
  worth a drive-by fix someday.

---

### Advanced Reports Plan Gate 🏗
**What:** Gate Ledger, Balance Sheet, and Trial Balance behind `plan:advanced_reports` middleware.

**Current state:** `advanced_reports` feature flag exists on plans and `RequiresPlan` middleware is available, but no report routes are gated. Define which reports require it and apply `->middleware('plan:advanced_reports')` in `routes/web.php`.

---

### Bank Reconciliation 💡
**What:** Match imported bank statement lines against existing transactions. Flag unmatched items. Post auto-reconciliation journal entries.

**Needed:** `bank_statements` table, CSV/OFX import, matching engine, reconciliation UI. Depends on bank accounts already existing (`bank_accounts` table is in place).

---

### Document / Attachment Management 💡
**What:** Attach supporting documents (receipts, contracts, supplier invoices) to transactions, expenses, and work orders.

**Needed:** `attachments` polymorphic table, S3/local storage driver, upload UI component, viewer in show pages. Would benefit maintenance work orders and expense claims most.

---

### VAT Filing Due Date Accuracy 🏗
**What:** The top-bar VAT deadline banner uses a hardcoded day-of-month and doesn't account for weekends, public holidays, or whether this month's deadline has already passed.

**Needed:** Replace the static `VatService::VAT_FILING_DAY` calculation with a proper business-day-aware next-deadline helper. Optionally load Nigerian public holidays from a config file.

---

### Android Mobile App (Flutter) 🔍
**What:** A native Android (+ iOS) companion app for AccountTaxNG with limited offline capability and background sync. Separate repository — does not modify the existing Laravel project beyond adding an API layer.

**Stack decision (agreed 2026-05-21):**
- **Flutter** — cross-platform Android + iOS, single codebase
- **Drift** — type-safe SQLite ORM for local offline storage
- **Riverpod** — state management
- **Dio** — HTTP client with interceptors for token refresh and offline request queuing
- **flutter_workmanager** — background sync when app is backgrounded or network is restored
- **flutter_secure_storage** — Sanctum token storage
- **Laravel Sanctum** — mobile token auth on the existing Laravel app (30-day expiry + refresh)

**Offline capability scope:**
| Feature | Offline | Notes |
|---|---|---|
| Create / edit invoices | ✅ | Queued, synced on reconnect |
| Create / edit expenses | ✅ | Queued |
| View customers, vendors | ✅ | Cached from last sync |
| Record payments | ✅ | Queued |
| View dashboard / reports | ✅ | Cached snapshot |
| Send invoice email | ❌ | Queued — fires when online |
| FIRS submission | ❌ | Always requires connection |
| Subscription / billing | ❌ | Always requires connection |
| File attachments | ❌ | Deferred to v2 |

**Sync strategy:** Timestamp-based differential sync. Client POSTs `{ last_synced_at, mutations[] }` to `POST /api/v1/sync`; server returns all records modified since `last_synced_at`. Conflict resolution: last-write-wins on `updated_at`; conflicts surfaced in the UI, never silently merged. Invoice sequential numbers (`INV-YYYYMM-NNNN`) are always server-assigned — mobile creates with a temporary UUID local ID, server returns the real number on first sync.

**Laravel additions required (routes/api.php):**
- `POST /api/v1/auth/login` + `POST /api/v1/auth/logout` + `GET /api/v1/auth/user`
- CRUD under `auth:sanctum` for invoices, customers, expenses, payments
- `GET /api/v1/plans/limits` — returns current tenant plan limits for local enforcement
- `POST /api/v1/sync` — batch mutations in, delta out
- All routes gated by existing `plan:feature` middleware — no new enforcement logic needed

**Partial groundwork already exists (re-checked 2026-09-12):** `routes/api.php`
now has Sanctum token auth (`POST`/`DELETE /auth/token` — not named
`/login`/`/logout` but functionally equivalent) and partial Invoice CRUD
(`index`/`store`/`summary`/`show`/`recordPayment`, no `update`/`destroy`),
plus a Tax Engine group not in the original spec. Still missing: customers
CRUD, expenses CRUD, `GET /plans/limits`, `POST /sync`, and the
`plan:feature` gating this section assumed would already be there (it isn't
— see the API Access item above, same gap). Worth reusing/renaming the
existing auth-token route rather than adding a parallel `/login` endpoint
when this is picked up.

**Key design constraints:**
- Tenant resolved from `auth()->user()->tenant_id` on every API request — same as web; global tenant scope applies identically
- Plan limits checked locally (UX) but enforced server-side on every API write (security)
- Never let the mobile app generate sequential invoice/quote numbers
- `device_id` column on syncable records for conflict attribution

**Build order:**
1. Laravel API layer — Sanctum setup, `/api/v1` auth + CRUD routes, sync endpoint
2. Flutter project scaffold — auth flow, Drift schema matching server models, Dio + Riverpod wiring
3. Online-only mode — prove full API round-trip before adding offline complexity
4. Offline write queue — mutations written to local Drift DB first, flushed to server on reconnect
5. Differential sync — `last_synced_at` pull on app foreground and workmanager background tick
6. Conflict UI — surface unresolved conflicts (same record edited on two devices while offline)
7. v2: file attachments, mandate-based recurring payments

**Secondary gateway note:** Flutterwave is the recommended second payment gateway for the mobile billing flow (parallel to Paystack; same Bearer-token pattern; native Payment Plans for recurring). Monnify deferred — its OAuth2 token refresh and mandate flow add complexity better suited to v2.

---

- [x] **Quote monthly limit** — fixed 2026-11-01. Added a separate `quotes_per_month` limit key (not shared with invoices — proformas and invoices are tracked independently) to `Plan::LIMIT_DEFAULTS`, `Tenant::withinLimit()`'s count match, and `PlanSeeder`. Enforced in `QuoteController::store()`, mirroring `InvoiceController::store()`'s exact pattern. Superadmin plan form gained a "Quotes per Month" field. Tests: [tests/Feature/QuoteLimitTest.php](tests/Feature/QuoteLimitTest.php).
- [x] **Customer limit** — fixed 2026-11-01. There's no dedicated Customers CRUD controller in this app (only `CustomerController::quickStore()`, the AJAX quick-create used from invoice/quote forms) — `withinLimit('customers')` is now checked there, returning a 422 JSON error the existing frontend already handles gracefully. Tests: [tests/Feature/CustomerLimitTest.php](tests/Feature/CustomerLimitTest.php).
- [x] **Advanced Reports gate** — fixed 2026-11-01. `plan:advanced_reports` now wraps Ledger, Balance Sheet, and Trial Balance under `routes/web.php`'s `reports.` group; Profit & Loss, VAT, CIT, and Tax Summary stay ungated (core compliance/bookkeeping, consistent with the Chart-of-Accounts precedent in TODO.md). Tests: [tests/Feature/AdvancedReportsGateTest.php](tests/Feature/AdvancedReportsGateTest.php).
- [x] **API Access gate** — fixed 2026-11-01. `plan:api_access` applied to the `invoices` and `tax` route groups in `routes/api.php` (not the `auth/token` issuance/revocation routes, which must stay reachable regardless of plan). Tests: [tests/Feature/ApiAccessGateTest.php](tests/Feature/ApiAccessGateTest.php).
  - **Found and fixed a blocking bug along the way**: Sanctum's `personal_access_tokens` migration had never been published into this app — the table didn't exist in any migrated environment (confirmed on both the local dev DB and the Postgres test DB), meaning `POST /api/v1/auth/token` (`$user->createToken()`) could never have actually worked despite the API being otherwise fully wired. Published via `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --tag=sanctum-migrations` and migrated. **Worth checking whether production has this table** — if not, the entire token-auth API has been silently broken there too.
  - **Also found and fixed**: `RequiresPlan` middleware's `canAccess($feature)` check uses the plan-flag name as the module key, but `User::MODULE_LIST` (which drives per-staff module-access overrides) was missing `advanced_reports`, `api_access`, **and the pre-existing `inventory_reports`** — meaning any non-admin accountant hitting a route gated by one of these would've been silently denied regardless of their plan, since the missing key always defaulted to `false` for non-admins. Added all three to `MODULE_LIST`.

---

## Referral Program

Reviewed 2026-10-31. Tenant referral codes, ₦2,000/₦20,000 credit
rewards, and superadmin reporting are real and working (`app/Models/Referral.php`,
`app/Services/ReferralService.php`, `app/Http/Controllers/ReferralController.php`,
`app/Http/Controllers/SuperAdmin/ReferralReportController.php`). Test
coverage and a referrer-earned-credit notification were added this pass
(see `tests/Feature/Referral*Test.php` and `app/Notifications/ReferralRewardedNotification.php`).
The items below are still open:

- [ ] **Dead `qualified` referral status** — `Referral::STATUS_QUALIFIED`
  is defined, present in the DB enum, and rendered as a badge in both
  [referrals/index.blade.php:86](resources/views/referrals/index.blade.php#L86)
  and [superadmin/referrals/index.blade.php:53](resources/views/superadmin/referrals/index.blade.php#L53),
  but no code path ever sets it — `ReferralService::qualifyFirstPayment()`
  jumps straight `pending → rewarded`. Either implement a real two-stage
  qualification flow or remove the dead enum value/badges.
- [x] **Referral reward only wired to the Paystack webhook** — fixed
  2026-11-01. A real test signup + annual payment surfaced this live:
  `BillingController::callback()` (the synchronous browser-redirect path)
  writes the `subscription_payments` row and activates the plan before the
  async webhook ever arrives, so `recordPayment()` in
  [PaystackWebhookController](app/Http/Controllers/PaystackWebhookController.php#L132)
  found the reference already existed and skipped `qualifyFirstPayment()`
  entirely — the referral stayed `pending` for every normal foreground
  checkout. Fixed by calling `qualifyFirstPayment()` from both
  `BillingController::callback()` and the referral-credit-fully-covered
  branch of `checkout()` — safe since the method already self-guards on
  "first successful payment" + "still-pending referral". Regression test:
  [tests/Feature/BillingReferralQualificationTest.php](tests/Feature/BillingReferralQualificationTest.php).
- [ ] **Acquisition/UTM columns are write-only** — `acquisition_source`,
  `utm_source`, `utm_medium`, `utm_campaign` on `tenants` are captured at
  registration (`TenancyService::registerTenant()`) but never displayed
  or queried anywhere, including the superadmin tenant detail page. Dead
  weight unless a reporting feature is planned.
- [ ] **Self-referral guard is Postgres-only** — the `CHECK` constraint
  added in migration `2026_05_25_100001_create_referrals_table.php` is
  skipped on SQLite, so it's silently absent for any test environment
  that doesn't run on Postgres.
- [ ] **No credit expiry and no admin override tool** — `referral_credit_ngn`
  never decays, and there's no superadmin control to manually adjust or
  revoke a tenant's balance/ledger if a reward was granted in error.

---

## Email Delivery

- [x] **Invoice email not wired** — implemented 2026-05-13. `InvoiceEmail` Mailable (PDF attached via DomPDF), `SendInvoiceEmail` queued Job (3 tries, 120s backoff), dispatched from `InvoiceController::sendEmail()` when customer has an email on file. Flash message adapts: confirms email address, warns if no email on file, silent for walk-in customers.
- [x] **Quote email** — implemented 2026-05-13. Same pattern: `QuoteEmail` Mailable + `SendQuoteEmail` Job, dispatched from `QuoteController::send()`.

Production queue requirement — the jobs use ShouldQueue so you need a queue worker running on the server
# In supervisor or as a background process:
php artisan queue:work --tries=3 --backoff=120

# Or for a one-off (not recommended for production):
php artisan queue:listen


---

## Public Links

- [x] **Quote public links** — fixed 2026-11-01. `public_token` (uuid, auto-generated on creation, back-filled for existing quotes) + `GET /q/{token}` and `/q/{token}/pdf` public routes, `PublicQuoteController`, `quotes/public.blade.php` (mirrors `invoices/public.blade.php`). "Copy Link" button added to `quotes/show.blade.php` (hidden for `draft`), and the quotation email now includes a "View Quotation Online →" button. Scope is view + PDF download only — no public self-service accept/decline; that stays the existing tenant-side "Accept & Generate Invoice" / "Decline" actions on the authenticated show page. Tests: [tests/Feature/QuotePublicLinkTest.php](tests/Feature/QuotePublicLinkTest.php).
  - **Found and fixed a related bug in the existing invoice public link too**: `public_token` is a `uuid` column, but neither `/inv/{token}` nor the new `/q/{token}` route constrained the `{token}` parameter to UUID shape — a garbage/malformed link caused a raw Postgres `22P02 invalid input syntax for type uuid` 500 error instead of a clean 404. Fixed both routes with `->whereUuid('token')`.

---

## Miscellaneous

- [ ] **VAT due date banner** — the top bar hardcodes `VatService::VAT_FILING_DAY` as a static day-of-month. It doesn't account for weekends, public holidays, or the next month if the deadline has already passed this month.
- [x] **Impersonation exit banner** — added 2026-04-30 in [app.blade.php](resources/views/layouts/app.blade.php). Orange banner with Exit Impersonation button shown when `session()->has('superadmin_id')`.
- [x] **Audit log viewer** — resolved (confirmed present 2026-09-12; exact
  implementation date unknown). Two viewers exist: `ActivityLogController`
  ([app/Http/Controllers/ActivityLogController.php](app/Http/Controllers/ActivityLogController.php))
  gives each tenant a filterable, paginated view of their own audit trail
  (by user/event/date range); `SuperAdminController::auditLogs()`
  ([app/Http/Controllers/SuperAdmin/SuperAdminController.php:182](app/Http/Controllers/SuperAdmin/SuperAdminController.php#L182))
  gives SuperAdmin a cross-tenant view with event-type stats.
- [ ] **Vendor quick-create** — re-checked 2026-09-12, still open. `POST /vendors/quick`
  (route name `vendors.quick-store`) exists ([routes/web.php:186](routes/web.php#L186))
  but there is still no matching AJAX call in the quote/invoice create forms.
- [x] **PDF logo not shown for new tenants** — resolved (confirmed 2026-09-12;
  exact implementation date unknown). [resources/views/invoices/pdf.blade.php:101](resources/views/invoices/pdf.blade.php#L101)
  guards the `<img>` tag with `@if($invoice->tenant->logo)` — no broken image
  for tenants without one.
