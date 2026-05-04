# Commission Enhancement — Open Questions for Decision

**Audience**: Project owner / stakeholder
**Date**: 2026-04-29
**Status**: All decisions locked in ✅

---

## QNA-01 — Commission rate combination semantics (refines Decision 8)

**Context**: Decision 8 picked "Fixed + Percentage Combination" e.g. `RM50 + 2%`. The spec text says "multiple commission tiers supported" but doesn't say how the values combine.

**Decision**: **Additive** — `commission = (sale × percentage/100) + fixed`. If a value is unused, set it to 0; UI hides it. Both contribute to a single `Commission.amount`.

---

## QNA-02 — Timing of `Partner` model deletion (refines Decision 2D)

**Context**: Decision 2 chose deprecate Partner. But `Partner` is still referenced in: `AgentRegistrationController`, `Agent/RequestPayoutController`, `Partner/DashboardController`, `Admin/PartnerController` CRUD, 4 Vue pages, the `partner` Spatie role, and the `partner` middleware.

**Decision**: **Two-step PR** — PR 1 = additive (new role/parent columns, refactor controllers to use Agents). PR 2 = drop tables + delete Partner files once PR 1 has soaked. Allows rollback of PR 1 without data loss.

---

## QNA-03 — Default upline / business partner

**Context**: Today, `AgentRegistrationController` falls back to `Partner::find(1)` when no referral code is provided. After Phase 1 the equivalent is "Agent id=X with `agent_role='business_partner'`".

**Decision**: **Seed a canonical BP Agent** with `is_default` flag. Encode in `BusinessPartnerSeeder`. Mirrors today's fallback behavior with minimum churn.

---

## QNA-04 — Is `parent_agent_id` required or optional?

**Context**: An Agent with no parent earns own_sales only. An Agent Leader with no parent works but no one earns BP overrides above them.

**Decision**: **Optional everywhere** — allow null. Validation only kicks in if a parent is set. Easy to tighten later.

---

## QNA-05 — Where does the BP "company email" / CC field live?

**Context**: `AgentRegistrationController:178-180` uses `Partner::find(1)`'s email to CC notifications. The Agent model has `company_email_address` and `individual_email`.

**Decision**: Use `company_email_address` on the BP Agent. Always CC both user email and company email if both exist, otherwise just whichever is present. This way no CC is missed when the BP is an individual with no company email.

---

## QNA-06 — Spatie `partner` role disposition

**Context**: Spatie has roles `admin`, `agent`, `partner`. After collapsing Partner into Agent, the `partner` Spatie role is orphaned.

**Decision**: **Drop `partner` role** — all former partner users get `agent` Spatie role + `business_partner` agent_role. One Spatie role (`agent`) + `agent_role` enum. UI gates on `agent_role`.

---

## QNA-07 — `commission_rate` legacy column on `commissions` table

**Context**: `commissions` table has both `commission_rate` and `applied_rate` (decimal, percentage). With combined fixed+pct (QNA-01) the legacy `commission_rate` is ambiguous.

**Decision**: Keep `applied_rate` as percentage, add `applied_fixed_amount`, drop the redundant `commission_rate` column. Each commission row records exactly what % and what RM was applied for a clean audit trail.

---

## QNA-08 — Per-role rates: 6 rows or fewer?

**Context**: Phase 1 migration proposes 6 rate keys in SystemSetting: `agent_own_sales`, `agent_leader_own_sales`, `agent_leader_override_agent`, `business_partner_own_sales`, `business_partner_override_agent`, `business_partner_override_agent_leader`.

**Decision**: **6 rates, fully decoupled** — Agent Leader's own sales rate is configurable separately from a regular Agent's. Marginal storage cost, maximum flexibility. Values can be mirrored to collapse tiers if desired.

---

## QNA-09 — `AgentCommissionRate` per-role override

**Context**: Currently one `agent_commission_rates` row per agent with `custom_rate` (percentage). With roles, commission depends on whether the sale is own_sales vs override-from-subordinate.

**Decision**: **Multi-row `agent_commission_rates`** keyed by `(agent_id, kind)` where kind ∈ {own_sales, override_agent, override_agent_leader}. Maximum flexibility per agent.

---

## QNA-10 — Commission generation when commission rate is 0

**Context**: With combined fixed+pct, a role might have `0% + RM0` (intentionally disabled).

