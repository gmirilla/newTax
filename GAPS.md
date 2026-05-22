# AccountTaxNG — Known Gaps & Follow-Through Tracker

Updated: 2026-05-21. Tick items off as each is completed.

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

**Current state:** `api_access` feature flag exists on plans, `routes/api.php` exists but has no `plan:api_access` gate, no API key model or management UI.

**Needed:** `api_keys` table, key generation UI in Settings, `ApiKeyMiddleware` to authenticate bearer tokens, `plan:api_access` applied to `routes/api.php`, rate limiting per key.

---

### Quote Public Links 🔍
**What:** Customers receive a shareable link to view and accept a quote online (same as `/inv/{token}` for invoices).

**Current state:** Only invoices have `public_token` UUID and `GET /inv/{token}` public route. Quotes have no equivalent.

**Needed:** `public_token` on quotes table (migration), generate on creation, `GET /q/{token}` public route + `PublicQuoteController`, view similar to `public-invoice.blade.php`.

---

### Vendor Quick-Create in Invoice / Quote Forms 🏗
**What:** When creating an invoice or quote, users can create a new vendor inline without leaving the form.

**Current state:** `POST /vendors/quick` route exists but there is no AJAX call wired in the invoice/quote create forms.

**Needed:** Connect the existing route to an Alpine.js modal on the invoice/quote create forms (same UX as customer quick-create if that exists).

---

### Accountant / Partner Programme 💡
**What:** Accounting firms managing multiple SME clients get a single login to switch between client companies, a partner discount on billing, and bulk invoice capabilities.

**Design considerations:**
- `partner_firms` table with own subscription tier
- Many-to-many between partner firm and tenants (`firm_tenant_access`)
- Impersonation-style context switch (similar to existing superadmin impersonation)
- Consolidated billing: firm pays one invoice covering all managed tenants

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

- [ ] **Quote monthly limit** — invoices enforce `withinLimit('invoices_per_month')` in `QuoteController::store()` but quotes are not currently limited. Decide: share the same counter, or add a separate `quotes_per_month` limit key.
- [ ] **Customer limit** — `Plan` supports a `customers` limit key and `Tenant::withinLimit` can check it, but `CustomerController::store()` has no limit enforcement.
- [ ] **Advanced Reports gate** — the `advanced_reports` feature flag exists on plans and `RequiresPlan` middleware is available, but no report routes are gated by it. Define which reports require it (e.g., Ledger, Balance Sheet) and apply `->middleware('plan:advanced_reports')`.
- [ ] **API Access gate** — `api_access` feature flag is in plans but `routes/api.php` has no `plan:api_access` middleware applied.

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

- [ ] **Quote public links** — only invoices have a `public_token` UUID and a public view route (`/inv/{token}`). Quotes have no equivalent. Add `public_token` to quotes table, generate on creation, add `GET /q/{token}` public route + view if this is a desired feature.

---

## Miscellaneous

- [ ] **VAT due date banner** — the top bar hardcodes `VatService::VAT_FILING_DAY` as a static day-of-month. It doesn't account for weekends, public holidays, or the next month if the deadline has already passed this month.
- [x] **Impersonation exit banner** — added 2026-04-30 in [app.blade.php](resources/views/layouts/app.blade.php). Orange banner with Exit Impersonation button shown when `session()->has('superadmin_id')`.
- [ ] **Audit log viewer** — `AuditLogMiddleware` records all actions but there is no UI in SuperAdmin to browse them.
- [ ] **Vendor quick-create** — `POST /vendors/quick` route exists but there is no matching AJAX call in the quote/invoice create form (vendors are not selectable as recipients there; this may be intentional).
- [ ] **PDF logo not shown for new tenants** — `Invoice::pdf` blade references `$invoice->tenant->logo_url`. If no logo is uploaded, ensure a graceful fallback (no broken `<img>` tag). Verify the null check is in place.
