I've identified the two missing lines from the original:
- Part 14.2: missing "Extend existing enum to include `expired`:" before the SQL
- Part 16.4: missing "Extend commissions.status enum to add `cancelled`:" before the SQL

# Commission Structure Enhancement - Comprehensive Requirements

**Document Status**: Requirements Definition  
**Last Updated**: 2026-04-28  
**Prepared for**: Developer Handover

---

## Executive Summary

Comprehensive enhancement to Penurwill Agent commission system. Current: flat rates per agent. New: **Business Partner** and **Agent Leader** roles with **override commission** — earn from own sales + subordinate sales.

### Key Changes
- Add hierarchical agent roles (Agent → Agent Leader → Business Partner)
- Support own sales + override commission tracking
- Flexible commission config (% or RM amount)
- Detailed payout reports with commission breakdown

---

## Part 1: EXISTING SYSTEM ANALYSIS

### Current Commission Architecture

#### 1. Database Schema (Existing)
```
agents (id, individual_name, company_name, profile_type, referral_code_id, partner_id, status...)
├── commission_rates (id, agent_id, custom_rate, effective_from)
├── commissions (id, agent_id, sale_id, amount, commission_rate, commission_source, status)
├── sales (id, agent_id, amount, commission_amount, sale_date...)
└── payouts (id, agent_id, amount, status, payment_method...)
    └── payout_items (id, payout_id, commission_id...)

referral_codes (id, agent_id, code, commission_rate, is_active, expires_at...)
system_settings (id, commission_default_rate, partner_default_commission_rate, referral_code_prefix)
```

#### 2. Models & Relationships
- **Agent**: `hasMany(Commission)`, `hasMany(Sale)`, `hasMany(Payout)`, `hasOne(AgentCommissionRate)`
- **Commission**: `belongsTo(Agent)`, `belongsTo(Sale)`, `hasMany(PayoutItem)`
- **Sale**: `belongsTo(Agent)`, `hasOne(Commission)`
- **Payout**: `hasMany(PayoutItem)`, `belongsTo(Agent)`
- **SystemSetting**: Global config with commission defaults

#### 3. Commission Calculation (Current)
```php
// In Sale::trackSale()
$commissionAmount = $saleData['amount'] * ($referralCodeModel->commission_rate / 100);

// Commission always generated in 3 sources:
- referral_code (custom per referral code)
- agent_rate (custom per agent)
- system_default (fallback)
```

**Limitations**:
- Only **own sales** commission supported
- No hierarchical relationships between agents
- No override commission mechanism
- Percentage-based rates only
- No commission type distinction in reporting

#### 4. Payout System (Current)
- **Payout** table stores aggregated amounts
- **PayoutItem** links commissions to payouts (many-to-many)
- No breakdown by type (own vs override)
- Report shows total only

#### 5. System Settings (Current)
```php
// SystemSettingController only manages:
- commission_default_rate (%)
- partner_default_commission_rate (%)
- referral_code_prefix

// Validation: numeric, 0-100 (percentage only)
```

#### 6. UI Components (Current)
- **Admin/SystemSettings.vue**: Edit global commission rates
- **Admin/CommissionsList.vue**: View commissions grouped by month/agent
- **Admin/CommissionDetail.vue**: View individual agent commissions
- **Admin/PayoutsList.vue / PayoutCreate.vue**: Manage payouts

---

## Part 2: NEW REQUIREMENTS

### New Commission Structure

Three agent roles with hierarchical commission earning:

#### Role 1: Business Partner
- **Own Sales Commission**: Earns from own customer sales
- **Agent Leader Override Commission**: Earns from all managed Agent Leaders
- **Agent Override Commission**: Earns from all managed Agents (direct or via Agent Leaders)

#### Role 2: Agent Leader
- **Own Sales Commission**: Earns from own customer sales
- **Agent Override Commission**: Earns from all managed Agents

#### Role 3: Agent (Existing)
- **Own Sales Commission**: Own sales only
- No override capability

### Hierarchical Structure Example
```
Business Partner (BP1)
├── Agent Leader (AL1) - managed by BP1
│   ├── Agent (A1) - managed by AL1
│   └── Agent (A2) - managed by AL1
├── Agent (A3) - managed directly by BP1
└── Agent Leader (AL2) - managed by BP1
    └── Agent (A4) - managed by AL2
```

**Commission Flow**: When Agent A1 makes a sale:
1. **A1 earns**: Own sales commission
2. **AL1 earns**: Override from A1's sale
3. **BP1 earns**: Override from A1's sale (via AL1's chain)

---

## Part 3: WHAT CAN BE PATCHED vs. NEW IMPLEMENTATION

### A. PATCHES (Minimal Changes to Existing Code)

#### 1. Agent Model - Add Agent Role Field
**File**: `app/Models/Agent.php`
```php
// PATCH: Add role tracking to Agent model
protected $fillable = [
    // ... existing fields ...
    'agent_role', // NEW: enum('agent', 'agent_leader', 'business_partner')
    'parent_agent_id', // NEW: FK to parent (Agent Leader or Business Partner managing this agent)
];

// NEW: Add relationship for hierarchy
public function parentAgent() { return $this->belongsTo(Agent::class, 'parent_agent_id'); }
public function subordinateAgents() { return $this->hasMany(Agent::class, 'parent_agent_id'); }
```

**Migration**:
```php
Schema::table('agents', function (Blueprint $table) {
    $table->enum('agent_role', ['agent', 'agent_leader', 'business_partner'])
        ->default('agent')
        ->after('status');
    $table->foreignId('parent_agent_id')
        ->nullable()
        ->after('agent_role')
        ->constrained('agents');
});
```

#### 2. Commission Model - Add Commission Type Field
**File**: `app/Models/Commission.php`
```php
// PATCH: Track commission type (own_sales vs override)
protected $fillable = [
    // ... existing fields ...
    'commission_type', // NEW: enum('own_sales', 'override')
    'commission_category', // NEW: enum('business_partner', 'agent_leader', 'agent') - for BP's dual overrides
];
```

**Migration**:
```php
Schema::table('commissions', function (Blueprint $table) {
    $table->enum('commission_type', ['own_sales', 'override'])
        ->default('own_sales')
        ->after('commission_source');
    $table->enum('commission_category', ['business_partner', 'agent_leader', 'agent'])
        ->nullable()
        ->after('commission_type');
    $table->foreignId('earning_agent_id')
        ->nullable()
        ->after('agent_id')
        ->comment('The agent earning override commission (different from agent_id which is the sales agent)');
});
```

**Explanation**: 
- `agent_id` = who made the sale (gets own_sales commission)
- `earning_agent_id` = who earns override commission (NEW)
- `commission_type` = own_sales vs override
- `commission_category` = which role earned it

