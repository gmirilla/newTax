# AccountTaxNG — TODO

A running list of outstanding work items. See [GAPS.md](GAPS.md) for the fuller
phase-by-phase history and design-level proposals — this file is for
shorter, actionable to-dos.

---

## ✅ Fixed (2026-09-13): Paginate the storefront catalog

`StorefrontController::index()` used to load the tenant's entire published
catalog on every visit (`->get()`, no limit). Fixed:

- Products and services are now paginated independently via
  `->paginate(24, ['*'], 'products_page')` / `...'services_page'` — distinct
  `pageName`s so paging one doesn't collide with or reset the other.
- Per the user's call over a combined-grid-via-SQL-UNION alternative, the
  storefront view now splits into two clearly labeled sections ("Products" /
  "Services"), each with its own heading, grid, and pager
  (`resources/views/storefront/index.blade.php`) — the per-tile "Service"
  badge was removed as redundant now that the section heading says so.
- Found and fixed a real, pre-existing, unrelated bug in the same pass: the
  category-tab list used to be computed from the already-category-filtered
  product/service collections, so clicking one category tab collapsed the
  tab bar down to just that category, hiding every sibling tab. The tab list
  is now computed from its own separate, unfiltered, lightweight
  `distinct()->pluck('storefront_category_id')` query — fixes both the
  pagination independence and the tab-collapse bug at once.
- Tests added to `tests/Feature/StorefrontModuleTest.php`: pagination
  correctness + independence between the two paginators, and a direct
  regression test for the tab-collapse bug.

Still open, tracked separately below: storefront search (once built, will
combine with this same pagination) and the "Discover Storefronts" directory.

---

## ✅ Fixed (2026-09-13): Cap storefront images at 4, with multi-select upload

`StorefrontProductController::uploadImage()` / `StorefrontServiceController::uploadImage()`
used to accept unlimited images, one file per submission. Fixed:

- `StorefrontProduct::MAX_IMAGES` / `StorefrontService::MAX_IMAGES` (both
  `= 4`) — a flat constant, not plan-gated (no reason found to gate it).
- Upload forms now use `<input type="file" name="images[]" multiple>`,
  processed as a batch server-side.
