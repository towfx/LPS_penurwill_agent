# Vue Components Reference

Single index of every component in `resources/js/Pages/Design/Components/` and the layouts in `resources/js/Pages/Design/`. Live preview: **`/design/design-01`**.

> Rule of thumb: never re-implement a primitive that already exists here. If a screen needs a slight variation, extend the existing component (props/slots) before forking.

## How to use this doc

- **Available now** — built, used by Design01, safe to import in production pages.
- **Planned reusable cards** — patterns we'll need for Phase 4 + 7. Listed with rationale so they get built once and reused, not re-invented per page.

---

## Available components

Import path: `resources/js/Pages/Design/Components/<Name>.vue` (relative imports from a Page: `../Design/Components/<Name>.vue`).

### Layout chrome

| Component | Use for |
|---|---|
| `Sidebar.vue` | Reference sidebar nav. Production pages compose via `AdminLayout` / `AgentLayout`, not this directly. |
| `Header.vue` | Top app bar with menu toggle + user menu. Same — used through layouts. |
| `PageHeader.vue` | Standard page top: breadcrumb + title + description + right-side `#actions` slot. Drop at top of every Admin/Agent screen. |
| `Breadcrumb.vue` | Trail of links. Used inside `PageHeader` but also standalone. Pass `items: [{ label, href? }]`. |

### Cards

| Component | Use for |
|---|---|
| `Card.vue` | Base white surface with rounded-xl, border, shadow-sm. Wrap any grouped content. |
| `CardHeader.vue` | Top zone of a `Card` — padding + bottom rule. |
| `CardTitle.vue` | Title text inside `CardHeader`. |
| `CardContent.vue` | Body zone of a `Card` with consistent padding. |
| `Showcase.vue` | Card variant used **only** by the Design01 reference (title + description + slot). Do not use in production — use `Card` directly. |
| `StatsCard.vue` | Headline metric tile: title, big value, change %, trend icon, optional progress bar. Icon prop accepts `DollarSign`, `CreditCard`, `Banknote`, `Wallet`, `Receipt`, `Users`, `UserPlus`, `UserCheck`, `ShoppingCart`, `Package`, `FileText`, `Activity`, `TrendingUp`, `TrendingDown`, `Clock`, `CheckCircle`, `AlertCircle`. |

### Form primitives

All form inputs are designed to live inside `FormField.vue`, which renders `label + control + hint/error`.

| Component | Use for |
|---|---|
| `FormField.vue` | Wrap every form control. Props: `label`, `required`, `hint`, `error`. |
| `Input.vue` | Text-style inputs (`type` defaults to `text`; supports `email`, `number`, `password`, etc.). v-model. `invalid`/`disabled` props. |
| `Textarea.vue` | Multi-line text. v-model + `rows`. |
| `Select.vue` | Dropdown. Pass `options: [{ value, label }]` or simple strings. v-model. |
| `Checkbox.vue` | Single boolean toggle with label slot. v-model on `Boolean`. |
| `Radio.vue` | One option in a radio group. Group via shared `name` prop and v-model. |
| `FileInput.vue` | File upload with drop-zone styling, filename preview, "Remove file" button. v-model on `File`. |

### Buttons

| Component | Use for |
|---|---|
| `Button.vue` | All clickable actions. `variant`: `default` (stone-900) · `secondary` (amber-600) · `destructive` (red-600) · `outline` · `ghost` · `link`. `size`: `default` · `sm` · `lg` · `icon`. |

### Status & feedback

| Component | Use for |
|---|---|
| `Badge.vue` | Generic count/label pill. Variants: `default`, `secondary`, `success`, `warning`, `destructive`, `outline`. |
| `StatusBadge.vue` | Entity-state pill backed by the `status-*` classes in `app.css`. Pass `status="pending|approved|processing|paid|active|inactive|suspended|rejected|expired|cancelled"`. |
| `Alert.vue` | Inline static page message (border-left bar). Variants: `default`, `success`, `warning`, `destructive`. |
| `AlertsSection.vue` | Pre-composed list of sample alerts (reference only). |
| `Toast.vue` | Transient flash notification with icon + dismissible. Wire to `$page.props.flash.success / .error`. |
| `Progress.vue` | Linear progress bar; pass `value` 0-100. |
| `Skeleton.vue` | Shimmer placeholder while loading. Props: `width`, `height`. |

