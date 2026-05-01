# Commission Enhancement: Decision Outcomes

**Status**: ✅ **DECISIONS LOCKED IN** - Ready for Phase 0 Implementation


---

## CRITICAL DECISIONS (Must Answer Before Phase 0)

### ✋ Decision 1: TrackingService Refactoring Approach

**Question**: How should we handle commission generation in TrackingService?

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
- ✅ **DRY principle**: No code duplication - commission logic in ONE place
- ✅ **Testable**: Can mock CommissionGenerator in tests
- ✅ **Reusable**: Other controllers (admin, dashboard) can also use CommissionGenerator
- ✅ **Maintainable**: Change commission logic → only update CommissionGenerator
- ✅ **Future-proof**: Easy to add listeners/events later without touching TrackingService

**Cons**:
- ⚠️ Minor: Need to create new CommissionGenerator service (small extra file)
- ⚠️ Minor: Requires understanding of dependency injection

**Complexity**: 🟢 **LOW** (3-5 hours to implement)
- Just extract 15-20 lines of code into new service
- Update 1 file (TrackingService)
- Add 1 new file (CommissionGenerator)

**Database Changes**: None needed

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
- New commission types needed? Only add to CommissionGenerator
- No scattered commission logic across multiple files

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

**Context**: Currently, the system has:
- **Partner model**: Company-level entity managing multiple agents
- **Agent model**: Individual/company agents with `partner_id` foreign key
- **New requirement**: Agent-to-agent hierarchy for override commissions

**The Problem**: Two separate hierarchies that can conflict. The solution is to use a single unified hierarchy.

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
- ✅ **Clearest for users**: No confusion about hierarchies
- ✅ **Best for commission logic**: Natural match for "Agent Leader" and "Business Partner" roles
- ✅ **No data migration needed**: No production Partner data exists
- ✅ **Can rename tables**: Can rename "agents" to "accounts" or "personnel" if clearer
- ✅ **Unified access control**: All via agents_users, not separate partners/partner_users
- ✅ **Future flexibility**: Easy to add new roles (Franchise, Distributor, etc.)

**Cons**:
- ⚠️ Partner model removed (but no data affected)
- ⚠️ Code using Partner model needs updates (should be minimal based on code review)
- ⚠️ Tests using Partner removed/updated

**Complexity**: 🟢 **LOW** (5-6 hours for refactoring)
- No data migration (no Partner data exists)
- Just code refactoring and test updates
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
- Easy to understand
- Easy to extend (add new roles)
- Clean audit trail
- Simple queries

**Hidden Consequences** (all positive):
- Partner reports become Agent reports (same data, clearer)
- Easier to implement commission hierarchy visualization
- Easier to add "move agent between parents" feature
- Easier to implement partner-level reporting
- Easier to add role-based permissions

**Why This Works Well for You**:
- ✅ Simple to understand and maintain
- ✅ Database is cheap → no harm in slightly denormalized structure
- ✅ Can add extra UI screens (Partner Dashboard, Agent Dashboard) without code complexity
- ✅ Easy to introduce workflow (monthly commission cutoff, approval process, etc.)
- ✅ No data migration burden
- ✅ Clean architecture going forward

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

**Question**: How should payout reports show breakdown of commissions by type?

**Context**: Payout reports need to show:
- Own sales commission total
- Override commission from Agent sales total  
- Override commission from Agent Leader sales total

**The Problem**: Currently PayoutItem just links commissions to payouts. To show breakdown, must look in Commission table.

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

**Estimated Query Volume** (provide these if you want to validate):
- Payouts per agent per month: 1 (standard case)
- Max agents in system: 200-500
- Monthly report queries: ~500-2000
- Need real-time reporting: Not required, monthly cutoff is fine

---

### ✋ Decision 4: Commission Recalculation Policy

**Question**: When an agent moves to a different parent or changes role, what happens to commissions they've already earned?

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
- ✅ **Simplest to implement**: No reversal logic needed
- ✅ **Clean audit trail**: Earnings are immutable once created
- ✅ **Fast operation**: Just update parent_agent_id, done
- ✅ **Easy to explain**: "Your past earnings are fixed, future earnings follow your new manager"
- ✅ **No data corruption risk**: No complex calculation/reversal process
- ✅ **Matches accounting**: Like fixed historical records
- ✅ **Easy to debug**: Commission earning never changes
- ✅ **Good for workflow**: No recalculation needed for monthly cutoff

