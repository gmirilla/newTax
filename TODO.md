# AccountTaxNG — TODO

A running list of outstanding work items. See [GAPS.md](GAPS.md) for the fuller
phase-by-phase history and design-level proposals — this file is for
shorter, actionable to-dos.

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
