# Todo: Editable Email Templates

> Full audit of every `app/Mail/*` class and `resources/views/emails/*` blade file.
> Single source of truth: `config/mail_templates.php`

---

## 1. Complete Email Audit

### Legend
- **Layout**: `custom-html` = hand-written inline-styled HTML table layout · `x-mail` = uses Laravel `<x-mail::message>` Markdown component · `stub` = minimal placeholder HTML

---

### 1.1 Agent Registration & Onboarding

| # | Ref / Slug | Mailable Class | Blade View | Layout | Constructor args |
|---|---|---|---|---|---|
| 1 | `agent-registered-notification` | `AgentRegisteredNotification` | `agent-registered-notification.blade.php` | custom-html | `Agent $agent` |
| 2 | `account-created-notification` | `AccountCreatedNotification` | `account-created-notification.blade.php` | stub | `User $user, Agent $agent` |
| 3 | `account-created-by-admin-notification` | `AccountCreatedByAdminNotification` | `account-created-by-admin-notification.blade.php` | stub | `User $user, Agent $agent, string $temporaryPassword` |
| 4 | `email-verification-code` | `EmailVerificationCode` | `email-verification-code.blade.php` | custom-html | `RegistrationVerification $verification` |

### 1.2 Agent Lifecycle

| # | Ref / Slug | Mailable Class | Blade View | Layout | Constructor args |
|---|---|---|---|---|---|
| 5 | `agent-renewal-reminder` | `AgentRenewalReminderNotification` | `agent-renewal-reminder.blade.php` | x-mail | `Agent $agent` |
| 6 | `agent-expiry-alert` | `AgentExpiryAlertNotification` | `agent-expiry-alert.blade.php` | x-mail | `Agent $agent` |
| 7 | `suspension-appeal-notification` | `SuspensionAppealNotification` | `suspension-appeal-notification.blade.php` | stub | `Agent $agent, string $message` |

### 1.3 Commissions

| # | Ref / Slug | Mailable Class | Blade View | Layout | Constructor args |
|---|---|---|---|---|---|
| 8 | `commission-earned` | `CommissionEarnedNotification` | `commission-earned.blade.php` | x-mail | `Commission $commission` |
| 9 | `commission-paid` | `CommissionPaidNotification` | `commission-paid.blade.php` | x-mail | `Commission $commission` |

### 1.4 Payouts

| # | Ref / Slug | Mailable Class | Blade View | Layout | Constructor args |
|---|---|---|---|---|---|
| 10 | `payout-request-notification` | `PayoutRequestNotification` | `payout-request-notification.blade.php` | custom-html | `Payout $payout` |
| 11 | `payout-paid-notification` | `PayoutPaidNotification` | `payout-paid-notification.blade.php` | custom-html | `Payout $payout` |
| 12 | `payout-cancelled-notification` | `PayoutCancelledNotification` | `payout-cancelled-notification.blade.php` | stub | `Payout $payout` |

### 1.5 Inbox & System

| # | Ref / Slug | Mailable Class | Blade View | Layout | Constructor args |
|---|---|---|---|---|---|
| 13 | `inbox-notification` | `InboxNotificationEmail` | `inbox-notification.blade.php` | custom-html | `AgentNotification $notification` |
| 14 | `team-invitation` | *(Jetstream built-in)* | `team-invitation.blade.php` | x-mail (component) | `$invitation` |

> **Note:** `team-invitation` is Jetstream-owned. **Excluded** from the editable templates system.

---

## 2. Central Registry: `config/mail_templates.php`

**All template metadata lives in one config file.** Config is the schema; DB stores admin-edited content.

The config is keyed by `ref` slug. Each entry defines:
- `title` — default subject line (with placeholders)
- `required_vars` — array of placeholder names the Mailable must supply
- `preview_vars` — sample values for the admin preview
- `messages` — **object keyed by `var_name`**, each defining `label`, `type`, and `default`

### Key design: messages keyed by var_name

Messages are an **associative array** keyed by `var_name`, NOT a numeric array. This means:
- DB stores: `{ "body_with_partner": { "type": "quill", "content": "..." }, "footer_message": { ... } }`
- Form submits: `form.messages.body_with_partner`, `form.messages.footer_message`
- Blade reads: `$template->getFilled('body_with_partner')`

