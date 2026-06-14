# Commission Enhancement: Quick Reference Guide

**For Developers**: Start here before reading full documents.

---

## 📋 Document Map

| Document | Purpose | Read When |
|----------|---------|-----------|
| **NEW_REQUIREMENT.md** | Full specification, schema, implementation plan | Starting implementation |
| **ANALYSIS_AND_DECISIONS.md** | Gaps, issues, decision points, recommendations | Before starting Phase 0 |
| **QUICK_REFERENCE.md** | This file - key points and checklist | Right now! |
| **DECISION_OUTCOMES.md** | Record decisions made (to be created) | After decisions confirmed |

---

## ⚠️ TOP 5 CRITICAL ISSUES (MUST RESOLVE FIRST)

### 1. **TrackingService NOT REFACTORED** 🔴 BLOCKING
- Current: Sales tracked via `TrackingService::trackSale()` (API endpoint)
- Problem: New CommissionGenerator service won't be used
- Solution: Refactor TrackingService to use CommissionGenerator
- Timeline: Phase 0 (before other phases)

**Action**:
```php
// TrackingService::trackSale() needs updating
$sale = Sale::create($data);
$this->generator->generateForSale($sale);  // NEW
```

---

### 2. **Partner vs. Agent Hierarchy CONFLICT** 🔴 BLOCKING
- Current: Two separate hierarchies (Partner → Agent) and (Agent → Agent)
- Problem: Unclear which takes precedence
- Solution: Clarify business model before implementation

**Questions to Answer**:
- Can Agent be managed by BOTH Partner AND Agent Leader?
- Should Partner become "Business Partner" agent role?
- Should Partner stay separate or merge into Agent model?

---

### 3. **User Role vs Agent Role SYNC** 🟠 HIGH PRIORITY
- Current: Spatie User roles (user.roles) separate from Agent roles (agent.agent_role)
- Problem: Can create inconsistent states
- Solution: Decide sync strategy

**Options**:
- A: Keep separate (needs validation)
- B: Sync with events (automatic)
- C: Computed property (agent_role reads from Spatie as fallback)

---

### 4. **PayoutItem Design NOT SPECIFIED** 🟠 HIGH PRIORITY
- Current: No way to filter payouts by commission_type efficiently
- Problem: Payout reports slow with large datasets
- Solution: Choose denormalization strategy

**Recommended**: Add commission_type + commission_category to PayoutItem

---

### 5. **Commission Recalculation POLICY MISSING** 🟠 HIGH PRIORITY
- Current: Document assumes no recalculation
- Problem: What happens when agent moves to different parent?
- Solution: Define policy (retroactive vs. forward-only)

**Recommended**: Forward-only (no recalculation), add audit logging

---

## 🎯 EXISTING SYSTEM NOTES FOR DEVELOPERS

### Architecture Overview
```
User (Spatie roles: admin, agent)
  ↓ (via agents_users pivot)
Agent (profile_type: individual|company)
  ├─ CommissionRate (custom rate per agent)
  ├─ ReferralCode (code per agent, has commission_rate)
  ├─ Sales (sales made by this agent)
  ├─ Commissions (commissions earned)
  └─ Payouts (payout requests)
    └─ PayoutItems (links commissions to payouts)

Partner (optional, manages agents)
  └─ Agent (via partner_id)
```

### Current Commission Flow
```
API Call: POST /api/agents/track/sale
  ↓
TrackingService::trackSale()
  ├─ Validate referral code
  ├─ Calculate commission (hardcoded: custom_rate or 10%)
  ├─ Create Sale record
  ├─ Create Commission record
  └─ Log activity

Result: One commission per sale (own_sales only)
```

### Key Services Already Exist
- **TrackingService** (`app/Services/TrackingService.php`): Handles API calls, creates sales/visits/referrals
- **ActivityLog** (`app/Models/ActivityLog.php`): Audit trail for all CRUD operations
- **Spatie Permission**: User roles (admin, agent, partner)

### Important Models & Their Relationships
```php
Agent:
  - hasMany(Commission)
  - hasOne(AgentCommissionRate)
  - belongsTo(ReferralCode)
  - belongsTo(Partner)

Commission:
  - belongsTo(Agent)        # Who made the sale
  - belongsTo(Sale)
  - hasMany(PayoutItem)

Sale:
  - belongsTo(Agent)
  - hasOne(Commission)

SystemSetting:
  - commission_default_rate (10.00)
  - partner_default_commission_rate (5.00)
  - referral_code_prefix ('REF')
```

---

## 🔧 IMPLEMENTATION CHECKLIST

