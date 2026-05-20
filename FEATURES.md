# AccountTaxNG — Feature Documentation

> **Version scheme:** `v MAJOR.MINOR.PATCH`
> Major = new module or breaking change · Minor = new capability within a module · Patch = enhancement or fix to existing feature
>
> Newest version first. Each module section carries a `[vX.Y]` tag showing when it was introduced.

---

## Changelog

### v0.5.1 — 2026-05-20
- **Enterprise pricing card** on public marketing pricing page — dark-panel "Contact Us" CTA, feature highlights, styled to match brand

### v0.5.0 — 2026-05-19
- **Maintenance & Asset Management module** — asset register, preventive maintenance (PM) schedules, work orders (open → assigned → in-progress → completed → closed), breakdown reporting, labour logging, inventory part consumption, GL cost posting, daily artisan command for PM auto-generation
- **Enterprise Billing** — superadmin-managed plans with negotiated pricing; enterprise agreements (billing cycle, payment terms, contract dates); platform invoice CRUD (PLT-YYYYMM-NNNN); mark-sent, mark-paid (with payment reference), void, PDF download; tenant-facing read-only managed-plan billing page; superadmin Enterprise sidebar section and overview dashboard

### v0.4.0 — 2026-05-14 · 2026-05-15
- **Inventory Management** — items (product/service/raw material), categories, units of measure, stock movements, low-stock alerts, CSV/Excel import, restock requests (draft → submitted → approved/rejected → received)
- **Inventory Reports** — stock valuation, low-stock report, sales analytics
- **Sales Orders** — create from customer/items, link to invoices, fulfillment status
- **Manufacturing** — Bills of Materials, Production Orders (draft → in-progress → completed), raw material consumption, finished-goods stock posting, GL cost entries, restock-shortfall requests from production
- **Supplier Bills** — receive goods against restock requests, record supplier invoice, partial and full payment, AP GL entries
- **Bank Accounts** — manage company bank accounts, set default per-currency
- **Activity Log** — per-tenant chronological log of all user actions, filterable by user and action type

### v0.3.0 — 2026-05-09 · 2026-05-11
- **Marketing website** — landing page, features page, pricing page (dynamic DB-driven plan cards + billing toggle + FAQ), contact page
- **Email verification** — required before accessing app; resend verification link flow

### v0.2.0 — 2026-05-01 · 2026-05-13
- **Subscription plans** — DB-driven plans (Free, Growth, Business) with feature flags and usage limits; plan CRUD in SuperAdmin
- **Paystack integration** — self-serve checkout for monthly/annual plans, callback verification, recurring subscription codes
- **Paystack webhooks** — `charge.success`, `subscription.create`, `subscription.disable`, `invoice.payment_failed`; idempotent event storage; HMAC-SHA512 signature verification
- **Trial flow** — 14-day trial on registration, trial banners (active / expiring soon / expired), `subscriptions:downgrade-expired-trials` nightly command
- **Grace period** — 7-day grace window after subscription expiry, orange banner, grace-aware limit enforcement
- **Billing UI** — plan comparison, upgrade/downgrade, cancel flow, payment history, pending plan change notice
- **Email notifications** — `TrialEndingSoon`, `SubscriptionActivated`, `PaymentFailed`, `SubscriptionCancelled`, `InvoiceEmail` (with PDF), `QuoteEmail` (with PDF); queued via `ShouldQueue`
- **Team invitations** — admin invites users by email, role assignment (admin/accountant/staff), token-based acceptance
- **SuperAdmin platform** — dashboard (plan breakdown, subscription health, overdue invoices, enterprise tenants), company management, impersonation, extend trial, send bulk reminders, audit logs, subscription override

### v0.1.0 — 2026-04-04 · 2026-04-10
- **Core accounting** — chart of accounts, journal entries, double-entry GL, transaction ledger, export to Excel/PDF
- **Invoicing** — create/edit/send invoices, line items, VAT/WHT auto-calculation, payment recording, status tracking (draft → sent → paid → overdue), public share link (`/inv/{token}`), PDF download, Excel import
- **Quotes / Proformas** — create/edit quotes, convert to invoice, PDF download, email send
- **Expense management** — record expenses with category, VAT, WHT, vendor, receipt attachment
- **Customer management** — create/edit customers, contact details, transaction history
- **Vendor management** — supplier directory, WHT category assignment
- **Payroll & PAYE** — employee records, salary components, payroll runs, payslip generation, PAYE computation, CSV/Excel employee import
- **VAT compliance** — VAT computation on invoices/expenses, VAT return filing tracker, VAT report
- **WHT compliance** — WHT deduction on payments, WHT certificates, exemption management
- **CIT compliance** — corporate income tax computation, filing tracker
- **Tax summary report** — consolidated VAT + WHT + CIT view
- **Financial reports** — Profit & Loss, Balance Sheet, Trial Balance, General Ledger (all with PDF and Excel export)
- **NRS / FIRS e-Invoicing** — submit invoices directly to the Federal Inland Revenue Service portal (plan-gated: `firs` flag)
- **Company settings** — company profile, logo upload, FIRS onboarding, bank accounts
- **Multi-tenancy** — single-database multi-tenant with global tenant scope; every query automatically scoped by `tenant_id`
- **Authentication** — registration, login, password reset, email verification, role-based access (`admin`, `accountant`, `staff`)

