# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

**Penurwill Agent** is a Laravel 12 + Vue 3 commission tracking and agent management application built with Inertia.js. It enables agents to register, track referrals and sales, and request payouts while providing admins with commission management and visibility.

## Tech Stack

- **Backend**: Laravel 12, Jetstream, Sanctum, Spatie Permissions
- **Frontend**: Vue 3 (Composition API with `<script setup>`), Inertia.js, Tailwind CSS, shadcn-vue
- **Database**: SQLite (testing), PostgreSQL (production)
- **Build**: Vite + laravel-vite-plugin
- **Testing**: PHPUnit with Feature and Unit test suites
- **Package Manager**: pnpm

## Docker Development Setup

The project includes Docker configuration for MySQL database development on macOS.

### Quick Setup (One-time)

```bash
# Start MySQL container
docker-compose -f docker/dev/docker-compose.yml up -d

# Generate app key and run migrations
php artisan key:generate
php artisan migrate
```

### Using Docker Helper Scripts

```bash
# Source the helper functions (add to ~/.zshrc or ~/.bash_profile for persistence)
source docker/dev/docker.sh

# Then use convenient commands:
docker-up           # Start containers
docker-down         # Stop containers
docker-reset        # Stop and remove all data
docker-logs         # View container logs
docker-status       # Check container status
docker-mysql        # Run MySQL commands
```

### Database Configuration

The `.env` file is pre-configured for Docker MySQL:
- **Host**: `127.0.0.1`
- **Port**: `3306`
- **Database**: `penurwill`
- **Username**: `mysql`
- **Password**: `password`

### Additional Services

- **phpMyAdmin**: Available at `http://localhost:8080`
  - Username: `mysql`
  - Password: `password`

For detailed Docker setup and troubleshooting, see `docker/dev/README.md`.

## Development Commands

### Start Development Server
```bash
composer dev
```
Runs all services concurrently: PHP server, queue listener, log streaming, and Vite dev server.

### Frontend Build
```bash
npm run build      # Production build
npm run dev        # Development with Vite
pnpm build        # Production build (preferred)
pnpm dev          # Development (preferred)
```

### Laravel Artisan Commands
```bash
php artisan serve           # Start PHP server
php artisan tinker          # Interactive shell
php artisan migrate         # Run migrations
php artisan test            # Run all tests
php artisan test tests/Feature/ExampleTest.php  # Run specific test
php artisan test --filter=TestName               # Run tests matching name
```

### Database
```bash
php artisan migrate         # Run migrations
php artisan migrate:rollback # Rollback last migration
php artisan migrate:fresh   # Fresh migration (drops all tables)
php artisan db:seed        # Run seeders
```

## Project Structure

```
app/
  Http/
    Controllers/
      Admin/                # Admin dashboard, agent, payout, commission management
      Agent/                # Agent dashboard, sales, payouts
      Auth/                 # Authentication
      ProfileController.php
      AgentProfileController.php
      AgentRegistrationController.php
  Models/
    Agent.php              # Main agent model
    User.php               # User model (via Jetstream)
    Sale.php               # Sales tracking
    Commission.php         # Commission records
    Payout.php             # Payout requests
    PayoutItem.php         # Individual payout items
    ActivityLog.php        # Audit logging
    ReferralCode.php       # Agent referral codes
    AgentVisit.php         # Tracked agent visits
    Partner.php            # Partner companies
    BankAccount.php        # Agent bank details
    AgentCommissionRate.php # Custom commission rates

resources/
  js/
    Pages/
      Auth/                 # Login, register, password reset, etc.
      Admin/                # Admin dashboard and management pages
      Agent/                # Agent dashboard and tools
      Profile/              # User profile pages
      Design/               # Layout reference pages (not for production)
    Layouts/
      AppLayout.vue        # Default layout for authenticated users
      AdminLayout.vue      # Admin-specific layout
      AgentLayout.vue      # Agent-specific layout
    Components/            # Reusable Vue components
    lib/
      utils.js             # Helper functions (formatCurrency, etc.)

routes/
  web.php                  # Web routes with Inertia responses
  api.php                  # API routes (tracking, etc.)

database/
  migrations/              # Schema migrations
  factories/               # Model factories for testing
  seeders/                 # Database seeders

config/                    # Configuration files
```