```php
<?php
// config/mail_templates.php

return [

    /*
    |--------------------------------------------------------------------------
    | #1 — Agent Registered Notification
    |--------------------------------------------------------------------------
    */
    'agent-registered-notification' => [
        'title' => 'New Agent Registered - [AGENT_NAME]',
        'required_vars' => [
            'AGENT_NAME',
            'PARTNER_COMPANY_NAME',
            'CONFIG_APP_NAME',
        ],
        'preview_vars' => [
            'AGENT_NAME'            => 'John Doe',
            'PARTNER_COMPANY_NAME'  => 'Acme Corp Sdn Bhd',
            'CONFIG_APP_NAME'       => 'Penurwill',
        ],
        'messages' => [
            'body_with_partner' => [
                'label'   => 'Body — with partner',
                'type'    => 'quill',
                'default' => 'A new agent has been registered under [PARTNER_COMPANY_NAME].',
            ],
            'body_no_partner' => [
                'label'   => 'Body — no partner',
                'type'    => 'quill',
                'default' => 'A new agent has been registered.',
            ],
            'footer_message' => [
                'label'   => 'Footer message',
                'type'    => 'text',
                'default' => 'Please login to [CONFIG_APP_NAME] to view more details about this agent.',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | #2 — Account Created Notification
    |--------------------------------------------------------------------------
    */
    'account-created-notification' => [
        'title' => 'Your Agent Account Has Been Created',
        'required_vars' => [
            'AGENT_NAME',
            'CONFIG_APP_NAME',
        ],
        'preview_vars' => [
            'AGENT_NAME'      => 'John Doe',
            'CONFIG_APP_NAME' => 'Penurwill',
        ],
        'messages' => [
            'body_main' => [
                'label'   => 'Main body',
                'type'    => 'quill',
                'default' => 'Your agent account has been created on [CONFIG_APP_NAME]. Please log in to view full details.',
            ],
            'footer_message' => [
                'label'   => 'Footer message',
                'type'    => 'text',
                'default' => 'This is an automated message.',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | #3 — Account Created By Admin Notification
    |--------------------------------------------------------------------------
    */
    'account-created-by-admin-notification' => [
        'title' => 'Your Agent Account Has Been Created',
        'required_vars' => [
            'AGENT_NAME',
            'TEMP_PASSWORD',
            'CONFIG_APP_NAME',
        ],
        'preview_vars' => [
            'AGENT_NAME'      => 'John Doe',
            'TEMP_PASSWORD'   => 'TempPass123!',
            'CONFIG_APP_NAME' => 'Penurwill',
        ],
        'messages' => [
            'body_main' => [
                'label'   => 'Main body',
                'type'    => 'quill',
                'default' => 'Your agent account has been created by an administrator on [CONFIG_APP_NAME]. Your temporary password is shown below. Please change it after your first login.',
            ],
            'body_password' => [
                'label'   => 'Password section',
                'type'    => 'text',
                'default' => 'Your temporary password: [TEMP_PASSWORD]',
            ],
            'footer_message' => [
                'label'   => 'Footer message',
                'type'    => 'text',
                'default' => 'This is an automated message.',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | #4 — Email Verification Code
    |--------------------------------------------------------------------------
    */
    'email-verification-code' => [
        'title' => 'Your Verification Code',
        'required_vars' => [
            'VERIFICATION_CODE',
            'CONFIG_APP_NAME',
        ],
        'preview_vars' => [
            'VERIFICATION_CODE' => '482910',
            'CONFIG_APP_NAME'   => 'Penurwill',
        ],
        'messages' => [
            'body_main' => [
                'label'   => 'Main body',
                'type'    => 'text',
                'default' => 'Use the code below to verify your email address. It expires in 15 minutes.',
            ],
            'body_ignore' => [
                'label'   => 'Ignore notice',
                'type'    => 'text',
                'default' => 'If you did not request this, you can safely ignore this email.',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | #5 — Agent Renewal Reminder
    |--------------------------------------------------------------------------
    */
    'agent-renewal-reminder' => [
        'title' => 'Your Penurwill membership renewal is coming up',
        'required_vars' => [
            'AGENT_NAME',
            'EXPIRES_AT',
            'CONFIG_APP_NAME',
            'CONFIG_APP_URL',
        ],
        'preview_vars' => [
            'AGENT_NAME'      => 'John Doe',
            'EXPIRES_AT'      => '2026-07-15',
            'CONFIG_APP_NAME' => 'Penurwill',
            'CONFIG_APP_URL'  => 'https://app.penurwill.com',
        ],
        'messages' => [
            'body_main' => [
                'label'   => 'Main body',
                'type'    => 'quill',
                'default' => 'Hi [AGENT_NAME], your Penurwill membership expires on [EXPIRES_AT]. Please log in to renew before the expiry date to keep earning commissions.',
            ],
            'button_label' => [
                'label'   => 'Button text',
                'type'    => 'text',
                'default' => 'View your profile',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | #6 — Agent Expiry Alert
    |--------------------------------------------------------------------------
    */
    'agent-expiry-alert' => [
        'title' => 'Action required: your Penurwill membership expires today',
        'required_vars' => [
            'AGENT_NAME',
            'EXPIRES_AT',
            'CONFIG_APP_NAME',
            'CONFIG_APP_URL',
        ],
        'preview_vars' => [
            'AGENT_NAME'      => 'John Doe',
            'EXPIRES_AT'      => '2026-06-13',
            'CONFIG_APP_NAME' => 'Penurwill',
            'CONFIG_APP_URL'  => 'https://app.penurwill.com',
        ],
        'messages' => [
            'body_main' => [
                'label'   => 'Main body',
                'type'    => 'quill',
                'default' => 'Hi [AGENT_NAME], your Penurwill membership expires today ([EXPIRES_AT]). Please renew immediately to avoid suspension of your account.',
            ],
            'button_label' => [
                'label'   => 'Button text',
                'type'    => 'text',
                'default' => 'Renew now',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | #7 — Suspension Appeal Notification
    |--------------------------------------------------------------------------
    */
    'suspension-appeal-notification' => [
        'title' => 'Suspension Appeal — [AGENT_NAME]',
        'required_vars' => [
            'AGENT_NAME',
            'APPEAL_MESSAGE',
            'CONFIG_APP_NAME',
        ],
        'preview_vars' => [
            'AGENT_NAME'      => 'John Doe',
            'APPEAL_MESSAGE'  => 'I believe my account was suspended in error. Please review my case.',
            'CONFIG_APP_NAME' => 'Penurwill',
        ],
        'messages' => [
            'body_main' => [
                'label'   => 'Main body',
                'type'    => 'quill',
                'default' => 'Agent [AGENT_NAME] has submitted a suspension appeal.',
            ],
            'body_appeal' => [
                'label'   => 'Appeal message section',
                'type'    => 'text',
                'default' => 'Appeal message: [APPEAL_MESSAGE]',
            ],
            'footer_message' => [
                'label'   => 'Footer message',
                'type'    => 'text',
                'default' => 'Please log in to [CONFIG_APP_NAME] to review and take action.',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | #8 — Commission Earned
    |--------------------------------------------------------------------------
    */
    'commission-earned' => [
        'title' => 'You earned a new commission',
        'required_vars' => [
            'COMMISSION_AMOUNT',
            'COMMISSION_TYPE',
            'SALE_ID',
            'CONFIG_APP_NAME',
            'CONFIG_APP_URL',
        ],
        'preview_vars' => [
            'COMMISSION_AMOUNT' => '150.00',
            'COMMISSION_TYPE'   => 'own_sales',
            'SALE_ID'           => '42',
            'CONFIG_APP_NAME'   => 'Penurwill',
            'CONFIG_APP_URL'    => 'https://app.penurwill.com',
        ],
        'messages' => [
            'body_main' => [
                'label'   => 'Main body',
                'type'    => 'quill',
                'default' => 'You earned a [COMMISSION_TYPE] commission of RM [COMMISSION_AMOUNT] from sale #[SALE_ID].',
            ],
            'button_label' => [
                'label'   => 'Button text',
                'type'    => 'text',
                'default' => 'View commissions',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | #9 — Commission Paid
    |--------------------------------------------------------------------------
    */
    'commission-paid' => [
        'title' => 'Your commission has been paid',
        'required_vars' => [
            'COMMISSION_AMOUNT',
            'PAID_AT',
            'CONFIG_APP_NAME',
            'CONFIG_APP_URL',
        ],
        'preview_vars' => [
            'COMMISSION_AMOUNT' => '150.00',
            'PAID_AT'           => '2026-06-13 10:30:00',
            'CONFIG_APP_NAME'   => 'Penurwill',
            'CONFIG_APP_URL'    => 'https://app.penurwill.com',
        ],
        'messages' => [
            'body_main' => [
                'label'   => 'Main body',
                'type'    => 'quill',
                'default' => 'Your commission of RM [COMMISSION_AMOUNT] has been paid on [PAID_AT].',
            ],
            'button_label' => [
                'label'   => 'Button text',
                'type'    => 'text',
                'default' => 'View payouts',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | #10 — Payout Request Notification
    |--------------------------------------------------------------------------
    */
    'payout-request-notification' => [
        'title' => 'New Payout Request - [AGENT_NAME]',
        'required_vars' => [
            'PAYOUT_ID',
            'AGENT_NAME',
            'PAYOUT_AMOUNT',
            'PAYOUT_CREATED_AT',
            'CONFIG_APP_NAME',
        ],
        'preview_vars' => [
            'PAYOUT_ID'         => '101',
            'AGENT_NAME'        => 'John Doe',
            'PAYOUT_AMOUNT'     => '500.00',
            'PAYOUT_CREATED_AT' => '13 Jun 2026, 10:30 AM',
            'CONFIG_APP_NAME'   => 'Penurwill',
        ],
        'messages' => [
            'body_main' => [
                'label'   => 'Main body',
                'type'    => 'quill',
                'default' => 'A new payout request has been submitted and requires your attention.',
            ],
            'footer_message' => [
                'label'   => 'Footer message',
                'type'    => 'text',
                'default' => 'Please review and process this payout request at your earliest convenience.',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | #11 — Payout Paid Notification
    |--------------------------------------------------------------------------
    */
    'payout-paid-notification' => [
        'title' => 'Payout Processed - RM [PAYOUT_AMOUNT]',
        'required_vars' => [
            'PAYOUT_ID',
            'PAYOUT_AMOUNT',
            'PAYOUT_PAID_AT',
            'CONFIG_APP_NAME',
        ],
        'preview_vars' => [
            'PAYOUT_ID'       => '101',
            'PAYOUT_AMOUNT'   => '500.00',
            'PAYOUT_PAID_AT'  => '13 Jun 2026, 10:30 AM',
            'CONFIG_APP_NAME' => 'Penurwill',
        ],
        'messages' => [
            'body_main' => [
                'label'   => 'Main body',
                'type'    => 'quill',
                'default' => 'Your payout has been processed and paid successfully.',
            ],
            'body_notes' => [
                'label'   => 'Additional notes',
                'type'    => 'text',
                'default' => 'The funds have been transferred to your registered bank account. If you have any questions or concerns, please contact our support team.',
            ],
            'body_bank_file' => [
                'label'   => 'Bank file notice',
                'type'    => 'text',
                'default' => 'Note: A bank transfer file is available for download in your payout details.',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | #12 — Payout Cancelled Notification
    |--------------------------------------------------------------------------
    */
    'payout-cancelled-notification' => [
        'title' => 'Payout Request Cancelled',
        'required_vars' => [
            'PAYOUT_ID',
            'AGENT_NAME',
            'PAYOUT_AMOUNT',
            'CONFIG_APP_NAME',
        ],
        'preview_vars' => [
            'PAYOUT_ID'       => '101',
            'AGENT_NAME'      => 'John Doe',
            'PAYOUT_AMOUNT'   => '500.00',
            'CONFIG_APP_NAME' => 'Penurwill',
        ],
        'messages' => [
            'body_main' => [
                'label'   => 'Main body',
                'type'    => 'quill',
                'default' => 'Your payout request #[PAYOUT_ID] for RM [PAYOUT_AMOUNT] has been cancelled.',
            ],
            'body_notes' => [
                'label'   => 'Additional notes',
                'type'    => 'text',
                'default' => 'If you believe this was done in error, please contact support or submit a new payout request.',
            ],
            'footer_message' => [
                'label'   => 'Footer message',
                'type'    => 'text',
                'default' => 'This is an automated notification from [CONFIG_APP_NAME].',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | #13 — Inbox Notification
    |--------------------------------------------------------------------------
    */
    'inbox-notification' => [
        'title' => '[NOTIFICATION_SUBJECT]',
        'required_vars' => [
            'NOTIFICATION_SUBJECT',
            'NOTIFICATION_BODY',
            'CONFIG_APP_NAME',
            'CONFIG_APP_URL',
        ],
        'preview_vars' => [
            'NOTIFICATION_SUBJECT' => 'Important Update',
            'NOTIFICATION_BODY'    => 'You have a new message in your inbox.',
            'CONFIG_APP_NAME'      => 'Penurwill',
            'CONFIG_APP_URL'       => 'https://app.penurwill.com',
        ],
        'messages' => [
            'body_main' => [
                'label'   => 'Main body',
                'type'    => 'text',
                'default' => '[NOTIFICATION_BODY]',
            ],
            'button_label' => [
                'label'   => 'Button text',
                'type'    => 'text',
                'default' => 'View in Inbox',
            ],
            'footer_message' => [
                'label'   => 'Footer message',
                'type'    => 'text',
                'default' => 'This is an automated notification from [CONFIG_APP_NAME]. Please do not reply to this email.',
            ],
        ],
    ],

];
```

