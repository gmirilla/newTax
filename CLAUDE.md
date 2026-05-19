# NaijaBooks — Claude Code Project Guide

## Stack
- Laravel 13, PHP 8.3
- **Database: PostgreSQL** (production and local dev both run Postgres)
- Blade + Alpine.js + Tailwind CSS frontend
- Paystack for payments (Nigerian market)

---

## Database Rules — CRITICAL

The database is **PostgreSQL**. All raw SQL must be PostgreSQL-compatible.

### Forbidden — MySQL-only syntax
| Do NOT use | Use instead |
|---|---|
| `CURDATE()` | `CURRENT_DATE` |
| `NOW()` for date-only | `CURRENT_DATE` |
| `IFNULL(a, b)` | `COALESCE(a, b)` |
| `GROUP_CONCAT(...)` | `string_agg(..., ',')` or handle in PHP |
| `RAND()` | `RANDOM()` |
| `DATE_FORMAT(col, fmt)` | `TO_CHAR(col, fmt)` |
| `DATEDIFF(a, b)` | `(a::date - b::date)` |
| `LIMIT x, y` (offset shorthand) | `LIMIT x OFFSET y` |

### Prefer standard SQL over DB-specific extensions
- Use `SUM(CASE WHEN condition THEN 1 ELSE 0 END)` for conditional counts — not `FILTER (WHERE ...)` even though Postgres supports it, since it confuses agents trained on MySQL examples.
- Use `COALESCE(expr, 0)` for null-to-zero.
- Use `CURRENT_DATE` / `CURRENT_TIMESTAMP` (standard SQL) not vendor aliases.

### Prefer Eloquent over raw SQL
Use query builder methods wherever possible. Only drop to `selectRaw` / `whereRaw` when Eloquent has no equivalent. When you must write raw SQL, re-read the table above before submitting.

### Case-insensitive LIKE
Use `ilike` (PostgreSQL) not `like` for case-insensitive string searches:
```php
->where('name', 'ilike', '%' . $search . '%')
```

---

## Code Conventions

### Tenant scoping
- Most models have a `tenant` global scope; bypass with `->withoutGlobalScope('tenant')` when querying across tenant boundaries in controllers that already resolve the tenant from `auth()->user()->tenant`.
- Always filter `->where('tenant_id', $tenant->id)` explicitly after bypassing the scope.

### Intelephense P1013 errors on `auth()`
`auth()->user()` and `auth()->id()` show Intelephense "Undefined method" warnings throughout the codebase. These are **pre-existing IDE false positives** — not real runtime errors. Ignore them.

### Policy tenant comparisons
All policy `tenant_id` comparisons use `==` (loose equality), not `===`. This is intentional: `User.tenant_id` is cast to `int` but related model `tenant_id` columns are not, so strict comparison fails.

### Audit logging
`AuditLog::record()` calls are placed **after** `DB::transaction()` closes, never inside. This prevents business transaction rollbacks from wiping audit records.

### GL account codes
| Code | Account |
|---|---|
| 1100 | Accounts Receivable |
| 1200 | General Inventory |
| 1201 | Raw Materials Inventory |
| 1202 | Finished Goods Inventory |
| 2001 | Accounts Payable |
| 3001 | Owner's Equity |
| 4001 | Revenue |

---

## Testing Requirements

Every new feature or route added to the codebase **must** have corresponding tests.

### Test structure
- **Unit tests** (`tests/Unit/`) — test model methods in isolation via `new Model([...])`. No database, no HTTP, no `RefreshDatabase`. These run fast.
- **Feature tests** (`tests/Feature/`) — test service layer and HTTP lifecycle end-to-end with `RefreshDatabase`.

### HTTP feature test setup
Routes under `middleware('plan:feature_name')` require:
1. A `Plan` with `limits = ['feature_name' => true]`
2. The `Tenant` with `plan_id`, `subscription_status = 'active'`, and `subscription_expires_at` in the future
3. `app(BookkeepingService::class)->provisionDefaultAccounts($tenant)` for any test that posts GL entries

```php
// Minimum setup for inventory routes:
$plan = Plan::create(['slug' => 'business', ..., 'limits' => ['inventory' => true]]);
$tenant = Tenant::create([..., 'plan_id' => $plan->id, 'subscription_status' => 'active', 'subscription_expires_at' => now()->addYear()]);
```

### Bypassing global scopes in tests
When seeding test data directly (not via HTTP), bypass tenant global scopes:
```php
InventoryItem::withoutGlobalScope('tenant')->create([...]);
RestockRequest::withoutGlobalScope('tenant')->create([...]);
```

### What to test per feature
| What changed | Required tests |
|---|---|
| New route | HTTP test: happy path + 403/redirect for unauthorised role |
| New model method | Unit test covering normal case + edge/boundary case |
| GL-posting action | Assert `Transaction` and `JournalEntry` debits == credits |
| Plan/limit gate | Test allowed plan and denied plan separately |

---

## Module Access Pattern

Routes are protected with `middleware('plan:feature_name')` which checks both plan-level (`Tenant::planAllows()`) and user-level (`User::canAccess()`) access.

Nav availability variables (`$canInventory`, `$canManufacturing`, `$canMaintenance`, etc.) are computed once in `resources/views/layouts/app.blade.php`.

Current plan feature flags: `payroll`, `firs`, `advanced_reports`, `inventory`, `inventory_reports`, `manufacturing`, `maintenance`, `api_access`.
