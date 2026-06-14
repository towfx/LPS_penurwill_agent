The compressed file content was provided inline. The fix: add the missing H1 heading at the top. Here is the fixed compressed file:

---

# Commission Enhancement: Decision Outcomes

**Status**: ✅ **DECISIONS LOCKED IN** - Ready for Phase 0 Implementation


---

## CRITICAL DECISIONS (Must Answer Before Phase 0)

### ✋ Decision 1: TrackingService Refactoring Approach

**Question**: How handle commission generation in TrackingService?

---

**Decision**: Inject CommissionGenerator into TrackingService ✅

**Architecture**:
```
TrackingService (API endpoint)
  └─ Receives: POST /api/agents/track/sale
     └─ Calls: CommissionGenerator::generateForSale($sale)
        └─ Returns: Multiple commission records
```

**Pros**:
- ✅ **Cleanest code**: Single responsibility - TrackingService validates/creates sale, CommissionGenerator creates commissions
- ✅ **DRY**: Commission logic in ONE place
- ✅ **Testable**: Can mock CommissionGenerator in tests
- ✅ **Reusable**: Other controllers (admin, dashboard) can use CommissionGenerator
- ✅ **Maintainable**: Change commission logic → only update CommissionGenerator
- ✅ **Future-proof**: Easy add listeners/events without touching TrackingService

**Cons**:
- ⚠️ Minor: Need new CommissionGenerator service
- ⚠️ Minor: Requires dependency injection understanding

**Complexity**: 🟢 **LOW** (3-5 hours)
- Extract 15-20 lines into new service
- Update 1 file (TrackingService)
- Add 1 file (CommissionGenerator)

**Database Changes**: None

**Code Maintenance**:
```php
// TrackingService.php (SIMPLE)
public function __construct(CommissionGenerator $generator) {
    $this->generator = $generator;
}

public function trackSale(array $data) {
    $sale = Sale::create($data);
    $this->generator->generateForSale($sale);  // Clean one-liner
    return $sale;
}

// CommissionGenerator.php (DETAILED)
public function generateForSale(Sale $sale): array {
    // All complex commission logic here
    // Easy to understand when separated
}
```

**Long-term Maintenance**: 🟢 **EXCELLENT**
- Commission rules change? Only edit CommissionGenerator
- New commission types? Only add to CommissionGenerator
- No scattered commission logic

---

**Status**: LOCKED ✅

**Rationale**:
```
Clean architecture, single source of truth for commission logic, reusable across all endpoints (API, admin, dashboard).
Easy to understand and maintain. Future commission features don't require touching TrackingService.
```

**Implementation Notes**:
```
Phase 0 Tasks:
1. Create app/Services/CommissionGenerator.php
2. Move commission logic from TrackingService to CommissionGenerator
3. Inject CommissionGenerator into TrackingService
4. Run tests - verify no behavior change
5. (Optional) Remove old commission logic from TrackingService if adding new structure

Estimated effort: 4 hours
```

---

### ✋ Decision 2: Partner vs. Agent Hierarchy Structure

**Question**: How do Partner and Agent hierarchies relate?

**Context**: System has:
- **Partner model**: Company-level entity managing multiple agents
- **Agent model**: Individual/company agents with `partner_id` foreign key
- **New requirement**: Agent-to-agent hierarchy for override commissions

**Problem**: Two separate hierarchies that conflict. Use single unified hierarchy.

---

**Decision**: Deprecate Partner, Migrate to Agent-Only ✅

**Architecture**:
```
Remove Partner model completely
Use ONLY Agent hierarchy with agent_role

Migration: Each existing Partner becomes a Business Partner Agent

OLD:          NEW:
Partner A     Business Partner Agent (A)
  ├─ Agent1     └─ Agent Leader (A1)
  ├─ Agent2       ├─ Agent
  └─ Agent3       └─ Agent
              └─ Agent (direct to BP)
```

**Database Schema Changes**:
```sql
-- REMOVE (no data migration needed because no data exists yet):
-- DROP TABLE partners;
-- DROP TABLE partner_users;

-- ADD to agents:
ALTER TABLE agents ADD COLUMN agent_role ENUM(...) DEFAULT 'agent';
ALTER TABLE agents ADD COLUMN parent_agent_id BIGINT UNSIGNED NULLABLE;

-- Keep partner_id for backward compatibility OR remove it entirely
-- Since no data migration needed, we can remove it!
ALTER TABLE agents DROP COLUMN partner_id;
```

