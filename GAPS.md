# NaijaBooks — Known Gaps & Follow-Through Tracker

Updated: 2026-04-30. Tick items off as each is completed.

Phase 3 (Trial Flow) completed 2026-04-30.

---

## Critical Bugs

- [x] **InvoiceController: missing `use` for Excel facade** — fixed 2026-04-30. Added `use Maatwebsite\Excel\Facades\Excel;` to [InvoiceController.php](app/Http/Controllers/InvoiceController.php).

---

## Uncommitted Work

Everything since commit `251f0fb` (Added Company Logo support) is unstaged. A single commit covers:
- Public invoice UUID links (`PublicInvoiceController`, `Invoice::public_token`, migrations)
- VAT/WHT reactivity fix on create forms
- Preview modal on invoice & quote create forms
- Phase 1: DB-driven plans (`Plan` model, migrations, `PlanSeeder`, SuperAdmin plan CRUD)
- Phase 1: `Tenant` subscription helpers (`planAllows`, `withinLimit`, `isOnTrial`, etc.)
- Phase 2: `RequiresPlan` middleware, route gating, billing page, sidebar locked states

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

## Feature Enforcement Gaps

- [ ] **Quote monthly limit** — invoices enforce `withinLimit('invoices_per_month')` in `QuoteController::store()` but quotes are not currently limited. Decide: share the same counter, or add a separate `quotes_per_month` limit key.
- [ ] **Customer limit** — `Plan` supports a `customers` limit key and `Tenant::withinLimit` can check it, but `CustomerController::store()` has no limit enforcement.
- [ ] **Advanced Reports gate** — the `advanced_reports` feature flag exists on plans and `RequiresPlan` middleware is available, but no report routes are gated by it. Define which reports require it (e.g., Ledger, Balance Sheet) and apply `->middleware('plan:advanced_reports')`.
- [ ] **API Access gate** — `api_access` feature flag is in plans but `routes/api.php` has no `plan:api_access` middleware applied.

---

## Email Delivery

- [ ] **Invoice email not wired** — `InvoiceController::sendEmail()` sets status to `sent` and posts the revenue journal but the actual email dispatch is commented out (`// TODO: Dispatch SendInvoiceEmail job`). Needs: `SendInvoiceEmail` Mailable + Job, queued dispatch, and a queue worker configured in production.
- [ ] **Quote email** — `QuoteController::send()` likely has the same gap (unverified).

---

## Public Links

- [ ] **Quote public links** — only invoices have a `public_token` UUID and a public view route (`/inv/{token}`). Quotes have no equivalent. Add `public_token` to quotes table, generate on creation, add `GET /q/{token}` public route + view if this is a desired feature.

---

## Miscellaneous

- [ ] **VAT due date banner** — the top bar hardcodes `VatService::VAT_FILING_DAY` as a static day-of-month. It doesn't account for weekends, public holidays, or the next month if the deadline has already passed this month.
- [x] **Impersonation exit banner** — added 2026-04-30 in [app.blade.php](resources/views/layouts/app.blade.php). Orange banner with Exit Impersonation button shown when `session()->has('superadmin_id')`.
- [ ] **Audit log viewer** — `AuditLogMiddleware` records all actions but there is no UI in SuperAdmin to browse them.
- [ ] **Vendor quick-create** — `POST /vendors/quick` route exists but there is no matching AJAX call in the quote/invoice create form (vendors are not selectable as recipients there; this may be intentional).
- [ ] **PDF logo not shown for new tenants** — `Invoice::pdf` blade references `$invoice->tenant->logo_url`. If no logo is uploaded, ensure a graceful fallback (no broken `<img>` tag). Verify the null check is in place.