---

## Feature Catalogue

### Authentication & Access Control `[v0.1]`
| Capability | Notes |
|---|---|
| Registration + email verification | Required before app access (`v0.2`) |
| Login / logout / password reset | Standard Laravel Breeze flow |
| Role-based access: `admin`, `accountant`, `staff` | `role` middleware on all routes; staff get read-only portal |
| Team invitations | Admin invites by email; token-based acceptance (`v0.2`) |
| Impersonation | SuperAdmin can log in as any tenant admin (`v0.2`) |
| Per-user module access overrides | Staff can be granted specific module access |

---

### Invoicing `[v0.1]`
| Capability | Notes |
|---|---|
| Create / edit / delete invoices | Line items, discounts, VAT, WHT |
| Automatic tax calculations | VAT (7.5%), WHT rates per vendor category |
| Payment recording | Partial and full payments, multiple payment methods |
| Status lifecycle | Draft → Sent → Paid → Overdue → Cancelled |
| PDF download | DomPDF with company logo |
| Public share link | `/inv/{token}` — no login required |
| Email to customer | Queued job, PDF attached, customer email required |
| Excel import | Bulk invoice upload via `InvoicesImport` |
| Invoice search & statistics | Monthly revenue, overdue count, collection rate |
| Plan-gated limit | `invoices_per_month` enforced in `withinLimit()` |

---

### Quotes / Proformas `[v0.1]`
| Capability | Notes |
|---|---|
| Create / edit / delete quotes | Same line-item model as invoices |
| Convert to invoice | One-click conversion, original quote preserved |
| PDF download | Shared PDF template with invoices |
| Email to customer | `QuoteEmail` queued mailable (`v0.2`) |

---

### Expenses `[v0.1]`
| Capability | Notes |
|---|---|
| Record expenses | Category, amount, VAT, WHT, vendor, date |
| Receipt attachment | File upload stored on disk |
| Expense reports | Filter by category, date range, vendor |

---

### Customers `[v0.1]`
| Capability | Notes |
|---|---|
| Customer directory | Name, email, phone, address, TIN |
| Transaction history | Invoices and payments per customer |
| Plan-gated limit | `customers` limit enforced (enforcement pending — see GAPS.md) |

---

### Vendors `[v0.1]`
| Capability | Notes |
|---|---|
| Vendor / supplier directory | Contact details, WHT category |
| WHT exemption management | Per-vendor exemption records |

---

### Payroll & PAYE `[v0.1]`
| Capability | Notes |
|---|---|
| Employee records | Profile, salary structure, bank details |
| Payroll runs | Monthly computation, payslip generation |
| PAYE computation | Tax tables per income band |
| Excel / CSV import | Bulk employee onboarding |
| Plan-gated | `payroll` feature flag + `payroll_staff` limit |

---

### Tax Compliance `[v0.1]`
| Capability | Notes |
|---|---|
| VAT | Computation on invoices/expenses, return tracker, report |
| WHT | Deductions, certificates, exemptions |
| CIT | Corporate income tax computation, filing tracker |
| Tax summary | Consolidated VAT + WHT + CIT dashboard |
| FIRS e-Invoicing | Direct submission to FIRS portal (`firs` plan flag) |

---

### Financial Reports `[v0.1]`
| Report | Export formats |
|---|---|
| Profit & Loss | PDF, Excel |
| Balance Sheet | PDF, Excel |
| Trial Balance | Screen only |
| General Ledger | PDF, Excel |
| VAT Report | Screen only |
| CIT Report | Screen only |
| Tax Summary | Screen only |

> Advanced reports (Ledger, Balance Sheet) intended to be plan-gated by `advanced_reports` — enforcement pending, see GAPS.md.

---

### Chart of Accounts & GL `[v0.1]`
| Capability | Notes |
|---|---|
| Double-entry journal entries | Debit = credit enforced |
| Account types | Asset, Liability, Equity, Revenue, Expense |
| Transaction ledger | Per-account running balance |
| Excel / PDF export | Full ledger export |
| Auto-provisioned on registration | `BookkeepingService::provisionDefaultAccounts()` |

---

### Inventory Management `[v0.4]`
| Capability | Notes |
|---|---|
| Items | Product, service, raw material types; SKU, cost/sale price, unit of measure |
| Categories & units | Hierarchical categories; custom UOM |
| Stock movements | Inbound, outbound, adjustment; running balance |
| Low-stock alerts | Per-item reorder threshold, alert list |
| Restock requests | Draft → Submitted → Approved/Rejected → Received |
| Supplier bills | Generated on receive; partial/full payment; AP GL entries |
| Sales orders | Linked to customers; fulfillment tracking; inventory deduction on fulfillment |
| Inventory reports | Stock valuation, low-stock report, sales analytics |
| CSV/Excel import | Bulk item onboarding |
| Plan-gated | `inventory` flag; `inventory_reports` for analytics |