**Example Data Migration** (very simple, no actual data):
```
If Partner 'Global Sales' exists with agents:

BEFORE:
  partners: {id: 1, name: 'Global Sales', ...}
  agents: [{id: 10, name: 'Ali', partner_id: 1}, ...]

AFTER:
  agents: [
    {id: 1, name: 'Global Sales', agent_role: 'business_partner', parent_agent_id: NULL},
    {id: 10, name: 'Ali', agent_role: 'agent', parent_agent_id: 1}
  ]

NOTE: No production data exists yet! Can just update code and tests.
```

**Pros**:
- ✅ **Simplest overall**: Single Agent model does everything
- ✅ **Easiest to maintain**: One hierarchy, one role field
- ✅ **Clearest for users**: No hierarchy confusion
- ✅ **Best for commission logic**: Natural match for "Agent Leader" and "Business Partner" roles
- ✅ **No data migration**: No production Partner data exists
- ✅ **Can rename tables**: "agents" → "accounts" or "personnel" if clearer
- ✅ **Unified access control**: All via agents_users, not separate partners/partner_users
- ✅ **Future flexibility**: Easy add new roles (Franchise, Distributor, etc.)

**Cons**:
- ⚠️ Partner model removed (no data affected)
- ⚠️ Code using Partner model needs updates (minimal per code review)
- ⚠️ Tests using Partner removed/updated

**Complexity**: 🟢 **LOW** (5-6 hours refactoring)
- No data migration
- Code refactoring + test updates
- Model relationships simple

**Database Changes**:
```sql
-- What's removed:
DROP TABLE partners;          -- No data, safe
DROP TABLE partner_users;     -- No data, safe
ALTER TABLE agents DROP COLUMN partner_id;  -- Clean slate

-- What's added:
ALTER TABLE agents ADD COLUMN agent_role;
ALTER TABLE agents ADD COLUMN parent_agent_id;

-- Optional: Rename for clarity
RENAME TABLE agents TO accounts;  -- If "agent" confuses (role vs model)
```

**Code Maintenance**: 🟢 **EXCELLENT**
```php
// Simple and clear
$manager = $agent->parentAgent;

// Role-based logic
match($agent->agent_role) {
    'agent' => 'Earn own sales commission only',
    'agent_leader' => 'Earn own + agent override',
    'business_partner' => 'Earn own + agent + leader override',
};

// No Partner model confusion
// No partner_id vs parent_agent_id confusion
```

**Long-term Maintenance**: 🟢 **EXCELLENT**
- One model, one hierarchy
- Easy to understand + extend
- Clean audit trail
- Simple queries

**Hidden Consequences** (all positive):
- Partner reports → Agent reports (same data, clearer)
- Easier commission hierarchy visualization
- Easier "move agent between parents"
- Easier partner-level reporting
- Easier role-based permissions

**Why This Works**:
- ✅ Simple to understand + maintain
- ✅ Database cheap → no harm in slightly denormalized structure
- ✅ Add extra UI screens without code complexity
- ✅ Easy workflow (monthly commission cutoff, approval process)
- ✅ No data migration burden
- ✅ Clean architecture

---

### **UNIFIED HIERARCHY STRUCTURE**

```
Business Partner 'Global Sales Ltd'
│
├─ Agent Leader 'Team A'
│   ├─ Agent Ali
│   └─ Agent Eka
│
└─ Agent Leader 'Team B'
    ├─ Agent Budi
    └─ Agent Chitra
```

Single hierarchy for everything ✅
Same data for all reports ✅
No confusion ✅

---

**Implementation Plan**:
```sql
Phase 1 - Database (1 hour):
1. Add agent_role column to agents
2. Add parent_agent_id column to agents  
3. Add indexes on both columns
4. Keep partner_id initially (safe), remove in Phase 2 if desired

Phase 2 - Code Refactoring (3 hours):
1. Remove Partner model references
2. Update tests to use Agent instead of Partner
3. Update controllers to create Business Partner agents (not Partners)
4. Update access control (partners_users → agents_users)

Phase 3 - Optional Cleanup (1 hour):
1. Remove partner_id from agents (if confirmed unused)
2. Rename Partner model references in comments/docs
3. Update migration scripts
```