> **`team-invitation`** is Jetstream-owned — excluded from registry.

---

## 3. How the Config Drives Everything

### 3.1 DB stores messages as flat object `{ var_name: "content" }`

The `messages` JSON column is a **flat key→value map**. The `type` info (quill vs text) lives only in `config/mail_templates.php` — no duplication in the DB.

```json
{
  "body_with_partner": "A new agent has been registered under [PARTNER_COMPANY_NAME].",
  "body_no_partner":   "A new agent has been registered.",
  "footer_message":    "Please login to [CONFIG_APP_NAME] to view more details."
}
```

### 3.2 Seeder reads config to populate DB
```php
// database/seeders/TemplateEmailSeeder.php
foreach (config('mail_templates') as $ref => $spec) {
    $messages = [];
    foreach ($spec['messages'] as $varName => $msgSpec) {
        $messages[$varName] = $msgSpec['default'];
    }

    TemplateEmail::updateOrCreate(
        ['ref' => $ref],
        [
            'title'    => $spec['title'],
            'messages' => $messages,
        ]
    );
}
```

### 3.3 Edit form builds fields keyed by var_name
The controller passes the config registry alongside the DB row:
```php
// TemplateEmailController@edit
$template = TemplateEmail::findOrFail($id);
$registry = config("mail_templates.{$template->ref}");

return Inertia::render('Admin/EmailTemplateEdit', [
    'template' => $template,
    'registry' => $registry,
]);
```