**Decision**: **Use SystemSetting default** — add a `skip_zero_commissions` boolean to SystemSetting. If enabled, skip creating the row. If disabled, create with `amount = 0` for audit purposes.

---

## QNA-11 — Caching strategy for SystemSetting

**Context**: `SystemSetting::first()` is called per request in many places. With new fields it'll be hit harder, especially during commission generation.

**Decision**: **`Cache::remember('commission_config', 3600, …)` flushed on update** — trivial to add, big win. Already recommended in `ANALYSIS_AND_DECISIONS.md` Issue 5.

---

## QNA-12 — Partner dashboard route

**Context**: `Partner/DashboardController` and `routes/web.php`'s `partner` middleware group are wired today. Decision 2D removes Partner entirely.

**Decision**: **Delete partner routes** — let the `/dashboard` redirect detect `agent_role='business_partner'` and route to the agent dashboard with a role prop. One dashboard component branching on role is simpler to maintain.

---

## QNA-13 — Sale visibility for Agent Leaders / Business Partners

**Context**: `Agent/SalesController::index` currently lists `Sale::where('agent_id', $agent->id)`. Should leaders/BPs see their subordinates' sales?

**Decision**: **Show subordinate sales with a "source" column**, scoped to descendants via `AgentHierarchy::getSubordinates(agent, recursive=true)`. Leaders need visibility into the sales generating their override commissions for trust/disputes.

---

## QNA-14 — Recalculation guard rail (refines Decision 4)

**Context**: Decision 4 = no recalculation. But the agent's parent can still be changed at any time, and existing pending commissions might surprise the new parent.

**Decision**: **No guard** — admin owns the consequence. Add a confirmation modal showing the count of pending commissions before allowing the hierarchy change.

---

## QNA-15 — Test database engine

**Context**: Tests use sqlite-in-memory. Migrations use `enum` and FKs with `nullOnDelete`. Sqlite tolerates enums as varchar.

**Decision**: **Keep `enum` columns, rely on Eloquent casts** — matches existing migrations like `agents.profile_type`. Repo already uses enums and tests pass.

---

## QNA-16 — Fee Payment History Tracking (CRD April 2026)

**Context**: `fee_payment_status` on the agents table tracks current state (pending/paid/overdue/waived). CRD requires audit log of fee updates and payment status.

**Decision**: **Add a `fee_payments` table** — `agent_id`, `fee_type` (entry/renewal), `role`, `amount`, `paid_at`, `recorded_by` FK to users. Queryable, exportable, and does not pollute ActivityLog.

---

## QNA-17 — Renewal Notification Timing (CRD April 2026)

**Context**: CRD requires a renewal reminder notification before expiry. A daily scheduled job checks `renewal_due_at`.

**Decision**: **Configurable in SystemSetting** — add `renewal_reminder_days_before` integer column. CRD states all values must be editable from the backend; timing is a business decision that may change.

---

## QNA-18 — Refund Trigger Mechanism (CRD April 2026)

**Context**: Decision 17 locks in reversal via new negative-amount commission row. Who triggers the refund and how?

**Decision**: **Both — manual now, webhook later** — implement an admin "Mark as Refunded" action on the sale detail page that auto-creates reversal commission rows. Extract the logic into a service method so a future payment gateway webhook can call the same method.

---

## QNA-19 — Store `commission_calc_type` explicitly or derive it (CRD April 2026)

**Context**: Decision 14 adds `commission_calc_type` (percentage/fixed). Alternative: derive from column values at runtime.

**Decision**: **Store `commission_calc_type` explicitly** — one enum column. Clear, queryable, prevents ambiguity in the additive case (both rate and fixed_amount non-zero). Avoids scattering derivation logic across calculators, reports, and UI.

---

## QNA-20 — `commission_calc_type` on AgentCommissionRate (CRD April 2026)

**Context**: QNA-09 Decision extended `AgentCommissionRate` to multi-row by kind. Decision 14 adds `commission_calc_type` to `system_settings` and `commissions`.

**Decision**: **Yes, add `commission_calc_type` to `agent_commission_rates`** — consistent with system-wide pattern. Allows a BP to override with fixed RM while agents use the system percentage default.

---

## Summary table