**Partner Migration Plan**:
```
Since no Partner data exists in production, just code changes:
1. Replace Partner model creation → create Agent with agent_role='business_partner'
2. Replace partner_id assignment → parent_agent_id assignment
3. Replace partner_users relationship → agents_users relationship
4. Update tests and factories

Example:
  OLD: Partner::create(['name' => 'Global Sales', ...])
  NEW: Agent::create(['name' => 'Global Sales', 'agent_role' => 'business_partner', ...])
```

---

### ✋ Decision 3: PayoutItem Denormalization

**Question**: How should payout reports show commission breakdown by type?

**Context**: Payout reports need:
- Own sales commission total
- Override commission from Agent sales total
- Override commission from Agent Leader sales total

**Problem**: PayoutItem just links commissions to payouts. Breakdown requires querying Commission table.

---

**Decision**: Denormalize to PayoutItem ✅

**Architecture**:
```
payout_items table (denormalized):
  - payout_id
  - commission_id
  - amount (copied from commission)
  - commission_type  (NEW: own_sales | override)
  - commission_category (NEW: agent | agent_leader | business_partner)
```

**Database Schema**:
```sql
ALTER TABLE payout_items ADD COLUMN commission_type
  ENUM('own_sales', 'override') AFTER commission_id;

ALTER TABLE payout_items ADD COLUMN commission_category
  ENUM('agent', 'agent_leader', 'business_partner') NULLABLE
  AFTER commission_type;

CREATE INDEX idx_payout_items_type ON payout_items(payout_id, commission_type);
```

**Code Implementation**:
```php
PayoutItem::create([
    'payout_id'           => $payout->id,
    'commission_id'       => $commission->id,
    'amount'              => $commission->amount,
    'commission_type'     => $commission->commission_type,
    'commission_category' => $commission->commission_category,
]);
```

---

**Status**: LOCKED ✅

**Rationale**:
```
You prefer simple, understandable code and cheap storage.
Denormalization is acceptable when data doesn't change after insertion.
Commissions don't change after payout, so no sync risk.
Fast queries enable smooth workflow (monthly cutoff, approval process).
Database is cheap, extra columns not a problem.
Easy to add reports and visualizations later.
```

**Implementation Notes**:
```
Phase 3 Tasks:
1. Add commission_type column to payout_items
2. Add commission_category column to payout_items
3. Update PayoutItem model fillable
4. Update PayoutItemFactory
5. Update PayoutReportGenerator to include breakdown
6. Add payout breakdown display to PayoutDetail.vue
7. Test that breakdown queries are fast (<10ms per query)

Estimated effort: 3 hours
Schema change: Simple, 2 columns added
Data flow: Commission → PayoutItem (copy type/category)
Query impact: Queries 10x faster than join
```

**Monthly Cutoff Workflow** (with this choice):
```
Admin clicks "Generate April Payouts"
  ↓
Loop through agents
  ├─ Get pending commissions
  ├─ Create Payout record
  ├─ Create PayoutItems (with commission_type copied)
  └─ Show breakdown instantly
      Own Sales: RM X
      Override: RM Y
  ↓
Admin clicks "Approve" → Email sent ✅

Simple, fast, understandable.
```

**Estimated Query Volume** (provide these to validate):
- Payouts per agent per month: 1 (standard)
- Max agents: 200-500
- Monthly report queries: ~500-2000
- Real-time reporting: Not required, monthly cutoff fine

---

### ✋ Decision 4: Commission Recalculation Policy

**Question**: When agent moves or changes role, what happens to already-earned commissions?

**Scenario Example**:
```
Day 1:  Agent Ali makes sale (RM1000)
        Agent Leader Budi earns override (RM50)
        Commission is in "pending" state
        
Day 5:  Ali moves from Budi → Agent Leader Chitra
        
Question: 
- Should Budi keep the RM50 he already earned?
- Should Chitra get the RM50 instead?
- What if commission already paid?
```

---

**Decision**: No Recalculation — Forward-Only ✅

**Logic**:
```
When agent moves:
  - Past commissions (pending or paid): Stay with original earner
  - Future sales: Go to new hierarchy
  
Example:
  Day 1-5: Ali under Budi → Budi earns all override
  Day 5+: Ali under Chitra → Chitra earns all override
  
  Budi keeps RM50 from Day 1 sale
  Chitra gets override from future sales
```