### Phase 0: Refactor Existing (NEW - CRITICAL)
- [ ] Extract commission calculation to separate service
- [ ] Update TrackingService to use new CommissionCalculator
- [ ] Run all existing tests - must pass with zero behavior change
- [ ] Document all commission rate fallback paths
- [ ] Merge to main branch before Phase 1

### Phase 1: Schema & Models
- [ ] Create migration: Add agent_role + parent_agent_id to agents
- [ ] Create migration: Add commission_type + earning_agent_id to commissions
- [ ] Create migration: Update system_settings with new fields
- [ ] Update Agent model with relationships
- [ ] Update Commission model with relationships
- [ ] Create AgentHierarchy service
- [ ] Create factory for hierarchical agents (for tests)
- [ ] Seed existing agents as 'agent' role
- [ ] Run tests - all passing

### Phase 2: Commission Generation
- [ ] Create CommissionCalculator service (percentage + fixed amount support)
- [ ] Create CommissionGenerator service (multi-level commission logic)
- [ ] Create CommissionConfig service (validation + defaults)
- [ ] Update TrackingService to use CommissionGenerator
- [ ] Add transaction handling (all-or-nothing commission creation)
- [ ] Add error handling + logging
- [ ] Write integration tests
- [ ] Test with API endpoint: POST /api/agents/track/sale

### Phase 3: Payout Reporting
- [ ] Add commission_type + commission_category to PayoutItem migration
- [ ] Create PayoutReportGenerator service
- [ ] Update CommissionController::detail() for breakdown view
- [ ] Update CommissionDetail.vue to show breakdown
- [ ] Test with sample hierarchy data

### Phase 4: Admin UI
- [ ] Update SystemSettingController for new fields
- [ ] Create SystemSettingsUpdate form with rate type selectors
- [ ] Add Agent role selector in AgentCreate/AgentUpdate
- [ ] Add Parent agent selector in AgentCreate/AgentUpdate
- [ ] Add validation (prevent cycles, invalid role combinations)
- [ ] Test UI with various hierarchies

### Phase 5: Testing & Docs
- [ ] Run full test suite
- [ ] Create migration script for Partners → Business Partners (if needed)
- [ ] Verify all existing commissions marked as 'own_sales'
- [ ] Performance test with 1000+ agents/commissions
- [ ] Update CLAUDE.md with new architecture
- [ ] Create API documentation for changes

---

## 📊 Database Schema Summary

### New Fields to Add

#### agents table
```sql
agent_role ENUM('agent', 'agent_leader', 'business_partner') DEFAULT 'agent'
parent_agent_id BIGINT UNSIGNED NULLABLE
```

#### commissions table
```sql
commission_type ENUM('own_sales', 'override') DEFAULT 'own_sales'
commission_category ENUM('business_partner', 'agent_leader', 'agent') NULLABLE
earning_agent_id BIGINT UNSIGNED NULLABLE
```

#### system_settings table
```sql
-- Remove (or keep for backward compat):
-- commission_default_rate, partner_default_commission_rate

-- Add:
agent_commission_rate DECIMAL(5,2) DEFAULT 10.00
agent_commission_type ENUM('percentage', 'fixed_amount') DEFAULT 'percentage'

agent_leader_override_rate DECIMAL(5,2) DEFAULT 5.00
agent_leader_override_type ENUM('percentage', 'fixed_amount') DEFAULT 'percentage'

business_partner_own_sales_rate DECIMAL(5,2) DEFAULT 10.00
business_partner_own_sales_type ENUM('percentage', 'fixed_amount') DEFAULT 'percentage'

business_partner_agent_leader_override_rate DECIMAL(5,2) DEFAULT 5.00
business_partner_agent_leader_override_type ENUM('percentage', 'fixed_amount') DEFAULT 'percentage'

business_partner_agent_override_rate DECIMAL(5,2) DEFAULT 2.00
business_partner_agent_override_type ENUM('percentage', 'fixed_amount') DEFAULT 'percentage'
```

#### payout_items table (OPTIONAL but RECOMMENDED)
```sql
commission_type ENUM('own_sales', 'override') -- For faster queries
commission_category ENUM('business_partner', 'agent_leader', 'agent') NULLABLE
```

---

## 🧪 Testing Guide

### Unit Tests (Service Layer)
```php
// tests/Unit/Services/CommissionCalculatorTest.php
- test_percentage_calculation()
- test_fixed_amount_calculation()
- test_rate_precedence_agent_rate_wins()
- test_rate_precedence_referral_code_wins()
- test_system_default_as_fallback()

// tests/Unit/Services/AgentHierarchyTest.php
- test_get_direct_manager()
- test_get_full_chain_to_business_partner()
- test_prevent_cycle_creation()
- test_validate_role_hierarchy()

// tests/Unit/Services/CommissionConfigTest.php
- test_validate_commission_amounts()
- test_validate_rate_types()
- test_system_defaults_applied()
```