#### 3. SystemSetting Model - Extend Configuration
**File**: `app/Models/SystemSetting.php`
```php
protected $fillable = [
    // ... existing fields ...
    'agent_commission_rate',
    'agent_commission_type', // NEW: 'percentage' or 'fixed_amount'
    'agent_leader_override_rate',
    'agent_leader_override_type', // NEW: 'percentage' or 'fixed_amount'
    'business_partner_own_sales_rate',
    'business_partner_own_sales_type', // NEW: 'percentage' or 'fixed_amount'
    'business_partner_agent_leader_override_rate',
    'business_partner_agent_leader_override_type', // NEW
    'business_partner_agent_override_rate',
    'business_partner_agent_override_type', // NEW
];

protected function casts(): array {
    return [
        'agent_commission_rate' => 'decimal:2',
        'agent_leader_override_rate' => 'decimal:2',
        'business_partner_own_sales_rate' => 'decimal:2',
        'business_partner_agent_leader_override_rate' => 'decimal:2',
        'business_partner_agent_override_rate' => 'decimal:2',
    ];
}
```

**Migration**:
```php
Schema::table('system_settings', function (Blueprint $table) {
    // Remove or deprecate old fields
    $table->dropColumn(['commission_default_rate', 'partner_default_commission_rate']);
    
    // Add new structure
    $table->decimal('agent_commission_rate', 5, 2)->default(10.00);
    $table->enum('agent_commission_type', ['percentage', 'fixed_amount'])->default('percentage');
    
    $table->decimal('agent_leader_override_rate', 5, 2)->default(5.00);
    $table->enum('agent_leader_override_type', ['percentage', 'fixed_amount'])->default('percentage');
    
    $table->decimal('business_partner_own_sales_rate', 5, 2)->default(10.00);
    $table->enum('business_partner_own_sales_type', ['percentage', 'fixed_amount'])->default('percentage');
    
    $table->decimal('business_partner_agent_leader_override_rate', 5, 2)->default(5.00);
    $table->enum('business_partner_agent_leader_override_type', ['percentage', 'fixed_amount'])->default('percentage');
    
    $table->decimal('business_partner_agent_override_rate', 5, 2)->default(2.00);
    $table->enum('business_partner_agent_override_type', ['percentage', 'fixed_amount'])->default('percentage');
});
```

---

### B. NEW IMPLEMENTATIONS (Building Blocks)

#### 1. Commission Calculator Service (NEW)
**File**: `app/Services/CommissionCalculator.php`

Encapsulates all commission calculation logic, supports percentage and fixed amounts:

```php
namespace App\Services;

class CommissionCalculator {
    
    /**
     * Calculate commission based on type (percentage or fixed)
     */
    public function calculate(float $saleAmount, float $rate, string $type): float {
        if ($type === 'percentage') {
            return $saleAmount * ($rate / 100);
        } else if ($type === 'fixed_amount') {
            return $rate; // Fixed amount regardless of sale
        }
        return 0;
    }
    
    /**
     * Get applicable commission rate for agent
     * Considers: agent custom rate > referral code rate > system default
     */
    public function getApplicableRate(Agent $agent, ?ReferralCode $code = null): array {
        // Priority: AgentCommissionRate > ReferralCode > SystemSetting
        // Returns ['rate' => float, 'type' => 'percentage'|'fixed_amount', 'source' => string]
    }
    
    /**
     * Calculate override commissions for hierarchical chain
     */
    public function calculateHierarchyCommissions(Sale $sale): array {
        // Returns all commission records to be created:
        // - Own sales commission
        // - Agent Leader override (if exists)
        // - Business Partner override (if exists)
    }
}
```

#### 2. Commission Generator Service (NEW)
**File**: `app/Services/CommissionGenerator.php`

Creates Commission records based on sales and business rules:

```php
namespace App\Services;

class CommissionGenerator {
    
    /**
     * Generate commission records for a sale
     * Called when Sale::trackSale() completes
     */
    public function generateForSale(Sale $sale): array {
        // 1. Create own_sales commission for agent
        // 2. Find parent Agent Leader (if exists) and create override commission
        // 3. Find parent Business Partner and create override commission
        // Uses CommissionCalculator for rate calculations
    }
    
    /**
     * Update agent hierarchy (called when agent_role or parent_agent_id changes)
     */
    public function updateHierarchyCommissions(Agent $agent): void {
        // Recalculate future commission eligibility
        // TODO: Consider retroactive vs forward-only approach
    }
}
```

#### 3. Payout Report Generator (NEW)
**File**: `app/Services/PayoutReportGenerator.php`

Generates detailed payout reports with commission type breakdown:

```php
namespace App\Services;

class PayoutReportGenerator {
    
    /**
     * Generate detailed payout report for an agent
     */
    public function generateReport(Agent $agent, int $year, int $month): PayoutReport {
        // Returns breakdown:
        // - Own sales commissions (total, count)
        // - Override commissions from Agent sales (total, count)
        // - Override commissions from Agent Leader sales (total, count)
        // - Grand total
    }
    
    /**
     * Export as array for Vue components
     */
    public function toArray(): array {
        // Format for UI display in payout reports
    }
}
```

#### 4. Commission Configuration Manager (NEW)
**File**: `app/Services/CommissionConfig.php`

Manages commission configuration logic (applicable rates, validation):

```php
namespace App\Services;

class CommissionConfig {
    
    /**
     * Validate commission configuration
     */
    public function validate(array $config): array {
        // Returns validation errors or empty array
        // Validates: rate ranges, type consistency, hierarchy logic
    }
    
    /**
     * Get all applicable configurations for a specific agent
     */
    public function getAgentConfig(Agent $agent): array {
        // Returns applicable rates based on agent_role
    }
    
    /**
     * Get system defaults as fallback
     */
    public function getSystemDefaults(): array {
        // Current SystemSetting values formatted
    }
}
```

#### 5. Payment Hierarchy Service (NEW)
**File**: `app/Services/AgentHierarchy.php`

Manages agent-to-agent relationships and hierarchy traversal:

```php
namespace App\Services;

class AgentHierarchy {
    
    /**
     * Get direct manager (parent agent)
     */
    public function getDirectManager(Agent $agent): ?Agent {
        return $agent->parentAgent;
    }
    
    /**
     * Get all managers up the chain
     */
    public function getManagementChain(Agent $agent): Collection {
        // Returns [Agent, Agent Leader, Business Partner] chain
    }
    
    /**
     * Get all subordinates (Agents or Agent Leaders managed by this agent)
     */
    public function getSubordinates(Agent $agent, ?string $role = null): Collection {
        // Optionally filter by role
    }
    
    /**
     * Validate hierarchy change (prevent cycles, validate roles)
     */
    public function validateHierarchyChange(Agent $agent, ?Agent $newParent): bool {
        // Business logic validation
    }
}
```