The Vue form iterates `registry.messages` (an object) to build named fields:
```vue
<div v-for="(spec, varName) in registry.messages" :key="varName">
  <label>{{ spec.label }} <code class="text-xs">messages.{{ varName }}</code></label>

  <!-- Quill.js for type=quill -->
  <QuillEditor
    v-if="spec.type === 'quill'"
    v-model="form.messages[varName]"
  />
  <!-- Textarea for type=text -->
  <textarea
    v-else
    v-model="form.messages[varName]"
  />

  <!-- Show available placeholders -->
  <p class="text-xs text-gray-500">
    Available: {{ registry.required_vars.map(v => `[${v}]`).join(', ') }}
  </p>
</div>
```

**Submitted payload** looks like:
```json
{
  "title": "New Agent Registered - [AGENT_NAME]",
  "messages": {
    "body_with_partner": "A new agent has been registered under [PARTNER_COMPANY_NAME].",
    "body_no_partner":   "A new agent has been registered.",
    "footer_message":    "Please login to [CONFIG_APP_NAME] to view more details."
  }
}
```

### 3.4 Model reads by var_name key
```php
// In blade:
{!! $template->getFilled('body_with_partner') !!}
{!! $template->getFilled('footer_message') !!}

// Model method:
public function getFilled(string $varName, string $default = ''): string
{
    $messages = $this->filled_messages ?? $this->messages;
    return $messages[$varName] ?? $default;
}
```

