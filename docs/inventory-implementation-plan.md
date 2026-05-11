# Inventory, Stock & Sales Management — Implementation Plan

**Project:** AccountTaxNG  
**Date:** 2026-05-11  
**Status:** Planning  
**Confirmed Decisions:**
- COGS Method: Weighted Average
- Walk-in / cash sales: allowed (customer_id nullable)
- Stock locations: single location
- Invoice integration: reuse existing `Invoice` / `InvoiceItem` models

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Database Schema](#2-database-schema)
3. [Models](#3-models)
4. [Roles & Permissions](#4-roles--permissions)
5. [Phase 1 — Migrations & Models](#phase-1--migrations--models)
6. [Phase 2 — Item Catalog UI](#phase-2--item-catalog-ui)
7. [Phase 3 — Low Stock Alerts](#phase-3--low-stock-alerts)
8. [Phase 4 — Sales Order Flow](#phase-4--sales-order-flow)
9. [Phase 5 — Restock Request Workflow](#phase-5--restock-request-workflow)
10. [Phase 6 — Invoice & Bookkeeping Integration](#phase-6--invoice--bookkeeping-integration)
11. [Phase 7 — Reports](#phase-7--reports)
12. [Routes](#12-routes)
13. [Estimated Timeline](#13-estimated-timeline)

---

## 1. Architecture Overview

The inventory module sits inside the existing multi-tenant Laravel app. All new tables carry `tenant_id` (FK → `tenants.id`) and are scoped by a global scope on each model, matching the pattern used across `Account`, `Invoice`, `Transaction`, etc.

### How it connects to existing models

```
InventoryItem ──────────────────────────────────────────────────────┐
     │                                                               │
     ├── StockMovement (type: sale)  ──► SalesOrder ──► Invoice      │
     │                                       │          (existing)   │
     ├── StockMovement (type: restock) ◄── RestockRequest            │
     │                                       │                       │
     └── InventoryAlert                      └──► Transaction        │
                                                  + JournalEntry     │
                                                  (via Bookkeeping   │
                                                   Service)          │
                                                                     │
Account 1200 Inventory  ◄────────────────────────────────────────────┘
Account 5001 COGS
Account 1100 Accounts Receivable
Account 2001 Accounts Payable
Account 4001 Sales Revenue
```

### Weighted Average Cost — Calculation Rule

On every restock receipt:
```
new_avg_cost = (current_stock × current_avg_cost + qty_received × unit_cost)
               ÷ (current_stock + qty_received)
```

`avg_cost` is stored on `inventory_items` and updated atomically inside a DB transaction whenever a `RestockRequest` is marked `received`. The sale records `cost_price_at_sale` by snapshotting `avg_cost` at the moment of sale — this locks in COGS even if future restocks change the average.

---

## 2. Database Schema

### 2.1 `inventory_categories`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `tenant_id` | FK → tenants | |
| `name` | string(100) | |
| `description` | text nullable | |
| `is_active` | boolean | default true |
| `created_at`, `updated_at` | timestamps | |

---

### 2.2 `inventory_items`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `tenant_id` | FK → tenants | |
| `category_id` | FK → inventory_categories nullable | |
| `name` | string(150) | |
| `sku` | string(50) nullable | unique per tenant |
| `description` | text nullable | |
| `unit` | string(30) | e.g. "piece", "kg", "carton" |
| `selling_price` | decimal(15,2) | |
| `cost_price` | decimal(15,2) | initial cost (used before first restock) |
| `avg_cost` | decimal(15,2) | weighted average — updated on every restock |
| `current_stock` | decimal(15,3) | denormalised cache; source of truth is stock_movements |
| `restock_level` | decimal(15,3) | alert fires when current_stock ≤ this |
| `is_active` | boolean | default true |
| `created_by` | FK → users | |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp nullable | soft delete |

> **Note:** `current_stock` uses decimal(15,3) to support fractional units (e.g. kg, litres). For whole-unit businesses this is fine — just always input integers.

---

### 2.3 `stock_movements`

Append-only ledger. Never updated after insert.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `tenant_id` | FK → tenants | |
| `item_id` | FK → inventory_items | |
| `type` | enum | `sale`, `restock`, `adjustment_in`, `adjustment_out`, `opening` |
| `quantity` | decimal(15,3) | always positive |
| `unit_cost` | decimal(15,2) | cost at time of movement |
| `running_balance` | decimal(15,3) | stock level after this movement |
| `reference_type` | string nullable | polymorphic: `SalesOrder`, `RestockRequest` |
| `reference_id` | bigint nullable | |
| `notes` | text nullable | |
| `created_by` | FK → users | |
| `created_at` | timestamp | no `updated_at` — immutable |

---

### 2.4 `restock_requests`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `tenant_id` | FK → tenants | |
| `item_id` | FK → inventory_items | |
| `request_number` | string | auto-generated e.g. RST-2026-0001 |
| `quantity_requested` | decimal(15,3) | |
| `unit_cost` | decimal(15,2) | estimated or quoted cost |
| `supplier_name` | string(150) nullable | |
| `supplier_invoice_no` | string(100) nullable | filled on receipt |
| `notes` | text nullable | |
| `status` | enum | `pending`, `approved`, `rejected`, `received`, `cancelled` |
| `requested_by` | FK → users | |
| `approved_by` | FK → users nullable | |
| `approved_at` | timestamp nullable | |
| `received_at` | timestamp nullable | |
| `rejection_reason` | text nullable | |
| `invoice_id` | FK → invoices nullable | supplier bill generated on receipt |
| `created_at`, `updated_at` | timestamps | |

---

### 2.5 `sales_orders`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `tenant_id` | FK → tenants | |
| `order_number` | string | auto-generated e.g. SO-2026-0001 |
| `customer_id` | FK → customers nullable | null = walk-in / cash sale |
| `customer_name` | string(150) nullable | for walk-in sales (no customer record) |
| `sale_date` | date | |
| `subtotal` | decimal(15,2) | |
| `vat_amount` | decimal(15,2) | default 0 |
| `discount_amount` | decimal(15,2) | default 0 |
| `total_amount` | decimal(15,2) | |
| `payment_method` | enum | `cash`, `bank_transfer`, `pos`, `cheque`, `online` |
| `payment_reference` | string nullable | |
| `status` | enum | `draft`, `confirmed`, `cancelled` |
| `notes` | text nullable | |
| `invoice_id` | FK → invoices nullable | created on confirmation |
| `transaction_id` | FK → transactions nullable | GL posting on confirmation |
| `created_by` | FK → users | |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp nullable | soft delete |

---

### 2.6 `sale_order_items`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `sale_order_id` | FK → sales_orders | |
| `item_id` | FK → inventory_items | |
| `description` | string | snapshot of item name at time of sale |
| `quantity` | decimal(15,3) | |
| `unit_price` | decimal(15,2) | selling price at time of sale |
| `cost_price_at_sale` | decimal(15,2) | snapshot of avg_cost → used for COGS |
| `subtotal` | decimal(15,2) | quantity × unit_price |
| `vat_applicable` | boolean | default false |
| `vat_rate` | decimal(5,2) | default 7.5 |
| `vat_amount` | decimal(15,2) | default 0 |
| `total` | decimal(15,2) | |
| `sort_order` | unsignedInteger | |
| `created_at`, `updated_at` | timestamps | |

---

### 2.7 `inventory_alerts`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `tenant_id` | FK → tenants | |
| `item_id` | FK → inventory_items | |
| `type` | enum | `low_stock`, `out_of_stock` |
| `stock_at_alert` | decimal(15,3) | snapshot of stock when alert fired |
| `notified_at` | timestamp nullable | when email was sent |
| `seen_at` | timestamp nullable | when admin dismissed the alert |
| `created_at`, `updated_at` | timestamps | |

---

## 3. Models

### 3.1 `InventoryItem`

```php
class InventoryItem extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'tenant_id', 'category_id', 'name', 'sku', 'description', 'unit',
        'selling_price', 'cost_price', 'avg_cost', 'current_stock',
        'restock_level', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'selling_price'  => 'decimal:2',
            'cost_price'     => 'decimal:2',
            'avg_cost'       => 'decimal:2',
            'current_stock'  => 'decimal:3',
            'restock_level'  => 'decimal:3',
            'is_active'      => 'boolean',
        ];
    }

    // Relationships
    public function tenant(): BelongsTo     // → Tenant
    public function category(): BelongsTo   // → InventoryCategory
    public function movements(): HasMany    // → StockMovement
    public function alerts(): HasMany       // → InventoryAlert

    // Helpers
    public function isBelowRestockLevel(): bool
    public function isOutOfStock(): bool
    public function recalculateAvgCost(float $qtyIn, float $unitCost): float
}
```

**Observer** (`InventoryItemObserver`): fires after `current_stock` is decremented → checks restock level → creates `InventoryAlert` and dispatches `SendLowStockNotification` job.

---

### 3.2 `StockMovement`

```php
// Immutable — no update/delete allowed
class StockMovement extends Model
{
    public $timestamps = false; // only created_at
    const UPDATED_AT = null;

    protected $fillable = [
        'tenant_id', 'item_id', 'type', 'quantity', 'unit_cost',
        'running_balance', 'reference_type', 'reference_id', 'notes', 'created_by',
    ];

    public function item(): BelongsTo
    public function reference(): MorphTo   // → SalesOrder or RestockRequest
    public function creator(): BelongsTo   // → User
}
```

---

### 3.3 `RestockRequest`

```php
class RestockRequest extends Model
{
    // Status constants
    const STATUS_PENDING   = 'pending';
    const STATUS_APPROVED  = 'approved';
    const STATUS_REJECTED  = 'rejected';
    const STATUS_RECEIVED  = 'received';
    const STATUS_CANCELLED = 'cancelled';

    public function item(): BelongsTo
    public function requester(): BelongsTo   // via requested_by
    public function approver(): BelongsTo    // via approved_by
    public function invoice(): BelongsTo     // → Invoice (supplier bill)

    public function canBeApproved(): bool    // status === pending
    public function canBeReceived(): bool    // status === approved
}
```

---

### 3.4 `SalesOrder`

```php
class SalesOrder extends Model
{
    use SoftDeletes;

    const STATUS_DRAFT     = 'draft';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';

    public function customer(): BelongsTo    // → Customer (nullable)
    public function items(): HasMany         // → SaleOrderItem
    public function invoice(): BelongsTo     // → Invoice
    public function transaction(): BelongsTo // → Transaction
    public function creator(): BelongsTo     // → User

    public function recalculateTotals(): void
    public function canBeConfirmed(): bool   // status === draft + items not empty
    public function canBeCancelled(): bool   // status ∈ [draft, confirmed]
}
```

---

## 4. Roles & Permissions

| Action | admin | accountant | staff |
|---|:---:|:---:|:---:|
| Manage item catalog (CRUD) | ✅ | ✅ | ❌ |
| View item catalog | ✅ | ✅ | ✅ |
| Create / edit sales order | ✅ | ✅ | ✅ |
| Confirm sales order | ✅ | ✅ | ✅ |
| Cancel sales order | ✅ | ✅ | own only |
| Create restock request | ✅ | ✅ | ✅ |
| Approve / reject restock | ✅ | ✅ | ❌ |
| Mark restock received | ✅ | ✅ | ❌ |
| Manual stock adjustment | ✅ | ✅ | ❌ |
| View inventory reports | ✅ | ✅ | limited |
| Dismiss stock alerts | ✅ | ✅ | ❌ |

**Policy classes to create:** `InventoryItemPolicy`, `SalesOrderPolicy`, `RestockRequestPolicy`

Uses existing `User::isAdmin()` and `User::isAccountant()` helper methods.

---

## Phase 1 — Migrations & Models

**Goal:** Database foundation with all 7 tables, models, relationships, global tenant scope.

### Tasks

- [ ] Migration: `create_inventory_categories_table`
- [ ] Migration: `create_inventory_items_table`
- [ ] Migration: `create_stock_movements_table`
- [ ] Migration: `create_restock_requests_table`
- [ ] Migration: `create_sales_orders_table`
- [ ] Migration: `create_sale_order_items_table`
- [ ] Migration: `create_inventory_alerts_table`
- [ ] Model: `InventoryCategory` with `TenantScope` global scope
- [ ] Model: `InventoryItem` with observer registration
- [ ] Model: `StockMovement` (immutable, `created_at` only)
- [ ] Model: `RestockRequest` with status constants
- [ ] Model: `SalesOrder` with soft delete
- [ ] Model: `SaleOrderItem`
- [ ] Model: `InventoryAlert`
- [ ] Observer: `InventoryItemObserver`
- [ ] Add `HasMany` relationships to existing `Tenant` model
- [ ] Policy: `InventoryItemPolicy`, `SalesOrderPolicy`, `RestockRequestPolicy`
- [ ] Register policies in `AuthServiceProvider`

**Estimated: 1–2 days**

---

## Phase 2 — Item Catalog UI

**Goal:** Admin/Accountant can manage the item catalog.

### Controllers

```
app/Http/Controllers/Inventory/
    InventoryCategoryController.php   (resource: index, store, update, destroy)
    InventoryItemController.php       (resource: index, create, store, edit, update, destroy)
                                      + adjustStock() POST
```

### Views

```
resources/views/inventory/
    items/
        index.blade.php    — searchable table, stock badges, low-stock highlight
        create.blade.php   — item form
        edit.blade.php     — item form (pre-filled)
        show.blade.php     — item detail + movement history tab
    categories/
        index.blade.php    — inline edit/delete table
```

### UI Features

- Stock status badge: `In Stock` (green) / `Low Stock` (amber) / `Out of Stock` (red)
- Searchable + filterable by category and status
- Movement history tab on `show.blade.php` — paginated `StockMovement` log
- Manual adjustment form (admin/accountant only): reason, quantity, type (in/out)

**Estimated: 2 days**

---

## Phase 3 — Low Stock Alerts

**Goal:** Automatic notification when stock falls to or below restock level.

### Implementation

**`InventoryItemObserver@updated`:**
```php
if ($item->isDirty('current_stock') && $item->current_stock <= $item->restock_level) {
    $type = $item->current_stock <= 0 ? 'out_of_stock' : 'low_stock';
    // Only create alert if no unacknowledged alert already exists for this item+type
    InventoryAlert::firstOrCreate([
        'tenant_id' => $item->tenant_id,
        'item_id'   => $item->id,
        'type'      => $type,
        'seen_at'   => null,
    ], ['stock_at_alert' => $item->current_stock]);
    SendLowStockNotification::dispatch($item);
}
```

**`SendLowStockNotification` (queued job):**
- Notifies all `admin` and `accountant` users of the tenant
- Sends database notification + email
- Sets `notified_at` on the alert

**Dashboard alert bar:**
- Query unseen alerts for current tenant
- Show dismissable banner: "3 items are low on stock — [View]"
- `POST /inventory/alerts/{alert}/dismiss` → sets `seen_at`

**Estimated: 1 day**

---

## Phase 4 — Sales Order Flow

**Goal:** Sales staff can create and confirm sales; stock is decremented; invoice is generated.

### Controller

```
app/Http/Controllers/Inventory/SalesOrderController.php
    index()     GET  /inventory/sales
    create()    GET  /inventory/sales/create
    store()     POST /inventory/sales
    show()      GET  /inventory/sales/{order}
    edit()      GET  /inventory/sales/{order}/edit
    update()    PUT  /inventory/sales/{order}
    confirm()   POST /inventory/sales/{order}/confirm
    cancel()    POST /inventory/sales/{order}/cancel
```

### Confirmation Flow (inside DB transaction)

```php
DB::transaction(function () use ($order) {

    // 1. Validate stock availability
    foreach ($order->items as $line) {
        if ($line->item->current_stock < $line->quantity) {
            throw new InsufficientStockException($line->item);
        }
    }

    // 2. Snapshot avg_cost, write StockMovements, decrement current_stock
    foreach ($order->items as $line) {
        $line->update(['cost_price_at_sale' => $line->item->avg_cost]);

        StockMovement::create([
            'tenant_id'        => $order->tenant_id,
            'item_id'          => $line->item_id,
            'type'             => 'sale',
            'quantity'         => $line->quantity,
            'unit_cost'        => $line->item->avg_cost,
            'running_balance'  => $line->item->current_stock - $line->quantity,
            'reference_type'   => SalesOrder::class,
            'reference_id'     => $order->id,
            'created_by'       => auth()->id(),
        ]);

        $line->item->decrement('current_stock', $line->quantity);
    }

    // 3. Create Invoice (reuse existing Invoice model)
    $invoice = Invoice::create([
        'tenant_id'      => $order->tenant_id,
        'customer_id'    => $order->customer_id,   // nullable for walk-in
        'invoice_number' => $this->nextInvoiceNumber($order->tenant_id),
        'invoice_date'   => $order->sale_date,
        'due_date'       => $order->sale_date,      // immediate for cash sales
        'subtotal'       => $order->subtotal,
        'vat_amount'     => $order->vat_amount,
        'total_amount'   => $order->total_amount,
        'amount_paid'    => $order->total_amount,   // cash = paid immediately
        'balance_due'    => 0,
        'status'         => 'paid',
        'is_b2c'         => is_null($order->customer_id),
        'currency'       => 'NGN',
        'created_by'     => auth()->id(),
    ]);

    // Mirror line items into InvoiceItem records
    foreach ($order->items as $line) {
        $invoice->items()->create([
            'description'   => $line->description,
            'quantity'      => $line->quantity,
            'unit_price'    => $line->unit_price,
            'subtotal'      => $line->subtotal,
            'vat_applicable'=> $line->vat_applicable,
            'vat_rate'      => $line->vat_rate,
            'vat_amount'    => $line->vat_amount,
            'total'         => $line->total,
            'account_code'  => '4001',   // Sales Revenue
        ]);
    }

    // 4. Post GL entries via BookkeepingService
    $cogs = $order->items->sum(fn($l) => $l->quantity * $l->cost_price_at_sale);

    app(BookkeepingService::class)->postJournalEntry(
        tenant: $order->tenant,
        data: [
            'reference'        => $invoice->invoice_number,
            'transaction_date' => $order->sale_date,
            'type'             => 'sale',
            'amount'           => $order->total_amount,
            'description'      => "Sale: {$order->order_number}",
            'created_by'       => auth()->id(),
        ],
        entries: [
            // Cash/AR debit
            ['account_code' => '1001', 'entry_type' => 'debit',  'amount' => $order->total_amount],
            // Sales Revenue credit
            ['account_code' => '4001', 'entry_type' => 'credit', 'amount' => $order->subtotal],
            // VAT Payable credit (if applicable)
            ...($order->vat_amount > 0 ? [
                ['account_code' => '2100', 'entry_type' => 'credit', 'amount' => $order->vat_amount],
            ] : []),
            // COGS debit
            ['account_code' => '5001', 'entry_type' => 'debit',  'amount' => $cogs],
            // Inventory Asset credit
            ['account_code' => '1200', 'entry_type' => 'credit', 'amount' => $cogs],
        ]
    );

    // 5. Link invoice + transaction to sales order
    $order->update([
        'status'     => SalesOrder::STATUS_CONFIRMED,
        'invoice_id' => $invoice->id,
    ]);
});
```

### Cancellation Flow

- Reverse `StockMovement` rows with `adjustment_in` entries
- Void the linked `Invoice` (`status = 'void'`)
- Post reversal journal entry
- Update `SalesOrder.status = 'cancelled'`

**Estimated: 3 days**

---

## Phase 5 — Restock Request Workflow

**Goal:** Sales staff requests restock → accountant/admin approves → goods received → stock updated + supplier bill generated.

### Controller

```
app/Http/Controllers/Inventory/RestockRequestController.php
    index()    GET  /inventory/restock
    create()   GET  /inventory/restock/create
    store()    POST /inventory/restock
    show()     GET  /inventory/restock/{request}
    approve()  POST /inventory/restock/{request}/approve
    reject()   POST /inventory/restock/{request}/reject
    receive()  POST /inventory/restock/{request}/receive
    cancel()   POST /inventory/restock/{request}/cancel
```

### Receive Flow (inside DB transaction)

```php
DB::transaction(function () use ($restockRequest) {

    // 1. Recalculate weighted average cost
    $item      = $restockRequest->item;
    $newAvgCost = $item->recalculateAvgCost(
        $restockRequest->quantity_requested,
        $restockRequest->unit_cost
    );

    // 2. Write StockMovement
    StockMovement::create([
        'tenant_id'       => $restockRequest->tenant_id,
        'item_id'         => $item->id,
        'type'            => 'restock',
        'quantity'        => $restockRequest->quantity_requested,
        'unit_cost'       => $restockRequest->unit_cost,
        'running_balance' => $item->current_stock + $restockRequest->quantity_requested,
        'reference_type'  => RestockRequest::class,
        'reference_id'    => $restockRequest->id,
        'created_by'      => auth()->id(),
    ]);

    // 3. Update item stock + avg_cost atomically
    $item->update([
        'current_stock' => $item->current_stock + $restockRequest->quantity_requested,
        'avg_cost'      => $newAvgCost,
    ]);

    // 4. Generate supplier bill using existing Invoice model
    //    (type hint: purchase — uses is_b2c=false, customer_id=null)
    $bill = Invoice::create([
        'tenant_id'      => $restockRequest->tenant_id,
        'customer_id'    => null,
        'invoice_number' => 'BILL-' . $this->nextBillNumber($restockRequest->tenant_id),
        'reference'      => $restockRequest->supplier_invoice_no,
        'invoice_date'   => now()->toDateString(),
        'due_date'       => now()->addDays(30)->toDateString(),
        'subtotal'       => $restockRequest->quantity_requested * $restockRequest->unit_cost,
        'total_amount'   => $restockRequest->quantity_requested * $restockRequest->unit_cost,
        'balance_due'    => $restockRequest->quantity_requested * $restockRequest->unit_cost,
        'status'         => 'sent',   // awaiting payment to vendor
        'notes'          => "Restock: {$restockRequest->request_number} | Supplier: {$restockRequest->supplier_name}",
        'currency'       => 'NGN',
        'created_by'     => auth()->id(),
    ]);

    // 5. Post GL entries
    $totalCost = $restockRequest->quantity_requested * $restockRequest->unit_cost;

    app(BookkeepingService::class)->postJournalEntry(
        tenant: $restockRequest->tenant,
        data: [
            'reference'        => $bill->invoice_number,
            'transaction_date' => now()->toDateString(),
            'type'             => 'purchase',
            'amount'           => $totalCost,
            'description'      => "Restock received: {$restockRequest->request_number}",
            'created_by'       => auth()->id(),
        ],
        entries: [
            // Inventory Asset debit
            ['account_code' => '1200', 'entry_type' => 'debit',  'amount' => $totalCost],
            // Accounts Payable credit (owed to supplier)
            ['account_code' => '2001', 'entry_type' => 'credit', 'amount' => $totalCost],
        ]
    );

    // 6. Update restock request
    $restockRequest->update([
        'status'      => RestockRequest::STATUS_RECEIVED,
        'received_at' => now(),
        'invoice_id'  => $bill->id,
    ]);
});
```

### Notifications

| Event | Who is notified |
|---|---|
| New restock request created | All `accountant` + `admin` users of tenant |
| Request approved | The `requested_by` user |
| Request rejected | The `requested_by` user |
| Request received | The `approved_by` user + tenant admins |

**Estimated: 2 days**

---

## Phase 6 — Invoice & Bookkeeping Integration

**Goal:** Ensure all financial flows are correctly wired; no orphaned GL entries.

### Account Codes Used

| Account | Code | Direction |
|---|---|---|
| Cash on Hand | 1001 | Debit on sale receipt |
| Accounts Receivable | 1100 | Debit on credit sale |
| Inventory Asset | 1200 | Debit on restock; Credit on sale (COGS) |
| Accounts Payable | 2001 | Credit on restock receipt |
| VAT Payable | 2100 | Credit on VAT-applicable sales |
| Sales Revenue | 4001 | Credit on every sale |
| Cost of Goods Sold | 5001 | Debit on every sale |

> All codes exist in `Account::DEFAULT_ACCOUNTS` — no new accounts needed.

### Invoice `is_b2c` Usage

- Walk-in / cash sales: `is_b2c = true`, `customer_id = null`, `customer_name` stored on `SalesOrder`
- Named customer sales: `is_b2c = false`, `customer_id` set
- Supplier bills: `is_b2c = false`, `customer_id = null`, `reference` = supplier invoice number

### Existing `Invoice::recalculateTotals()` Integration

After creating `InvoiceItem` records, call `$invoice->recalculateTotals()` to ensure all totals are consistent.

**Estimated: 2 days**

---

## Phase 7 — Reports

**Goal:** Management can view stock health and sales performance.

### Report Pages

```
app/Http/Controllers/Inventory/InventoryReportController.php
    stockValuation()   GET /inventory/reports/stock-valuation
    lowStock()         GET /inventory/reports/low-stock
    movements()        GET /inventory/reports/movements
    salesByItem()      GET /inventory/reports/sales-by-item
    salesByPeriod()    GET /inventory/reports/sales-by-period
    restockHistory()   GET /inventory/reports/restock-history
```

### Report Definitions

**Stock Valuation Report**
```
Item | Category | Unit | Qty in Stock | Avg Cost | Stock Value | Selling Price | Potential Revenue
```
- `stock_value = current_stock × avg_cost`
- `potential_revenue = current_stock × selling_price`
- Total row at bottom

**Low Stock Report**
```
Item | SKU | Current Stock | Restock Level | Shortfall | Last Restocked
```
- Filter: `current_stock <= restock_level`
- Action button: "Create Restock Request"

**Stock Movement Log**
- Date range filter + item filter + type filter
- Shows: date, item, type, qty in, qty out, running balance, reference, user

**Sales by Item** (date range filter)
```
Item | Units Sold | Revenue | COGS | Gross Profit | Margin %
```
- Sourced from `sale_order_items` joined to confirmed `sales_orders`

**Sales by Period** (group by day/week/month)
```
Period | Orders | Units Sold | Revenue | COGS | Gross Profit
```

**Restock History**
```
Request No. | Item | Qty | Unit Cost | Total Cost | Supplier | Requested By | Approved By | Received Date
```
- Filter by status and date range

### Export

All reports: `Export to CSV` button using Laravel's built-in `Response::streamDownload()`.

**Estimated: 2 days**

---

## 12. Routes

Add to `routes/web.php` inside the `auth` middleware group:

```php
Route::prefix('inventory')->name('inventory.')->middleware(['auth'])->group(function () {

    // Item Catalog
    Route::resource('categories', InventoryCategoryController::class)
         ->except(['show']);
    Route::resource('items', InventoryItemController::class);
    Route::post('items/{item}/adjust', [InventoryItemController::class, 'adjustStock'])
         ->name('items.adjust');

    // Sales Orders
    Route::resource('sales', SalesOrderController::class)
         ->except(['destroy']);
    Route::post('sales/{order}/confirm', [SalesOrderController::class, 'confirm'])
         ->name('sales.confirm');
    Route::post('sales/{order}/cancel',  [SalesOrderController::class, 'cancel'])
         ->name('sales.cancel');

    // Restock Requests
    Route::resource('restock', RestockRequestController::class)
         ->except(['edit', 'update', 'destroy']);
    Route::post('restock/{request}/approve', [RestockRequestController::class, 'approve'])
         ->name('restock.approve');
    Route::post('restock/{request}/reject',  [RestockRequestController::class, 'reject'])
         ->name('restock.reject');
    Route::post('restock/{request}/receive', [RestockRequestController::class, 'receive'])
         ->name('restock.receive');
    Route::post('restock/{request}/cancel',  [RestockRequestController::class, 'cancel'])
         ->name('restock.cancel');

    // Alerts
    Route::post('alerts/{alert}/dismiss', [InventoryAlertController::class, 'dismiss'])
         ->name('alerts.dismiss');

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('stock-valuation',  [InventoryReportController::class, 'stockValuation'])
             ->name('stock-valuation');
        Route::get('low-stock',        [InventoryReportController::class, 'lowStock'])
             ->name('low-stock');
        Route::get('movements',        [InventoryReportController::class, 'movements'])
             ->name('movements');
        Route::get('sales-by-item',    [InventoryReportController::class, 'salesByItem'])
             ->name('sales-by-item');
        Route::get('sales-by-period',  [InventoryReportController::class, 'salesByPeriod'])
             ->name('sales-by-period');
        Route::get('restock-history',  [InventoryReportController::class, 'restockHistory'])
             ->name('restock-history');
    });
});
```

---

## 13. Estimated Timeline

| Phase | Description | Days |
|---|---|:---:|
| 1 | Migrations, Models, Policies | 2 |
| 2 | Item Catalog UI | 2 |
| 3 | Low Stock Alerts | 1 |
| 4 | Sales Order Flow | 3 |
| 5 | Restock Request Workflow | 2 |
| 6 | Invoice & Bookkeeping Integration | 2 |
| 7 | Reports | 2 |
| **Total** | | **14** |

---

## Appendix — Directory Structure

```
app/
  Events/
    LowStockDetected.php
  Http/Controllers/Inventory/
    InventoryCategoryController.php
    InventoryItemController.php
    InventoryAlertController.php
    SalesOrderController.php
    RestockRequestController.php
    InventoryReportController.php
  Jobs/
    SendLowStockNotification.php
  Models/
    InventoryCategory.php
    InventoryItem.php
    StockMovement.php
    RestockRequest.php
    SalesOrder.php
    SaleOrderItem.php
    InventoryAlert.php
  Observers/
    InventoryItemObserver.php
  Policies/
    InventoryItemPolicy.php
    SalesOrderPolicy.php
    RestockRequestPolicy.php

database/migrations/
  2026_05_11_000001_create_inventory_categories_table.php
  2026_05_11_000002_create_inventory_items_table.php
  2026_05_11_000003_create_stock_movements_table.php
  2026_05_11_000004_create_restock_requests_table.php
  2026_05_11_000005_create_sales_orders_table.php
  2026_05_11_000006_create_sale_order_items_table.php
  2026_05_11_000007_create_inventory_alerts_table.php

resources/views/inventory/
  items/
    index.blade.php
    create.blade.php
    edit.blade.php
    show.blade.php
  categories/
    index.blade.php
  sales/
    index.blade.php
    create.blade.php
    show.blade.php
  restock/
    index.blade.php
    create.blade.php
    show.blade.php
  reports/
    stock-valuation.blade.php
    low-stock.blade.php
    movements.blade.php
    sales-by-item.blade.php
    sales-by-period.blade.php
    restock-history.blade.php
  partials/
    alert-banner.blade.php
```