---

## Part 4: SUGGESTED DATABASE SCHEMA

### New/Modified Tables

#### 1. agents table (MODIFIED)
```sql
ALTER TABLE agents ADD COLUMN agent_role ENUM('agent', 'agent_leader', 'business_partner') DEFAULT 'agent' AFTER status;
ALTER TABLE agents ADD COLUMN parent_agent_id BIGINT UNSIGNED NULLABLE AFTER agent_role;
ALTER TABLE agents ADD FOREIGN KEY (parent_agent_id) REFERENCES agents(id) ON DELETE SET NULL;

-- Indexes for hierarchy queries
CREATE INDEX idx_agents_parent ON agents(parent_agent_id);
CREATE INDEX idx_agents_role ON agents(agent_role);
```

#### 2. commissions table (MODIFIED)
```sql
ALTER TABLE commissions ADD COLUMN commission_type ENUM('own_sales', 'override') DEFAULT 'own_sales' AFTER commission_source;
ALTER TABLE commissions ADD COLUMN commission_category ENUM('business_partner', 'agent_leader', 'agent') NULLABLE AFTER commission_type;
ALTER TABLE commissions ADD COLUMN earning_agent_id BIGINT UNSIGNED NULLABLE AFTER agent_id;
ALTER TABLE commissions ADD FOREIGN KEY (earning_agent_id) REFERENCES agents(id) ON DELETE SET NULL;

-- Indexes for reporting
CREATE INDEX idx_commissions_earning_agent ON commissions(earning_agent_id);
CREATE INDEX idx_commissions_type ON commissions(commission_type);
CREATE INDEX idx_commissions_category ON commissions(commission_category);
```

#### 3. system_settings table (MODIFIED)
```sql
-- Keep for backward compatibility or deprecate old fields
ALTER TABLE system_settings DROP COLUMN commission_default_rate;
ALTER TABLE system_settings DROP COLUMN partner_default_commission_rate;

-- Agent commission settings
ALTER TABLE system_settings ADD COLUMN agent_commission_rate DECIMAL(5,2) DEFAULT 10.00;
ALTER TABLE system_settings ADD COLUMN agent_commission_type ENUM('percentage', 'fixed_amount') DEFAULT 'percentage';

-- Agent Leader override commission settings
ALTER TABLE system_settings ADD COLUMN agent_leader_override_rate DECIMAL(5,2) DEFAULT 5.00;
ALTER TABLE system_settings ADD COLUMN agent_leader_override_type ENUM('percentage', 'fixed_amount') DEFAULT 'percentage';

-- Business Partner own sales commission settings
ALTER TABLE system_settings ADD COLUMN business_partner_own_sales_rate DECIMAL(5,2) DEFAULT 10.00;
ALTER TABLE system_settings ADD COLUMN business_partner_own_sales_type ENUM('percentage', 'fixed_amount') DEFAULT 'percentage';

-- Business Partner override from Agent Leaders
ALTER TABLE system_settings ADD COLUMN business_partner_agent_leader_override_rate DECIMAL(5,2) DEFAULT 5.00;
ALTER TABLE system_settings ADD COLUMN business_partner_agent_leader_override_type ENUM('percentage', 'fixed_amount') DEFAULT 'percentage';

-- Business Partner override from Agents
ALTER TABLE system_settings ADD COLUMN business_partner_agent_override_rate DECIMAL(5,2) DEFAULT 2.00;
ALTER TABLE system_settings ADD COLUMN business_partner_agent_override_type ENUM('percentage', 'fixed_amount') DEFAULT 'percentage';
```

#### 4. commission_audit_log table (NEW - OPTIONAL)
For tracking commission calculation history and auditing:
```sql
CREATE TABLE commission_audit_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    agent_id BIGINT UNSIGNED NOT NULL,
    sale_id BIGINT UNSIGNED NOT NULL,
    commission_id BIGINT UNSIGNED NULLABLE,
    calculated_amount DECIMAL(10,2),
    applied_rate DECIMAL(5,2),
    rate_type VARCHAR(50),
    source VARCHAR(50),
    hierarchy_level INT,
    created_at TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (agent_id) REFERENCES agents(id),
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (commission_id) REFERENCES commissions(id)
);
```

---

## Part 5: IMPLEMENTATION ROADMAP

### Phase 1: Foundation (Week 1-2)
- [ ] Migration: add `agent_role` + `parent_agent_id` to agents
- [ ] Migration: add `commission_type`, `commission_category`, `earning_agent_id` to commissions
- [ ] Migration: update system_settings with new commission structure
- [ ] Update Agent + Commission models: new relationships + fillable fields
- [ ] Create `CommissionCalculator` service
- [ ] Create `AgentHierarchy` service
- [ ] Unit tests for both services

### Phase 2: Business Logic (Week 2-3)
- [ ] Create `CommissionGenerator` service (sale → commission creation)
- [ ] Update `Sale::trackSale()` to call `CommissionGenerator`
- [ ] Create `CommissionConfig` service for validation + defaults
- [ ] Create `PayoutReportGenerator` service
- [ ] Update `Commission` model relationships for `earning_agent_id`
- [ ] Integration tests for commission calculation

### Phase 3: Admin Interface (Week 3-4)
- [ ] Update `SystemSettingController::update()` for new commission fields
- [ ] New form validation for flexible rate types
- [ ] Update `SystemSettingsUpdate.vue` with new commission settings UI
- [ ] New sections: agent commission, AL override, BP own sales, BP override (2 fields)
- [ ] Add help text per setting

### Phase 4: Agent Management (Week 4)
- [ ] Update `AgentController::store()` + `update()` for `agent_role` + `parent_agent_id`
- [ ] Form fields for role + parent selection in agent create/edit
- [ ] Validation via `AgentHierarchy::validateHierarchyChange()`
- [ ] Update `AgentUpdate.vue` + `AgentCreate.vue` with role + parent selection
- [ ] Optional: hierarchy tree view

### Phase 5: Reporting (Week 5)
- [ ] Update `CommissionController::detail()` to use `PayoutReportGenerator`
- [ ] Update `CommissionDetail.vue`: show breakdown (own sales, override from agents, override from AL, grand totals)
- [ ] Update `PayoutCreate.vue`: detailed breakdown by commission type
- [ ] Add commission type filter to `CommissionsList.vue`

### Phase 6: Testing & Polish (Week 6)
- [ ] End-to-end tests for all role combinations
- [ ] Data migration: existing agents → `role='agent'`, `parent_agent_id=NULL`
- [ ] Data migration: existing commissions → `commission_type='own_sales'`
- [ ] Performance testing for hierarchy queries
- [ ] Documentation update