### 3.5 Model validates vars from config
```php
public function getMissingVars(array $suppliedVars): array
{
    $requiredVars = config("mail_templates.{$this->ref}.required_vars", []);
    return array_diff($requiredVars, array_keys($suppliedVars));
}
```

### 3.6 Preview uses config preview_vars
```php
// TemplateEmailController@preview
$registry    = config("mail_templates.{$template->ref}");
$previewVars = $registry['preview_vars'] ?? [];
$template->fillData($previewVars);
$missingVars = $template->getMissingVars($previewVars);
```

---

## 4. Preview — Missing Variable Flagging

The preview controller (`TemplateEmailController@preview`) will:

1. Load the template row from the database.
2. Load `preview_vars` from `config('mail_templates.{ref}.preview_vars')`.
3. Call `fillData($previewVars)` on the template.
4. Call `getMissingVars($previewVars)` — compares against `config(required_vars)`.
5. **After replacement**, also scan the compiled HTML for any remaining `[UPPERCASE_PLACEHOLDER]` patterns (catches vars used in content but missing from both config and supplied data).
6. If any missing vars found → inject a **visible warning banner** at the top of the preview HTML:

```html
<div style="background:#fef3cd;border:2px solid #d4423f;padding:12px 16px;margin-bottom:16px;border-radius:6px;font-family:sans-serif;">
  <strong style="color:#d4423f;">⚠ Missing Variables:</strong>
  <span style="color:#92400e;">[PARTNER_COMPANY_NAME], [SOME_OTHER_VAR]</span>
</div>
```

