# WhatsApp Back-Office Assistant — Spec (Draft v0.1)

Status: **Planning / research.** No code written. Last updated 2026-11-13.

## 1. Decisions (locked)

| # | Decision | Consequence |
|---|---|---|
| D1 | **One shared business number** for all tenants | Tenants are identified by the sender's verified phone, not by the number they message. The free service-reply allowance (if any) is shared platform-wide. |
| D2 | **PIN is ON** | Every user sets a PIN; PIN required for money-moving actions (see §5). |
| D3 | **Admins and accountants only** at launch | Staff role cannot link a number. Gate with `User::isAdmin()` / accountant check plus module access. |
| D4 | Back-office assistant only (no storefront over WhatsApp) | Storefront stays web + optional `wa.me` click-to-chat later. |

## 2. Open decisions

| # | Question | Notes |
|---|---|---|
| O1 | Direct Meta Cloud API vs a provider (Twilio, 360dialog, etc.) | Direct = no markup, we own webhooks/compliance. Provider pricing unverified. |
| O2 | Is the message allowance bundled in plans, or sold as add-on? | Depends on confirmed service-message billing (R1). |
| O3 | Natural-language layer (LLM) in v1 or later? | Spec assumes structured menus first; LLM maps free text to the same handlers later. |
| O4 | Which plans get the `whatsapp` flag? | Needs pricing decision after cost confirmation. |
| O5 | Data retention period for message content | NDPA review needed. |
| O6 | Receipt photo extraction: manual entry vs OCR | OCR adds vendor and cost. |

## 3. Goals and non-goals

**Goals**
- Let admins and accountants check key figures and perform common bookkeeping actions from WhatsApp.
- Send business notifications (invoice sent, payment received, overdue) through WhatsApp.
- Reuse existing services, plan limits, roles and audit logging; add no parallel business logic.

**Non-goals (v1)**
- Payroll, VAT/CIT filing, FIRS submission, journal entries, user management.
- Staff-role access.
- Marketing broadcasts.
- A general-purpose chatbot (prohibited by Meta policy since 2026-01-15).
- Storefront operation over WhatsApp.

## 4. Capabilities by risk tier

| Tier | Actions | Verification |
|---|---|---|
| T1 Read-only | Outstanding receivables, overdue invoices, today's sales, customer balance, daily summary, low-stock | Verified linked phone |
| T2 Low-risk write | Create customer, create draft invoice, send invoice/reminder, log expense (created **unapproved**) | Linked phone + explicit confirmation reply |
| T3 Money-moving | Record payment, approve expense, void/cancel invoice | Linked phone + confirmation + **PIN** |

Anything not listed is unsupported and answered with a pointer to the web app.

## 5. Security requirements

**S1 Webhook authenticity.** Verify `X-Hub-Signature-256` on every POST using the app secret; reject with 403 otherwise. Support Meta's GET verification handshake.

**S2 Identity.** A sender is acted on only if their E.164 phone exists in `whatsapp_links` with `verified_at` set. Unknown numbers get one generic reply and no account information.

**S3 Linking.** Initiated in the web app by a logged-in admin/accountant. A one-time code (expiry 10 minutes, single use) is sent from the user's phone to the business number, or delivered via authentication template. One phone maps to exactly one user; one user to one phone.

**S4 PIN.** 4–6 digits, set in the web app, stored hashed. Required for T3. Lockout after 3 consecutive failures for 30 minutes; unlock via web app. PIN is never echoed back or logged. Users are told to delete the PIN message after sending (WhatsApp cannot delete it for them).

**S5 Tenant isolation.** Every handler runs with `app('currentTenant')` bound and the linked user authenticated, because model scopes and services depend on them (`Customer` scope: `app()->bound('currentTenant')`; `InvoiceService` writes `auth()->id()`). A feature test must prove tenant A's link cannot read or change tenant B's data.