---

## Part 6: DEVELOPER HANDOVER GUIDE

### Code Organization Philosophy

Service-oriented architecture, clear separation:

1. **Models** (`app/Models/`): Data structure + relationships only
2. **Services** (`app/Services/`): Business logic, calculations, validation
3. **Controllers** (`app/Http/Controllers/`): Request handling + Inertia responses
4. **Migrations** (`database/migrations/`): Schema changes
5. **Tests** (`tests/`): Unit + feature coverage

### Key Development Points

#### 1. Commission Calculation Flow
```
User creates a Sale via API
    ↓
Sale::trackSale() creates Sale record
    ↓
CommissionGenerator::generateForSale() called
    ↓
CommissionCalculator::calculateHierarchyCommissions() returns array of commission records:
    - Own sales commission for Agent
    - Override commission for Agent Leader (if exists)
    - Override commissions for Business Partner (if exists)
    ↓
Create all Commission records in transaction
    ↓
Sale complete, commissions ready for payout
```

#### 2. Rate Resolution Priority
```
1. Check AgentCommissionRate (individual custom rate) → USE if exists
2. Check ReferralCode (code-specific rate) → USE if exists
3. Check SystemSetting (role-based default) → USE as fallback
```

#### 3. Override Commission Logic
```
When Agent A1 makes a sale:

// Own commission
Commission.create({
    agent_id: A1.id,
    earning_agent_id: A1.id,
    commission_type: 'own_sales',
    amount: calculate(saleAmount, agentRate, agentType)
})

// Agent Leader override (if A1 has parent Agent Leader)
Commission.create({
    agent_id: A1.id,  // Sale made by A1
    earning_agent_id: AL1.id,  // But AL1 earns
    commission_type: 'override',
    commission_category: 'agent_leader',
    amount: calculate(saleAmount, agentLeaderRate, agentLeaderType)
})

// Business Partner override (if BP exists in chain)
Commission.create({
    agent_id: A1.id,
    earning_agent_id: BP1.id,
    commission_type: 'override',
    commission_category: 'agent',  // Earning from Agent's sale
    amount: calculate(saleAmount, businessPartnerAgentRate, businessPartnerAgentType)
})
```

#### 4. Testing Strategy

**Unit Tests** (`tests/Unit/Services/`):
- `CommissionCalculatorTest`: Percentage vs fixed amount calculations
- `AgentHierarchyTest`: Chain traversal, validation
- `CommissionConfigTest`: Configuration validation

**Feature Tests** (`tests/Feature/`):
- `CommissionCalculationTest`: Full flow with multiple roles
- `PayoutReportTest`: Report generation accuracy
- `SystemSettingTest`: Admin update handling

**Example Test Case**:
```php
function test_agent_leader_earns_override_commission_from_agent_sale() {
    $bp = Agent::factory()->create(['agent_role' => 'business_partner']);
    $al = Agent::factory()->create([
        'agent_role' => 'agent_leader',
        'parent_agent_id' => $bp->id
    ]);
    $agent = Agent::factory()->create([
        'agent_role' => 'agent',
        'parent_agent_id' => $al->id
    ]);
    
    $sale = Sale::factory()->create([
        'agent_id' => $agent->id,
        'amount' => 1000.00
    ]);
    
    // Should create 3 commissions
    $this->assertCount(3, Commission::where('sale_id', $sale->id)->get());
    
    // Agent Leader should have override commission
    $alCommission = Commission::where([
        ['sale_id', $sale->id],
        ['earning_agent_id', $al->id],
        ['commission_type', 'override']
    ])->first();
    
    $this->assertEquals(50.00, $alCommission->amount); // 5% of 1000
}
```

#### 5. Migration Path for Existing Data

**Prepare Migration Script**:
```php
// database/migrations/2026_04_28_add_hierarchy_to_existing_agents.php

public function up() {
    // Phase 1: Schema changes (already done)
    
    // Phase 2: Data migration
    DB::transaction(function () {
        // Set all existing agents to 'agent' role
        Agent::query()->update(['agent_role' => 'agent']);
        
        // Set all existing commissions to 'own_sales' type
        Commission::query()->update([
            'commission_type' => 'own_sales',
            'earning_agent_id' => DB::raw('agent_id')
        ]);
        
        // Create default system settings if not exists
        SystemSetting::updateOrCreate(
            [],
            [
                'agent_commission_rate' => 10.00,
                'agent_commission_type' => 'percentage',
                'agent_leader_override_rate' => 5.00,
                'agent_leader_override_type' => 'percentage',
                // ... etc
            ]
        );
    });
}
```

#### 6. Performance Considerations

**Query Optimization**:
- Index `agents(agent_role)` for role filtering
- Index `commissions(earning_agent_id, commission_type)` for payout reports
- Index `agents(parent_agent_id)` for hierarchy queries

**N+1 Query Prevention**:
```php
// Good: Use eager loading
Commission::where('earning_agent_id', $agentId)
    ->with('agent', 'sale')
    ->get();

// Bad: Avoid
$commissions = Commission::where('earning_agent_id', $agentId)->get();
foreach ($commissions as $commission) {
    $commission->agent; // N+1 query!
}
```

**Caching**:
- Cache `SystemSetting::first()` with 1-hour TTL
- Clear cache on system settings update
```php
public function update(Request $request) {
    // ... validation and update ...
    Cache::forget('system_settings');
    // ...
}
```

#### 7. Common Pitfalls

1. **Forgetting to set `earning_agent_id`**: Set explicitly even if same as `agent_id`
2. **Not validating hierarchy before saving**: Use `AgentHierarchy::validateHierarchyChange()`
3. **Mixing percentage and fixed amounts**: Always check `*_type` field before calculation
4. **Not handling NULL `parent_agent_id`**: Top-of-hierarchy agents have NULL parent
5. **Forgetting to update tests**: Schema changes need test updates

---

## Part 7: FILE CHECKLIST FOR IMPLEMENTATION

### New Files to Create
- [ ] `app/Services/CommissionCalculator.php`
- [ ] `app/Services/CommissionGenerator.php`
- [ ] `app/Services/PayoutReportGenerator.php`
- [ ] `app/Services/CommissionConfig.php`
- [ ] `app/Services/AgentHierarchy.php`
- [ ] `tests/Unit/Services/CommissionCalculatorTest.php`
- [ ] `tests/Unit/Services/AgentHierarchyTest.php`
- [ ] `tests/Feature/CommissionCalculationTest.php`