- Per the user's call: a batch that would exceed the cap uploads as many as
  fit and flashes a message naming how many were skipped ("Added 1 photo. 2
  were not added — you've reached the 4-photo limit."), rather than
  rejecting the whole batch. Already-at-cap uploads add nothing, with an
  error flash.
- The admin view now shows a running `N/4` count and hides the upload form
  entirely once at the cap (replaced with a "remove one to add more" note),
  rather than only handling it as a rejected submission after the fact.
  Existing images past the cap (if any) are never force-deleted — only new
  uploads are blocked.
- Tests added to `tests/Feature/StorefrontModuleTest.php`: under-cap batch,
  over-cap batch (partial accept), already-at-cap (rejects all), delete
  re-enabling upload, and the view hiding the form at the cap (products +
  one mirrored case for services).

---

## ✅ Fixed (2026-09-13): Storefront search

`StorefrontController::index()` used to only support `?category=` — no way
for a customer to search by name. Fixed, following the existing
`db_like()`-based search convention already used by `InventoryItemController::index()`:

- A `?q=` param now filters products (by `InventoryItem.name` or
  `web_description`) and services (by `name` or `description`) via
  `db_like()` (driver-aware `ilike`/`like`).
- Combines correctly with both the existing `?category=` filter and the
  `products_page`/`services_page` pagination (same `->withQueryString()`
  mechanism as the pagination fix) — searching, filtering, and paging never
  drop each other.
- A search box was added to `resources/views/storefront/index.blade.php`;
  the category tabs and empty state were updated to preserve/reflect the
  active search term ("No results for '...' — try a different search").
- Scoped to the catalog index page only, not the storefront layout header on
  every page (kept the change contained to where category filtering and
  pagination already live, rather than adding a new site-wide search
  surface in the same pass).
- Tests added to `tests/Feature/StorefrontModuleTest.php`: matches products
  by name, matches services by name, and combines correctly with an active
  category filter (tenant isolation was already covered by existing tests).

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

## ✅ Fixed (2026-09-13): Amend a filed/paid VAT Return

Since `VatService::createOrUpdateReturn()` locks figures once a return is
`filed`/`paid`, tenants needed a deliberate way to correct one. Fixed:

- **Confirmed against real guidance first** (the "Needed" list below asked
  for this rather than assuming): per Taxngr's "How to Amend a Tax Return
  Already Filed With the NRS", a self-amendment is filed as a new corrected
  return with its own reference and explanation, not an in-place edit —
  confirming the reset-to-pending design was the right shape.
- `VatService::amendReturn(VatReturn, string $reason)` — recomputes the
  period's figures, resets `status` to `pending`/`nil_return`, clears
  `filing_reference`/`filed_date`/`filed_by`, appends the reason to the
  existing `notes` column (no new columns needed), and writes a
  `vat_return.amended` audit log entry with before/after figures + reason.
  A separate method from `createOrUpdateReturn()`, which stays permanently
  safe for the ordinary Recalculate/Compute path.
- `TaxController::vatAmend()` + `POST /tax/vat/{vatReturn}/amend` (route
  name `tax.vat.amend`), mirroring `vatFiled()`/`vatPaid()`'s plain-validation
  shape. Only `filed` returns can be amended; `paid` is blocked in v1 with a
  message pointing at manual adjustment (money's already moved — modeling a
  refund/additional-payment flow is real extra scope, revisit only if a
  tenant actually needs it).
- UI: an "Amend Return" toggle + reason field on `filed` rows in
  `tax/vat/index.blade.php`; a muted "Contact support to amend a paid
  return" note on `paid` rows instead of a control.
- Tests added to `tests/Feature/VatReturnCalculationTest.php`: amending a
  filed return recomputes/resets/audits correctly; amending paid/pending
  returns is rejected; a reason is required; an amended return re-files
  normally afterward; the view renders the new controls correctly for both
  `filed` and `paid` rows.

---

## ✅ Fixed (2026-09-13): Chart of Accounts management

Tenants previously had no way to view, create, edit, or deactivate their own
GL accounts — the chart was entirely system-provisioned once at
registration. Fixed:

- New **Settings → Chart of Accounts** page (`AccountController` + view),
  gated `role:admin` only, same as Bank Accounts sits — no plan gate. This
  was a deliberate call: every other plan-gated feature (payroll, FIRS,
  inventory, manufacturing, maintenance, storefront, api_access,
  advanced_reports) is operational or reporting-depth; core bookkeeping
  (bank accounts, transactions, the ledger itself) is ungated even on Free
  today, and there was no reason found to make this the first-ever
  bookkeeping paywall.
- **Guard rails for `is_system` accounts** (the 29 defaults): can be
  renamed, re-described, re-sub-typed, and activated/deactivated, but their
  `code`/`type` are stripped server-side on update regardless of what's
  submitted, and delete is blocked entirely by `AccountPolicy`. This closes
  a real hazard found during investigation: many controllers/services do a
  direct `Account::where('code', 'XXXX')` lookup when posting GL entries
  (some, like `TransactionController`, `firstOrFail()` on it), so renaming
  or retyping a load-bearing code would silently break GL posting or the
  type-based report aggregation elsewhere — `is_system` existed as a column
  with a comment ("cannot be deleted") but had zero enforcement anywhere
  until now.
- **Custom accounts** get full CRUD, with the new account's `code` validated
  against its `type`'s leading-digit convention (1xxx asset / 2xxx liability
  / 3xxx equity / 4xxx revenue / 5xxx expense) and the existing
  `sub_type` DB enum (invalid values now caught by validation, not a raw
  DB error). Delete is blocked (with a message pointing at deactivation
  instead) if the account has journal entries — mirrors
  `BankAccountController`'s exact existing pattern for the same situation.
- Fixed the stale help doc
  ([resources/views/help/topics/bookkeeping.blade.php](resources/views/help/topics/bookkeeping.blade.php))
  that pointed at a fictional "Bookkeeping → Chart of Accounts" menu.
- Tests added: `tests/Feature/ChartOfAccountsTest.php` (12 cases — viewing,
  role-gating, valid/invalid custom-account creation, editing a custom vs.
  system account, delete guards for system accounts and accounts with
  journal entries, tenant isolation).