**Pros**:
- ✅ **Simplest**: No reversal logic
- ✅ **Clean audit trail**: Earnings immutable once created
- ✅ **Fast operation**: Just update parent_agent_id
- ✅ **Easy to explain**: "Past earnings fixed, future follow new manager"
- ✅ **No corruption risk**: No complex calculation/reversal
- ✅ **Matches accounting**: Fixed historical records
- ✅ **Easy to debug**: Commission earning never changes
- ✅ **Good for workflow**: No recalculation at monthly cutoff

**Cons**:
- ⚠️ **Possible fairness issue**: Budi gets commission even without actually managing Ali
  - BUT: Rare in real business
  - AND: Admin can handle manually (block moves with pending, or manual override)

**Complexity**: 🟢 **LOW**
- Just update parent_agent_id
- No reversal, no commission updates, no recalculation
- 1-2 lines of code

**Code Implementation**:
```php
// Update hierarchy - that's it!
$agent->update([
    'parent_agent_id' => $newParent->id,
]);

// All past commissions stay as-is ✅
// All future sales use new hierarchy ✅
```

**Data Flow**:
```
┌────────────────────────┐
│ Agent Ali              │
│ parent_agent_id: 5     │ ← OLD
└────────────────────────┘
         ↓ (update)
┌────────────────────────┐
│ Agent Ali              │
│ parent_agent_id: 7     │ ← NEW
└────────────────────────┘

Commission Table:
  id=1, sale_id=X, earning_agent_id=5, amount=50  ← Stays same!
  
New Sales:
  id=Y → Creates commission with earning_agent_id=7  ← Uses new parent
```

**Long-term Maintenance**: 🟢 **EXCELLENT**
- No recalculation logic
- No reversal edge cases
- Clear business logic

**When This Works Well**:
- Most agent moves legitimate (promotion, team change)
- Pending commissions usually paid quickly (weekly/monthly)
- Admin handles edge cases manually

**Hidden Benefit**: Works well with monthly workflow!
```
Monthly Cutoff Process:

1. Check for pending hierarchy changes
   IF pending commissions exist:
      Block the change OR
      Approve manually

2. Process payouts for old hierarchy

3. Allow hierarchy changes after payout

This prevents any ambiguity!
```

---

**Status**: LOCKED ✅

**Rationale**:
```
Simplest implementation - just update parent_agent_id.
Past commissions stay with original earner (immutable records).
Future sales go to new hierarchy.
Clear audit trail with no reversals or blocking.
Works well with monthly cutoff workflow.
```

---

### ✋ Decision 5: User Role Synchronization

**Question**: How should Spatie User roles sync with Agent roles?

**Context**: User table (Spatie roles: admin, agent, partner) separate from Agent table (new agent_role: agent, agent_leader, business_partner)

**Decision**: Computed Property ✅

**Approach**:
- Agent role reads from Spatie if available
- Fallback to agent_role column
- Gradual migration path
- Flexible and backward compatible

---

## CONFIGURATION DECISIONS

### ✋ Decision 6: Commission Rate Type Support

**Decision**: SystemSetting + AgentCommissionRate ✅

**Approach**:
- Global settings: flexible types (% and fixed RM)
- Individual agent rates: flexible types
- ReferralCode stays percentage-only
- Good balance between flexibility and simplicity

---

### ✋ Decision 7: Commission Calculation Order

**Decision**: Simplified Priority ✅ *(updated — ReferralCode rates removed)*

**Priority Order** (confirmed):
1. `AgentCommissionRate` (highest — per-agent kind override)
2. `SystemSetting` role-based rate (lowest — global default)

**Removed**: `ReferralCode.commission_rate` no longer part of calculation priority. ReferralCode links visits to agents for attribution but carries no rate override. All rate decisions go through AgentCommissionRate or SystemSetting.

**Commission Calculation Flow** (document in CommissionCalculator docblock):
```
getApplicableRate(Agent $agent, string $kind):
  1. Check AgentCommissionRate where (agent_id = $agent->id AND kind = $kind)
     → If found: return custom_percentage, custom_fixed_amount, commission_calc_type
  2. Fall back to SystemSetting role-based rate:
     → key = {agent_role}_{kind}_percentage / _fixed_amount / _calc_type
     → Return that
```

---

### ✋ Decision 8: Fixed Amount Commission Behavior

**Decision**: Fixed + Percentage Combination ✅

**Approach**:
- Agent gets RM50 + 2% of sale amount (example)
- Multiple commission tiers supported
- Complex but flexible for business needs

---

## BUSINESS LOGIC DECISIONS

### ✋ Decision 9: Override Commission Eligibility

**Decision**: Override only if parent exists with correct role ✅