**S6 Authorisation.** Handlers must call the same policies and `canAccess()` module checks as the web UI. A user with a module disabled cannot use it via WhatsApp.

**S7 Confirmations.** State-changing actions show a summary and need an explicit YES. Confirmation tokens are single-use and expire after 10 minutes. Replying after expiry restarts the flow.

**S8 Audit.** Every write is recorded with channel `whatsapp` and provider message ID, using `AuditLog::record()` **after** `DB::transaction()` closes (project convention).

**S9 Rate limiting.** Per-user and per-tenant limits on inbound handling and outbound sends. Defaults to define during build.

**S10 Kill switches.** Per-tenant disable and global disable. Disabling stops handling and sends immediately.

**S11 Data minimisation.** Don't send full account numbers, tax IDs or credentials. Mask amounts above a configurable threshold until PIN is entered (optional hardening).

## 6. Functional requirements

### 6.1 Linking and onboarding
- FR1: Web app "WhatsApp" settings page for admin/accountant: link, unlink, set/change PIN, view status.
- FR2: Admins can see and revoke any user's link in their tenant.
- FR3: Linking is blocked when the tenant's plan lacks the `whatsapp` flag or the user is a staff role.

### 6.2 Conversation handling
- FR4: Any unrecognised message returns the main menu (interactive buttons/list).
- FR5: Multi-step flows keep state in `whatsapp_conversations` with expiry; "cancel" always exits.
- FR6: Customer lookup by fuzzy name returns up to 5 matches to choose from; never guess silently when ambiguous.
- FR7: Amounts parsed in Naira with thousands separators ("50,000", "50k"); always echoed back in the confirmation.

### 6.3 Actions (v1)
| Action | Tier | Backing code | Notes |
|---|---|---|---|
| Balance / outstanding | T1 | `ReportService` / Invoice queries | Read only |
| Overdue invoices | T1 | Invoice query | List capped at 10 with link to web |
| Customer balance | T1 | `Customer` | |
| Create customer | T2 | `CustomerController` logic (check for service extraction) | Respects `customers` plan limit |
| Create draft invoice | T2 | `InvoiceService::create` | Must call `withinLimit('invoices_per_month')` and `invalidateLimitCache` like the controller |
| Send invoice / reminder | T2 | `SendInvoiceEmail` job and WhatsApp template | |
| Log expense | T2 | **Needs extraction**: `TransactionController::storeExpense` has no service | Created unapproved |
| Record payment | T3 | `InvoiceService::recordPayment` | Needs payment account id; default per tenant setting |
| Approve expense | T3 | `approveExpense` (controller, extract) | |
| Void/cancel invoice | T3 | Check existing path | |

### 6.4 Outbound notifications
- FR8: Templates (utility category) for: invoice sent, payment received, invoice overdue, daily summary, link OTP (authentication).
- FR9: Customer-facing messages require recorded opt-in (`whatsapp_customer_optins`). Without opt-in, fall back to email.
- FR10: Every outbound send is logged (reuse/extend `NotificationLog`), with status and error.

### 6.5 Billing and limits
- FR11: New plan feature flag `whatsapp` and limit `whatsapp_messages_per_month` (null = unlimited) in `Plan::LIMIT_DEFAULTS`; add to PlanController form and PlanSeeder.
- FR12: Usage page for admins (messages this month vs allowance) and super-admin cost view per tenant.
- FR13: When allowance is exhausted: T1/T2 replies continue only if provider cost is zero; otherwise send a single "limit reached, continue on web" message.

## 7. Non-functional requirements