## Key Architecture Patterns

### Activity Logging
All create, update, and delete operations must log to ActivityLog. The user must be explicitly supplied by the controller (no automatic detection).

```php
// In controller
$user = auth()->user();

// After creating
ActivityLog::logCreate($user, $model, $model->toArray());

// After updating
ActivityLog::logUpdate($user, $model, $before, $model->toArray());

// After deleting
ActivityLog::logDelete($user, $model, $before);

// Or use fluent interface for custom actions
$log = ActivityLog::createInstance()
    ->setUser($user)  // REQUIRED
    ->setAction('custom_action')
    ->setTarget($model)
    ->setDescription('Custom message');
```

### Agent Commission Tracking

**Hierarchy roles**: `agent` → `agent_leader` → `business_partner`. Stored in `agents.agent_role`. Each agent has a `parent_agent_id` pointing to their upline.

**Commission flow**: A sale generates multiple `Commission` rows via `CommissionGenerator`:
1. `own_sales` commission for the selling agent
2. `override` commission (type `agent_leader`) for the direct leader
3. `override` commission (type `business_partner`) for the BP upline

**Rate resolution** (priority order): `AgentCommissionRate` row keyed by `(agent_id, kind)` → `SystemSetting` role-based defaults.

**Commission types**: `commission_type` (`own_sales`|`override`), `commission_category` (`agent`|`agent_leader`|`business_partner`), `commission_calc_type` (`percentage`|`fixed`).

**Fee management**: `FeeService` records entry/renewal fees to `fee_payments`. Sets `registered_at`, `expires_at`, `renewal_due_at` on the agent. `RenewalService` runs daily via scheduler.

**Reversal**: `RefundService::reverseSale()` is the single entry point — creates negative-amount `Commission` rows with `is_reversal=true`, `status=cancelled`. Respects `reversal_time_limit` (days) from `SystemSetting`.

**Key SystemSetting keys**: `agent_own_sales_percentage`, `agent_leader_override_agent_percentage`, `business_partner_override_agent_percentage`, `business_partner_override_agent_leader_percentage`, `entry_fee_agent`, `renewal_fee_agent`, `membership_duration_days`, `renewal_reminder_days_before`, `reversal_time_limit`, `skip_zero_commissions`.

- Agents create referral codes (ReferralCode model)
- Visits are tracked via REST API and tracking pixels (AgentVisit model)
- Sales are tracked (Sale model) which automatically creates Commission records via `CommissionGenerator`
- `/api/agents/track/sale` now returns multiple commission IDs (one per earner in the hierarchy)
- Agents request payouts which create Payout and PayoutItem records

### Role-Based Access Control
Uses Spatie/laravel-permission:
- Roles: `admin`, `agent` (`partner` role deprecated — use `business_partner` agent_role)
- Permissions checked in controllers via `auth()->user()->hasPermissionTo()`
- Use `HasRoleChecks` trait for common role checking logic

### Inertia Page Routing
Pages are resolved from `resources/js/Pages/{name}.vue`. Always include:
- Breadcrumbs (link back to parent)
- Clear title and description
- Semantic HTML
- Responsive design (mobile-first)

### File Organization
- Vue components use `<script setup>` syntax
- 2 spaces for Vue/JavaScript indentation
- 4 spaces for PHP indentation
- Import statements at top of files
- CSS: Use Tailwind classes; custom styles in `resources/css/app.css`

## Styling Guidelines

### Design Reference
**Before building any new page, visit `/design/design-01` in the browser.** The source file `resources/js/Pages/Design/Design01.vue` is the design system reference. It shows the component library, color palette, typography, and layout patterns in use.

- Components live in `resources/js/Pages/Design/Components/` — use these directly in production pages
- Icons: lucide-vue-next only (no other icon libraries)
- Layouts: `AdminLayout.vue` for admin pages, `AgentLayout.vue` for agent pages
- `@TODO` comments inside Design01.vue mark elements still to be added — complete these before starting frontend work