### Overlays

| Component | Use for |
|---|---|
| `Modal.vue` | Base dialog. v-model open state. Slots: default body, `#header`, `#footer`. Sizes: `sm`/`md`/`lg`/`xl`. |
| `ConfirmationModal.vue` | Destructive-action confirm pattern (delete/refund/reject). Emits `@confirm`. Defaults to `confirmVariant="destructive"`. |

### Data display

| Component | Use for |
|---|---|
| `Tabs.vue`, `TabsList.vue`, `TabsTrigger.vue`, `TabsContent.vue` | Tab navigation. v-model on `Tabs`; trigger/content matched by `value` prop. |
| `Pagination.vue` | Footer for tables. v-model:current-page; emits `@change`. Renders "Showing X–Y of Z" + numbered pagers with ellipses. |
| `Stepper.vue` | Horizontal step indicator (Pending → Approved → Processing → Paid). Pass `steps: [{ label, meta? }]` + `activeIndex`. |
| `EmptyState.vue` | No-records placeholder for every list screen. Props: `title`, `description`, `icon`. `#action` slot for CTA. |
| `ActivityTimeline.vue` | Reference timeline for activity feed (sample data). Use as starting point for real timelines. |

### Charts

| Component | Use for |
|---|---|
| `LineChart.vue` | Trends over time. |
| `BarChart.vue` | Categorical comparisons. |
| `PieChart.vue` | Share of total. |

### Tables (reference)

These are populated with sample data in Design01. For production, build screen-specific tables using the same styling, then promote shared parts back into `Components/`.

| Component | Use for |
|---|---|
| `UsersTable.vue` | Generic users list reference. |
| `AgentsTable.vue` | Agents list reference. |
| `PartnersTable.vue` | Partners list reference. **Will be removed/repurposed** when Partner module is dropped (QNA-02). |

---

## Layouts

Path: `resources/js/Pages/Design/<Layout>.vue` — each wraps a sidebar + header around a content slot.

| Layout | Use for |
|---|---|
| `AdminLayout.vue` | Default for every page under `Pages/Admin/*`. |
| `AgentLayout.vue` | Default for every page under `Pages/Agent/*` (incl. business_partner agents — QNA-12). |
| `ProfileLayout.vue` | Profile sub-pages (own profile, edit, security). |
| `PartnerLayout.vue` | **Slated for removal** — Partner module is being dropped (QNA-02). Do not build new pages on it. |

---

## Planned reusable cards (build-on-demand)

Phase 4 + 7 surface several recurring card shapes. Adding them to `Components/` now (rather than inlining inside each page) avoids duplication. Listed in priority order — build the first time a Phase 4 page needs them.

### High priority (used by ≥3 pages)

| Card | Where it appears | Intended for |
|---|---|---|
| `BannerCard.vue` | Agent/Dashboard (suspended, rejected, payment-pending), Admin/Dashboard (scheduler stale, failed jobs) | Full-width attention banner with icon + title + description + primary CTA. Distinct from inline `Alert` — sits at the top of a page and drives next action. Variants mirror `Alert` (info/success/warning/error). |
| `InfoCard.vue` | AgentView, PayoutDetail, CommissionDetail | Title + grid of `{ label, value }` rows. Replaces ad-hoc dl/dt blocks scattered across view pages. Slot per row for status badges, links. |
| `MetricBreakdownCard.vue` | Agent/Dashboard (own / override-agent / override-leader tiles), Admin/PayoutsList totals | Grouped sub-metrics inside one card: header label + N stacked rows of `{ label, amount, badge? }`. Smaller than `StatsCard`, denser than `InfoCard`. |
| `TableCard.vue` | Every list page (AgentsList, CommissionsList, PayoutsList, ActivityLog, Inbox, FeePayments, Referral visits) | `Card` shell with header (`title`, optional `#actions` for filter/export/new), `<slot />` for the table body, footer for `Pagination`. Standardises spacing + filter row placement. |