**Rules**:
- Agent → no override
- Agent with parent Agent Leader → 1 override
- Agent with parent Business Partner → 1 override
- Agent Leader with parent Business Partner → 2 overrides

---

### ✋ Decision 10: Payout Report Grouping

**Decision**: Tabbed interface — all groupings (by type, source, period, detail) ✅

**Displays** (tabbed interface):
- Tab 1: By Commission Type
- Tab 2: By Sales Source
- Tab 3: By Time Period
- Tab 4: Detailed Transaction List

---

## TESTING & VALIDATION DECISIONS

### ✋ Decision 11: Backward Compatibility Testing

**Decision**: New system only — no backward compat tests required ✅

**Rationale**: No production data. Focus testing on new commission system only.

---

## PHASE TIMING DECISIONS

### ✋ Decision 12: Implementation Timeline

**Aggressive Timeline**: **2 weeks total** ✅

**Target**: Core commission system live ASAP
- Fast implementation with iterative improvements
- Release MVP, add features incrementally
- All phases compressed to meet timeline

---

## FINAL DECISIONS SUMMARY

**All 12 critical decisions locked**:

1. TrackingService approach: **Option A** ✅
2. Partner vs Agent hierarchy: **Option D** ✅
3. PayoutItem denormalization: **Option A** ✅
4. Commission recalculation policy: **Option A** ✅
5. User role synchronization: **Option C** ✅
6. Commission rate type support: **Option B** ✅
7. Commission calculation priority: **Current order** ✅
8. Fixed amount commission behavior: **Option C** ✅
9. Override commission eligibility: **Option A** ✅
10. Payout report grouping: **Option D** ✅
11. Backward compatibility testing: **New system only** ✅
12. Implementation timeline: **2 weeks total** ✅

---

**Document Status**: ✅ **APPROVED FOR IMPLEMENTATION**

---

## Decision 13: Fee Management Storage (CRD April 2026)

**Question**: Store entry/renewal fees as flat columns on `system_settings` or separate `fee_configurations` table?

**Decision**: Flat columns on `system_settings`. No join overhead. Admin edits all fees on one settings page. Consistent with existing convention. 8 new columns total.

**Status**: LOCKED ✅

---

## Decision 14: Commission Calculation Type Column Naming (CRD April 2026)

**Question**: CRD introduces `commission_type` (percentage vs fixed). Existing plan uses `commission_type` / `commission_category` for hierarchy role. How to resolve naming conflict?

**Decision**: Introduce `commission_calc_type` (enum: `percentage`, `fixed`) on `system_settings`, `commissions`, and `agent_commission_rates`. Reserve `commission_category` for hierarchy role.

**UX vs Architecture**: CRD intends either/or UX, and as of 2026-05-18 the calculator matches that intent — strictly either/or, no additive blending. Both `rate` and `fixed_amount` columns remain stored (for audit + toggle round-trip), but `CommissionCalculator::calculate` follows `commission_calc_type` and ignores the unused column. When `commission_calc_type = 'percentage'` → set `commission_fixed_amount = 0`. When `commission_calc_type = 'fixed'` → set rate = 0. The earlier additive variant from the original Decision 8 / QNA-01 has been retired (see QNA-01 revision).

**Status**: LOCKED ✅

---

## Decision 15: Role Name Editability Storage (CRD April 2026)

**Question**: Store editable role display names in `system_settings`, Spatie role metadata, or dedicated `role_labels` table?

**Decision**: 3 new string columns on `system_settings` (`role_name_agent`, `role_name_leader`, `role_name_business_partner`). Frontend reads from Inertia shared props via `HandleInertiaRequests::share()`. No hardcoded role label strings in Vue files.

**Status**: LOCKED ✅

---

## Decision 16: Renewal & Expiry Lifecycle Field Location (CRD April 2026)

**Question**: Track `registered_at`, `expires_at`, `renewal_due_at`, `fee_payment_status` on `agents` table or separate `agent_memberships` table?

**Decision**: 4 nullable columns on `agents` (`registered_at`, `expires_at`, `renewal_due_at`, `fee_payment_status`). No extra join. If membership history needed later, columns can migrate via migration.

**Status**: LOCKED ✅

---

## Decision 17: Commission Reversal on Refund (CRD April 2026)

**Question**: How to reverse commission when sale is refunded?