---

### Manufacturing `[v0.4]`
| Capability | Notes |
|---|---|
| Bills of Materials | Multi-level ingredient/component lists per finished product |
| Production Orders | Draft → In-Progress → Completed |
| Raw material consumption | Auto-deducts components on completion |
| Finished goods posting | Increments finished-goods stock |
| GL cost entries | Materials cost posted to inventory accounts |
| Restock shortfall requests | Auto-creates restock requests for insufficient components |
| Plan-gated | `manufacturing` feature flag |

---

### Maintenance & Asset Management `[v0.5]`
| Capability | Notes |
|---|---|
| Asset register | Asset code (AST-NNNN), type, location, acquisition cost, depreciation |
| Asset statuses | Active, Under Maintenance, Breakdown, Retired |
| PM Schedules | Frequency (daily/weekly/monthly/quarterly/annual), checklist, assigned technician |
| Auto WO generation | Daily artisan command `maintenance:generate-pm-work-orders` |
| Work Orders | MWO-YYYYMM-NNNN numbering; Open → Assigned → In Progress → Completed → Closed |
| Work order sources | Preventive, Corrective, Request |
| Inventory parts | Add/remove parts to WO; stock deducted on WO close |
| Labour logging | Hours, hourly rate, auto-calculated cost |
| Breakdown reporting | BRK-YYYYMM-NNNN numbering; auto-creates corrective WO; downtime tracking |
| Cost recording | Labour + parts cost per WO; all-time cost per asset |
| GL posting on close | Dr 5500 Maintenance Expense / Cr 1200 Inventory + 2001 AP |
| Dashboard | Open WOs, overdue WOs, breakdown assets, month cost, top assets by cost |
| Plan-gated | `maintenance` feature flag |

---

### Bank Accounts `[v0.4]`
| Capability | Notes |
|---|---|
| Multiple accounts per tenant | Bank name, account number, currency |
| Default account | Set per-company default for payment recording |

---

### Activity Log `[v0.4]`
| Capability | Notes |
|---|---|
| Per-tenant action log | All user actions with timestamp, IP, and user agent |
| Filterable | By user, action type, date range |

---

### Billing & Subscriptions `[v0.2]`
| Capability | Notes |
|---|---|
| DB-driven plans | Free, Growth, Business; feature flags + usage limits |
| 14-day trial | Assigned on registration; trial banners; nightly downgrade job |
| Paystack checkout | Monthly and annual billing; recurring subscription codes |
| Paystack webhooks | `charge.success`, renewals, cancellations, payment failures |
| Grace period | 7 days after expiry before limits revert |
| Self-serve upgrade/downgrade | Upgrades immediate; downgrades end-of-cycle |
| Cancel flow | Modal confirmation; reverts at period end |
| Payment history | Last 12 payments on billing page |
| Email notifications | Trial ending, activated, payment failed, cancelled |
| Enterprise plans | Managed pricing, no Paystack self-serve, invoiced billing (`v0.5`) |

---

### Enterprise Billing `[v0.5]`
| Capability | Notes |
|---|---|
| Enterprise agreements | Negotiated price, billing cycle, payment terms, contract dates |
| Platform invoices | PLT-YYYYMM-NNNN; draft → sent → paid/overdue/void |
| Invoice actions | Mark sent, mark paid (with method + reference), void, PDF download |
| PDF invoices | DomPDF; branding, line items, payment status, payment receipt block |
| Tenant billing view | Read-only managed-plan page; agreement details; invoice history |
| SuperAdmin overview | Enterprise sidebar section; all enterprise tenants in one table; overdue count |
| SuperAdmin dashboard | Overdue invoice count + enterprise tenant count in KPI strip |

---

### SuperAdmin Platform `[v0.2]`
| Capability | Notes |
|---|---|
| Dashboard | Total companies, plan breakdown, subscription health, overdue invoices, new this month |
| Company management | List, search, activate/deactivate, view details |
| Subscription override | Update plan, status, expiry date from superadmin |
| Extend trial | Add days to any tenant's trial |
| Impersonation | Log in as tenant admin; exit banner; audit logged |
| Send reminders | Individual and bulk subscription reminder emails |
| Plan CRUD | Create/edit/delete plans; feature flags; Paystack plan code |
| Audit logs | Searchable log of all superadmin actions |
| Enterprise billing | Per-tenant agreements and platform invoice management (`v0.5`) |

---

### Marketing Website `[v0.3]`
| Page | Notes |
|---|---|
| Landing page | Hero, features overview, pricing teaser, testimonials, CTA |
| Features page | Module-by-module feature breakdown |
| Pricing page | Dynamic DB-driven plan cards, annual/monthly toggle, FAQ, enterprise card (`v0.5.1`) |
| Contact page | Enquiry form, demo booking |

---

*Last updated: 2026-05-20 · v0.5.1*