### Integration Tests (Full Flow)
```php
// tests/Feature/CommissionCalculationTest.php
- test_agent_own_sales_commission()
- test_agent_leader_earns_override()
- test_business_partner_earns_both_overrides()
- test_hierarchy_with_three_levels()
- test_fixed_amount_commission_ignores_sale_size()
- test_mixed_percentage_and_fixed_amounts()
```

### API Tests
```php
// POST /api/agents/track/sale with new hierarchy
- test_sale_creates_multiple_commissions()
- test_commission_amounts_correct_for_all_earners()
- test_response_includes_all_commission_ids()
```

---

## 🚨 Common Mistakes to Avoid

❌ **DON'T**:
- Assume `$commission->agent` is earning agent (might be sales agent for overrides)
- Forget to validate parent_agent_id hierarchy before saving
- Mix percentage and fixed amount logic without checking type
- Hardcode commission percentages (always use SystemSetting)
- Forget `earning_agent_id` when creating override commissions
- Assume all Agent records have proper roles (test with NULL/missing role)
- Forget transaction wrapping when creating multiple commissions
- Create PayoutItem without commission_type (if denormalizing)

✅ **DO**:
- Use explicit method names: `$commission->earningAgent()` vs `$commission->salesAgent()`
- Validate hierarchy in AgentHierarchy service before any changes
- Check `commission_type` field before calculation
- Always use CommissionCalculator and CommissionConfig services
- Add `earning_agent_id` for override commissions
- Add migration to set roles for existing agents
- Wrap commission generation in DB::transaction()
- Update PayoutItem when adding commission_type to denormalize

---

## 🔍 How to Debug Commission Issues

### Scenario: Commission amount is wrong
```php
// Check rate precedence
$agent = Agent::find($agentId);
$custom = $agent->commissionRate?->custom_rate;  // Exists?
$code = $agent->referralCode?->commission_rate;  // Exists?
$system = SystemSetting::first();

// Then verify calculation
$rate = $custom ?? $code ?? $system->agent_commission_rate;
$type = $system->agent_commission_type;  // percentage or fixed?

// Calculate
if ($type === 'percentage') {
    $amount = $saleAmount * ($rate / 100);  // Percentage
} else {
    $amount = $rate;  // Fixed amount, ignore $saleAmount
}
```

### Scenario: Override commission not created
```php
// Check hierarchy
$agent = Agent::find($agentId);
$parent = $agent->parentAgent;  // NULL means no override

// Check if parent has right role
if ($parent && $parent->agent_role === 'agent_leader') {
    // Should create override
}

// Check commission records
Commission::where('sale_id', $saleId)->get();
// Should have 2: own_sales + override
```

### Scenario: Payout report showing wrong totals
```php
// Use repository to get breakdown
$breakdown = $repo->getPayoutBreakdown($agent, 2026, 4);

// Verify:
// $breakdown['own_sales'] - Agent earned from their sales
// $breakdown['override_agent'] - Agent earned from subordinate Agent sales
// $breakdown['override_agent_leader'] - Business Partner earned from AL sales
```

---

## 📞 When to Ask Questions

- **Design questions**: Use ANALYSIS_AND_DECISIONS.md Part G
- **Rate calculation questions**: Check CommissionCalculator service examples
- **Hierarchy questions**: Check AgentHierarchy service documentation
- **Migration questions**: Check Part F in ANALYSIS_AND_DECISIONS.md
- **Architecture questions**: Check Part C in ANALYSIS_AND_DECISIONS.md

---

## 🎬 Getting Started

1. **Read this file** (5 min) ← You are here
2. **Read ANALYSIS_AND_DECISIONS.md Part G** (10 min) - Clarification questions
3. **Fill out DECISION_OUTCOMES.md** (15 min) - Make choices
4. **Read NEW_REQUIREMENT.md** (30 min) - Full specification
5. **Start Phase 0** - Refactor TrackingService

---

## ✅ Success Criteria

Implementation complete when:
- [ ] All tests pass (existing + new)
- [ ] Commission breakdown appears in payout reports
- [ ] Agent hierarchy settable via admin UI
- [ ] Fixed amount commissions work correctly
- [ ] No duplicate commissions for same sale
- [ ] PayoutItems filterable by commission_type
- [ ] TrackingService uses CommissionGenerator
- [ ] No backward compatibility issues with existing payouts
- [ ] Performance: 1000+ agent queries complete in <500ms
- [ ] Documentation updated in CLAUDE.md

---

**Questions?** Check ANALYSIS_AND_DECISIONS.md or ask in code review!