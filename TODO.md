# AccountTaxNG — TODO

A running list of outstanding work items. See [GAPS.md](GAPS.md) for the fuller
phase-by-phase history and design-level proposals — this file is for
shorter, actionable to-dos.

---

## VAT Returns should be based on payment received, not invoiced amount 🔍

**What:** [VatService::computeMonthlyReturn()](app/Services/VatService.php#L50) and
[VatService::getDashboardSummary()](app/Services/VatService.php#L135) both sum the
**full** `vat_amount` of every invoice with status `sent`, `partial`, or `paid`
that falls in the period. For a `partial` invoice this counts VAT on the whole
invoice total, not on the portion actually paid — reported by the user
(2026-09-10): *"When calculating VAT Returns Due, VAT should be calculated
based only on the payment received, not on the full sum."*

**Current state (confirmed 2026-09-10):**
- [VatService.php:56-60](app/Services/VatService.php#L56-L60) — output VAT:
  `Invoice::where('vat_applicable', true)->whereIn('status', ['sent','partial','paid'])->sum('vat_amount')`.
  A ₦100,000 invoice (₦7,500 VAT) with only ₦20,000 received still contributes
  the full ₦7,500 to output VAT for the period.
- `Invoice` already tracks `amount_paid` / `total_amount` / `balance_due`
  ([Invoice.php:20,34-35](app/Models/Invoice.php#L20)), so the data needed for
  a paid-proportion calculation already exists — `output_vat` just isn't using
  it. Cash-basis VAT for a partial invoice would be
  `vat_amount * (amount_paid / total_amount)`.
- Input VAT has the same shape of issue on the expense side
  ([VatService.php:63-67](app/Services/VatService.php#L63-L67)) if expenses
  ever get a comparable partial-payment status — worth checking `Expense`'s
  `status` values (`approved`, `paid`) when this is picked up.
- **Recalculating an existing return**: technically possible today —
  revisiting "Compute New Return" for the same year/month re-runs
  `VatService::createOrUpdateReturn()`, which is a `firstOrNew` + refresh of
  the computed figures ([VatService.php:98-118](app/Services/VatService.php#L98-L118)).
  But there's no discoverable way to trigger this for a specific period: the
  VAT Returns table ([tax/vat/index.blade.php:94-119](resources/views/tax/vat/index.blade.php#L94-L119))
  only offers "Mark Filed" / "Mark Paid" / "View Details" per row — no
  "Recalculate" action once a return exists, especially for `pending` returns
  where new invoices/payments may have landed since it was first computed.
  Also note: revisiting the compute page silently overwrites the financial
  figures even for a return already `filed`/`paid` (only `status` is
  protected from being reset) — worth deciding whether recalculating a
  filed/paid return should be blocked or explicitly confirmed, once this is
  addressed.

**Needed:**
- Decide the cash-basis formula and whether it applies per invoice-payment or
  needs a running allocation across multiple partial payments on the same
  invoice.
- Update `computeMonthlyReturn()` (and `getDashboardSummary()`) to use
  paid-proportion VAT instead of `vat_amount` directly for `partial` invoices.
- Add an explicit "Recalculate" action per period on the VAT Returns index
  (`pending` at minimum; decide the filed/paid case above).
- Test coverage per CLAUDE.md conventions: a partial invoice should contribute
  less than its full `vat_amount` to `output_vat`, and a recalculate action
  should update the figures without disturbing `status`/`filing_reference`
  once filed.

---

## Amend a filed/paid VAT Return 🔍

**What:** Once [VAT Returns should be based on payment received](#vat-returns-should-be-based-on-payment-received-not-invoiced-amount-)
above locks `output_vat`/`input_vat`/`net_vat_payable` after a return is
`filed` or `paid` (so recompute stops silently overwriting numbers already
reported to NRS), users still need a deliberate way to correct a filed return
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
