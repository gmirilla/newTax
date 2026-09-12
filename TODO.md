# AccountTaxNG — TODO

A running list of outstanding work items. See [GAPS.md](GAPS.md) for the fuller
phase-by-phase history and design-level proposals — this file is for
shorter, actionable to-dos.

---

## Paginate the storefront catalog

**What:** Flagged 2026-09-12 while critiquing the storefront-search TODO
below — the public storefront loads its *entire* published catalog on every
visit, with no pagination. Fine for a handful of items; becomes a real
problem (slow page loads, huge unbounded queries) as soon as a tenant lists
more than a page's worth of products/services.

**Current state (confirmed 2026-09-12):**
- `StorefrontController::index()` ([app/Http/Controllers/StorefrontController.php:23-59](app/Http/Controllers/StorefrontController.php#L23))
  runs both the product and service queries with a plain `->get()` — no
  `paginate()`, no limit, no offset. Every published product and service for
  the tenant loads on a single request, every time, category filter or not.
- This predates and is independent of the storefront-search TODO, but the two
  are linked: adding search without also paginating just means "search the
  same unbounded query," not a real fix — both should land together, or
  pagination first.
- The rest of the app already has the convention to mirror: tenant-admin list
  pages consistently use `->paginate(25)` (e.g. `StorefrontProductController::index()`
  and `StorefrontServiceController::index()` already do this on the admin
  side — [app/Http/Controllers/StorefrontProductController.php:15-30](app/Http/Controllers/StorefrontProductController.php#L15),
  [app/Http/Controllers/StorefrontServiceController.php:16-25](app/Http/Controllers/StorefrontServiceController.php#L16)).
  The public catalog just never picked up the same pattern.

**Needed:**
- Switch `StorefrontController::index()`'s product/service queries to
  `paginate()` (or a combined/interleaved pagination scheme, since products
  and services are two separate queries merged into one visual grid —
  worth deciding whether to paginate each independently with its own page
  link, or merge-then-slice for one unified page of "products and services").
  A grid than mixes two separately-paginated result sets in one page is a
  little awkward — decide the UX here before implementing.
  ("catalog page 2" of a store with fewer products than services would look odd
  if paginated independently.)
- Update `resources/views/storefront/index.blade.php` to render pagination
  links (mirroring `{{ $items->links() }}` already used on the admin side)
  while preserving the active `?category=` (and future `?q=` search) query
  string, the same way `StorefrontProductController::index()` already does
  with `->withQueryString()`.
- Test coverage per CLAUDE.md conventions: a tenant with more products than
  one page's worth is correctly paginated, and category/search filters
  combine correctly with pagination (no losing the filter when paging).

---

## Cap storefront product/service images at 4, with proper multi-select upload

**What:** Requested 2026-09-12. Today a tenant can already attach more than
one image to a product or service, but there's no defined maximum, and the
upload UX only accepts one file per submission.

**Current state (confirmed 2026-09-12):**
- `StorefrontProductController::uploadImage()` ([app/Http/Controllers/StorefrontProductController.php:80-95](app/Http/Controllers/StorefrontProductController.php#L80))
  and `StorefrontServiceController::uploadImage()` ([app/Http/Controllers/StorefrontServiceController.php:90-104](app/Http/Controllers/StorefrontServiceController.php#L90))
  enforce **no maximum** — a tenant can keep clicking "Add photo" indefinitely.
- The upload forms (`resources/views/storefront_admin/products/index.blade.php:116`,
  `resources/views/storefront_admin/services/index.blade.php:174`) are a single
  `<input type="file" name="image">` with no `multiple` attribute — one file
  picked and submitted at a time, even though the backend already supports
  many (`storefront_product_images`/`storefront_service_images` are proper
  one-to-many tables today).

**Needed:**
- Add a hard cap of 4 images per product/service — reject the upload (with a
  clear error) once `$product->images()->count() >= 4`.
- Support selecting multiple files in one go (`<input type="file" multiple>`)
  rather than one-at-a-time, with the same 4-image ceiling applied to the
  batch (e.g. reject if the batch would push the count over 4, or upload only
  as many as fit — decide which).
- Decide whether the cap is a hard product/plan-wide constant or eventually
  plan-gated (e.g. higher tiers get more images) — start with a flat constant
  unless there's a reason to gate it.
- Test coverage per CLAUDE.md conventions: uploading a 5th image is rejected;
  a batch upload that would exceed 4 is handled per the decision above;
  existing images past a retroactively-applied cap (if any tenant already has
  more than 4 today) aren't force-deleted, just blocked from adding more.

---

## Storefront search (find items within a store)

**What:** Requested 2026-09-12. Customers can currently only browse a
storefront by category filter — there's no way to search by name.

**Current state (confirmed 2026-09-12):**
- `StorefrontController::index()` ([app/Http/Controllers/StorefrontController.php:23-57](app/Http/Controllers/StorefrontController.php#L23))
  only accepts a `?category=` query param — no `search`/`q` param, no
  name/description matching at all.
- The rest of the app already has an established search-filter convention to
  mirror: `InventoryItemController::index()` ([app/Http/Controllers/Inventory/InventoryItemController.php:33-38](app/Http/Controllers/Inventory/InventoryItemController.php#L33))
  does `->when($request->filled('search'), ...)` matching `name`/`sku`/`description`
  via the `db_like()` helper (driver-aware `ilike`/`like`, per CLAUDE.md's
  Postgres `ilike` rule) — the storefront search should follow the same
  shape, matching product name (via `InventoryItem.name`) and service name,
  plus maybe `web_description`/`description`.

**Needed:**
- Add a search box to `resources/views/storefront/index.blade.php`, submitting
  a `?q=` (or `?search=`) param alongside the existing `?category=`, combinable
  with it.
- Extend `StorefrontController::index()`'s product and service queries with
  the `db_like()`-based `when($request->filled(...))` pattern above.
- Decide whether search should also work from the storefront layout's header
  (visible on every page, not just the catalog) — check `resources/views/storefront/layout.blade.php`
  when this is picked up.
- Test coverage per CLAUDE.md conventions: search matches by name, is
  tenant-scoped (doesn't leak another tenant's products), and combines
  correctly with an active category filter.

---

## Marketing site: "Discover Storefronts" directory

**What:** Requested 2026-09-12. Let visitors to the marketing site (not just
people who already have a direct shop link) browse/discover tenant
storefronts.

**Current state (confirmed 2026-09-12):**
- `MarketingController` ([app/Http/Controllers/MarketingController.php](app/Http/Controllers/MarketingController.php))
  only has `home`/`features`/`pricing`/`about`/`faq`/`taxRules`/`contact` — no
  directory/listing action, no public route for one.
- There is currently **no way to discover a storefront at all** except being
  given its direct link (`{tenant:slug}/shop`) — no public listing exists
  anywhere in the app today.
- `Storefront` ([app/Models/Storefront.php](app/Models/Storefront.php)) has
  no flag for "listed in a public directory" — only `is_enabled` (shop is
  reachable at all) and `vat_applicable`. **Every enabled storefront would
  become publicly discoverable by default** if this just queried
  `is_enabled = true` tenants — worth deciding explicitly rather than
  defaulting a business into public listing without their say.

**Needed:**
- Decide the opt-in question above first: a new `Storefront.is_listed`
  (or similar) flag, defaulting to `false`, with a toggle in
  `storefront_admin/settings.blade.php` next to the existing `is_enabled`
  checkbox — a tenant enabling their shop for direct links shouldn't
  automatically mean "list me publicly" without asking.
- New `MarketingController::discoverStorefronts()` (or a small dedicated
  controller) + route + view, querying tenants with an enabled, listed,
  plan-eligible storefront (`is_active`, `planAllows('storefront')`,
  `storefront.is_enabled`, `storefront.is_listed`).
- Decide what's shown per store in the directory (name, logo, description,
  banner?) and whether it needs its own search/category/pagination given it
  could span many tenants — likely wants the same search treatment as the
  per-store search TODO above, just across tenants instead of within one.
- Test coverage per CLAUDE.md conventions: only listed + enabled + plan-eligible
  storefronts appear; an unlisted-but-enabled storefront is still reachable
  by direct link but doesn't show up in discovery.

---

## Storefront gives no notice when it goes dark on subscription lapse 🔍

**What:** When a tenant's subscription lapses (past the 7-day grace period —
see `Tenant::subscriptionActive()`) or they're downgraded off a storefront-
including plan, `planAllows('storefront')` starts returning `false` and the
entire public storefront 404s with zero explanation — no "this store is
temporarily unavailable" messaging, nothing. Confirmed 2026-09-12 while
investigating "what happens when a storefront subscription ends."

**Current state:**
- [EnsureStorefrontEnabled](app/Http/Middleware/EnsureStorefrontEnabled.php)
  gates the whole public route group (`{tenant:slug}/shop/*`) on
  `$tenant->is_active && $tenant->planAllows('storefront') && $tenant->storefront->is_enabled`
  and just `abort(404)`s if any of those fail — same bare 404 whether the
  shop was never enabled, is deliberately disabled, or lapsed on billing.
- This includes `storefront.order.status` — a customer with a saved
  order-confirmation link from *before* the lapse gets a 404 too, with no way
  to tell "this store is down" from "this link is wrong."
- Nothing on the tenant side proactively warns them their storefront is about
  to go dark either — no email/banner tied to the existing `TrialEndingSoon`-style
  notification pattern ([DowngradeExpiredTrials.php](app/Console/Commands/DowngradeExpiredTrials.php)
  already sends a "trial ending soon" email 3 days out; there's no storefront-
  specific equivalent).
- Nothing is deleted or disabled in the data — `Storefront`/`StorefrontProduct`/`StorefrontService`/`StorefrontOrder`
  rows are untouched, and everything reappears exactly as it was once the
  tenant resubscribes/upgrades. This item is purely about the *experience*
  during the gap, not data safety.

**Needed:**
- Decide what a lapsed/disabled storefront should show instead of a bare 404
  — likely a distinct "this store is temporarily unavailable" page rather
  than reusing the generic 404, at least for the case where a `Storefront`
  row exists but access is currently denied (vs. genuinely no such
  tenant/slug, which should probably stay a real 404).
- Decide whether `storefront.order.status` deserves special handling so an
  existing customer with a valid order token isn't told nothing at all.
- Consider a heads-up notification to the tenant before the grace period
  expires (mirroring `TrialEndingSoon`), so they're not surprised by orders
  going unreachable.
- Test coverage per CLAUDE.md conventions once the approach is decided.

---

## ✅ Fixed (2026-09-12): VAT Returns now calculated on payment received

VAT Returns previously counted the full `vat_amount` of every invoice touched
in a period, even a `partial` invoice — inflating what's actually due to NRS.
Fixed:

- `VatService::sumOutputVatReceived()` (new) prorates each invoice's
  `vat_amount` by `amount_paid / total_amount` (clamped at 1.0 to guard
  against overpayment); used by `computeMonthlyReturn()`, `getDashboardSummary()`,
  and `ReportService::getComplianceDashboard()`/`getVatReport()`, which had the
  same bug on the Tax dashboard's compliance card. Input VAT (`Expense`)
  wasn't changed — confirmed it has no partial-payment concept to prorate.
- `VatService::createOrUpdateReturn()` now locks `output_vat`/`input_vat`/`net_vat_payable`
  once a return is `filed` or `paid` — recompute is a no-op for those, so
  revisiting "Compute New Return" can no longer silently overwrite numbers
  already reported to NRS. A genuine change to a filed/paid return needs the
  "Amend" workflow below instead.
- A "Recalculate" link was added to `pending`/`nil_return` rows on
  `tax/vat/index.blade.php`, reusing the existing `tax.vat.compute` route.
- Recomputing an existing return now writes a `vat_return.recalculated`
  audit log entry (only when the figures actually changed).
- Tests: `tests/Unit/VatCalculationTest.php` (also fixed along the way — its
  `@test` docblock annotations were silently not being picked up by this
  project's PHPUnit 12 install; converted to `test_`-prefixed method names so
  all 15 tests, old and new, actually run) and the new
  `tests/Feature/VatReturnCalculationTest.php`.

*Same-shape bug still open elsewhere:* `tests/Unit/CitCalculationTest.php`,
`PayeCalculationTest.php`, and `WhtCalculationTest.php` likely have the same
`@test`-annotation problem (not fixed here — out of scope for this change,
worth a quick pass later).

---

## Amend a filed/paid VAT Return 🔍

**What:** Now that `VatService::createOrUpdateReturn()` locks `output_vat`/`input_vat`/`net_vat_payable`
once a return is `filed` or `paid` (see the fix above), users still need a
deliberate way to correct a filed return
when new information shows up — a late invoice, a corrected expense, a
payment-allocation fix. This item is that escape hatch: an explicit,
audited "Amend" action, distinct from the ordinary recompute/"Recalculate"
flow, which only ever touches `pending`/`nil_return` returns.

**Proposed approach:**
- **Trigger:** a separate "Amend Return" action on `filed`/`paid` rows in
  [tax/vat/index.blade.php](resources/views/tax/vat/index.blade.php), gated
  to `admin`/`accountant` (same as the rest of Tax), requiring a short
  free-text reason — this is compliance-sensitive, so it shouldn't be a
  silent one-click recompute like the `pending` case.
- **History, not new columns:** don't add `original_output_vat`-style
  snapshot columns to `vat_returns`. This codebase already has
  `AuditLog::record()` for before/after diffs (see the "Audit logging"
  convention in CLAUDE.md, and e.g. `sales_order.cancelled` in
  [SalesOrderController.php](app/Http/Controllers/Inventory/SalesOrderController.php)
  for the pattern) — capture the amendment the same way:
  `AuditLog::record('vat_return.amended', $vatReturn, $before, $after, 'tax,approval')`
  with the reason in the audit payload. Called after the update, per the
  existing convention (never inside the same `DB::transaction()`).
- **Re-filing, not auto-reconciliation (v1 scope):** an amendment recomputes
  the figures but does **not** try to automatically reconcile a payment
  already recorded against the old `net_vat_payable`. Simplest v1: amending
  a `filed` return resets its status back to `pending` and clears
  `filing_reference`/`filed_date`, requiring the user to re-file the
  corrected figure with NRS and re-enter a new reference — mirrors what
  actually happens with NRS (an amended return is its own filing). Amending
  a `paid` return should probably just be blocked in v1 (money has already
  moved) with a message pointing at manual adjustment, rather than trying to
  model a refund/additional-payment flow — revisit only if this turns out to
  be a real user need.
- **New method, not reuse:** add `VatService::amendReturn(VatReturn $vatReturn, string $reason): VatReturn`
  rather than loosening `createOrUpdateReturn()` — keeps the ordinary
  recompute path (used by "Compute New Return") permanently safe for
  `pending` returns only, with amendment as a separate, explicit, audited
  code path.

**Needed:**
- Confirm the re-filing-required behavior above is actually what a Nigerian
  VAT filer expects (worth a quick check against FIRS/NRS guidance rather
  than assuming) before building it.
- `VatService::amendReturn()` + `TaxController::vatAmend()` + route.
- UI: "Amend Return" action + reason prompt on `filed` rows (and the
  paid-is-blocked messaging) in `tax/vat/index.blade.php`.
- Test coverage per CLAUDE.md conventions: amending a `filed` return updates
  figures, resets status to `pending`, clears the filing reference, and
  writes an audit log entry with reason + before/after amounts; amending a
  `paid` return is rejected.

---

## Chart of Accounts management 🔍

**What:** Tenants currently have no way to view, create, edit, or deactivate
their own GL accounts. The `Account` model (Chart of Accounts) is entirely
system-managed — the full set is provisioned once, automatically, via
`BookkeepingService::provisionDefaultAccounts()` at tenant registration, and
nothing after that point ever creates a new `Account` row for a tenant.

**Current state (confirmed 2026-09-10):**
- No `AccountController`, no route, no view exists for account CRUD — checked
  every `Account::create()` call site in the app; the only one outside a test
  is the registration-time provisioning call.
- The only tenant-facing account screen is the read-only account-code filter
  dropdown on **Reports → Ledger**.
- [resources/views/help/topics/bookkeeping.blade.php](resources/views/help/topics/bookkeeping.blade.php#L27)
  tells users to go to *"Bookkeeping → Chart of Accounts"* — that page/menu
  doesn't exist anywhere in the app. Stale docs describing a feature that was
  either never built or removed; needs fixing regardless of when this item
  gets picked up.
- This already bit real functionality once: `InventoryImportController` and
  `ProductionOrderController` (Manufacturing) both depend on GL codes 1201/1202
  existing, and those two accounts were simply missing from the default chart
  for every tenant — fixed 2026-09-10 by adding them to
  `Account::DEFAULT_ACCOUNTS` plus a backfill migration
  (`2026_09_10_000007_add_raw_material_and_finished_goods_accounts.php`). That
  fix only works because it's a code-level default; any *tenant-specific*
  custom account (e.g. a second bank account's GL line, a custom expense
  category) still has no way to ever exist, short of a manual DB insert.

**Needed:**
- `AccountController` (index/create/edit/deactivate) — likely under
  `Settings → Chart of Accounts` or a new `Bookkeeping` nav section, matching
  the existing help-doc's implied location.
- Guard rails: `is_system` accounts (the default 29) should not be deletable
  and probably not renamable/re-typed, only activatable/deactivatable —
  custom tenant-added accounts get full CRUD.
- Validate new account codes against the existing numbering convention
  (1xxx asset / 2xxx liability / 3xxx equity / 4xxx revenue / 5xxx expense)
  so custom accounts don't break report groupings that key off the code range.
- Plan-gate decision: is this Free-tier or a paid-plan feature? (Everything
  else account-adjacent — Advanced Reports, Inventory — is plan-gated.)
- Test coverage per CLAUDE.md conventions once routes exist.
