# Agent Rename Scheme

Configurable role labels. Admin can rename the three agent-hierarchy roles across all UI surfaces without code changes.

## Roles

| Internal key | Default label | SystemSetting column |
|---|---|---|
| `agent` | Agent | `role_name_agent` |
| `agent_leader` | Leader | `role_name_leader` |
| `business_partner` | Business Partner | `role_name_business_partner` |

Internal keys (`agents.agent_role` enum, Spatie role names, permission strings) never change. Only display labels.

## Backend

### Source of truth

`system_settings` table, columns `role_name_agent`, `role_name_leader`, `role_name_business_partner`. Edited at `/admin/system-settings/update`. Validated in [SystemSettingController::update()](../../app/Http/Controllers/Admin/SystemSettingController.php#L47-L50) (`sometimes|string|max:100`).

### Global Inertia share

[HandleInertiaRequests](../../app/Http/Middleware/HandleInertiaRequests.php#L60) loads `SystemSetting::first()` per request and shares as `page.props.systemSettings.role_name_*` with English fallbacks.

### Backend-rendered labels

Where labels are baked into payload (e.g. org-chart node `title`), controller must read `SystemSetting::first()` and map `agent_role` → label. Pattern:

```php
$settings = SystemSetting::first();
$labels = [
    'agent' => $settings->role_name_agent ?? 'Agent',
    'agent_leader' => $settings->role_name_leader ?? 'Leader',
    'business_partner' => $settings->role_name_business_partner ?? 'Business Partner',
];
$title = strtoupper($labels[$agent->agent_role] ?? $agent->agent_role);
```

Applied in:
- [AgentController::hierarchy()](../../app/Http/Controllers/Admin/AgentController.php#L838)
- [Agent\HierarchyController::index()](../../app/Http/Controllers/Agent/HierarchyController.php#L14)

## Frontend

### Composable

[`resources/js/composables/useRoleNames.js`](../../resources/js/composables/useRoleNames.js) — single source for UI labels.

```js
import { useRoleNames } from '../../composables/useRoleNames.js'

const { roleNames, roleNamesPlural, roleLabel } = useRoleNames()
// roleNames.value.agent           → "Agent" (or configured override)
// roleNamesPlural.value.agent     → "Agents" (naive +"s")
// roleLabel('agent_leader')       → "Leader"
```

Returns `computed` refs reacting to `page.props.systemSettings`. Plural form is naive `label + "s"`.

### Usage rules

- Templates: bind via `{{ roleNames.agent }}` (Vue auto-unwraps refs).
- Script computed/JS expressions: use `.value` → `roleNames.value.agent`.
- Static arrays (breadcrumbs, tabs) containing labels MUST be wrapped in `computed(() => [...])` to react.

### Files using composable

| File | Surface |
|---|---|
| `resources/js/Pages/Design/Components/Sidebar.vue` | Admin menu items, profile label |
| `resources/js/Pages/Admin/AgentsList.vue` | Page title |
| `resources/js/Pages/Design/Components/AgentsTable.vue` | Table header (uses local `roleNames` block, equivalent) |
| `resources/js/Pages/RegisterAsAgent.vue` | Step labels, info text, summary |
| `resources/js/Pages/Agent/Profile.vue` | Page title, edit button |
| `resources/js/Pages/Agent/ProfileEdit.vue` | Page title |
| `resources/js/Pages/Agent/PaymentComplete.vue` | Breadcrumb |
| `resources/js/Pages/Agent/Inbox.vue` | Breadcrumb |
| `resources/js/Pages/Agent/Referral.vue` | Breadcrumb |
| `resources/js/Pages/Admin/AgentHierarchy.vue` | Title, breadcrumb |
| `resources/js/Pages/Agent/AgentHierarchy.vue` | Breadcrumb |

### Intentionally NOT renamed

- `Sidebar.vue` line 19 (`Admin` / `Partner` / `Agent` dashboard header) — system word for which dashboard, not a configurable role.
- `SystemSettings.vue` / `SystemSettingsUpdate.vue` form-field captions — they ARE the editor.
- Files that already inline the pattern `computed(() => ({ agent: page.props.systemSettings?.role_name_agent || 'Agent', ... }))` — out of scope; safe but unrefactored.

## Adding new surface

1. Import composable: `import { useRoleNames } from '<path>/composables/useRoleNames.js'`.
2. Destructure: `const { roleNames, roleNamesPlural } = useRoleNames()`.
3. Replace hardcoded `"Agent"` / `"Leader"` / `"Business Partner"` with `roleNames.agent` etc.
4. If label sits inside a static array, wrap array in `computed(() => [...])`.
5. Backend-emitted labels: map in controller using `SystemSetting::first()` (see pattern above).

## Verification

1. Login admin → `/admin/system-settings/update` → set labels e.g. `"Sales Rep"`, `"Team Lead"`, `"Regional Partner"` → save.
2. Hard-refresh. Check:
   - Sidebar menu items
   - `/admin/agents/list?type=...` titles
   - `/admin/agent/hierarchy` node chips show `SALES REP` / `TEAM LEAD` / `REGIONAL PARTNER`
   - `/agent/hierarchy` same
   - `/agent/profile`, `/agent/inbox`, `/agent/referral`, `/agent/payment/complete` breadcrumbs
   - `/register-as-agent` step labels + info text
3. Reset labels to blank → English defaults reappear.

## Constraints

- Internal keys (`agent_role` values, role names in Spatie, permission strings) stay English. Labels only.
- Plural is naive `+"s"`. Non-English locales requiring different pluralization need a separate scheme.
- `SystemSetting::first()` is called per request via middleware. Backend code reading it for label maps should reuse the same call where practical, not requery.