### Files to Modify
- [ ] `app/Models/Agent.php` - role field + hierarchy relationships
- [ ] `app/Models/Commission.php` - `commission_type` + `earning_agent_id`
- [ ] `app/Models/SystemSetting.php` - new commission config fields
- [ ] `app/Models/Sale.php` - update `trackSale()` to use `CommissionGenerator`
- [ ] `app/Http/Controllers/Admin/SystemSettingController.php` - new fields
- [ ] `app/Http/Controllers/Admin/AgentController.php` - role + `parent_agent_id`
- [ ] `resources/js/Pages/Admin/SystemSettingsUpdate.vue` - new commission UI
- [ ] `resources/js/Pages/Admin/CommissionDetail.vue` - breakdown by type
- [ ] `database/migrations/` - multiple migration files

### Configuration Files
- [ ] `.env` - no changes needed if using defaults
- [ ] `config/` - consider `config/commission.php` for strategy pattern

---

## Part 8: SUCCESS CRITERIA & TESTING CHECKLIST

### Functional Testing
- [ ] Admin can set different commission rates per role
- [ ] Admin can toggle each commission type (percentage vs fixed)
- [ ] Agents register + assigned role (default `agent`)
- [ ] Agent Leader assigned with parent Business Partner
- [ ] Agent sale → correct commissions generated for all hierarchy levels
- [ ] Override commissions only created if applicable parent exists
- [ ] Payout report shows separate lines: own_sales vs override
- [ ] Fixed amount commissions calculated correctly regardless of sale amount
- [ ] Percentage commissions calculated correctly

### Data Integrity Testing
- [ ] Agent cannot have parent of lower role
- [ ] Hierarchy cannot form cycles
- [ ] Existing agents migrated to `agent` role correctly
- [ ] Existing commissions marked `own_sales` correctly

### Performance Testing
- [ ] Hierarchy query for 1000-level chain < 500ms
- [ ] Commission generation for sale with 5-level hierarchy < 100ms
- [ ] Payout report for agent with 500+ commissions < 1s

### UI Testing
- [ ] System Settings shows all new commission fields
- [ ] Form validation blocks invalid amounts
- [ ] Agent create/edit: role selector
- [ ] Agent create/edit: parent selector (applicable roles only)
- [ ] Commission detail: breakdown with correct calculations
- [ ] Payout report: separate sections per commission type

---

## Part 9: BACKWARD COMPATIBILITY NOTES

### Existing Data
- All existing agents → `agent_role = 'agent'`
- All existing commissions → `commission_type = 'own_sales'`
- Existing payout history preserved

### API Changes
- `Sale::trackSale()` works same way — now generates multiple commissions
- `Commission` model has extra fields but queries remain backward compatible

### Deprecation Path
- Old `commission_default_rate` + `partner_default_commission_rate` can be deprecated
- Migration note for future field removal
- Suggest data export before removal

---

## Part 10: OPEN QUESTIONS FOR CLARIFICATION

Before implementation, confirm:

1. **Hierarchy Management**:
   - Can agents change parent/role? If yes, should existing commissions recalculate?
   - Can Business Partner manage both Agents and Agent Leaders directly?

2. **Override Commission Rules**:
   - Agent moves from one Agent Leader to another — do past commissions follow?
   - Can Business Partner override rates vary per Agent Leader (dynamic override)?

3. **Fixed Amount Commission**:
   - Per sale or per month/period?
   - Min/max on percentage commissions?

4. **Payout Processing**:
   - Payout include only pending, or allow selection by type?
   - Approval workflow for override commissions?

5. **Reporting**:
   - Historical reports showing rate changes over time?
   - Commission audit trail with rate changes + recalculations?

---

## Part 11: FUTURE ENHANCEMENTS (Out of Scope)

1. **Commission Tiers**: Rate changes based on total volume
2. **Time-Based Calculations**: Different rates per quarter/season
3. **Bonus Structures**: Additional commission for hitting targets
4. **Commission Chargeback**: Reverse/adjust paid commissions
5. **Batch Import**: Bulk hierarchy updates via CSV
6. **Advanced Reporting**: Drill-down analytics, trend analysis
7. **Webhook Notifications**: Alert on commission thresholds

---

## CONCLUSION

Flexible, scalable foundation for hierarchical commission management. Service-oriented architecture enables easy testing + future modifications without breaking existing code. Two-phase commission generation (own_sales + override) supports complex business rules while remaining mathematically simple and auditable.

**Ready for developer assignment.** Reach out with clarifications on open questions.

---

## Part 12: Fee Management System (CRD April 2026)

### 12.1 Overview

Configurable entry + renewal fees per role. Admin sets all values from backend. System applies fees on registration (entry) and renewal. No hardcoded fees.

### 12.2 Default Fee Values

| Role | Entry Fee (RM) | Renewal Fee (RM) | Renewal Toggle |
|---|---|---|---|
| Business Partner | 3,000 | 1,000 | Always enabled |
| Leader | 100 | 100 | Admin-configurable |
| Agent | 100 | 100 | Admin-configurable |

### 12.3 Database Fields (system_settings)

```php
// Migration: add_fee_config_and_role_names_to_system_settings_table
$table->decimal('entry_fee_business_partner', 10, 2)->default(3000.00)->after('partner_default_commission_rate');
$table->decimal('renewal_fee_business_partner', 10, 2)->default(1000.00)->after('entry_fee_business_partner');
$table->decimal('entry_fee_leader', 10, 2)->default(100.00)->after('renewal_fee_business_partner');
$table->decimal('renewal_fee_leader', 10, 2)->default(100.00)->after('entry_fee_leader');
$table->boolean('renewal_fee_leader_enabled')->default(true)->after('renewal_fee_leader');
$table->decimal('entry_fee_agent', 10, 2)->default(100.00)->after('renewal_fee_leader_enabled');
$table->decimal('renewal_fee_agent', 10, 2)->default(100.00)->after('entry_fee_agent');
$table->boolean('renewal_fee_agent_enabled')->default(true)->after('renewal_fee_agent');
```

### 12.4 Fee Application Logic

- On registration: record `entry_fee_{role}` event based on `agent_role`
- On renewal: record `renewal_fee_{role}` event if `renewal_fee_{role}_enabled` is true
- Fee payment state tracked on agents table via `fee_payment_status` (pending/paid/overdue/waived)

### 12.5 Admin UI

New "Fee Configuration" section on System Settings: one row per role — entry fee input, renewal fee input, renewal enabled toggle.

---

## Part 13: Role Name Editability (CRD April 2026)

Admin can change display labels for Agent, Leader, Business Partner throughout system. Useful when client rebrands role names without code changes.

### Database Fields (system_settings)

```php
$table->string('role_name_agent', 100)->default('Agent')->after('renewal_fee_agent_enabled');
$table->string('role_name_leader', 100)->default('Leader')->after('role_name_agent');
$table->string('role_name_business_partner', 100)->default('Business Partner')->after('role_name_leader');
```