**Decision**: Create new negative-amount Commission row (reversal entry). Original record untouched for audit. Reversal row: `is_reversal = true`, `original_commission_id` FK, `status = 'cancelled'`, `amount = -(original.amount)`. Payout blocked if any commission in batch has `status = 'cancelled'`.

**Status**: LOCKED ✅

---

## UPDATED FINAL DECISIONS SUMMARY

**35 critical decisions locked**:

1. TrackingService approach: **Option A** ✅
2. Partner vs Agent hierarchy: **Option D** ✅
3. PayoutItem denormalization: **Option A** ✅
4. Commission recalculation policy: **Option A** ✅
5. User role synchronization: **Option C** ✅
6. Commission rate type support: **Option B** ✅
7. Commission calculation priority: **AgentCommissionRate → SystemSetting (ReferralCode removed)** ✅
8. Fixed amount commission behavior: **Option C** ✅
9. Override commission eligibility: **Option A** ✅
10. Payout report grouping: **Option D** ✅
11. Backward compatibility testing: **New system only** ✅
12. Implementation timeline: **2 weeks total** ✅
13. Fee management storage: **Flat columns on system_settings** ✅
14. Commission calc type naming: **`commission_calc_type` column** ✅
15. Role name storage: **Flat columns on system_settings** ✅
16. Renewal & expiry field location: **Flat columns on agents** ✅
17. Refund commission reversal: **New negative-amount row** ✅
18. Commission reversal time limit: **`reversal_time_limit` in SystemSetting, default 60 days** ✅
19. Clawback from already-paid commissions: **Deducted during next payout calculation** ✅
20. Role downgrade consequences: **Keep subordinates, stop override commissions, admin popup warning** ✅
21. Admin reject after Stripe payment: **Last resort; manual refund with popup reminder; add automation as TODO** ✅
22. Admin-created agent fee handling: **Fee mandatory until approved; admin approval skips payment entirely** ✅
23. Stripe package: **Laravel Cashier** ✅
24. API response backward compatibility: **Return first commission only; no override details exposed** ✅
25. commission_fixed_amount precision: **DECIMAL(10,2) for all transaction fields; SystemSetting stored as string, parsed in CommissionCalculator** ✅
26. Cache invalidation strategy: **Flush ALL SystemSetting cache on every update** ✅
27. Email verification retry limit: **Max attempts configurable via `email_verification_max_retry` in SystemSetting, default 10; resets next day** ✅
28. Stripe credentials: **Account exists; keys provided separately by owner; add to `.env` before Phase 7** ✅
29. Terms & Conditions: **Static `/terms` page with placeholder text; no auth required; real content inserted before go-live** ✅
30. Manual transfer bank account: **Registration wizard reads bank details from `Agent::find(1)->bankAccount`** ✅
31. Commission rate defaults: **agent own-sales 10%, Leader override-agent 5%, BP override-agent 2%, BP override-leader 3% — seeded into SystemSettings** ✅
32. Fee defaults: **BP entry RM3000 / renewal RM1000; Leader entry RM100 / renewal RM100; Agent entry RM100 / renewal RM100 — seeded into SystemSettings** ✅
33. Membership duration: **365 days stored as `membership_duration_days` integer in SystemSettings (default 365); FeeService reads this — not hardcoded** ✅
34. Default Business Partner agent: **Agent #1; seeded by BusinessPartnerSeeder with `is_default=true`; used as upline fallback and system notification target** ✅
35. Referral code prefix: **`PENURWILL-` stored as `referral_code_prefix` in SystemSettings; default changed from `REF-`** ✅

---

## Decision 18: Commission Reversal Time Limit (Gap Resolution)

**Question**: Can admins reverse commission on sale from months/years ago?

**Decision**: Add `reversal_time_limit` integer column to `system_settings` (days). Default: 60. `RefundService::reverseSale()` checks `sale.created_at >= now()->subDays($setting->reversal_time_limit)` before creating reversal rows. If outside window, throw `ReversalWindowExpiredException` and block with error to admin.

**Status**: LOCKED ✅

---

## Decision 19: Clawback from Already-Paid Commissions (Gap Resolution)

**Question**: If reversal targets commission already in completed payout, how is money recovered?

**Decision**: Negative-amount reversal Commission row created immediately (Decision 17). On next payout request, `RequestPayoutController` totals all eligible commissions (pending, not in open/closed payout). Any `is_reversal = true` rows for that agent with `status = pending` included as negative amounts. Request total shown to agent is net figure. System blocks payout requests where net total ≤ 0. Automatic — no separate clawback flow.