7. Return structured JSON response:
```json
{
  "html": "...rendered email...",
  "missing_vars": ["PARTNER_COMPANY_NAME"],
  "all_vars": ["AGENT_NAME", "PARTNER_COMPANY_NAME", "CONFIG_APP_NAME"],
  "filled_vars": ["AGENT_NAME", "CONFIG_APP_NAME"]
}
```

The Vue edit page displays a warning badge next to the preview button when `missing_vars.length > 0`.

---

## 5. Database Schema (`template_emails`)

| Column | Type | Nullable | Description |
|---|---|---|---|
| `id` | `unsignedBigInteger` | No | Primary Key |
| `ref` | `string(100)` | No | Unique slug — must match a key in `config/mail_templates.php` |
| `title` | `string(255)` | No | Email subject (can contain `[PLACEHOLDERS]`) |
| `messages` | `json` | No | Flat object keyed by var_name: `{ "body_main": "content string...", "footer": "..." }` |
| `updated_by` | `foreignId` → `users` | Yes | Last admin editor |
| `created_at` | `timestamp` | Yes | |
| `updated_at` | `timestamp` | Yes | |

> **Note:** `required_vars` and `preview_vars` live in `config/mail_templates.php`, not in the DB. The DB only stores admin-editable content (title + messages). The config is the **schema** definition; the DB row is the **data**.

---

## 6. Model: `App\Models\TemplateEmail`

### Key Methods
- `fillData(array $vars): self` — replaces `[PLACEHOLDER]` in title + all message string values. For `quill`-typed blocks (looked up from config), handles Quill HTML output. Stores result in `$this->filled_messages`.
- `getFilled(string $varName, string $default = ''): string` — returns `filled_messages[$varName]` (a string).
- `getFilledTitle(string $default = ''): string` — returns compiled subject.
- `getMissingVars(array $suppliedVars): array` — reads `config("mail_templates.{$this->ref}.required_vars")`, returns diff.
- `getRegistry(): array` — returns the full config entry for this template's `ref`.
- `static render(string $ref, array $vars, ?string $fallbackSubject, ?array $fallbackMessages): self` — DB lookup with code fallback using config defaults.
- `static sanitizeQuillHtml(string $html): string` — Quill HTML sanitizer and inline styler.

---

## 7. Implementation Tasks Checklist

### Phase 1: Proof of Concept (PoC)