### Implementation Note

Frontend must read role names from `$page.props.systemSettings` (passed via Inertia shared data in `HandleInertiaRequests::share()`) — not hardcoded strings like "Agent" or "Leader".

---

## Part 14: Renewal & Expiry Lifecycle (CRD April 2026)

Track membership lifecycle per agent: renewal reminders, expiry blocking, fee enforcement.

### 14.1 Database Fields (agents table)

```php
// Migration: add_expiry_and_fee_status_to_agents_table
$table->date('registered_at')->nullable()->after('status');    // set on approval
$table->date('expires_at')->nullable()->after('registered_at'); // registered_at + 1 year
$table->date('renewal_due_at')->nullable()->after('expires_at'); // expires_at - 30 days
$table->enum('fee_payment_status', ['pending', 'paid', 'overdue', 'waived'])->default('pending')->after('renewal_due_at');
```

### 14.2 Status Enum Extension (agents.status)

Extend existing enum to include `expired`:

```sql
ALTER TABLE agents MODIFY status ENUM('active','inactive','suspended','banned','expired') DEFAULT 'active';
```

### 14.3 Lifecycle Rules

- Agent approved → `registered_at = today`, `expires_at = today + 365 days`, `renewal_due_at = expires_at - 30 days`
- Agent renewed → update `expires_at`, `renewal_due_at`, set `fee_payment_status = paid`
- Daily scheduled job → if `expires_at < today` and `fee_payment_status != paid` → set `status = expired`

---

## Part 15: Expanded Notifications (CRD April 2026)

New Mailable classes in `app/Mail/`:

| Event | Mailable Class | Trigger |
|---|---|---|
| Renewal reminder | `AgentRenewalReminderNotification` | X days before `renewal_due_at` (configurable in SystemSetting) |
| Expiry alert | `AgentExpiryAlertNotification` | Day `expires_at` reached if not renewed |
| Commission earned | `CommissionEarnedNotification` | After commission record created |
| Commission paid | `CommissionPaidNotification` | After payout marked paid |

---

## Part 16: Refund & Cancellation Handling (CRD April 2026)

### 16.1 Commission Reversal on Refund

When Sale refunded — do NOT update/delete original commission. Create new reversal Commission row:

- `amount` = negative of original
- `is_reversal` = true
- `original_commission_id` = FK to original
- `status` = `cancelled`

Full audit trail preserved.

### 16.2 Payout Block

PayoutController must validate before processing: no related Commission records with `status = 'cancelled'`.

### 16.3 New Fields on commissions Table

```php
// Migration: add_reversal_fields_to_commissions_table
$table->boolean('is_reversal')->default(false)->after('paid_by');
$table->foreignId('original_commission_id')->nullable()->constrained('commissions')->nullOnDelete()->after('is_reversal');
```

### 16.4 Commission Status Enum Extension

Extend commissions.status enum to add `cancelled`:

```sql
ALTER TABLE commissions MODIFY status ENUM('pending','approved','paid','cancelled') DEFAULT 'pending';
```

---

## Part 17: Flexible Commission Calculation Type (CRD April 2026)

### 17.1 Overview

Supports percentage-based and fixed-amount as configurable either/or. Both values stored; UI exposes as selector. When `commission_calc_type = 'percentage'`, fixed amount = 0. When `commission_calc_type = 'fixed'`, rate = 0.

**Naming note**: `commission_calc_type` = calculation method (percentage vs fixed). Distinct from `commission_category` = hierarchy role (own_sales, override_agent, override_agent_leader). See Decision 14.

### 17.2 Database Fields (system_settings)

```php
// Migration: add_commission_calc_type_to_system_settings_table
$table->enum('commission_calc_type', ['percentage', 'fixed'])->default('percentage')->after('commission_default_rate');
$table->decimal('commission_fixed_amount', 10, 2)->nullable()->after('commission_calc_type');
$table->enum('partner_commission_calc_type', ['percentage', 'fixed'])->default('percentage')->after('partner_default_commission_rate');
$table->decimal('partner_commission_fixed_amount', 10, 2)->nullable()->after('partner_commission_calc_type');
```

### 17.3 Database Fields (commissions table)

```php
// Migration: add_commission_calc_type_to_commissions_table
$table->enum('commission_calc_type', ['percentage', 'fixed'])->default('percentage')->after('commission_source');
$table->decimal('commission_fixed_amount', 10, 2)->nullable()->after('commission_calc_type');
$table->decimal('source_sale_amount', 10, 2)->nullable()->after('commission_fixed_amount');
$table->string('beneficiary_role', 50)->nullable()->after('source_sale_amount');
```

### 17.4 Admin UI

Commission section on System Settings: type dropdown (Percentage % / Fixed RM), dynamic input below. Percentage selected → show rate input. Fixed selected → show RM input. Repeat for partner commission rate.

---

## Part 18: Bug Fix — Company Representative IC File (CRD April 2026)

**Issue**: When `profile_type = 'company'`, form only exposes `company_reg_file` (SSM). Company representative IC file missing — no field, no upload, no download route.

### Fix — New Migration

```php
// Migration: add_company_representative_id_file_to_agents_table
$table->string('company_representative_id_file')->nullable()->after('company_reg_file');
```

### Fix — Files to Update

- `AgentProfileController` → add upload logic for `company_representative_id_file` (mirror `individual_id_file` pattern); add download route handler
- `Admin/AgentController` → same for admin-side view/edit
- `AgentUpdate.vue` → file upload input visible only when `profile_type === 'company'`
- `AgentView.vue` → download link visible only when `profile_type === 'company'`
- `Profile/Edit.vue` (agent side) → same as `AgentUpdate.vue`

---

## Part 19: Registration Wizard Rebuild — 6 Steps (GAP-01, GAP-02, GAP-03, GAP-09, GAP-10)

**Decision date**: 2026-05-01 | **Status**: Requirements definition

### 19.1 Overview

Wizard rebuilt from 5 to 6 steps:

| Step | Label | Key Change |
|------|-------|------------|
| 1 | Referral ID | No change |
| 2 | Package | No change |
| 3 | Your Details + Credentials | **NEW**: password + confirm password fields |
| 4 | Email Verification | **NEW step**: 6-digit code |
| 5 | T&C + Fee Payment | **MODIFIED**: T&C checkbox + [Skip Payment] |
| 6 | Confirmation | Minor text update; [Log In] CTA added |

### 19.2 Database Schema Additions