**Status**: LOCKED ✅

---

## Decision 20: Role Downgrade Consequences (Gap Resolution)

**Question**: When admin downgrades Leader → Agent (or BP → Leader), what happens?

**Decision**:
- **Subordinate structure preserved**: `parent_agent_id` of existing subordinates NOT changed. Subordinates remain linked to downgraded agent as parent.
- **Override commissions stop**: After role change, `CommissionGenerator` won't create override commissions (no longer meets role threshold). Past commissions immutable (Decision 4).
- **Admin popup warning**: When admin saves downgrade AND agent has direct subordinates, show blocking confirmation modal: "⚠ This agent has {N} subordinates. After downgrade they will no longer earn override commissions from those agents. Subordinates must be manually reassigned if desired. Continue?"
- **Payout uses current role**: Always reads `agent_role` at request time. No recalculation of past commissions.

**Status**: LOCKED ✅

---

## Decision 21: Admin Reject After Stripe Payment (Gap Resolution)

**Question**: If admin rejects paid applicant, what happens to payment?

**Decision**:
- Rejection of paid agent = **last resort** (admin should request corrected documents first).
- **No automated refund**. When admin clicks [Reject Application] on fee-paid agent, modal reminder popup: "⚠ This agent has a completed fee payment. Stripe refunds must be processed manually via the Stripe dashboard. Please refund before or after rejecting. [Confirm Rejection]".
- **Automation is TODO** (Phase 7+): Future enhancement to trigger Stripe refund via Cashier automatically on rejection. List in TODOS.md backlog.
- Stripe dashboard refund: Admins can initiate refunds against any Checkout Session ID stored in `fee_payments.payment_reference`.

**Status**: LOCKED ✅

---

## Decision 22: Admin-Created Agent Fee Handling (Gap Resolution)

**Question**: When admin creates agent via `/admin/agents/add`, does fee apply? What if admin approves without verifying payment?

**Decision**:
- Fee **mandatory** for all agents regardless of creation method — until approved.
- Admin may upload manual bank transfer receipt on behalf of agent (same as self-registration manual path).
- Admin clicks **[Approve Agent]** without fee record: approval **skips fee entirely** — no fee_payments row, `fee_payment_status` set to `waived`. Intentional: admin takes explicit responsibility.
- Admin clicks **[Approve Agent]** with fee paid: normal approval flow, `FeeService::applyEntryFee` called.
- [Approve Agent] button always visible (not blocked by fee), but UI shows current fee status clearly.

**Status**: LOCKED ✅

---

## Decision 23: Stripe Package (Gap Resolution)

**Question**: Which Stripe integration library?

**Decision**: **Laravel Cashier** (`laravel/cashier`). Rationale: native Laravel integration, handles Checkout Sessions, webhook verification, charge records with minimal boilerplate. `FeeService` wraps Cashier calls so rest of codebase never imports Cashier directly.

**Status**: LOCKED ✅

---

## Decision 24: API Response Backward Compatibility (Gap Resolution)

**Question**: Tracking API (`POST /api/agents/track/sale`) currently returns single commission. With multi-level commissions, response changes. How to handle existing integrations?

**Decision**: No API versioning. Response returns **first (own_sales) commission** for sale agent + agent info — same shape as today. Override commissions internal, not surfaced to external API callers. `AgentTrackingController` returns `$commissions->firstWhere('commission_type', 'own_sales')`. No breaking change for existing integrations.

**Status**: LOCKED ✅

---

## Decision 25: commission_fixed_amount Decimal Precision (Gap Resolution)

**Question**: What precision for fixed commission amounts?

**Decision**: All commission/fee monetary columns use `DECIMAL(10,2)`. SystemSetting rate/fixed columns stored as `DECIMAL(10,2)`. `CommissionCalculator::calculate()` explicitly casts inputs to `float` before arithmetic to prevent string-math bugs.

**Status**: LOCKED ✅

---

## Decision 26: Cache Invalidation on SystemSetting Update (Gap Resolution)

**Question**: Current plan caches SystemSetting 1 hour. Rate change + sale within cache window = old rate used.

**Decision**: **Flush entire `commission_config` cache key on every `SystemSetting` update** — no partial flush, no TTL dependency. `SystemSettingController::update()` calls `CommissionConfig::flush()` as last step before redirect. 1-hour TTL acts as safety net only. Acceptable trade-off: admins change settings rarely.

**Status**: LOCKED ✅