**Backend & Config**
- [✓] Create `config/mail_templates.php` with 1 registry (`payout-paid-notification`)
- [✓] Create migration `create_template_emails_table`
- [✓] Create `App\Models\TemplateEmail` model
- [✓] Implement `fillData()`, `getFilled()`, `getFilledTitle()` (key-based lookup)
- [✓] Implement `getMissingVars()` reading from config
- [✓] Implement `getRegistry()` helper
- [✓] Implement Quill HTML sanitizer/styler
- [✓] Implement `render()` with code fallback (reads config defaults)
- [✓] Create `TemplateEmailSeeder` that reads `config/mail_templates.php`
- [✓] Write Unit Tests for `TemplateEmail` helpers and methods

**Backend Controller & Routes**
- [✓] Create `App\Http\Controllers\Admin\TemplateEmailController`
  - [✓] `index()` — list all templates
  - [✓] `edit($id)` — pass template + registry to Inertia
  - [✓] `update(Request $request, $id)` — validate message keys match config, save + ActivityLog
  - [✓] `preview($id)` — render HTML preview with missing-var banner
- [✓] Add admin routes in `routes/web.php`

**Vue Frontend**
- [✓] Install and setup Quill.js for Phase 1
- [✓] Build `EmailTemplatesIndex.vue` (list page)
- [✓] Build `EmailTemplateEdit.vue` (form builds `messages[varName]` from registry, Quill.js + textarea, preview iframe + missing-var warnings)
- [✓] Add sidebar link in `AdminLayout.vue`

**Mailable Integration (PoC)**
- [✓] `PayoutPaidNotification` + blade

---

### Phase 2: Full Rollout

**Config & Seeder Expansion**
- [✓] Expand `config/mail_templates.php` to include remaining 12 templates
- [✓] Update `TemplateEmailSeeder` to process all templates

**Layout Standardization**
- [✓] Upgrade 4 stub templates to custom inline HTML (account-created, account-created-by-admin, suspension-appeal, payout-cancelled)
- [✓] Convert 4 x-mail templates to custom inline HTML (agent-renewal-reminder, agent-expiry-alert, commission-earned, commission-paid)

**Mailable Integration (Remaining 12)**
- [✓] `AgentRegisteredNotification` + blade
- [✓] `AccountCreatedNotification` + blade
- [✓] `AccountCreatedByAdminNotification` + blade
- [✓] `EmailVerificationCode` + blade
- [✓] `AgentRenewalReminderNotification` + blade
- [✓] `AgentExpiryAlertNotification` + blade
- [✓] `SuspensionAppealNotification` + blade
- [✓] `CommissionEarnedNotification` + blade
- [✓] `CommissionPaidNotification` + blade
- [✓] `PayoutRequestNotification` + blade
- [✓] `PayoutCancelledNotification` + blade
- [✓] `InboxNotificationEmail` + blade

**Full Testing**
- [✓] Run full test suite to ensure no regressions
- [✓] Feature tests for all mailables to ensure rendering works

---

## 8. Observations & Gaps Found

### Stub templates need real content
Templates #2, #3, #7, #12 currently use a **generic stub** body (`"This is a notification from {{ config('app.name') }}..."`). These should be upgraded to full custom-html layouts during the mailable integration phase so admins actually have meaningful paragraphs to edit.

### Mixed layout approaches
- **4 templates** use full custom inline-styled HTML tables (agent-registered, payout-paid, payout-request, inbox).
- **4 templates** use Laravel's `<x-mail::message>` Markdown component (renewal-reminder, expiry-alert, commission-earned, commission-paid).
- **4 templates** are minimal stubs (account-created, account-created-by-admin, suspension-appeal, payout-cancelled).

**Recommendation:** Standardize all templates to the **custom inline-styled HTML** approach during this project. The `x-mail` Markdown component applies its own CSS theme which conflicts with the branded Penurwill design (cream backgrounds, forest-dark headers, gold accents). Converting them to the branded HTML layout also means the preview will be WYSIWYG-accurate.

### `InboxNotificationEmail` is special
The `inbox-notification` template uses `$notification->subject` and `$notification->body` which are **fully dynamic at send time** (admin writes the content per-notification). The editable template here only controls the surrounding chrome text (header label, button label, footer message), not the core body.

### Config is schema, DB is data
The `config/mail_templates.php` file defines the **structure** (what vars exist, what message blocks exist, their types and labels). The `template_emails` DB table stores the **admin-edited content** keyed by the same var_names. The seeder populates initial DB content from config defaults. If a new message block is added to config, re-running the seeder will add it.