```php
// agents table (Migration 2026_05_01_000014)
$table->timestamp('tc_accepted_at')->nullable();        // when T&C accepted
$table->timestamp('first_login_at')->nullable();        // null = never logged in
$table->string('suspension_reason')->nullable();        // admin-entered reason
$table->text('rejection_reason')->nullable();           // admin-entered reason

// New table: registration_verifications (Migration 2026_05_01_000015)
Schema::create('registration_verifications', function (Blueprint $table) {
    $table->id();
    $table->string('email')->index();
    $table->string('code', 6);
    $table->timestamp('expires_at');
    $table->unsignedTinyInteger('attempts')->default(0);
    $table->boolean('verified')->default(false);
    $table->timestamps();
});
```

### 19.3 Step 3 — Credentials

Append to existing profile/details step:

| Field | Validation |
|-------|-----------|
| Login Email | required, email, unique:users,email (with exception for self) |
| Password | required, min:8, confirmed |
| Confirm Password | required, same:password |

Email pre-check before wizard displays (POST `/get-started/check-email`):
- Not in users table → `{status: 'new'}` — allow registration
- In users table with `password IS NOT NULL` → `{status: 'login'}` — redirect to login
- In users table with `password IS NULL` OR `email_verified_at IS NULL` → `{status: 'reset'}` — redirect to reset password

### 19.4 Step 4 — Email Verification

**Mailable**: `EmailVerificationCode` — subject "Your Penurwill registration code: {code}"

**Verification record lifecycle**:
1. Created when user clicks Next on Step 3
2. TTL: 15 min (`expires_at = now()->addMinutes(15)`)
3. Max 3 wrong attempts; on 3rd, `attempts` exhausted
4. On success: `verified = true`; proceed to create User + Agent records

**User + Agent auto-creation** (on email verify success):
```php
// Create User
$user = User::create([
    'name'              => $profileData['name'],
    'email'             => $email,
    'password'          => Hash::make($passwordFromSession),
    'email_verified_at' => now(),  // auto-verified since we just confirmed the email
]);

// Create Agent
$agent = Agent::create([
    ...$profileData,
    'status'         => 'pending',
    'fee_payment_status' => 'pending',
    'agent_role'     => $packageRole,
    'parent_agent_id' => $referralParentId,
]);

// Assign role
$user->assignRole('agent');

// Clear password from session — no longer needed
// Send AccountCreatedNotification email
Mail::to($user->email)->queue(new AccountCreatedNotification($user, $agent));
```

### 19.5 Step 5 — T&C + Payment

T&C checkbox required before any payment action:
- `tc_accepted_at` set on agent record on form submit
- All payment buttons + [Skip] `disabled` until checkbox checked (Vue computed)

**Skip Payment path**:
```php
// AgentRegistrationController::skipPayment()
$agent->tc_accepted_at = now();
$agent->save();
Auth::login($agent->user);
return redirect()->route('agent.dashboard');
```

Agent dashboard detects `fee_payment_status === 'pending'` → shows payment banner.

### 19.6 Reset Password Also Verifies Email

In `app/Http/Controllers/Auth/NewPasswordController.php` (or Jetstream equivalent), after password set:

```php
if (is_null($user->email_verified_at)) {
    $user->email_verified_at = now();
    $user->save();
}
```

---

## Part 20: Notification / Inbox System (GAP-06, GAP-11)

**Decision date**: 2026-05-01 | **Status**: Requirements definition

### 20.1 Overview

In-app notification inbox for all agent roles. Every significant system event creates an `AgentNotification` record. Email notifications continue in parallel.

### 20.2 Database Schema

```php
// Migration 2026_05_01_000016
Schema::create('agent_notifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
    $table->string('type', 100);           // e.g. 'payout_cancelled'
    $table->string('subject', 255);
    $table->text('body');
    $table->boolean('is_read')->default(false);
    $table->timestamp('read_at')->nullable();
    $table->string('related_model', 100)->nullable();
    $table->unsignedBigInteger('related_id')->nullable();
    $table->timestamps();
    $table->index(['agent_id', 'is_read']);
});
```

### 20.3 NotificationService

```php
namespace App\Services;

class NotificationService
{
    public function notify(
        Agent $agent,
        string $type,
        string $subject,
        string $body,
        ?string $relatedModel = null,
        ?int $relatedId = null
    ): AgentNotification {
        return AgentNotification::create([
            'agent_id'      => $agent->id,
            'type'          => $type,
            'subject'       => $subject,
            'body'          => $body,
            'related_model' => $relatedModel,
            'related_id'    => $relatedId,
        ]);
    }

    // Notify the system admin (Agent#1)
    public function notifyAdmin(string $type, string $subject, string $body, ...): AgentNotification

    // Notify all commission earners in a sale's chain
    public function notifyChain(Sale $sale, string $type, string $subject, string $body): void
}
```

### 20.4 Notification Types Reference