### Medium priority (used by 2 pages)

| Card | Where it appears | Intended for |
|---|---|---|
| `PayoutProgressCard.vue` | Agent/Dashboard, Agent/RequestPayout | "Available to Request: RM X / Min: RM Y" + progress bar + disabled-when-below-threshold CTA (Decision 18). Encapsulates the threshold + tooltip logic. |
| `ReferralCodeCard.vue` | Agent/Dashboard, Agent/Referral | Code chip + copy-to-clipboard button + shareable URL row + optional QR. Single source of the copy-feedback UX. |
| `NotificationRow.vue` | Agent/Inbox (Unread/Pending/Archived tabs) | Inbox row: icon + subject + body excerpt + timestamp + unread dot + checkbox for bulk + per-row action (Mark Read / Archive). |
| `PackageCard.vue` | AgentRegistration step 2 | Selectable package tile (radio behavior): title + price + feature bullets + selected/disabled visual state. |

### Lower priority (page-specific, but still worth a component)

| Card | Where it appears | Intended for |
|---|---|---|
| `SchedulerHealthCard.vue` | Admin/Dashboard | Job name + last-ran timestamp + status badge (`OK / STALE / FAILED`) + "view failed jobs" link. |
| `FeePaymentRow.vue` | Admin/FeePayments | Single fee event row (entry/renewal, amount, recorded_by, date). |
| `WizardStepHeader.vue` | AgentRegistration | Step 1-of-6 indicator above each wizard step (uses `Stepper` underneath but with the wizard's variants). |
| `AgentSummaryCard.vue` | Hierarchy view, AgentView header | Avatar + name + role + status badge + parent link. Reusable in trees and side panels. |
| `EmptyStateInline.vue` | Inside table rows / small cards where the full `EmptyState` is too large | Slimmer one-line "No data yet" pattern. Could just be a prop on `EmptyState` (`size="sm"`) — confirm before forking. |

### Build guidelines

1. **Promote, don't pre-build.** Only create the card when the second page needs it. The first occurrence stays inline; on the second, extract.
2. **Compose, don't duplicate.** Most cards above are `Card` + existing primitives wired together. Avoid inventing new visual styles.
3. **Props over flags.** If you find yourself adding `if (variant === 'foo')` for the third time, the card is doing too much — split it.
4. **Update this doc.** When a planned card ships, move it from this section into the "Available components" tables above.

---

## Section files (reference only)

`resources/js/Pages/Design/Sections/*Showcase.vue` — each file documents one primitive inside Design01. **Never import these into production pages.** They exist to keep `Design01.vue` short and let new sections be added/removed independently.

Current sections: `PageHeaderShowcase`, `ButtonShowcase`, `BadgeShowcase`, `FormShowcase`, `AlertShowcase`, `ModalShowcase`, `EmptyStateShowcase`, `StepperShowcase`, `PaginationShowcase`, `ChartShowcase`, `SkeletonShowcase`, `ColorPaletteShowcase`, `TypographyShowcase`, `IconReferenceShowcase`.

---

## Quick reference

| Need | Reach for |
|---|---|
| New page top | `PageHeader` + `Breadcrumb` |
| New form | `FormField` + (`Input` / `Textarea` / `Select` / `Checkbox` / `Radio` / `FileInput`) |
| Confirm a destructive action | `ConfirmationModal` |
| Show no-data state | `EmptyState` |
| Show entity status | `StatusBadge` |
| Page-level success/error message | `Alert` (static) or `Toast` (flash) |
| Headline metric | `StatsCard` |
| List-screen footer | `Pagination` |
| Lifecycle indicator | `Stepper` |
| Loading placeholder | `Skeleton` |
| Class merging | `cn()` from `resources/js/lib/utils.js` |
| Currency | `formatCurrency('RM', amount)` from `resources/js/lib/utils.js` |
| Icons | `lucide-vue-next` only |