### Component Usage
| Need | Use |
|------|-----|
| Buttons | `Design/Components/Button.vue` — variants: `default`, `secondary`, `destructive`, `outline`, `ghost`, `link` |
| Cards | `Design/Components/Card.vue` + `CardHeader`, `CardContent`, `CardTitle` |
| Status badges | `Design/Components/Badge.vue` — variants: `default`, `secondary`, `destructive`, `success`, `warning`, `outline` |
| Tabs | `Design/Components/Tabs.vue` + `TabsList`, `TabsTrigger`, `TabsContent` |
| Progress bars | `Design/Components/Progress.vue` |
| Stat cards | `Design/Components/StatsCard.vue` |
| Alerts | `Design/Components/Alert.vue` |
| Charts | `Design/Components/LineChart.vue`, `BarChart.vue`, `PieChart.vue` |
| Class merging | `cn()` from `lib/utils.js` |

### Color Palette
Defined as CSS variables in `resources/css/app.css` and as Tailwind tokens:
- `text-forest-dark` / `bg-forest-dark`: #162d25 — primary headings, sidebar
- `text-forest-light` / `bg-forest-light`: #5d775f — icons, avatars
- `text-gold` / `bg-gold`: #bc9c5f — highlights, stat icons
- `bg-cream`: #eae1d0 — page backgrounds
- Accents: `accent-red` (#d4423f), `accent-orange` (#e07b39), `accent-green` (#7a9b7d), `accent-blue` (#4a6b73), `accent-gray` (#8a9ba8)
- Neutral scale: `stone-50` through `stone-900` for borders, text, backgrounds

### Currency Formatting
**Always use the global formatCurrency helper for currency values:**
```javascript
import { formatCurrency } from '../../lib/utils.js'

// In template: {{ formatCurrency('RM', amount) }}
// In script: const formatted = formatCurrency('RM', 1234.56) // "RM 1,234.56"
```

## Testing

### Run All Tests
```bash
php artisan test
```

### Run Specific Test Suite
```bash
php artisan test tests/Feature/          # Feature tests
php artisan test tests/Unit/             # Unit tests
```

### Run Single Test
```bash
php artisan test tests/Feature/ExampleTest.php
php artisan test --filter=methodName
```

Tests use sqlite in-memory database. Feature tests can test full request flows with Inertia responses.

## Common Development Tasks

### Adding a New Page
1. Create controller method that returns Inertia response with data
2. Create `.vue` file in `resources/js/Pages/` with proper layout
3. Include breadcrumbs, title, description
4. Add route in `routes/web.php`
5. **Polish UI using the design system** — see `resources/js/Pages/Design/Design01.vue` for the reference implementation. Use components from `resources/js/Pages/Design/Components/` for consistency. Visit `/design/design-01` in browser to preview.

### Adding a New Model/Migration
1. Create migration: `php artisan make:migration create_table_name`
2. Create model: `php artisan make:model ModelName`
3. Define relationships in model
4. Add activity logging to associated controllers
5. Create factory if needed for testing

### Adding API Endpoints
1. Add routes in `routes/api.php`
2. Create controller in `app/Http/Controllers/` (no subdirectory for API)
3. Include validation and proper HTTP status codes
4. Document in `API_VISIT_TRACKING.md` if public-facing

### Modifying Agent Profile
See `API_VISIT_TRACKING.md` for document tracking system. When editing agent fields, ensure:
- Document schema changes in migrations
- Update AgentProfileController
- Update Profile/AgentProfile Vue components
- Test file upload/download functionality
- Add activity logging for profile updates

## Important Notes

### Email Notifications
- Use Mailable classes in `app/Mail/`
- Examples: `AgentRegisteredNotification`, `PayoutPaidNotification`, `PayoutRequestNotification`
- Handle email failures gracefully in controllers

### File Downloads
File downloads use cache-busting helper function. Check existing implementations before adding new download routes.

### Validation
- Validate at system boundaries (user input, external APIs)
- Trust internal code and framework guarantees
- Use Laravel's validation in form requests

## Docker

See `DOCKER.md` for complete Docker documentation including:
- Development MySQL setup (macOS)
- Production multi-service setup (Nginx, PHP-FPM, MySQL, Redis)
- Building and deploying with Docker
- Troubleshooting and optimization

Quick production build:
```bash
docker build -t penurwill:latest .
docker-compose -f docker-compose.prod.yml up -d
```

## Related Documentation

- **Docker Guide**: See `DOCKER.md` for containerization documentation
- **API Tracking**: See `API_VISIT_TRACKING.md` for full agent tracking API documentation
- **Cursor Rules**: See `.cursorrules` for additional development guidelines
- **Laravel Docs**: https://laravel.com/docs/12.x
- **Vue 3 Docs**: https://vuejs.org
- **Inertia.js Docs**: https://inertiajs.com

## Coding Guidelines
- Follow PSR-12 coding standards for PHP
- Use consistent naming conventions (camelCase for variables, StudlyCase for classes)
- Write clear, descriptive @phpdoc comments for all methods unless the code is self-explanatory
- Use meaningful variable and method names that convey intent
- Avoid deep nesting; refactor into smaller methods if necessary
- Handle exceptions gracefully and log errors for debugging
- Write tests for new features and bug fixes to ensure reliability
- Follow existing design system, @routes/web.php#42-44

<!-- gitnexus:start -->
# GitNexus — Code Intelligence

This project is indexed by GitNexus as **LPS_penurwill_agent** (4707 symbols, 8789 relationships, 245 execution flows). Use the GitNexus MCP tools to understand code, assess impact, and navigate safely.

> If any GitNexus tool warns the index is stale, run `npx gitnexus analyze` in terminal first.

## Always Do

- **MUST run impact analysis before editing any symbol.** Before modifying a function, class, or method, run `gitnexus_impact({target: "symbolName", direction: "upstream"})` and report the blast radius (direct callers, affected processes, risk level) to the user.
- **MUST run `gitnexus_detect_changes()` before committing** to verify your changes only affect expected symbols and execution flows.
- **MUST warn the user** if impact analysis returns HIGH or CRITICAL risk before proceeding with edits.
- When exploring unfamiliar code, use `gitnexus_query({query: "concept"})` to find execution flows instead of grepping. It returns process-grouped results ranked by relevance.
- When you need full context on a specific symbol — callers, callees, which execution flows it participates in — use `gitnexus_context({name: "symbolName"})`.

## Never Do

- NEVER edit a function, class, or method without first running `gitnexus_impact` on it.
- NEVER ignore HIGH or CRITICAL risk warnings from impact analysis.
- NEVER rename symbols with find-and-replace — use `gitnexus_rename` which understands the call graph.
- NEVER commit changes without running `gitnexus_detect_changes()` to check affected scope.

## Resources

| Resource | Use for |
|----------|---------|
| `gitnexus://repo/LPS_penurwill_agent/context` | Codebase overview, check index freshness |
| `gitnexus://repo/LPS_penurwill_agent/clusters` | All functional areas |
| `gitnexus://repo/LPS_penurwill_agent/processes` | All execution flows |
| `gitnexus://repo/LPS_penurwill_agent/process/{name}` | Step-by-step execution trace |

## CLI

| Task | Read this skill file |
|------|---------------------|
| Understand architecture / "How does X work?" | `.claude/skills/gitnexus/gitnexus-exploring/SKILL.md` |
| Blast radius / "What breaks if I change X?" | `.claude/skills/gitnexus/gitnexus-impact-analysis/SKILL.md` |
| Trace bugs / "Why is X failing?" | `.claude/skills/gitnexus/gitnexus-debugging/SKILL.md` |
| Rename / extract / split / refactor | `.claude/skills/gitnexus/gitnexus-refactoring/SKILL.md` |
| Tools, resources, schema reference | `.claude/skills/gitnexus/gitnexus-guide/SKILL.md` |
| Index, status, clean, wiki CLI commands | `.claude/skills/gitnexus/gitnexus-cli/SKILL.md` |

<!-- gitnexus:end -->