| Constant | Value | Used When |
|----------|-------|-----------|
| `TYPE_ACCOUNT_APPROVED` | `account_approved` | Admin approves agent |
| `TYPE_ACCOUNT_REJECTED` | `account_rejected` | Admin rejects application |
| `TYPE_ACCOUNT_CREATED` | `account_created` | User auto-created at Step 4 |
| `TYPE_PAYOUT_RECEIVED` | `payout_received` | Agent submits payout (notifies Admin#1) |
| `TYPE_PAYOUT_CANCELLED` | `payout_cancelled` | Admin cancels payout |
| `TYPE_PAYOUT_PAID` | `payout_paid` | Payout marked paid |
| `TYPE_NEW_TEAM_MEMBER` | `new_team_member` | New agent approved under parent |
| `TYPE_COMMISSION_REVERSED` | `commission_reversed` | Admin reverses sale |
| `TYPE_APPEAL_SUBMITTED` | `appeal_submitted` | Agent submits suspension appeal |
| `TYPE_RENEWAL_REMINDER` | `renewal_reminder` | Scheduler fires N days before expiry |
| `TYPE_APPROVAL_REQUESTED` | `approval_requested` | Rejected agent clicks Request Approval |

### 20.5 Payout Additions

```php
// Migration 2026_05_01_000017
$table->string('agent_note', 500)->nullable();   // agent's note at request time
$table->text('admin_note')->nullable();           // admin's note for cancel/reject
```

**Agent note**: Optional field on Request Payout screen. Max 500 chars. Displayed on admin payout detail.

**Admin note**: Required when admin cancels payout. Included in `TYPE_PAYOUT_CANCELLED` notification body.

### 20.6 Auto-Select All Commissions

Request Payout screen no longer allows manual selection. System auto-includes all eligible commissions (`status='pending'`, not in another payout, `is_reversal=false`).

**Minimum payout threshold** (see Part 21): Block submission if total < `min_payout_amount`.

---

## Part 21: Minimum Payout Threshold (GAP-12)

**Decision date**: 2026-05-01

### Schema

```php
// Migration 2026_05_01_000018 — system_settings
$table->decimal('min_payout_amount', 10, 2)->default(1.00);
```

### Seeder

```php
// SystemSettingsSeeder — add:
'min_payout_amount' => 1.00,
```

### Validation in RequestPayoutController

```php
$available = Commission::forEarner($agent->id)
    ->eligible()
    ->sum('amount');

if ($available < $settings->min_payout_amount) {
    return back()->withErrors(['amount' => "Minimum payout amount is RM {$settings->min_payout_amount}."])
}
```

---

## Part 22: Commission Reversal UI (GAP-04)

**Decision date**: 2026-05-01 (extends Part 16/17)

### 22.1 Admin UI additions

**`/admin/commission/detail` and `/admin/commissions/list`**:
- [Mark as Reversed] button per sale row (admin only)
- Button disabled if: sale already has active reversal, or `is_reversal=true`
- Confirmation modal shows payout impact warning (pending → deducted; paid → offset on next request)

**Flow**:
```
POST /admin/sales/{sale_id}/reverse
  → RefundService::reverseSale($sale, auth()->user())
  → Creates negative commission rows for entire earning chain
  → Calls NotificationService::notifyChain() for all earners
  → Activity-logged by RefundService
```

### 22.2 Agent-side display

- `/agent/commissions` — reversed rows: `Reversed` badge, negative RM in red
- `/agent/sales` — reversed sales: `Reversed` badge
- `/agent/sales/{id}` — reversal date + reason in warning card

### 22.3 Payout interaction

- Pending payout contains reversed commission → total updates automatically; `admin_note` on payout updated with reversal remark
- Paid payout → reversal commission appears in agent balance as negative; next payout request shows "⚠ Reversal applied: RM {amount} from Sale #{id} on {date} will be deducted"

---

## Part 23: Suspended Agent Experience (GAP-05)

**Decision date**: 2026-05-01

### 23.1 Access rules

| Feature | Suspended agent access |
|---------|----------------------|
| Login | ✓ Allowed |
| Dashboard | ✓ Allowed (shows suspension banner) |
| View sales / commissions / payouts | ✓ Read-only |
| Request payout | ✗ Blocked (button hidden; route returns 403) |
| Edit profile | ✓ Allowed |
| Submit appeal | ✓ Allowed |

### 23.2 Suspension banner

Shown on every agent page when `status === 'suspended'`. Contains:
- Reason (if set in `suspension_reason`)
- [Appeal Suspension] button → opens modal
- If suspended due to expired membership: additional [Renew Now →] button

### 23.3 Appeal flow

```
Agent: [Appeal Suspension]
  → Modal: optional message textarea
  → POST /agent/appeal-suspension
    → SuspensionAppealNotification email to Admin#1
    → NotificationService::notifyAdmin('appeal_submitted', ...)
    → NotificationService::notify($agent, 'appeal_submitted', "Your appeal has been submitted", ...)
    → ActivityLog: "Appeal submitted by {agent}"
```

---

## Part 24: First-Login Onboarding (GAP-13)

**Decision date**: 2026-05-01

### 24.1 Detection

After login (Fortify/Jetstream post-login redirect), middleware checks:

```php
if ($user->hasRole('agent')) {
    $agent = $user->agent;
    if ($agent && is_null($agent->first_login_at)) {
        return redirect()->route('get-started-guide');
    }
}
```

### 24.2 Slide content

Each slide: full-screen, centred illustration, H1 headline, short paragraph, [Next]. Final slide: [Go to My Dashboard].

**Slide count**: 6 for base agent; 6 for leader (slides 3–5 swapped); 6 for BP (slides 3–5 swapped). Same total — content swapped.

### 24.3 Completion

```php
// OnboardingController::complete()
$agent->first_login_at = now();
$agent->save();
return redirect()->route('agent.dashboard');
```

### 24.4 Admin-created agents

Admin creates agent → User created with `email_verified_at = now()` + password-reset token generated. `AccountCreatedByAdminNotification` email includes: "Click here to set your password: {reset_url}". On first login after password-set → directed to onboarding guide.

---

## Part 25: Referral Code Stats Page (GAP-15)

**Decision date**: 2026-05-01

### 25.1 Screen: `/agent/referral`

Data sources:
- `agent_visits` table — filtered by `referral_code_id`
- `commissions` table — filtered by `earning_agent_id` + `commission_type = own_sales` and linked `sale.referral_code_id`

### 25.2 Summary stats (default: last 90 days)

| Metric | Query |
|--------|-------|
| Total Visits | `AgentVisit::where('referral_code_id', $code->id)->count()` |
| Unique Visitors | `COUNT(DISTINCT ip_address)` |
| Conversions | `Sale::whereHas('referralCode', ...)->count()` |
| Conversion Rate | `conversions / total_visits * 100` |
| Total Commission | `Commission::forEarner($agent->id)->sum('amount')` in date range |

### 25.3 Visits table

Paginated `AgentVisit` records: datetime, IP, converted (yes/no), sale amount, commission.

---

## Part 26: Error Pages (GAP-18)

**Decision date**: 2026-05-01

### 26.1 Implementation

Register in `app/Exceptions/Handler.php`:

```php
public function register(): void
{
    $this->renderable(function (NotFoundHttpException $e, Request $request) {
        if ($request->inertia()) {
            return Inertia::render('Errors/404')->toResponse($request)->setStatusCode(404);
        }
    });
    // Similarly for 403, 419, 500
}
```

### 26.2 Shared layout

All error pages use default AppLayout (or minimal unauthenticated layout). Include:
- Centred icon (emoji or SVG)
- Error code (muted, small)
- Headline
- Short explanation
- Back + Home buttons (or Login Again for 419)

---

## Part 27: Admin Status Override (GAP-13)

**Decision date**: 2026-05-01

Admin can change `agents.status` to any value from edit form. Status dropdown on `/admin/agents/{id}/update` must include all enum values: `active`, `inactive`, `suspended`, `banned`, `expired`, `pending`, `rejected`.

`suspension_reason` field appears when admin selects `suspended`. `rejection_reason` textarea appears when `rejected` selected. Both stored on agent record.

**Activity log**: Status changes always logged: "Status changed: {old} → {new} by {admin}. Reason: {reason if provided}"

**Agent notification**: Admin manually changes status → inbox notification created:
- Approved → `TYPE_ACCOUNT_APPROVED`
- Rejected → `TYPE_ACCOUNT_REJECTED` with reason
- Suspended → new `TYPE_ACCOUNT_SUSPENDED` with reason
- Unsuspended/reactivated → new `TYPE_ACCOUNT_REACTIVATED`