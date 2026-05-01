# Commission Enhancement — Implementation TODOS

**Status**: Decisions locked (see `DECISION_OUTCOMES.md`). This file is the actionable build list.
**Updated**: 2026-04-30 (CRD: Fee Management + Flexible Commission Configuration integrated)
**Scope**: All concrete changes (modify) and additions (new) needed to deliver the hierarchical commission system + fee management across DB, models, services, controllers, UI, seeders, and tests.

> Conventions:
> - **[M]** = modify existing | **[N]** = new file/artifact | **[D]** = delete/deprecate
> - File paths are relative to repo root. All QNA items resolved (`TODOS_QNA.md`).

## Resolved QNA highlights (deltas from initial draft)

- **QNA-05** — BP CC email: use `company_email_address` AND user email; CC both if present, fall back to whichever is set.
- **QNA-09** — `AgentCommissionRate` becomes **multi-row** keyed by `(agent_id, kind)` where `kind ∈ {own_sales, override_agent, override_agent_leader}`. Each row carries `custom_percentage` + `custom_fixed_amount`.
- **QNA-10** — Add `skip_zero_commissions` boolean to `system_settings`. `CommissionGenerator` consults it: skip rows where pct=0 AND fixed=0 when on; persist with `amount=0` for audit when off.
- **QNA-16** — Add dedicated `fee_payments` table for full fee payment history per agent (entry/renewal events, amounts, timestamps, recorded_by).
- **QNA-17** — Add `renewal_reminder_days_before` integer to `system_settings`. Scheduled job uses this value, not a hardcoded 30 days.
- **QNA-18** — Refund trigger: admin "Mark as Refunded" UI now; extract logic into a service method so a future payment gateway webhook can reuse it.
- **QNA-19** — Store `commission_calc_type` as an explicit enum column (not derived from amounts).
- **QNA-20** — `AgentCommissionRate` also gets `commission_calc_type` column for per-agent type override.
- All other QNAs accepted suggestions (see `TODOS_QNA.md` for record).

---

## Phase 0 — Pre-flight Refactor (≈1 day)

Goal: extract commission logic out of `TrackingService` so it is replaceable, before adding hierarchy. No behavior change.