| # | Topic | Decision |
|---|---|---|
| 01 | Fixed+pct combination | Additive |
| 02 | Partner removal timing | Two-step PR |
| 03 | Default BP fallback | Seed canonical BP agent |
| 04 | Required parent? | Optional |
| 05 | BP CC email field | `company_email_address` + individual fallback |
| 06 | Spatie `partner` role | Drop |
| 07 | Commission rate columns | `applied_rate` + `applied_fixed_amount`, drop `commission_rate` |
| 08 | Number of rate keys | 6 (fully decoupled) |
| 09 | AgentCommissionRate granularity | Multi-row by `(agent_id, kind)` |
| 10 | Zero-rate row creation | SystemSetting `skip_zero_commissions` flag |
| 11 | SystemSetting caching | `Cache::remember` 1h, flush on update |
| 12 | Partner dashboard route | Delete, branch in agent dashboard |
| 13 | Leader/BP sales visibility | Show subordinate sales with source column |
| 14 | Parent-change guard | No block, confirmation modal only |
| 15 | Test DB engine | Keep sqlite + enum |
| 16 | Fee payment history | Dedicated `fee_payments` table |
| 17 | Renewal notification timing | Configurable via `renewal_reminder_days_before` in SystemSetting |
| 18 | Refund trigger mechanism | Manual now, webhook-ready service method |
| 19 | `commission_calc_type` storage | Explicit enum column |
| 20 | `commission_calc_type` on AgentCommissionRate | Yes, add column |

---

## GAP RESOLUTIONS (2026-05-03)

These items were identified as gaps after the initial QNA round and have been resolved by the project owner.

| # | Gap | Decision |
|---|-----|----------|
| G01 | Commission reversal time limit | `reversal_time_limit` in SystemSetting, default 60 days; `RefundService` enforces window |
| G02 | Clawback from already-paid commissions | Negative reversal rows auto-included in next payout request; block if net ≤ 0 |
| G03 | Role downgrade consequences | Keep subordinates; override stops; admin confirmation modal when downgrading leader with team |
| G04 | Admin reject after Stripe payment | Last resort; manual refund via Stripe dashboard; popup reminder on rejection; automation as future TODO |
| G05 | Admin-created agent fee | Admin approval always skips fee (waived); admin may upload receipt; fee mandatory until approved via any path |
| G06 | Stripe package | Laravel Cashier; all calls wrapped in FeeService |
| G07 | No index definitions | Add indexes with each new query — already in Phase 1 migrations; add as needed in later phases |
| G08 | Backfill existing Commission rows | Mark as TODO; no prod data exists |
| G09 | Existing Partner users | No partners to migrate; deprecation is clean |
| G10 | ReferralCode rate priority | Removed from calculation chain entirely; new order: AgentCommissionRate → SystemSetting |
| G11 | ProcessRenewals scheduling | Add to console.php; add `scheduler_logs` table; admin dashboard alert if stale > 24h; show failed jobs |
| G12 | Registration wizard resume | Cookie-based persistence (`reg_wizard_state`); resume pre-fills from Step 1 |
| G13 | Email verification resend | Max attempts from `email_verification_max_retry` (SystemSetting, default 10); resets next calendar day |
| G14 | Cache invalidation | Always flush all SystemSetting cache on every update (no TTL dependency for correctness) |
| G15 | API response backward compat | No v2; return first `own_sales` commission only; override rows not surfaced to external callers |
| G16 | First-login onboarding content | Add as TODO; content to be decided after product is complete |
| G17 | Admin commission rate preview | Add `CommissionRatePreview.vue` page; linked from SystemSettingsUpdate form |
| G18 | Payout minimum threshold UX | Progress indicator card in agent dashboard; payout request blocked below threshold |
| G19 | Appeal workflow + notification system | Fully-featured `agent_notifications` with Unread/Pending/Archived tabs; email dispatched on every notification creation |
| G20 | Referral stats page | Add to TODOS.md and ROLES_WORKFLOW.md; stats: visits, conversions, rate, avg days to convert |
| G21 | Phase 7 test coverage | Test plan added to TODOS.md Phase 7 section (8 new test files) |
| G22 | commission_fixed_amount precision | DECIMAL(10,2) for all transaction fields; SystemSetting amounts stored as DECIMAL(10,2) too |
| G23 | Partner deprecation rollback plan | No rollback needed; no production partner data exists |