- **NFR1 Latency:** webhook returns 200 in under 1 s; normal reply within ~5 s. The current scheduler-driven worker runs once per minute, which is too slow for chat. Use `dispatchAfterResponse()` for inbound handling (works on shared hosting) or a persistent worker.
- **NFR2 Idempotency:** unique constraint on provider message ID; duplicates (Meta retries) are ignored. Evaluate reusing `WebhookEvent`.
- **NFR3 Reliability:** outbound sends retry with backoff; permanent failures logged and visible to admins.
- **NFR4 Privacy:** NDPA review; define retention (O5); no message bodies in application logs.
- **NFR5 Meta policy:** purpose-built business assistant only; no open-ended general Q&A.
- **NFR6 Observability:** counters for webhook failures, send failures, queue delay, per-tenant volume.

## 8. Data model (proposed)

- `whatsapp_links`: id, tenant_id, user_id (unique), phone_e164 (unique), verified_at, pin_hash, failed_attempts, locked_until, disabled_at, timestamps.
- `whatsapp_conversations`: id, link_id, flow, state (json), expires_at, timestamps.
- `whatsapp_messages`: id, provider_message_id (unique), link_id nullable, direction, type, template nullable, status, pricing_category nullable, created_at.
- `whatsapp_customer_optins`: id, tenant_id, customer_id, phone_e164, opted_in_at, source.

Postgres rules from CLAUDE.md apply (no MySQL-only SQL; `ilike` for search).

## 9. Known code gaps to close before building

1. Extract expense create/approve logic from `TransactionController` into a service.
2. Services read `auth()->id()`; handlers must authenticate the linked user (or services accept an explicit actor).
3. Confirm every query used by handlers is tenant-scoped with `currentTenant` bound.
4. `User.phone` is unvalidated; add E.164 normalisation and uniqueness for linking.
5. Confirm the invoice limit check lives only in the controller; move it into a shared place so both channels enforce it.

## 10. Test plan (per CLAUDE.md)

- Webhook: bad signature → 403; verification handshake; duplicate message ID ignored; unknown number gets generic reply only.
- Per action: happy path + unauthorised role (staff, or module disabled).
- Cross-tenant isolation test (S5).
- GL-posting actions: assert `Transaction`/`JournalEntry` debits == credits.
- Plan gate: allowed plan and denied plan tested separately; invoice limit enforced over WhatsApp.
- Unit (no DB): PIN lockout counter, confirmation expiry, amount parsing, phone normalisation.

## 11. Phasing

| Phase | Scope | Exit criteria |
|---|---|---|
| 0 | Meta setup: business verification, number, payment method, template submission; resolve O1 | Sandbox number can send/receive |
| 1 | Linking, PIN, webhook, T1 read-only, outbound invoice/payment templates | S1–S6, S8 tests green |
| 2 | T2 writes with confirmations; expense service extraction | Every write confirmed and audited |
| 3 | T3 with PIN, receipt photo capture | Lockout and expiry tested |
| 4 | Optional LLM layer (O3), voice notes, daily digest | Decision after usage data |

## 12. Cost model (to validate)

Nigeria rates in USD per message (third-party rate cards, **verify against Meta's pricing page**): utility ≈ 0.0067, marketing ≈ 0.0516, authentication ≈ 0.0067 domestic.

Estimate for 500 tenants at ~100 bot replies and ~30 templates per tenant per month: roughly **$100–435 per month** depending on whether service replies are billed. At ₦1,500/$ (placeholder rate) that is about ₦150k–₦650k.

## 13. Research backlog

- R1: Confirm whether service (non-template) replies are billed since 1 Oct 2026, and the free allowance (reported as 1,000 per phone number per month). Directly affects D1's cost.
- R2: Get Twilio / 360dialog / other BSP price quotes (O1).
- R3: Confirm Nigeria authentication-template rate and whether registering the WABA outside Nigeria triggers the higher rate.
- R4: Meta Tech Provider / Embedded Signup requirements (only relevant if scope ever grows beyond one shared number).
- R5: Review NDPA obligations for storing financial chat content (O5).
- R6: Evaluate WhatsApp Flows for structured data entry (invoice creation) versus plain interactive messages.
- R7: Decide OCR approach and cost for receipts (O6).