- [N] [app/Services/CommissionCalculator.php](app/Services/CommissionCalculator.php) — pure calculator: `calculate(saleAmount, rate, type)`, `getApplicableRate(Agent, ?ReferralCode)`. Supports `percentage` and `fixed_amount` from day one (called with `percentage` only in Phase 0).
- [N] [app/Services/CommissionGenerator.php](app/Services/CommissionGenerator.php) — wraps current "create one own_sales commission" logic. In Phase 0 it must produce **identical** output to today.
- [N] [app/Providers/CommissionServiceProvider.php](app/Providers/CommissionServiceProvider.php) — bind `CommissionCalculator`, `CommissionGenerator`, `AgentHierarchy` (singletons). Register in [bootstrap/providers.php](bootstrap/providers.php).
- [M] [app/Services/TrackingService.php:202-300](app/Services/TrackingService.php#L202-L300) — inject `CommissionGenerator` via constructor. Replace inline rate math + `Commission::create` with `$generator->generateForSale($sale)`. Keep the activity log call but log against the returned commission collection.
- [M] [app/Http/Controllers/Api/AgentTrackingController.php](app/Http/Controllers/Api/AgentTrackingController.php) — verify constructor injection works after provider registers.
- [N] [tests/Feature/Api/TrackingServiceCommissionParityTest.php](tests/Feature/Api/TrackingServiceCommissionParityTest.php) — golden test: post a sale, assert exactly **one** `own_sales` commission with the same `applied_rate` and `amount` as legacy logic.
- [N] [database/seeders/SystemUserSeeder.php](database/seeders/SystemUserSeeder.php) — guarantees `system@penurwill.com` exists. Wire into [DatabaseSeeder.php](database/seeders/DatabaseSeeder.php).

---

## Phase 1 — Schema + Models (≈3 days)

### Migrations [N]
Create in order so FKs resolve:

#### Hierarchy & Commission (original)

1. [N] `database/migrations/2026_04_29_000001_add_role_and_parent_to_agents_table.php`
   ```php
   $table->enum('agent_role', ['agent', 'agent_leader', 'business_partner'])->default('agent')->after('status');
   $table->foreignId('parent_agent_id')->nullable()->after('agent_role')->constrained('agents')->nullOnDelete();
   $table->index('agent_role');
   ```
2. [N] `database/migrations/2026_04_29_000002_extend_commissions_for_hierarchy.php`
   ```php
   $table->foreignId('earning_agent_id')->nullable()->after('agent_id')->constrained('agents')->nullOnDelete();
   $table->enum('commission_type', ['own_sales', 'override'])->default('own_sales')->after('commission_source');
   $table->enum('commission_category', ['business_partner', 'agent_leader', 'agent'])->nullable()->after('commission_type');
   $table->index(['earning_agent_id', 'commission_type']);
   $table->index('commission_category');
   ```
3. [N] `database/migrations/2026_04_29_000003_extend_payout_items_for_breakdown.php` (Decision 3 — denormalize)
   ```php
   $table->enum('commission_type', ['own_sales', 'override'])->nullable()->after('commission_id');
   $table->enum('commission_category', ['business_partner', 'agent_leader', 'agent'])->nullable()->after('commission_type');
   $table->index(['payout_id', 'commission_type']);
   ```
4. [N] `database/migrations/2026_04_29_000004_restructure_system_settings_for_commission_config.php`
   ```php
   $table->dropColumn(['commission_default_rate', 'partner_default_commission_rate']);
   foreach (['agent_own_sales','agent_leader_own_sales','agent_leader_override_agent',
             'business_partner_own_sales','business_partner_override_agent','business_partner_override_agent_leader'] as $key) {
       $table->decimal("{$key}_percentage", 5, 2)->default(0);
       $table->decimal("{$key}_fixed_amount", 10, 2)->default(0);
   }
   $table->boolean('skip_zero_commissions')->default(true);
   ```
5. [N] `database/migrations/2026_04_30_000005_restructure_agent_commission_rates.php` (Decision 6 + QNA-09 multi-row)
   ```php
   $table->enum('kind', ['own_sales', 'override_agent', 'override_agent_leader'])->default('own_sales')->after('agent_id');
   $table->renameColumn('custom_rate', 'custom_percentage');
   $table->decimal('custom_fixed_amount', 10, 2)->default(0)->after('custom_percentage');
   $table->enum('commission_calc_type', ['percentage', 'fixed'])->default('percentage')->after('custom_fixed_amount'); // QNA-20
   $table->unique(['agent_id', 'kind']);
   ```
6. [N] `database/migrations/2026_04_30_000006_drop_partner_tables.php` — **defer to PR2** (QNA-02)
   ```php
   Schema::table('agents', fn ($t) => $t->dropConstrainedForeignId('partner_id'));
   Schema::dropIfExists('partner_users');
   Schema::dropIfExists('partners');
   ```

#### CRD: Fee Management & Flexible Commission (Decision 13–17)

7. [N] `database/migrations/2026_04_30_000007_add_fee_config_and_role_names_to_system_settings_table.php`
   ```php
   // Entry & renewal fees per role (Decision 13)
   $table->decimal('entry_fee_business_partner', 10, 2)->default(3000.00)->after('skip_zero_commissions');
   $table->decimal('renewal_fee_business_partner', 10, 2)->default(1000.00)->after('entry_fee_business_partner');
   $table->decimal('entry_fee_leader', 10, 2)->default(100.00)->after('renewal_fee_business_partner');
   $table->decimal('renewal_fee_leader', 10, 2)->default(100.00)->after('entry_fee_leader');
   $table->boolean('renewal_fee_leader_enabled')->default(true)->after('renewal_fee_leader');
   $table->decimal('entry_fee_agent', 10, 2)->default(100.00)->after('renewal_fee_leader_enabled');
   $table->decimal('renewal_fee_agent', 10, 2)->default(100.00)->after('entry_fee_agent');
   $table->boolean('renewal_fee_agent_enabled')->default(true)->after('renewal_fee_agent');
   // Renewal notification timing (QNA-17)
   $table->integer('renewal_reminder_days_before')->default(30)->after('renewal_fee_agent_enabled');
   // Role name editability (Decision 15)
   $table->string('role_name_agent', 100)->default('Agent');
   $table->string('role_name_leader', 100)->default('Leader');
   $table->string('role_name_business_partner', 100)->default('Business Partner');
   ```
8. [N] `database/migrations/2026_04_30_000008_add_commission_calc_type_to_system_settings_table.php` (Decision 14)
   ```php
   $table->enum('commission_calc_type', ['percentage', 'fixed'])->default('percentage');
   $table->decimal('commission_fixed_amount', 10, 2)->nullable();
   $table->enum('partner_commission_calc_type', ['percentage', 'fixed'])->default('percentage');
   $table->decimal('partner_commission_fixed_amount', 10, 2)->nullable();
   ```
9. [N] `database/migrations/2026_04_30_000009_add_expiry_and_fee_status_to_agents_table.php` (Decision 16)
   ```php
   $table->date('registered_at')->nullable()->after('status');
   $table->date('expires_at')->nullable()->after('registered_at');
   $table->date('renewal_due_at')->nullable()->after('expires_at');
   $table->enum('fee_payment_status', ['pending', 'paid', 'overdue', 'waived'])->default('pending')->after('renewal_due_at');
   // Extend status enum to add 'expired'
   DB::statement("ALTER TABLE agents MODIFY status ENUM('active','inactive','suspended','banned','expired') DEFAULT 'active'");
   ```
10. [N] `database/migrations/2026_04_30_000010_add_commission_calc_type_to_commissions_table.php` (Decision 14)
    ```php
    $table->enum('commission_calc_type', ['percentage', 'fixed'])->default('percentage')->after('commission_source');
    $table->decimal('commission_fixed_amount', 10, 2)->nullable()->after('commission_calc_type');
    $table->decimal('source_sale_amount', 10, 2)->nullable()->after('commission_fixed_amount');
    $table->string('beneficiary_role', 50)->nullable()->after('source_sale_amount');
    // Extend status enum to add 'cancelled'
    DB::statement("ALTER TABLE commissions MODIFY status ENUM('pending','approved','paid','cancelled') DEFAULT 'pending'");
    ```
11. [N] `database/migrations/2026_04_30_000011_add_reversal_fields_to_commissions_table.php` (Decision 17)
    ```php
    $table->boolean('is_reversal')->default(false)->after('paid_by');
    $table->foreignId('original_commission_id')->nullable()->constrained('commissions')->nullOnDelete()->after('is_reversal');
    ```
12. [N] `database/migrations/2026_04_30_000012_create_fee_payments_table.php` (QNA-16)
    ```php
    Schema::create('fee_payments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
        $table->enum('fee_type', ['entry', 'renewal']);
        $table->string('role', 50);
        $table->decimal('amount', 10, 2);
        $table->timestamp('paid_at')->nullable();
        $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamps();
    });
    ```
13. [N] `database/migrations/2026_04_30_000013_add_company_representative_id_file_to_agents_table.php` (bug fix — Part 18)
    ```php
    $table->string('company_representative_id_file')->nullable()->after('company_reg_file');
    ```

### Models

- [M] [app/Models/Agent.php](app/Models/Agent.php)
  - Add `agent_role`, `parent_agent_id` to `$fillable`.
  - Add `registered_at`, `expires_at`, `renewal_due_at`, `fee_payment_status`, `company_representative_id_file` to `$fillable`.
  - Casts: `agent_role` → string, dates → `'date'`, `fee_payment_status` → string.
  - Relations: `parentAgent()`, `subordinates()`, `descendants()` (recursive scope), `earnedCommissions()` → `hasMany(Commission, 'earning_agent_id')`, `feePayments()` → `hasMany(FeePayment)`.
  - Scopes: `scopeRole($query, $role)`, `scopeTopLevel()`, `scopeExpiringSoon($days)`.
  - Helpers: `isLeader()`, `isBusinessPartner()`, `getRoleAttribute()` (Decision 5C fallback).
  - Remove `partner()` relation + `partner_id` from fillable once Phase 4 migration runs.
- [M] [app/Models/Commission.php](app/Models/Commission.php)
  - Add `earning_agent_id`, `commission_type`, `commission_category`, `commission_calc_type`, `commission_fixed_amount`, `source_sale_amount`, `beneficiary_role`, `is_reversal`, `original_commission_id` to fillable + casts.
  - Relations: `earningAgent()`, `originalCommission()`, `reversals()`.
  - Scopes: `ownSales()`, `overrides()`, `forEarner($agentId)`, `reversals()`.
  - Constants: `TYPE_OWN_SALES`, `TYPE_OVERRIDE`, `CAT_AGENT`, `CAT_AGENT_LEADER`, `CAT_BUSINESS_PARTNER`, `CALC_PERCENTAGE`, `CALC_FIXED`.
- [M] [app/Models/PayoutItem.php](app/Models/PayoutItem.php) — add `commission_type`, `commission_category` to fillable; scopes `ownSales()`, `overrides()`.
- [M] [app/Models/SystemSetting.php](app/Models/SystemSetting.php)
  - Replace fillable/casts with the full list: 12 rate fields + `skip_zero_commissions` + 8 fee fields + `renewal_reminder_days_before` + 3 role name fields + 4 calc_type fields.
  - Add typed accessor `getCommissionConfigAttribute()` returning a structured array used by services.
- [M] [app/Models/AgentCommissionRate.php](app/Models/AgentCommissionRate.php)
  - Add `kind`, `custom_fixed_amount`, `commission_calc_type`; rename `custom_rate` → `custom_percentage`.
  - Constants `KIND_OWN_SALES`, `KIND_OVERRIDE_AGENT`, `KIND_OVERRIDE_AGENT_LEADER`. Scope `forKind($kind)`.
- [N] [app/Models/FeePayment.php](app/Models/FeePayment.php) — fillable: `agent_id`, `fee_type`, `role`, `amount`, `paid_at`, `recorded_by`. Relations: `agent()`, `recordedBy()`.
- [D] [app/Models/Partner.php](app/Models/Partner.php) — delete after Phase 4 refactor.

### Factories / Seeders

- [M] `database/factories/AgentFactory.php` — add `agent_role` (default `agent`), `parent_agent_id` null, `fee_payment_status`, `registered_at`. States: `agentLeader()`, `businessPartner()`, `under(Agent $parent)`, `expired()`.
- [M] [database/seeders/SystemSettingsSeeder.php](database/seeders/SystemSettingsSeeder.php) — seed all new fields: rate defaults (agent 10%, AL override 5%, BP override 2%), fee defaults (BP 3000/1000, Leader 100/100, Agent 100/100), role names (Agent/Leader/Business Partner), `renewal_reminder_days_before = 30`, `skip_zero_commissions = true`.
- [D] [database/seeders/PartnerSeeder.php](database/seeders/PartnerSeeder.php) — remove. Replace with `BusinessPartnerSeeder.php`.
- [N] `database/seeders/BusinessPartnerSeeder.php` — creates Agent with `agent_role='business_partner'`, `is_default=true` (or id=1), as the default upline fallback (QNA-03).
- [M] [database/seeders/DatabaseSeeder.php](database/seeders/DatabaseSeeder.php) — drop PartnerSeeder, add SystemUserSeeder + BusinessPartnerSeeder.
- [M] [database/seeders/AgentSeeder.php](database/seeders/AgentSeeder.php) — set `agent_role='agent'`, `parent_agent_id` pointing at seeded BP, `registered_at=today`, `expires_at=+1yr`.
- [M] [database/seeders/TestDataSeeder.php](database/seeders/TestDataSeeder.php) — same.

---

## Phase 2 — Services (≈3 days)

- [N] [app/Services/AgentHierarchy.php](app/Services/AgentHierarchy.php)
  - `getDirectManager(Agent): ?Agent`
  - `getManagementChain(Agent): Collection` — returns ancestors ordered child → BP top.
  - `getSubordinates(Agent, ?role=null): Collection`
  - `validateHierarchyChange(Agent $child, ?Agent $newParent): array` — checks role hierarchy, no cycles, no self-parent. Returns `[]` or list of errors.
  - `wouldCreateCycle(Agent $child, Agent $newParent): bool`
- [M] [app/Services/CommissionCalculator.php](app/Services/CommissionCalculator.php)
  - `getApplicableRate(Agent, string $kind, ?ReferralCode)` — resolves with Decision 7 priority: `AgentCommissionRate` (matched by kind) → ReferralCode (own_sales only) → `SystemSetting` (role-based). Returns `['percentage' => x, 'fixed_amount' => y, 'calc_type' => '…', 'source' => '…']`.
  - `calculate(saleAmount, percentage, fixed, calcType)` — when `calcType='percentage'`: `saleAmount * percentage/100`; when `calcType='fixed'`: returns fixed amount directly; additive fallback when both >0 (Decision 8 / QNA-01).
- [M] [app/Services/CommissionGenerator.php](app/Services/CommissionGenerator.php) — full implementation:
  - `generateForSale(Sale $sale): Collection<Commission>` wrapped in `DB::transaction`.
  - Persists `commission_calc_type`, `source_sale_amount`, `beneficiary_role` on each Commission row.
  - Walks `AgentHierarchy::getManagementChain` for overrides (Decision 9).
  - Respects `skip_zero_commissions` (QNA-10).
  - Activity-logs each persisted commission.
  - `regenerateConfigPreview(Agent $agent): array` — does not persist; used by admin UI.
- [N] [app/Services/CommissionConfig.php](app/Services/CommissionConfig.php) — wraps `SystemSetting::commission_config`. `getRateFor(string $earnerRole, string $sourceRole, string $kind)`. `flush()` called from `SystemSettingController::update`. Caches via `Cache::remember('commission_config', 3600, …)` (QNA-11).
- [N] [app/Services/PayoutReportGenerator.php](app/Services/PayoutReportGenerator.php) — tabbed report data (Decision 10):
  - `byCommissionType(Agent, $year, $month)`
  - `bySalesSource(Agent, $year, $month)`
  - `byTimePeriod(Agent, $from, $to)`
  - `transactions(Agent, …)` — flat list
  - All queries hit `payout_items` using denormalized `commission_type` (Decision 3).
- [N] [app/Services/FeeService.php](app/Services/FeeService.php) — fee management (Part 12 / Decision 13):
  - `applyEntryFee(Agent $agent, User $recordedBy): FeePayment` — creates `fee_payments` row for entry fee; sets `fee_payment_status = 'paid'`, `registered_at = today`, `expires_at = today + 365`, `renewal_due_at = expires_at - renewal_reminder_days_before`.
  - `applyRenewalFee(Agent $agent, User $recordedBy): FeePayment` — updates `expires_at`, `renewal_due_at`, `fee_payment_status = 'paid'`.
  - `getFeeAmountFor(string $agentRole, string $feeType): float` — reads from SystemSetting.
  - `isRenewalEnabled(string $agentRole): bool`.
- [N] [app/Services/RenewalService.php](app/Services/RenewalService.php) — lifecycle management (Part 14):
  - `sendRenewalReminders(): void` — finds agents where `renewal_due_at = today`; sends `AgentRenewalReminderNotification`.
  - `markExpiredAgents(): void` — finds agents where `expires_at < today AND fee_payment_status != 'paid'`; sets `status = 'expired'`.
  - `sendExpiryAlerts(): void` — finds agents where `expires_at = today AND status != 'paid'`; sends `AgentExpiryAlertNotification`.
- [N] [app/Services/RefundService.php](app/Services/RefundService.php) — commission reversal (Decision 17 / QNA-18):
  - `reverseSale(Sale $sale, User $admin): Collection<Commission>` — loads all commissions for the sale; creates a negative-amount reversal Commission row for each (`is_reversal=true`, `original_commission_id`, `status='cancelled'`, `amount=-original.amount`). Wrapped in `DB::transaction`. Activity-logs each reversal.
  - Designed to be called from both admin UI controller AND a future payment gateway webhook.
- [N] [app/Repositories/CommissionRepository.php](app/Repositories/CommissionRepository.php) (optional) — centralize raw aggregation queries.

### Scheduled Jobs [N]

- [N] [app/Console/Commands/ProcessRenewals.php](app/Console/Commands/ProcessRenewals.php) — calls `RenewalService::sendRenewalReminders()` and `markExpiredAgents()` and `sendExpiryAlerts()`.
- [M] [routes/console.php](routes/console.php) or [app/Console/Kernel.php](app/Console/Kernel.php) — schedule `ProcessRenewals` to run `->daily()`.

### Notifications [N]

- [N] [app/Mail/AgentRenewalReminderNotification.php](app/Mail/AgentRenewalReminderNotification.php) — sent X days before `renewal_due_at` (X = `renewal_reminder_days_before`).
- [N] [app/Mail/AgentExpiryAlertNotification.php](app/Mail/AgentExpiryAlertNotification.php) — sent on `expires_at` if not renewed.
- [N] [app/Mail/CommissionEarnedNotification.php](app/Mail/CommissionEarnedNotification.php) — sent after commission record created.
- [N] [app/Mail/CommissionPaidNotification.php](app/Mail/CommissionPaidNotification.php) — sent after payout marked as paid.

### Tests

- [N] `tests/Unit/Services/CommissionCalculatorTest.php`
- [N] `tests/Unit/Services/AgentHierarchyTest.php`
- [N] `tests/Unit/Services/CommissionConfigTest.php`
- [N] `tests/Unit/Services/FeeServiceTest.php`
- [N] `tests/Unit/Services/RefundServiceTest.php`
- [N] `tests/Feature/CommissionGenerationTest.php` — Agent / Agent+Leader / Agent+Leader+BP / fixed-only / combined fixed+pct / calc_type=fixed / cycle prevention.
- [N] `tests/Feature/PayoutReportTest.php` — verifies all 4 tabs.
- [N] `tests/Feature/FeeManagementTest.php` — entry fee applied on approval, renewal fee updates dates, expired status set correctly.

---

## Phase 3 — Controllers + Routes (≈2 days)

### Admin

- [M] [app/Http/Controllers/Admin/AgentController.php](app/Http/Controllers/Admin/AgentController.php)
  - `store` / `update`: validate `agent_role`, `parent_agent_id`. Call `AgentHierarchy::validateHierarchyChange` and reject 422 with errors.
  - On approval: call `FeeService::applyEntryFee($agent, auth()->user())`.
  - Show `registered_at`, `expires_at`, `renewal_due_at`, `fee_payment_status` in responses.
  - Handle `company_representative_id_file` upload (mirror `individual_id_file` pattern) — bug fix Part 18.
  - Add `downloadFile` support for `company_representative_id_file` field.
  - Provide `parents()` JSON endpoint: Agents with role `agent_leader` or `business_partner` for parent dropdown.
- [M] [app/Http/Controllers/Admin/SystemSettingController.php](app/Http/Controllers/Admin/SystemSettingController.php)
  - Validation: 12 rate fields (`*_percentage` 0–100, `*_fixed_amount` ≥ 0), 8 fee fields (decimal ≥ 0), 2 booleans, `renewal_reminder_days_before` (integer 1–365), 3 role name strings, 4 calc_type fields (in: percentage, fixed).
  - On update: `CommissionConfig::flush()` cache + activity log.
- [M] [app/Http/Controllers/Admin/CommissionController.php](app/Http/Controllers/Admin/CommissionController.php) — `index`/`detail` use `PayoutReportGenerator`. Add `commission_type`, `commission_category`, `commission_calc_type` filters.
- [M] [app/Http/Controllers/Admin/PayoutController.php](app/Http/Controllers/Admin/PayoutController.php)
  - `store`: copy `commission_type`, `commission_category` into each `PayoutItem`. Block if any commission has `status = 'cancelled'`.
  - `markAsPaid`: send `CommissionPaidNotification` to each earner.
  - `show`: pass breakdown to view.
- [N] [app/Http/Controllers/Admin/SaleController.php](app/Http/Controllers/Admin/SaleController.php) (or add to existing) — `markAsRefunded(Sale $sale)`: calls `RefundService::reverseSale($sale, auth()->user())`. Activity-logs the refund action.
- [N] [app/Http/Controllers/Admin/FeePaymentController.php](app/Http/Controllers/Admin/FeePaymentController.php) — `index`: list fee payments per agent. `store`: admin manually records a fee payment via `FeeService`. Used for the fee payment history UI.
- [D] [app/Http/Controllers/Admin/PartnerController.php](app/Http/Controllers/Admin/PartnerController.php) — delete after Phase 4 UI cutover.

### Agent

- [M] [app/Http/Controllers/Agent/CommissionController.php](app/Http/Controllers/Agent/CommissionController.php) — switch query to `earning_agent_id`. Pass breakdown via `PayoutReportGenerator`. Send `CommissionEarnedNotification` on new commission.
- [M] [app/Http/Controllers/Agent/SalesController.php](app/Http/Controllers/Agent/SalesController.php) — for Agent Leaders / Business Partners, include subordinate sales (filter by `AgentHierarchy::getSubordinates`).
- [M] [app/Http/Controllers/Agent/PayoutController.php](app/Http/Controllers/Agent/PayoutController.php) and [RequestPayoutController.php](app/Http/Controllers/Agent/RequestPayoutController.php) — switch eligibility query to `earning_agent_id`. Drop `Partner::find(1)` fallback.
- [M] [app/Http/Controllers/Agent/DashboardController.php](app/Http/Controllers/Agent/DashboardController.php) — show summary tiles per commission type + subordinate count + renewal/expiry status for the logged-in agent.
- [M] [app/Http/Controllers/AgentProfileController.php](app/Http/Controllers/AgentProfileController.php) — handle `company_representative_id_file` upload + download (bug fix Part 18, visible only when `profile_type='company'`).
- [D] [app/Http/Controllers/Partner/DashboardController.php](app/Http/Controllers/Partner/DashboardController.php) — remove; `/dashboard` redirect handles `agent_role='business_partner'` (QNA-12).

### Public

- [M] [app/Http/Controllers/AgentRegistrationController.php](app/Http/Controllers/AgentRegistrationController.php)
  - Replace `Partner::where('code', …)` + `Partner::find(1)` with Agent lookup by `agent_role='business_partner'`. Default upline = seeded BP agent (QNA-03).
  - Set `parent_agent_id` instead of `partner_id`.
  - QNA-05: CC both `company_email_address` and linked user email; dedupe.

### Routes

- [M] [routes/web.php](routes/web.php)
  - Remove `admin/partners*` group once UI cutover lands.
  - Remove `partner` middleware group.
  - Add `admin/agents/{id}/refund` → `SaleController::markAsRefunded`.
  - Add `admin/fee-payments*` → `FeePaymentController`.
- [M] [app/Http/Middleware/](app/Http/Middleware/) — drop `partner` middleware; admin/agent stay.
- [M] [app/Models/User.php](app/Models/User.php) — remove `partners()` relation.
- [M] [app/Http/Middleware/HandleInertiaRequests.php](app/Http/Middleware/HandleInertiaRequests.php) — share `systemSettings` (including role name fields) in `share()` so Vue can read role labels without hardcoding (Decision 15).

---

## Phase 4 — Frontend (Inertia/Vue) (≈3 days)

### Admin — System Settings

- [M] [resources/js/Pages/Admin/SystemSettingsUpdate.vue](resources/js/Pages/Admin/SystemSettingsUpdate.vue) — rebuild form with:
  - 6 commission rows × (percentage, fixed RM, calc_type dropdown) inputs. Group: Agent / Agent Leader / Business Partner.
  - **New "Fee Configuration" section**: entry fee + renewal fee + enabled toggle per role (Decision 13).
  - **New "Role Names" section**: editable text inputs for Agent/Leader/Business Partner labels (Decision 15).
  - `renewal_reminder_days_before` input.
  - `skip_zero_commissions` toggle.
  - Live commission preview using `CommissionGenerator::regenerateConfigPreview` JSON endpoint.
- [M] [resources/js/Pages/Admin/SystemSettings.vue](resources/js/Pages/Admin/SystemSettings.vue) — render new fields read-only in same sections.

### Admin — Agents

- [M] [resources/js/Pages/Admin/AgentsAdd.vue](resources/js/Pages/Admin/AgentsAdd.vue), [AgentUpdate.vue](resources/js/Pages/Admin/AgentUpdate.vue)
  - Add `agent_role` select.
  - Add `parent_agent_id` searchable select (filtered to roles ≥ child role).
  - Add `company_representative_id_file` upload visible only when `profile_type === 'company'` (bug fix Part 18).
  - Show `registered_at`, `expires_at`, `renewal_due_at`, `fee_payment_status` fields.
- [M] [AgentView.vue](resources/js/Pages/Admin/AgentView.vue)
  - Show role + parent + direct subordinates list.
  - Show `company_representative_id_file` download link when `profile_type === 'company'` (bug fix).
  - Show expiry / fee status info.
- [M] [AgentsList.vue](resources/js/Pages/Admin/AgentsList.vue) — add Role column, Expiry Status column, filter by role.

### Admin — Commissions & Payouts

- [M] [CommissionsList.vue](resources/js/Pages/Admin/CommissionsList.vue), [CommissionDetail.vue](resources/js/Pages/Admin/CommissionDetail.vue) — show `commission_type`, `commission_category`, `commission_calc_type`. 4-tab report layout (Decision 10).
- [M] [PayoutCreate.vue](resources/js/Pages/Admin/PayoutCreate.vue), [PayoutDetail.vue](resources/js/Pages/Admin/PayoutDetail.vue), [PayoutsList.vue](resources/js/Pages/Admin/PayoutsList.vue) — surface breakdown (Own / Override-Agent / Override-Leader) totals; filter by type. Show "Mark as Refunded" button on payable commissions.

### Admin — Fee Management [N]

- [N] `resources/js/Pages/Admin/FeePayments.vue` — list fee payment history per agent (entry/renewal events, amounts, recorded_by, dates).

### Agent

- [M] [resources/js/Pages/Agent/Commissions.vue](resources/js/Pages/Agent/Commissions.vue), [CommissionDetail.vue](resources/js/Pages/Agent/CommissionDetail.vue) — 4-tab breakdown (Decision 10). Show `commission_calc_type` label.
- [M] [Agent/Sales.vue](resources/js/Pages/Agent/Sales.vue) — for leaders/BPs, show "source agent" column for subordinate sales.
- [M] [Agent/Dashboard.vue](resources/js/Pages/Agent/Dashboard.vue) — tiles per commission type, subordinate count, renewal/expiry alert banner.
- [M] [Agent/RequestPayout.vue](resources/js/Pages/Agent/RequestPayout.vue) — pulls pending list by `earning_agent_id`.
- [M] [resources/js/Pages/Agent/Profile/Edit.vue](resources/js/Pages/Agent/Profile/Edit.vue) — add `company_representative_id_file` upload visible only when `profile_type === 'company'` (bug fix Part 18).

### Delete

- [D] [resources/js/Pages/Admin/PartnersList.vue](resources/js/Pages/Admin/PartnersList.vue), [PartnersAdd.vue](resources/js/Pages/Admin/PartnersAdd.vue), [PartnerView.vue](resources/js/Pages/Admin/PartnerView.vue), [PartnerUpdate.vue](resources/js/Pages/Admin/PartnerUpdate.vue) — delete or repurpose AgentsList with `?role=business_partner` filter (QNA-02).
- [M] AppSidebar / AdminLayout — remove "Partners" nav item; add "Hierarchy" view (tree of agents).

### Global

- Use `$page.props.systemSettings.role_name_agent` etc. instead of hardcoded "Agent" / "Leader" / "Business Partner" strings anywhere in Vue components (Decision 15).

---

## Phase 5 — Activity Logging + System User (≈0.5 day)

- [M] [app/Services/TrackingService.php](app/Services/TrackingService.php), [app/Services/CommissionGenerator.php](app/Services/CommissionGenerator.php) — replace `User::where('email', 'system@…')->first()` lookups with `SystemUser::resolve()`.
- [N] [app/Support/SystemUser.php](app/Support/SystemUser.php) — `resolve(): User` cached.
- [M] [app/Models/ActivityLog.php](app/Models/ActivityLog.php) — verify bulk-create tolerance. Add `logBulkCreate` helper if needed.
- Log these new events: fee payment recorded, fee config updated, role names updated, agent refund triggered, agent expired by scheduler, commission reversal created.

---

## Phase 6 — Cleanup, QA, Docs (≈1 day)

- [M] [CLAUDE.md](CLAUDE.md) — update "Agent Commission Tracking" section: roles/hierarchy/override flow, SystemSetting keys, fee management, renewal lifecycle.
- [M] [dev/docs/API_VISIT_TRACKING.md](dev/docs/API_VISIT_TRACKING.md) — note `/api/agents/track/sale` now returns multiple commission ids.
- [M] [routes/api.php](routes/api.php) — if sale-tracking response shape changes, document.
- [N] `tests/Feature/HierarchyAdminCrudTest.php` — admin can set role + parent, validation rejects cycles/role inversion.
- [N] `tests/Feature/PartnerRemovalTest.php` — registration without referral code falls back to default Business Partner agent.
- [N] `tests/Feature/CommissionReversalTest.php` — refund triggers negative commission rows, payout blocked on cancelled commissions.
- [N] `tests/Feature/RenewalLifecycleTest.php` — approval sets dates, scheduler marks expired, reminder sent at correct day.
- [N] `tests/Feature/CompanyIcFileTest.php` — company representative IC file uploads, saves, and downloads correctly.
- [M] `phpunit.xml` — confirm sqlite tests pass with new enum migrations.
- [ ] Manual smoke (record in PR): create BP → AL → Agent, post sale via API, view payout report tabs, run payout, confirm 3 commissions in right buckets. Set entry fee, approve new agent, confirm `fee_payments` row created. Upload company IC file as company-type agent, confirm download works.

---

## File Touchpoint Summary

| Area | Modify | New | Delete |
|---|---|---|---|
| Migrations | — | 13 | — |
| Models | Agent, Commission, PayoutItem, SystemSetting, AgentCommissionRate, User | FeePayment | Partner |
| Services | TrackingService, CommissionCalculator, CommissionGenerator | CommissionConfig, AgentHierarchy, PayoutReportGenerator, FeeService, RenewalService, RefundService, SystemUser helper | — |
| Controllers | Admin/{Agent,SystemSetting,Commission,Payout}, Agent/{Commission,Sales,Payout,RequestPayout,Dashboard}, AgentRegistration, AgentProfileController | Admin/{SaleController,FeePaymentController} | Admin/PartnerController, Partner/DashboardController |
| Routes / Middleware | web.php, HandleInertiaRequests, partner middleware removed | — | partner routes |
| Vue Pages | All Admin/{Agents,Commissions,Payouts,SystemSettings}*, Agent/{Commissions,CommissionDetail,Sales,Dashboard,RequestPayout,Profile/Edit} | Admin/FeePayments.vue | Admin/Partners* |
| Seeders / Factories | AgentFactory, AgentSeeder, TestDataSeeder, SystemSettingsSeeder, DatabaseSeeder | SystemUserSeeder, BusinessPartnerSeeder | PartnerSeeder |
| Mail | — | AgentRenewalReminderNotification, AgentExpiryAlertNotification, CommissionEarnedNotification, CommissionPaidNotification | — |
| Scheduled Jobs | — | ProcessRenewals command | — |
| Tests | — | 10+ new | — |
| Docs | CLAUDE.md, API_VISIT_TRACKING.md | — | — |

---

## Risk / Sequencing Notes

1. **Do not drop `partners` tables in the same commit as schema additions.** Land Phase 1 additive first; run the drop migration in a follow-up PR (QNA-02).
2. **`Partner::find(1)` CC email** — ensure the seeded default BP Agent owns the correct email field before removing the Partner fallback (QNA-05).
3. **`commission_calc_type` naming** — do not confuse with `commission_type` (own_sales/override) or `commission_category` (role). Three distinct columns. See Decision 14.
4. **Enum ALTER statements** — migrations 9 and 10 use raw `DB::statement` to extend enums. Sqlite tests will treat them as no-ops (Eloquent casts handle validation). Verified safe per QNA-15.
5. **Fee application on agent approval** — `AgentController::approve` must call `FeeService::applyEntryFee`. If fee amount is 0 (waived), still create a `fee_payments` row with `amount=0` so the audit trail is complete.
6. **Refund service is the single entry point** — do not create reversal commissions anywhere except `RefundService::reverseSale`. This ensures webhook integration (QNA-18) can reuse the same method.
7. **Role names in Vue** — read from `$page.props.systemSettings`, never hardcode strings. `HandleInertiaRequests::share()` must include them on every Inertia response (Decision 15).
8. **Spatie `partner` role** — becomes orphaned after QNA-06. Drop it in the same PR as the Partner removal migration (Phase 1 migration #6).

---

## Phase 7 — Gap Resolutions (UI/UX + Notification System + Registration Rebuild)

> All items below are derived from GAP-01 through GAP-18 decisions. Implement after Phase 6 QA is clean.

### Migrations [N]

14. [N] `database/migrations/2026_05_01_000014_add_credentials_and_tc_to_registration.php`
    ```php
    // agents table additions
    $table->timestamp('tc_accepted_at')->nullable()->after('fee_payment_status');
    $table->timestamp('first_login_at')->nullable()->after('tc_accepted_at');
    $table->string('suspension_reason')->nullable()->after('first_login_at');
    $table->text('rejection_reason')->nullable()->after('suspension_reason');
    // Extend status: add 'rejected' if not present
    DB::statement("ALTER TABLE agents MODIFY status ENUM('active','inactive','suspended','banned','expired','pending','rejected') DEFAULT 'pending'");
    ```

15. [N] `database/migrations/2026_05_01_000015_create_registration_verifications_table.php`
    ```php
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

16. [N] `database/migrations/2026_05_01_000016_create_agent_notifications_table.php`
    ```php
    Schema::create('agent_notifications', function (Blueprint $table) {
        $table->id();
        $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
        $table->string('type', 100);
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

17. [N] `database/migrations/2026_05_01_000017_add_note_fields_to_payouts_table.php`
    ```php
    $table->string('agent_note', 500)->nullable()->after('status');
    $table->text('admin_note')->nullable()->after('agent_note');
    ```

18. [N] `database/migrations/2026_05_01_000018_add_min_payout_amount_to_system_settings.php`
    ```php
    $table->decimal('min_payout_amount', 10, 2)->default(1.00)->after('renewal_reminder_days_before');
    ```

### Models [N/M]

- [N] [app/Models/AgentNotification.php](app/Models/AgentNotification.php)
  - Fillable: `agent_id`, `type`, `subject`, `body`, `is_read`, `read_at`, `related_model`, `related_id`
  - Relations: `agent()`
  - Scopes: `unread()`, `forAgent($agentId)`
  - Helper: `markRead()` sets `is_read=true`, `read_at=now()`
  - Constants: `TYPE_*` for each event type

- [N] [app/Models/RegistrationVerification.php](app/Models/RegistrationVerification.php)
  - Fillable: `email`, `code`, `expires_at`, `attempts`, `verified`
  - Methods: `isExpired()`, `isExhausted()` (attempts >= 3), `markVerified()`

- [M] [app/Models/Agent.php](app/Models/Agent.php)
  - Add `tc_accepted_at`, `first_login_at`, `suspension_reason`, `rejection_reason` to fillable + casts
  - Add relation: `notifications()` → `hasMany(AgentNotification)`
  - Helper: `isFirstLogin(): bool` checks `first_login_at === null`

- [M] [app/Models/Payout.php](app/Models/Payout.php)
  - Add `agent_note`, `admin_note` to fillable

- [M] [app/Models/SystemSetting.php](app/Models/SystemSetting.php)
  - Add `min_payout_amount` to fillable + cast as decimal

### Services [N/M]

- [N] [app/Services/NotificationService.php](app/Services/NotificationService.php)
  - `notify(Agent $agent, string $type, string $subject, string $body, ?string $relatedModel = null, ?int $relatedId = null): AgentNotification`
  - `notifyAdmin(string $type, string $subject, string $body, ?string $relatedModel = null, ?int $relatedId = null): AgentNotification` — sends to Agent#1
  - `notifyChain(Commission $commission, string $type, string $subject, string $body)` — notifies all earners in commission chain
  - All methods wrapped in try/catch — notification failure must never block the primary action

- [N] [app/Services/RegistrationVerificationService.php](app/Services/RegistrationVerificationService.php)
  - `generate(string $email): RegistrationVerification` — creates 6-digit code, 15-min expiry, sends email
  - `verify(string $email, string $code): bool` — checks code + expiry + increments attempts
  - `resend(string $email): RegistrationVerification` — invalidates old code, generates new one
  - `isExhausted(string $email): bool`

- [M] [app/Services/RefundService.php](app/Services/RefundService.php)
  - After reversal: call `NotificationService::notifyChain()` for all earners

- [M] [app/Services/FeeService.php](app/Services/FeeService.php)
  - After `applyEntryFee`: call `NotificationService::notify()` for agent

### Seeders

- [M] [database/seeders/SystemSettingsSeeder.php](database/seeders/SystemSettingsSeeder.php) — add `min_payout_amount = 1.00`

### Controllers [N/M]

#### Registration (rebuild)

- [M] [app/Http/Controllers/AgentRegistrationController.php](app/Http/Controllers/AgentRegistrationController.php)
  - Rebuild as 6-step multi-step form controller
  - `show()` → serves current step from session/cookie; defaults to Step 1
  - `store()` → handles step submission, advances step counter, validates per-step
  - `verifyEmail()` POST → delegates to `RegistrationVerificationService::verify()`; on success creates User+Agent
  - `resendCode()` POST → delegates to `RegistrationVerificationService::resend()`
  - `completePayment()` GET/POST (authenticated) → `/agent/payment/complete`
  - `stripeSuccess()` GET → `/register-as-agent/payment/success`
  - `stripeCancelled()` GET → `/register-as-agent/payment/cancelled`
  - `skipPayment()` POST → sets `tc_accepted_at`, auto-logs in, redirects to dashboard
  - On Step 3: pre-check email — if exists with password, return validation error; if exists no password, return `needs_reset` flag
  - All form state stored in signed cookie `reg_wizard_state` (excluding password fields)

#### Notification / Inbox

- [N] [app/Http/Controllers/Agent/NotificationController.php](app/Http/Controllers/Agent/NotificationController.php)
  - `index()` GET → paginated list of agent's notifications (latest first)
  - `markRead($id)` POST → marks single notification read
  - `markAllRead()` POST → marks all as read
  - Pass `unread_inbox_count` to Inertia shared props via `HandleInertiaRequests`

#### Admin Activity Log

- [N] [app/Http/Controllers/Admin/ActivityLogController.php](app/Http/Controllers/Admin/ActivityLogController.php)
  - `index()` GET → paginated, filterable `ActivityLog` list
  - Filters: date range, actor_id, action, target model
  - `export()` GET → CSV download of filtered results

#### Admin Agent (additions)

- [M] [app/Http/Controllers/Admin/AgentController.php](app/Http/Controllers/Admin/AgentController.php)
  - `approve()` — after setting `status=active`: call `NotificationService::notify()` for agent (approved) + parent (new team member)
  - `store()` — admin-created agents: create User with temp password + send `AccountCreatedByAdminNotification`
  - `requestApproval()` POST from agent side → resets status to pending + notifies admin inbox

#### Agent Appeal

- [N] [app/Http/Controllers/Agent/AppealController.php](app/Http/Controllers/Agent/AppealController.php)
  - `store()` POST `/agent/appeal-suspension` → sends email to admin + creates Inbox notification for agent

#### Payout (additions)

- [M] [app/Http/Controllers/Admin/PayoutController.php](app/Http/Controllers/Admin/PayoutController.php)
  - `cancel()` POST — new action: set `status=cancelled`, admin note required, notify agent via `NotificationService`
  - Store `agent_note` from request on `store()`
- [M] [app/Http/Controllers/Agent/RequestPayoutController.php](app/Http/Controllers/Agent/RequestPayoutController.php)
  - Auto-select all eligible commissions (no manual selection)
  - Block if total < `min_payout_amount`
  - Accept `agent_note` field
  - Notify admin inbox after payout created

#### Referral Stats

- [N] [app/Http/Controllers/Agent/ReferralController.php](app/Http/Controllers/Agent/ReferralController.php)
  - `index()` GET `/agent/referral` → referral code + visit stats + paginated visits table
  - Filter by date range + converted status

#### First Login

- [N] [app/Http/Controllers/Agent/OnboardingController.php](app/Http/Controllers/Agent/OnboardingController.php)
  - `show()` GET `/get-started-guide` → render onboarding slides; pass agent_role to Vue
  - `complete()` POST → set `agent.first_login_at = now()`, redirect to dashboard

#### Get Started Email Pre-Check

- [M] [app/Http/Controllers/GetStartedController.php](app/Http/Controllers/GetStartedController.php) (or create new)
  - `checkEmail()` POST → checks email; returns JSON `{status: 'new'|'login'|'reset'|'no_password'}`

### Routes [M]

Add to `routes/web.php`:

```php
// Registration additions
Route::post('/register-as-agent/verify-email', [AgentRegistrationController::class, 'verifyEmail'])->name('register-as-agent.verify-email');
Route::post('/register-as-agent/resend-code', [AgentRegistrationController::class, 'resendCode'])->name('register-as-agent.resend-code');
Route::get('/register-as-agent/payment/success', [AgentRegistrationController::class, 'stripeSuccess'])->name('register-as-agent.payment.success');
Route::get('/register-as-agent/payment/cancelled', [AgentRegistrationController::class, 'stripeCancelled'])->name('register-as-agent.payment.cancelled');
Route::post('/get-started/check-email', [GetStartedController::class, 'checkEmail'])->name('get-started.check-email');

// First login onboarding
Route::get('/get-started-guide', [OnboardingController::class, 'show'])->name('get-started-guide')->middleware(['auth:sanctum', ...]);
Route::post('/get-started-guide/complete', [OnboardingController::class, 'complete'])->name('get-started-guide.complete')->middleware(['auth:sanctum', ...]);

// Authenticated agent additions
Route::middleware(['auth:sanctum', ..., 'agent'])->prefix('agent')->name('agent.')->group(function () {
    Route::get('/payment/complete', [AgentRegistrationController::class, 'completePayment'])->name('payment.complete');
    Route::post('/payment/complete', [AgentRegistrationController::class, 'submitPayment'])->name('payment.complete.submit');
    Route::get('/referral', [ReferralController::class, 'index'])->name('referral');
    Route::get('/inbox', [NotificationController::class, 'index'])->name('inbox');
    Route::post('/inbox/{id}/read', [NotificationController::class, 'markRead'])->name('inbox.read');
    Route::post('/inbox/read-all', [NotificationController::class, 'markAllRead'])->name('inbox.read-all');
    Route::post('/appeal-suspension', [AppealController::class, 'store'])->name('appeal-suspension');
    Route::post('/request-approval', [AgentController::class, 'requestApproval'])->name('request-approval');
});

// Admin additions
Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log');
    Route::get('/activity-log/export', [ActivityLogController::class, 'export'])->name('activity-log.export');
    Route::post('/payout/{id}/cancel', [PayoutController::class, 'cancel'])->name('payout.cancel');
});
```

### Mail [N]

- [N] [app/Mail/AccountCreatedNotification.php](app/Mail/AccountCreatedNotification.php) — sent at Step 4 completion (self-registration); contains login URL, pending status note
- [N] [app/Mail/AccountCreatedByAdminNotification.php](app/Mail/AccountCreatedByAdminNotification.php) — sent when admin creates agent; contains temp password reset link
- [N] [app/Mail/EmailVerificationCode.php](app/Mail/EmailVerificationCode.php) — 6-digit code email for registration Step 4
- [N] [app/Mail/SuspensionAppealNotification.php](app/Mail/SuspensionAppealNotification.php) — sent to admin when agent appeals
- [N] [app/Mail/PayoutCancelledNotification.php](app/Mail/PayoutCancelledNotification.php) — sent when admin cancels payout

### Vue Pages [N/M]

#### Registration Rebuild

- [M] [resources/js/Pages/GetStarted.vue](resources/js/Pages/GetStarted.vue)
  - Add email pre-check field: input + [Continue] button, AJAX call to `/get-started/check-email`
  - Show inline result: new → redirect to wizard; login → show login link; reset → show reset link

- [M] [resources/js/Pages/AgentRegistration.vue](resources/js/Pages/AgentRegistration.vue) (or rebuild as wizard)
  - Progress indicator (step dots 1–6)
  - Step 1: Referral ID (existing)
  - Step 2: Package cards (existing)
  - Step 3: Profile + credentials (add password + confirm password fields at bottom)
  - Step 4: Email verification (6-digit code input, countdown timer, resend button)
  - Step 5: T&C checkbox + payment options (Stripe / Manual / Skip)
  - Step 6: Confirmation
  - Cookie restore logic on mount: pre-fill from `reg_wizard_state` cookie if present

#### New Agent Pages

- [N] `resources/js/Pages/Agent/Inbox.vue` — notifications list, unread badge, mark-read actions
- [N] `resources/js/Pages/Agent/Referral.vue` — referral code card + stats + visits table
- [N] `resources/js/Pages/GetStartedGuide.vue` — full-screen slide onboarding (5–6 slides, role-adaptive)
- [N] `resources/js/Pages/Agent/PaymentComplete.vue` — payment resume screen (same UI as wizard Step 5)

#### Admin Pages

- [N] `resources/js/Pages/Admin/ActivityLog.vue` — filterable activity log table + CSV export
- [M] [resources/js/Pages/Admin/PayoutDetail.vue](resources/js/Pages/Admin/PayoutDetail.vue) — add [Cancel Payout] button + admin note field + agent note display
- [M] [resources/js/Pages/Admin/AgentView.vue](resources/js/Pages/Admin/AgentView.vue) — show suspension_reason, rejection_reason; status override dropdown

#### Dashboard Additions

- [M] [resources/js/Pages/Agent/Dashboard.vue](resources/js/Pages/Agent/Dashboard.vue)
  - Suspended banner (GAP-05): shown when `status === 'suspended'`; [Appeal] button
  - Rejected banner (GAP-09): shown when `status === 'rejected'`; [Request Approval] button
  - Payment pending banner: shown when `fee_payment_status === 'pending'` (skipped payment); [Complete Payment] button
  - Inbox unread count badge in nav

#### Shared Components

- [N] `resources/js/Components/EmptyState.vue` — reusable empty state with icon, headline, subtext, optional CTA (GAP-17)
- Use `EmptyState.vue` in: Sales.vue, Commissions.vue, Payouts.vue, Team.vue, Inbox.vue, Referral.vue, Admin/AgentsList.vue, Admin/CommissionsList.vue, Admin/PayoutsList.vue, Admin/ActivityLog.vue

#### Error Pages

- [N] `resources/js/Pages/Errors/403.vue`
- [N] `resources/js/Pages/Errors/404.vue`
- [N] `resources/js/Pages/Errors/419.vue`
- [N] `resources/js/Pages/Errors/500.vue`
- [M] [app/Exceptions/Handler.php](app/Exceptions/Handler.php) — register Inertia error rendering for 403, 404, 419, 500

### HandleInertiaRequests Updates

- [M] [app/Http/Middleware/HandleInertiaRequests.php](app/Http/Middleware/HandleInertiaRequests.php)
  - Share `unread_inbox_count` in `share()`: `AgentNotification::forAgent($agent->id)->unread()->count()` (0 if not agent)
  - Share `agent_status` and `fee_payment_status` for dashboard banner logic

### Tests [N]

- [N] `tests/Feature/RegistrationWizardTest.php` — 6-step happy path, email verify, skip payment, resume
- [N] `tests/Feature/EmailVerificationTest.php` — code expiry, wrong code, exhausted attempts, resend
- [N] `tests/Feature/NotificationServiceTest.php` — notify, notifyAdmin, notifyChain, read/unread
- [N] `tests/Feature/PayoutNotesTest.php` — agent note stored, admin note on cancel, notification sent
- [N] `tests/Feature/SuspensionAppealTest.php` — appeal sends email + creates inbox notification
- [N] `tests/Feature/OnboardingTest.php` — first login redirects to guide, completion sets timestamp

---

## File Touchpoint Summary (Phase 7 additions)

| Area | Modify | New |
|---|---|---|
| Migrations | — | 5 (verifications, notifications, payout notes, min_payout, agent flags) |
| Models | Agent, Payout, SystemSetting | AgentNotification, RegistrationVerification |
| Services | RefundService, FeeService | NotificationService, RegistrationVerificationService |
| Controllers | AgentRegistrationController, Admin/AgentController, Admin/PayoutController, Agent/RequestPayoutController | Agent/NotificationController, Agent/ReferralController, Agent/OnboardingController, Agent/AppealController, Admin/ActivityLogController, GetStartedController |
| Routes | web.php | 15+ new routes |
| Vue Pages | Agent/Dashboard, Admin/PayoutDetail, Admin/AgentView, GetStarted, AgentRegistration | Agent/Inbox, Agent/Referral, GetStartedGuide, Agent/PaymentComplete, Admin/ActivityLog, Errors/{403,404,419,500} |
| Components | — | EmptyState.vue |
| Mail | — | AccountCreatedNotification, AccountCreatedByAdminNotification, EmailVerificationCode, SuspensionAppealNotification, PayoutCancelledNotification |
| Seeders | SystemSettingsSeeder | — |
| Middleware | HandleInertiaRequests | — |
| Tests | — | 6 new feature test files |
