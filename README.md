# 🇳🇬 AccountTaxNG — Nigerian SME Tax & Bookkeeping SaaS

A production-ready multi-tenant SaaS platform for Nigerian SMEs to manage bookkeeping, invoicing, and full tax compliance with NRS regulations.

---

## 🎯 Features

### Bookkeeping Engine
- Double-entry accounting (full debit/credit ledger)
- Nigerian SME Chart of Accounts (pre-provisioned)
- Trial Balance, Profit & Loss, Balance Sheet

### Invoicing (Tax-Compliant)
- Auto-generated invoice numbers (`INV-YYYYMM-NNNN`)
- **VAT @ 7.5%** auto-calculated per line item (Finance Act 2019)
- **WHT deduction** at source (5% / 10% configurable)
- TIN + VAT Number printed on every invoice
- PDF generation (DomPDF)
- Payment tracking (partial / full)

### 🏛️ Tax Engine (Core Nigerian Taxes)

| Tax | Rate | Deadline | Notes |
|-----|------|----------|-------|
| **VAT** | 7.5% | 21st of following month | Mandatory if turnover > ₦25M |
| **WHT (Services/Company)** | 5% | Monthly | Deducted at source |
| **WHT (Services/Individual)** | 10% | Monthly | Deducted at source |
| **WHT (Rent/Dividends)** | 10% | Monthly | — |
| **CIT (Small ≤₦25M)** | **0%** | 6 months after year-end | Filing still required |
| **CIT (Medium ₦25M–₦100M)** | **20%** | 6 months after year-end | + 2.5% Education Tax |
| **CIT (Large >₦100M)** | **30%** | 6 months after year-end | + 2.5% Education Tax |
| **PAYE** | 7% – 24% | Monthly (10th) | Progressive bands |

### Payroll & PAYE
- Employee salary structures (basic + allowances)
- PAYE progressive tax computation (Nigerian bands)
- Pension (Employee 8% / Employer 10%)
- NHF (2.5% of basic salary)
- Payslip generation

### Compliance Dashboard
- Real-time compliance score (0–100)
- Overdue VAT return alerts
- WHT schedule and remittance tracking
- NRS TaxPro-Max filing reference tracking
- Audit trail (immutable logs for every financial action)

### Multi-Tenancy
- Single database, tenant-scoped queries
- Company registration with auto tax category assignment
- Role-based access: **Admin**, **Accountant**, **Staff**

---

## 🚀 Installation

### Requirements
- PHP 8.2+
- MySQL 8.0+ or PostgreSQL 14+
- Redis (queues + caching)
- Composer 2.x
- Node.js 20+

### Setup

```bash
# 1. Install PHP dependencies
composer install

# 2. Copy environment file
cp .env.example .env

# 3. Generate application key
php artisan key:generate

# 4. Configure your database in .env
#    DB_CONNECTION=mysql
#    DB_DATABASE=accounttaxng

# 5. Run migrations
php artisan migrate

# 6. Seed demo data
php artisan db:seed

# 7. Install Node dependencies and build assets
npm install && npm run dev

# 8. Start the development server
php artisan serve
```

Visit `http://localhost:8000`

### Demo Credentials (after seeding)

| Role | Email | Password | Company |
|------|-------|----------|---------|
| Admin | admin@adetokunboventures.ng | password | Adetokunbo Ventures (Small – 0% CIT) |
| Admin | admin@chukwuemekatrading.com | password | Chukwuemeka & Sons (Medium – 20% CIT) |

---

## 🔧 Configuration

### Tax Settings (`.env`)
```env
NIGERIAN_VAT_RATE=7.5
NIGERIAN_VAT_THRESHOLD=25000000
NIGERIAN_CIT_SMALL_MAX=25000000
NIGERIAN_CIT_MEDIUM_MAX=100000000
```

### Scheduler
Add to server crontab:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled jobs:
- `tax:send-reminders` — Daily 08:00 (VAT + CIT filing reminders)
- `tax:generate-vat-returns` — 1st of each month
- `invoices:mark-overdue` — Daily 00:05

---

## 🏗️ Architecture