---

## Decision 27: Email Verification Retry Limit (Gap Resolution)

**Question**: How many failed verification attempts before lockout? Can users get new code?

**Decision**:
- Max attempts per code: configurable via `email_verification_max_retry` in `system_settings`. Default: **10**.
- Expiry per code: 15 minutes from generation.
- After exhausting max attempts, user must **Resend** for fresh code (resets counter).
- Daily limit: tracked per email per calendar day. If total daily attempts exceed `email_verification_max_retry`, block rest of day: "Too many attempts. Please try again tomorrow."
- Resend cooldown: 60 seconds between resend requests (enforced in Vue UI + server-side timestamp check).

**Status**: LOCKED ✅

---

## Decision 28: Stripe Credentials (Configuration Decision 2026-05-04)

**Question**: Is Stripe account set up?

**Decision**: Stripe account exists. Keys not committed to source control. Add `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` to `.env` before Phase 7. Reference `.env.example` for required variable names. Keys provided separately by project owner.

**Status**: LOCKED ✅

---

## Decision 29: Terms & Conditions Page (Configuration Decision 2026-05-04)

**Question**: Where does T&C link in registration Step 5 point? Who writes content?

**Decision**: Static public page at `/terms` (`Terms.vue`). No auth required. Placeholder content during development. Real legal text copied in before go-live. Page must exist so T&C checkbox in registration Step 5 can link to it. Route added outside auth middleware group in `routes/web.php`.

**Status**: LOCKED ✅

---

## Decision 30: System Bank Account for Manual Transfers (Configuration Decision 2026-05-04)

**Question**: Which bank account details does registration wizard display for manual bank transfer?

**Decision**: Display bank details from `Agent::find(1)->bankAccount`. Agent #1 = seeded default Business Partner (system owner). Admin must ensure Agent #1's bank account populated before enabling manual transfer flow in production.

**Status**: LOCKED ✅

---

## Decision 31: Commission Rate Defaults (Configuration Decision 2026-05-04)

**Question**: What are default commission rates seeded into SystemSettings?

**Decision**:
- Agent own-sales: **10%**
- Agent Leader override on agent sales: **5%**
- Business Partner override on agent sales: **2%**
- Business Partner override on Agent Leader sales: **3%**

All four seeded into SystemSettings as percentage columns. `AgentCommissionRate` rows can override per-agent-per-kind without changing global defaults.

**Status**: LOCKED ✅

---

## Decision 32: Fee Defaults (Configuration Decision 2026-05-04)

**Question**: What are default entry and renewal fees seeded into SystemSettings?

**Decision**:
- Business Partner: entry **RM 3,000** / renewal **RM 1,000**
- Agent Leader: entry **RM 100** / renewal **RM 100**
- Agent: entry **RM 100** / renewal **RM 100**

All six seeded as `DECIMAL(10,2)` columns in `system_settings`.

**Status**: LOCKED ✅

---

## Decision 33: Membership Duration (Configuration Decision 2026-05-04)

**Question**: How long is membership valid after approval? Hardcoded or configurable?

**Decision**: **365 days**, stored as `membership_duration_days` integer column in `system_settings` with default 365. `FeeService::applyEntryFee()` reads this value instead of hardcoding. Admin can adjust on System Settings page. Migration #7 (Phase 1) adds column.

**Status**: LOCKED ✅

---

## Decision 34: Default Business Partner Agent (Configuration Decision 2026-05-04)

**Question**: Which agent is system owner / default upline?

**Decision**: **Agent #1**, seeded by `BusinessPartnerSeeder` with `agent_role='business_partner'` and `is_default=true`. Used as: (a) default upline fallback when no referral code supplied during registration; (b) target for `NotificationService::notifyAdmin()`; (c) bank account source for manual transfer display. `is_default=true` flag added to agents table in Phase 1 migration #1.

**Status**: LOCKED ✅

---

## Decision 35: Referral Code Prefix (Configuration Decision 2026-05-04)

**Question**: What prefix for auto-generated referral codes? Current system generates `REF-XXXXXXXX`.

**Decision**: Change default prefix to **`PENURWILL-`**. Stored as `referral_code_prefix` string column (max 50 chars) in `system_settings`, default `'PENURWILL-'`. `ReferralCode` generation reads this setting. Migration #7 (Phase 1) adds column. Admin can change prefix on System Settings page for future codes (existing codes not renamed).

**Status**: LOCKED ✅