**Cons**:
- ⚠️ **Possible fairness issue**: If Budi didn't actually manage Ali, he still gets commission
  - BUT: This is rare in real business (you don't move agents without reason)
  - AND: Can be handled by admin (block moves with pending, or handle manually)

**Complexity**: 🟢 **LOW**
- Just update parent_agent_id
- No reversal logic
- No commission updates
- No recalculation
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
- No recalculation logic to maintain
- No reversal edge cases to handle
- Clear business logic
- Easy to understand

**When This Works Well**:
- Most agent moves are legitimate (promotion, team change)
- Pending commissions usually paid quickly (weekly/monthly)
- Admin can handle edge cases manually

**Hidden Benefit**: Works well with your workflow!
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
- Global settings: support flexible types (% and fixed RM)
- Individual agent rates: support flexible types
- ReferralCode stays percentage-only
- Good balance between flexibility and simplicity

---

### ✋ Decision 7: Commission Calculation Order

**Decision**: Current Priority ✅

**Priority Order** (confirmed):
1. AgentCommissionRate.custom_rate (highest priority)
2. ReferralCode.commission_rate
3. SystemSetting (lowest priority)

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
- Agent → no override created
- Agent with parent Agent Leader → 1 override created
- Agent with parent Business Partner → 1 override created
- Agent Leader with parent Business Partner → 2 overrides created

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

**Rationale**: No production data exists yet. Focus testing on new commission system only.

---

## PHASE TIMING DECISIONS

### ✋ Decision 12: Implementation Timeline

**Aggressive Timeline**: **2 weeks total** ✅

**Target**: Get core commission system live ASAP
- Fast implementation with iterative improvements
- Release MVP version, add features incrementally
- All phases compressed to meet timeline

---

## FINAL DECISIONS SUMMARY

**All 12 critical decisions have been locked in**:

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

**Question**: Store entry/renewal fees as flat columns on `system_settings` or in a separate `fee_configurations` table?

**Decision**: Flat columns on `system_settings`. No join overhead. Admin edits all fees on one settings page. Consistent with existing convention. 8 new columns total.

**Status**: LOCKED ✅

---

## Decision 14: Commission Calculation Type Column Naming (CRD April 2026)

**Question**: The CRD introduces `commission_type` (percentage vs fixed). The existing plan uses `commission_type` / `commission_category` for hierarchy role (own_sales, override_agent, override_agent_leader). How to resolve the naming conflict without breaking the existing plan?

**Decision**: Introduce `commission_calc_type` (enum: `percentage`, `fixed`) on `system_settings`, `commissions`, and `agent_commission_rates`. Reserve `commission_category` for hierarchy role.

**UX vs Architecture note**: CRD intends either/or UX. Architecture stays additive (both rate and fixed_amount stored). When `commission_calc_type = 'percentage'` → set `commission_fixed_amount = 0`. When `commission_calc_type = 'fixed'` → set rate = 0. UI exposes it as a choice; the additive system from Decision 8 / QNA-01 remains intact.

**Status**: LOCKED ✅

---

## Decision 15: Role Name Editability Storage (CRD April 2026)

**Question**: Store editable role display names in `system_settings`, Spatie role metadata, or a dedicated `role_labels` table?

**Decision**: 3 new string columns on `system_settings` (`role_name_agent`, `role_name_leader`, `role_name_business_partner`). Frontend reads from Inertia shared props via `HandleInertiaRequests::share()`. No hardcoded role label strings in Vue files.

**Status**: LOCKED ✅

---

## Decision 16: Renewal & Expiry Lifecycle Field Location (CRD April 2026)

**Question**: Track `registered_at`, `expires_at`, `renewal_due_at`, and `fee_payment_status` on the `agents` table or in a separate `agent_memberships` table?

**Decision**: 4 nullable columns on `agents` (`registered_at`, `expires_at`, `renewal_due_at`, `fee_payment_status`). No extra join. If membership history is needed in future, columns can be moved to a separate table via migration.

**Status**: LOCKED ✅

---

## Decision 17: Commission Reversal on Refund (CRD April 2026)

**Question**: How to reverse a commission when a sale is refunded?

**Decision**: Create a new negative-amount Commission row (reversal entry). Original record stays untouched for audit. Reversal row: `is_reversal = true`, `original_commission_id` FK, `status = 'cancelled'`, `amount = -(original.amount)`. Payout is blocked if any commission in the batch has `status = 'cancelled'`.

**Status**: LOCKED ✅

---

## UPDATED FINAL DECISIONS SUMMARY

**17 critical decisions are now locked in**:

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
13. Fee management storage: **Flat columns on system_settings** ✅
14. Commission calc type naming: **`commission_calc_type` column** ✅
15. Role name storage: **Flat columns on system_settings** ✅
16. Renewal & expiry field location: **Flat columns on agents** ✅
17. Refund commission reversal: **New negative-amount row** ✅