```
app/
├── Console/Commands/
│   ├── SendTaxReminders.php         # VAT + CIT email reminders
│   ├── GenerateVatReturns.php       # Monthly VAT return generation
│   └── MarkOverdueInvoices.php      # Daily overdue sweep
├── Http/
│   ├── Controllers/
│   │   ├── Api/                     # REST API (Sanctum-protected)
│   │   ├── Auth/AuthController.php
│   │   ├── DashboardController.php
│   │   ├── InvoiceController.php
│   │   ├── PayrollController.php
│   │   ├── ReportController.php
│   │   ├── TaxController.php
│   │   └── TransactionController.php
│   ├── Middleware/
│   │   ├── TenantMiddleware.php     # Resolves + binds current tenant
│   │   ├── RoleMiddleware.php       # RBAC enforcement
│   │   └── AuditLogMiddleware.php   # Immutable audit trail
│   └── Requests/InvoiceRequest.php
├── Models/
│   ├── Tenant.php                   # Company (tax category, VAT)
│   ├── Invoice.php + InvoiceItem.php + InvoicePayment.php
│   ├── Transaction.php + JournalEntry.php
│   ├── VatReturn.php, WhtRecord.php, CitRecord.php
│   ├── Employee.php, Payroll.php, PayrollItem.php
│   └── AuditLog.php
├── Services/
│   ├── VatService.php               # VAT 7.5%, monthly returns
│   ├── WhtService.php               # WHT rates + deduction logic
│   ├── CitService.php               # CIT 0/20/30%, min tax, edu tax
│   ├── PayeService.php              # PAYE bands, pension, NHF
│   ├── InvoiceService.php           # Invoice lifecycle + payment
│   ├── BookkeepingService.php       # Double-entry, trial balance, P&L
│   ├── ReportService.php            # Compliance dashboard, reports
│   └── TenancyService.php           # Tenant registration + resolution
└── Repositories/
    ├── InvoiceRepository.php
    ├── TransactionRepository.php
    └── TaxRepository.php
```

---

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Unit tests only (no DB)
php artisan test --testsuite=Unit

# Feature tests
php artisan test --testsuite=Feature
```

| Test Class | Coverage |
|-----------|----------|
| `VatCalculationTest` | 7.5% VAT, reverse VAT, filing deadlines |
| `WhtCalculationTest` | 5%/10% WHT rates, gross/net calculation |
| `CitCalculationTest` | Company size tiers, 0/20/30% rates, edu tax |
| `PayeCalculationTest` | Progressive PAYE bands, CRA, pension, NHF |
| `InvoiceTest` | Invoice creation, VAT/WHT, payment lifecycle |
| `TaxComplianceTest` | VAT thresholds, CIT exemptions, tenant setup |

---

## 🔌 REST API

Base URL: `/api/v1`

### Authentication
```bash
POST /api/v1/auth/token
{"email": "admin@company.ng", "password": "password"}
# Returns: {"token": "...", "user": {...}}
```

### Key Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/invoices` | List invoices |
| POST | `/api/v1/invoices` | Create invoice |
| POST | `/api/v1/invoices/{id}/payment` | Record payment |
| GET | `/api/v1/tax/compliance-dashboard` | Full compliance status |
| POST | `/api/v1/tax/vat/compute` | Compute VAT return |
| POST | `/api/v1/tax/wht/schedule` | WHT monthly schedule |
| POST | `/api/v1/tax/cit/compute` | Compute annual CIT |

---

## 📦 Required Packages

```bash
composer require barryvdh/laravel-dompdf maatwebsite/excel laravel/sanctum
```

---

## 🇳🇬 Nigerian Tax Law References

- **VAT Act** Cap V1 LFN 2004 (as amended by Finance Act 2019 – 7.5% rate)
- **Companies Income Tax Act (CITA)** Cap C21 LFN 2004
- **Personal Income Tax Act (PITA)** Cap P8 LFN 2004
- **Finance Acts** 2019, 2020, 2021, 2022, 2023
- **NRS Practice Note** on Withholding Tax
- **Contributory Pension Scheme Act 2014** (8%/10% rates)
- **National Housing Fund Act** (2.5% NHF)

---

> **Disclaimer:** This software is for bookkeeping assistance only. Always consult a qualified Nigerian tax professional (CITN member) for official tax advice.
