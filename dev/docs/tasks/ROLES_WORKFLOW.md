# ROLES_WORKFLOW.md
> Business Process & UI/UX Workflow Reference
> Last updated: 2026-05-01 | Status: DRAFT — pending stakeholder approval

---

## 1. Role Definitions

The system has two tiers of roles:

### System Roles (Auth level)
| Role | Description |
|------|-------------|
| `admin` | System operator. Manages all agents, commissions, payouts, settings. |
| `agent` | Field-level user. All three agent sub-roles log in under this system role. |
| `partner` | Legacy role — being deprecated. Migrated to `agent` hierarchy. |

### Agent Sub-Roles (within `agent` system role)
Stored as `agent_role` on the `agents` table. Role names are configurable in System Settings.

| agent_role | Default Display Name | Position in Hierarchy | Earns |
|------------|---------------------|-----------------------|-------|
| `agent` | Agent | Base (leaf node) | Own sales commissions only |
| `agent_leader` | Agent Leader | Mid tier | Own sales + override from Agents below |
| `business_partner` | Business Partner | Top tier | Own sales + override from Leaders + Agents below |

**Hierarchy Tree (example):**
```
Business Partner (BP)
├── Agent Leader A
│   ├── Agent 1
│   └── Agent 2
├── Agent Leader B
│   └── Agent 3
└── Agent 4 (direct under BP)
```

---

## 2. System Entry Points (Public)

```
/                     → redirect → /get-started
/get-started          → Landing page with CTA
/register-as-agent    → Multi-step registration wizard (6 steps)
/register-as-agent/verify-email → Email verification step (standalone page for resumed sessions)
/register-as-agent/payment/success → Stripe success return URL
/register-as-agent/payment/cancelled → Stripe cancel return URL
/register-as-agent/resume → Applicant resumes from dashboard after skipped payment
/login                → Login page
/get-started-guide    → First-login onboarding slide deck (role-adaptive)
/forgot-password      → Forgot password / reset request
/reset-password/{token} → Password reset form (also marks email verified on submit)
```

### Public Registration Steps (URL: `/register-as-agent`)

| Step | Label | Purpose |
|------|-------|---------|
| 1 | Referral ID | Optional referral code check + validation |
| 2 | Package | Choose Agent/Leader (RM 100) or Business Partner (RM 3000) |
| 3 | Your Details | Individual or Company profile + document uploads + login credentials |
| 4 | Email Verify | Verify email with 6-digit code — User + Agent record auto-created on success |
| 5 | T&C + Payment | Accept terms, then Stripe redirect or manual bank transfer + receipt upload |
| 6 | Done | Confirmation screen — or skip payment and auto-login |

---

## 3. Onboarding Workflow

### 3.1 Registration Multi-Step Form — `/register-as-agent`

The public registration form is a **multi-step wizard**. The same form handles all three paths: individual agent, company agent, and business partner applicant. Steps are shown as a progress indicator at the top.

---

#### Step 1 — Referral ID Check

```
┌─────────────────────────────────────────────────────────┐
│  Do you have a Referral ID?                             │
│                                                         │
│   ◉ Yes    ○ No                                         │
│                                                         │
│  [If Yes] Referral ID: [ ________________ ]            │
│           [Validate] → API checks code                  │
│                                                         │
│  Validation outcomes:                                   │
│   ✓ Valid   → show referring agent name (green banner)  │
│   ✗ Invalid → inline error, block proceed              │
│   ✗ Expired → inline error, block proceed              │
│                                                         │
│  [If No]  → continue without upline (system assigns    │
│             default Business Partner as parent)         │
└─────────────────────────────────────────────────────────┘
```

**Backend**: `GET /api/agents/track/code/{code}` — existing endpoint. Returns agent name, active status, expiry.

---

#### Step 2 — Package Selection

```
┌─────────────────────────────────────────────────────────┐
│  Select your registration package:                      │
│                                                         │
│  ┌─────────────────────────┐  ┌────────────────────┐   │
│  │  Agent / Agent Leader   │  │  Business Partner  │   │
│  │                         │  │                    │   │
│  │  Entry Fee: RM 100      │  │  Entry Fee: RM 3000│   │
│  │  (SystemSetting default)│  │  (SystemSetting)   │   │
│  │                         │  │                    │   │
│  │  ○ Select               │  │  ○ Select          │   │
│  └─────────────────────────┘  └────────────────────┘   │
│                                                         │
│  ⚠ Business Partner package requires Company profile   │
└─────────────────────────────────────────────────────────┘
```

**Data source**: Fee amounts pulled live from `SystemSetting` (`entry_fee_agent` / `entry_fee_business_partner`).

**Effect on agent_role**:
- Agent / Agent Leader package → `agent_role = 'agent'` (Admin upgrades to `agent_leader` later)
- Business Partner package → `agent_role = 'business_partner'`

> **Rule**: Business Partner applicants **must** use Company profile (Step 3 enforces this — the Individual option is disabled when BP package is selected).

---

#### Step 3 — Profile Type & Personal Particulars

```
┌─────────────────────────────────────────────────────────┐
│  What type of profile would you like to register?       │
│                                                         │
│  ┌──────────────┐   ┌──────────────────────────┐       │
│  │  Individual  │   │  Company                 │       │
│  │  (person,    │   │  (registered business,   │       │
│  │   no company)│   │   Sdn Bhd, etc.)         │       │
│  └──────────────┘   └──────────────────────────┘       │
│                                                         │
│  [BP package selected → Individual is disabled/greyed]  │
└─────────────────────────────────────────────────────────┘
```

**Path A — Individual profile fields:**

| Field | Required | Notes |
|-------|----------|-------|
| Full Name | ✓ | As per IC |
| NRIC / Passport No. | ✓ | |
| Phone Number | ✓ | |
| Email Address | ✓ | Used for login |
| Home Address | ✓ | Full address |
| IC / Passport Scan | ✓ | Upload (JPG/PNG/PDF, max 5MB) |
| Bank Account Name | ✓ | For payouts |
| Bank Name | ✓ | |
| Bank Account Number | ✓ | |

**Path B — Company profile fields:**

| Field | Required | Notes |
|-------|----------|-------|
| Company Name | ✓ | Registered name |
| Company Registration No. | ✓ | SSM or equivalent |
| Company Address | ✓ | Registered address |
| Company Phone | ✓ | |
| Company Email | ✓ | Used for notifications (CC'd) |
| Representative Name | ✓ | Person acting for company |
| Representative IC / Passport No. | ✓ | |
| Company Registration Doc | ✓ | Upload (PDF/JPG/PNG, max 10MB) |
| Representative IC Scan | ✓ | Upload (JPG/PNG/PDF, max 5MB) |
| Login Email | ✓ | May differ from company email |
| Bank Account Name | ✓ | Company bank account |
| Bank Name | ✓ | |
| Bank Account Number | ✓ | |

---

#### Step 3C — Login Credentials (bottom of Step 3 form)

Displayed at the bottom of the Step 3 form, after identity fields. Applies to both Individual and Company paths.

| Field | Required | Notes |
|-------|----------|-------|
| Login Email | ✓ | Auto-filled from profile email; must be unique in system |
| Password | ✓ | Min 8 characters — shown with visibility toggle |
| Confirm Password | ✓ | Must match password |

> **GAP-01**: Password fields are collected here so the User account can be auto-created immediately after email verification (Step 4). The `email` field is the login credential and must pass a pre-check: if it already exists in `users` table AND has a password set → block with "This email is registered, [Log in]". If email exists but no password or not verified → block with "This email requires a password reset, [Reset Password →]".

**Cookie persistence (GAP-03)**: After Step 3 is filled and the user clicks Next, all form data (excluding the password itself) is stored in a signed cookie (`reg_wizard_state`). If the user navigates away and returns, the form pre-fills from the cookie. Password/confirm fields are never stored in the cookie.

---

#### Step 4 — Email Verification

```
┌─────────────────────────────────────────────────────────┐
│  Verify Your Email Address                              │
│                                                         │
│  We've sent a 6-digit verification code to:            │
│  john@example.com                                       │
│                                                         │
│  Enter code:                                            │
│  [ _ ][ _ ][ _ ] — [ _ ][ _ ][ _ ]                    │
│                                                         │
│  [Verify & Continue]                                    │
│                                                         │
│  [Resend Code] — enabled after 60-second cooldown      │
│  ⏱  Code expires in: 14:32                             │
└─────────────────────────────────────────────────────────┘
```

**Backend logic**:
- On Step 3 Next → generate 6-digit code, store with 15-minute expiry in `registration_verifications` table (keyed by email)
- Send email with subject "Your Penurwill verification code: XXXXXX"
- On code submission → compare, check expiry, mark verified
- **On successful verification**: `User` account + `Agent` record are auto-created (`status = pending`). System sends "Account Created" email with login instructions.
- Cookie wizard state is retained — user can return via `/register-as-agent/resume` from the login page if they get interrupted after this point.

**Re-send logic**: Resend button is disabled for 60 seconds after each send. After 3 failed attempts, the form prompts user to restart.

---

#### Step 5 — Terms & Conditions + Fee Payment

```
┌─────────────────────────────────────────────────────────┐
│  Registration Fee                                       │
│                                                         │
│  Package:   Agent / Agent Leader   Amount: RM 100.00    │
│                                                         │
│  ☐  I have read and agree to the Terms & Conditions    │
│     [View Terms & Conditions ↗]                         │
│                                                         │
│  ── How would you like to pay? ──                       │
│                                                         │
│  ○  Pay via Card (Stripe)                               │
│     [Pay with Stripe →] (redirects to Stripe Checkout) │
│                                                         │
│  ○  Manual Bank Transfer                                │
│     ┌───────────────────────────────────────────────┐  │
│     │  Bank:    Maybank (example)                   │  │
│     │  Account: Penurwill Sdn Bhd                   │  │
│     │  Account No: 1234567890                       │  │
│     │  (pulled from Agent ID 1 bank_account record) │  │
│     └───────────────────────────────────────────────┘  │
│                                                         │
│     Upload Bank Transfer Receipt:                       │
│     [ Choose File ]  (PDF/JPG/PNG, max 5MB)             │
│     Reference / Note (optional): [ _____________ ]     │
│                                                         │
│  [Complete Registration]                                │
│                                                         │
│  ─ or ─                                                 │
│                                                         │
│  [Skip Payment for Now →]                               │
│  (You are already registered. Log in and complete       │
│   payment from your dashboard when ready.)              │
└─────────────────────────────────────────────────────────┘
```

**Stripe path**:
- T&C checkbox must be checked before Stripe button is enabled
- Redirect to Stripe Checkout with pre-filled amount from SystemSetting
- Stripe success URL: `/register-as-agent/payment/success?session_id={CHECKOUT_SESSION_ID}`
- Stripe cancel URL: `/register-as-agent/payment/cancelled`
- On success: Stripe webhook → `fee_payments` record auto-created (`fee_type=entry`, `payment_method=stripe`); `fee_payment_status = 'paid'`
- On cancel: user returned to Step 5 with notice "Payment was cancelled. You can try again or skip."

**Manual bank transfer path**:
- Receipt file stored against the agent record
- `fee_payment_status = 'pending_verification'` (admin must verify before activating)
- Admin sees receipt in `/admin/agents/{id}/view` Fee Status section

**Skip Payment flow (GAP-03)**:
- T&C checkbox must still be checked before skip is allowed
- Clicking [Skip Payment for Now] → system auto-logs in the user (account was created in Step 4)
- Agent remains at `status = pending`, `fee_payment_status = pending`
- Agent dashboard shows a persistent banner: "⚠ Your registration fee is unpaid. [Complete Payment →]"
- Link goes to `/agent/payment/complete` which shows the same payment UI

**Company bank info source**: Pulled from `Agent::find(1)->bankAccount` — the canonical system/company agent record. Admin maintains this via their agent profile.

**GAP-09 — Account email**: When the User + Agent record are auto-created at Step 4 (email verification success), the system sends an "Account Created" email to the agent. This email contains their login URL and confirms their registration is pending admin review.

---

#### Step 6 — Confirmation Screen

```
┌─────────────────────────────────────────────────────────┐
│  ✓ Registration Submitted!                              │
│                                                         │
│  Your application is under review.                      │
│                                                         │
│  What happens next:                                     │
│  1. Our team will verify your documents.               │
│  2. If you paid by bank transfer, we will verify       │
│     your receipt.                                       │
│  3. You will receive an email once approved.           │
│                                                         │
│  You can log in at any time to check your status.      │
│                                                         │
│  [Log In to My Account]   [Back to Home]                │
└─────────────────────────────────────────────────────────┘
```

---

### 3.2 Registration — Email Notifications on Submission

On successful payment confirmation (Step 6) or manual transfer upload, three email jobs are dispatched (in addition to the Account Created email sent at Step 4):

| Recipient | Trigger | Content |
|-----------|---------|---------|
| **Agent ID 1** (system owner / admin) | Always | New agent application received: name, package, profile type, fee payment method |
| **Referring Agent** (if referral code provided) | Only if referral code used | Someone registered using your referral code: applicant name, package |
| **Related Business Partner** (upline BP, if referral code resolves to one) | Only if BP upline exists | New agent registered under your network: applicant name, package |

> The referring agent email and business partner email may be the same person — deduplication applied (QNA-05: CC both `company_email_address` and linked user email).

---

### 3.3 Registration Flow — Full Decision Tree

```
GUEST: /get-started
  └── PRE-CHECK: Email entered → does user exist in system?
        ├── Exists + has password + verified → "Already registered. [Log in →]"
        ├── Exists + no password or not verified → "Reset your password first. [Reset →]"
        └── New email → proceed to registration

  └── [Register as Agent] → /register-as-agent
        │
        ├── STEP 1: Referral ID
        │     ├── Has code → validate → ✓ valid → attach referral_code, parent_agent_id
        │     │                      → ✗ invalid/expired → show error, block
        │     └── No code → parent_agent_id = default BP (Agent ID 1 or seeded BP)
        │
        ├── STEP 2: Package
        │     ├── Agent/Leader (RM 100) → agent_role = 'agent'
        │     └── Business Partner (RM 3000) → agent_role = 'business_partner'
        │                                       → forces Company profile in Step 3
        │
        ├── STEP 3: Profile Type + Fields + Login Credentials
        │     ├── Individual → fill personal particulars + IC upload + email + password
        │     ├── Company   → fill company particulars + reg doc + rep IC upload + login email + password
        │     └── Cookie: wizard state stored (excluding password) for resume
        │
        ├── STEP 4: Email Verification
        │     ├── 6-digit code sent to email → user enters code → verified
        │     │     → ✓ verified → User + Agent record auto-created (status=pending)
        │     │                 → "Account Created" email sent to agent
        │     │                 → wizard cookie updated with agent_id
        │     └── Resend available after 60s | Expires after 15 min
        │
        ├── STEP 5: T&C + Fee Payment
        │     ├── Must check T&C checkbox to proceed
        │     ├── Stripe → Stripe Checkout (success→/payment/success, cancel→/payment/cancelled)
        │     │             └── fee_payment_status = 'paid' immediately via webhook
        │     ├── Manual → upload receipt
        │     │             └── fee_payment_status = 'pending_verification'
        │     └── [Skip Payment] → auto-login, dashboard shows payment banner
        │
        └── STEP 6: Confirmation shown
              └── Emails dispatched: Agent #1 + Referring Agent + BP upline
                    │
                    └── ADMIN: /admin/agents/list → Filter: Pending
                          └── /admin/agents/{id}/view
                                ├── Review documents + receipt (if manual)
                                ├── [Verify Bank Transfer] (if manual payment)
                                │     └── fee_payment_status → 'paid'
                                └── [Approve Agent] → status: active (ALWAYS from Agent#1 / Admin)
                                      └── Admin assigns role upgrade if needed
                                            └── Email sent to agent → can log in

  REJECTED path:
  └── Rejected agent can still log in (account exists)
        └── Dashboard shows: "Your application was rejected. [View Reason]  [Request Approval →]"
              └── [Request Approval] re-triggers admin review workflow

  RESUME path (agent skipped payment):
  └── Agent logs in → sees payment banner on dashboard
        └── [Complete Payment →] → /agent/payment/complete (same payment UI as Step 5)
```

---

### 3.4 Role Assignment & Upgrade (Admin only)

```
ADMIN: /admin/agents/{id}/update
  └─ Agent Role field: Agent | Agent Leader | Business Partner
        └─ Parent Agent field (if Leader/BP): select their upline
              └─ Commission rates (own_sales %, override %)
                    └─ Save → agent_role + parent_agent_id updated
```

> **Rule**: The registration form sets an initial `agent_role` based on package. Upgrades (e.g. Agent → Agent Leader) are admin-only at any time after approval.

---

### 3.5 Fee Collection — Manual Verification Flow

```
ADMIN: /admin/agents/{id}/view
  │
  ├─ Fee Status card: PENDING VERIFICATION (manual transfer) / PAID (Stripe) / UNPAID
  │
  ├─ [Verify Bank Transfer] button (shown only when status = pending_verification)
  │     └─ Modal: confirm amount, reference, notes
  │           └─ fee_payment_status → 'paid'
  │
  └─ [Record Fee Received] button (for offline/waived cases)
        └─ Modal: Amount, Date, Reference Number, Notes, Fee Type (entry/renewal)
              └─ Submit → fee_payments record created
                    └─ fee_payment_status → 'paid'
```

---

### 3.6 Renewal Lifecycle

```
System: renewal_reminder_days_before (config) before expires_at
  └─ Scheduled job sends renewal reminder email to agent
        │
        └─ ADMIN: /admin/agents/list
              └─ Filter: Upcoming Renewals (next 30/60 days)
                    └─ /admin/agents/{id}/view
                          └─ [Record Renewal Fee Received] button
                                └─ Same modal as entry fee
                                      └─ expires_at extended
                                            └─ renewal_due_at recalculated
```

---

## 4. Admin Workflow

### 4.1 Admin Dashboard — `/admin/dashboard`

**Data Cards (top row — existing):**
| Card | Data | Trend |
|------|------|-------|
| Total Revenue This Month | Sum of paid commissions | vs last month % |
| Active Agents | Count (all sub-roles) | vs last month |
| Commissions Paid | Total paid amount | vs last month |
| System Conversion Rate | Referrals → Sales % | vs last period |

**NEW cards to add (second row):**
| Card | Data | Trend |
|------|------|-------|
| Pending Payout Requests | Count + total amount | — |
| Agents by Role | Agent / Leader / BP counts | — |
| Upcoming Renewals | Agents expiring ≤ 30 days | — |
| Fees Outstanding | Agents with unpaid entry fees | — |

**Charts:**
- Monthly Revenue (12-month line chart)
- Top Performing Agents (bar chart)
- Commission Distribution by Type — own_sales vs override (pie chart, NEW)
- Referrals vs Sales (30-day dual chart)

**Quick Actions:**
- [Add Agent] → `/admin/agents/add`
- [View Commissions] → `/admin/commissions/list`
- [Pending Payouts] → `/admin/payouts` (filtered: pending)

---

### 4.2 Agent Management

#### Screen: `/admin/agents/list`

**Filter Bar:**
- Role filter: All | Agent | Agent Leader | Business Partner
- Status filter: All | Active | Pending | Inactive | Suspended
- Search: name, email, referral code

**Table Columns:**
| Column | Notes |
|--------|-------|
| Name | Individual name or Company name |
| Role | Badge: Agent / Leader / BP |
| Status | Badge: Active / Pending / Inactive |
| Parent | Upline agent name (if any) |
| Sales This Month | Sum |
| Commission This Month | Sum |
| Fee Status | Paid / Unpaid badge (NEW) |
| Registered | Date |
| Actions | View · Edit |

**Action Buttons:**
| Button | Action | Leads To |
|--------|--------|---------|
| [Add Agent] | Navigate | `/admin/agents/add` |
| [Export] | Download | `agents.xls` |
| [View] (row) | Navigate | `/admin/agents/{id}/view` |
| [Edit] (row) | Navigate | `/admin/agents/{id}/update` |

---

#### Screen: `/admin/agents/{id}/view`

**Sections:**

**A. Agent Identity**
- Individual: Name, Phone, Email, Address, NRIC/Passport No., [Download IC File]
- Company: Company Name, Rep Name, Reg No., Address, Phone, Email, [Download Reg File]
- About / Bio

**B. Role & Hierarchy (NEW)**
| Field | Display |
|-------|---------|
| Agent Role | Badge (Agent / Leader / BP) |
| Parent Agent | Link to parent's view page |
| Direct Team | Count of agents directly below |
| Total Downline | Count of all agents in subtree |

**C. Fee Status (NEW)**
| Field | Display |
|-------|---------|
| Entry Fee | Amount + Status (Paid/Unpaid) + Date Paid |
| Next Renewal Due | Date (renewal_due_at) |
| Expires At | Date (expires_at) |
| Renewal Fee | Amount |
| Fee Payment History | Table: Date, Amount, Reference, Type (entry/renewal) |

**D. Commission Rates (NEW)**
| Field | Display |
|-------|---------|
| Own Sales Rate | % (from AgentCommissionRate or system default) |
| Override Rate | % (for commissions earned from downline) |
| Rate Source | Custom / System Default |

**E. Bank Account**
- Account Name, Account Number, Bank Name, IBAN, SWIFT Code

**F. Referral Code**
- Code, Commission Rate, Used Count, Active Status, Expires At

**Action Buttons:**
| Button | Condition | Action | Result |
|--------|-----------|--------|--------|
| [Approve Agent] | status ≠ active | POST `/agents/{id}/approve` | status → active |
| [Edit Agent] | always | Navigate | `/admin/agents/{id}/update` |
| [Record Fee Received] | fee unpaid OR renewal due | Modal → POST | fee_payments record created |
| [Download IC/Reg File] | file exists | GET file | Download |

---

#### Screen: `/admin/agents/add` & `/admin/agents/{id}/update`

**Form Sections:**
1. Profile Type: Individual / Company
2. Identity fields (conditional on type)
3. File uploads (IC or Reg Certificate)
4. **Role & Hierarchy (NEW)**
   - Agent Role: Agent | Agent Leader | Business Partner
   - Parent Agent: searchable dropdown (only Leaders/BPs shown)
5. **Commission Rates (NEW)**
   - Own Sales Rate %
   - Override Rate % (shown only if role = Leader or BP)
6. Bank Account details
7. Status

---

### 4.3 Commission Management

#### Screen: `/admin/commissions/list`

**Filter Bar:**
- Month + Year picker
- Role filter: All | Agent | Leader | BP
- Type filter: All | Own Sales | Override (NEW)

**Table Columns:**
| Column | Notes |
|--------|-------|
| Agent | Name + role badge |
| Own Sales Total | Sum of own_sales commissions |
| Override Total | Sum of override commissions (NEW) |
| Grand Total | Combined |
| Status | Pending / Paid breakdown |
| Month | Period |
| Actions | [Detail] |

**Action Buttons:**
| Button | Action |
|--------|--------|
| [Detail] (row) | Navigate → `/admin/commission/detail?agent_id=X&month=Y` |

---

#### Screen: `/admin/commission/detail`

**Header:** Agent Name, Month, Role badge

**Tab 1 — Own Sales**
| Column | Notes |
|--------|-------|
| Date | Sale date |
| Description | Product/service |
| Invoice No. | Reference |
| Sale Amount | RM |
| Rate | % applied |
| Commission | RM |
| Status | Pending/Paid badge |

**Tab 2 — Override (NEW, shown for Leader/BP only)**
| Column | Notes |
|--------|-------|
| Date | Sale date |
| Agent | Who made the sale |
| Sale Amount | RM |
| Override Rate | % |
| Commission | RM |
| Status | Pending/Paid badge |

---

### 4.4 Payout Management

#### Screen: `/admin/payouts`

**Summary Cards:**
| Card | Data |
|------|------|
| Pending Requests | Count + Total Amount |
| Paid This Month | Count + Total Amount |
| All Time Paid | Total Amount |

**Table Columns:**
| Column | Notes |
|--------|-------|
| ID | Payout reference |
| Agent | Name + role badge |
| Items | Count of commissions included |
| Amount | Total RM |
| Status | Pending / Paid badge |
| Requested | Date |
| Paid At | Date (if paid) |
| Actions | [View] |

**Action Buttons:**
| Button | Action |
|--------|--------|
| [Create Payout] | Navigate → `/admin/payout/create` |
| [View] (row) | Navigate → `/admin/payout/{id}` |

---

#### Screen: `/admin/payout/{id}` (Payout Detail)

**Summary Card:**
- Agent Name, Role badge, Requested Date, Total Amount, Status

**Agent Bank Information:**
- Account Name, Number, Bank Name, IBAN, SWIFT Code

**Bank Transfer Section:**
- Current file status (uploaded / not uploaded)
- [Download Bank Transfer] (if file exists)
- Upload input (PDF/JPG/PNG ≤ 5MB)
- [Upload] button

**Payout Items Table:**
| Column | Notes |
|--------|-------|
| Date | Sale/commission date |
| Type | Own Sale / Override badge (NEW) |
| Description | Sale description |
| Sale Amount | RM (blank for override rows) |
| Rate | % |
| Commission | RM |
| Status | Pending → Paid after mark-as-paid |

**Action Buttons:**
| Button | Condition | Action | Result |
|--------|-----------|--------|--------|
| [Upload Bank Transfer] | always | POST upload | File stored |
| [Mark as Paid] | status ≠ paid | POST mark-as-paid | All commissions → paid; email to agent |

---

#### Screen: `/admin/payout/create`

- Agent selector (search)
- Auto-loads all pending commissions for that agent (own_sales + override)
- Checkbox list of commissions to include (Type column visible)
- Summary: total amount, commission count
- [Create Payout] → creates Payout + PayoutItems

---

### 4.5 System Settings

#### Screen: `/admin/system-settings` & `/admin/system-settings/update`

**Existing settings:**
- Default Commission Rate %
- Referral Code Prefix

**NEW settings to add:**

**Role Names (editable labels)**
| Setting | Default |
|---------|---------|
| Agent Role Name (base) | "Agent" |
| Agent Role Name (mid) | "Agent Leader" |
| Agent Role Name (top) | "Business Partner" |

**Commission Rates by Role**
| Setting | Default |
|---------|---------|
| Agent Default Own-Sales Rate % | 10% |
| Agent Leader Override Rate % | 5% |
| Business Partner Override Rate % | 3% |
| Skip Zero Commissions | false |

**Fee Management**
| Setting | Default |
|---------|---------|
| Agent Entry Fee | RM 0 |
| Agent Leader Entry Fee | RM 0 |
| Business Partner Entry Fee | RM 0 |
| Agent Renewal Fee | RM 0 |
| Agent Leader Renewal Fee | RM 0 |
| Business Partner Renewal Fee | RM 0 |
| Renewal Reminder Days Before Expiry | 30 |

---

### 4.6 New Application Review & Agent Promotion

This section covers the two most critical admin responsibilities introduced by the new hierarchy: reviewing incoming registrations and promoting agents up the role ladder.

---

#### 4.6.1 Reviewing New Applications

All new registrations arrive with `status = pending`. Admin must review documents and verify fee payment before activating the agent.

**Entry point:** `/admin/agents/list` → filter by Status = **Pending**

```
Admin: /admin/agents/list
  └── Filter: Status = Pending
        └── Badge on row: fee status (PAID via Stripe | RECEIPT UPLOADED | FEE UNPAID)
              └── [View] → /admin/agents/{id}/view

/admin/agents/{id}/view — Pending Agent View:
  │
  ├─ STATUS BANNER: "Pending Approval" (yellow)
  │
  ├─ A. Identity Check
  │   Individual: Review name, NRIC, address
  │              [Download IC Scan] — open/download file
  │   Company:   Review company name, reg no., rep IC
  │              [Download Reg Document]
  │              [Download Rep IC Scan]
  │
  ├─ B. Package Selected
  │   Shows: "Agent / Leader — RM 100" or "Business Partner — RM 3,000"
  │   Shows: Profile type (Individual / Company)
  │
  ├─ C. Fee Payment Status
  │   ┌──────────────────────────────────────┐
  │   │ Stripe paid:   ✓ PAID via Stripe     │ → no action needed
  │   │ Manual upload: ⚠ RECEIPT UPLOADED    │ → [View Receipt] [Verify Receipt]
  │   │ Not paid:      ✗ NO PAYMENT          │ → [Record Fee Received] (offline)
  │   └──────────────────────────────────────┘
  │
  ├─ D. Referral / Upline Info
  │   Shows: Referring agent name (if code used), or "No referral"
  │   Shows: Assigned parent agent (from referral code, or default BP)
  │
  └─ E. Actions
        ├─ [Verify Receipt] (if manual payment)
        │     └─ Modal: confirm amount, date, reference → fee_payment_status = paid
        │
        ├─ [Approve Agent]  (enabled only when fee_payment_status = paid)
        │     └─ Confirmation dialog
        │           └─ status → active
        │                 └─ Email dispatched to agent: "Your account is approved"
        │                       └─ Agent can now log in
        │
        └─ [Reject Application]
              └─ Modal: reason (required)
                    └─ status → rejected
                          └─ Email to agent with rejection reason
```

**Admin Agents List — Pending tab columns:**
| Column | Notes |
|--------|-------|
| Name | Applicant name or company |
| Profile Type | Individual / Company badge |
| Package | Agent/Leader · BP badge |
| Fee | PAID / RECEIPT / UNPAID badge |
| Referral Code | Used code or "—" |
| Applied | Date |
| Actions | [View] [Quick Approve] |

> **Quick Approve** is available only when fee is already PAID (Stripe) — allows one-click approval without opening the detail page.

---

#### 4.6.2 Promoting an Agent (Role Upgrade)

Admin can upgrade an approved agent's role at any time. This is done from the agent edit page.

**Entry point:** `/admin/agents/{id}/view` → [Edit Agent] → `/admin/agents/{id}/update`

```
/admin/agents/{id}/update
  │
  ├─ Current Role: Agent  (badge, read display)
  │
  ├─ Change Role To:
  │   ○ Agent
  │   ○ Agent Leader   ← upgrade from Agent
  │   ○ Business Partner  ← upgrade from Leader (requires company profile)
  │
  ├─ [If Agent Leader or BP selected]:
  │   Parent Agent:  [ searchable dropdown — shows Leaders and BPs ]
  │   (pre-filled if agent already has a parent from referral code)
  │
  ├─ Commission Rates (editable):
  │   Own Sales Rate:    [  10  ] %  (pre-filled from system default)
  │   Override Rate:     [   5  ] %  (shown only for Leader / BP)
  │
  └─ [Save Changes]
        └─ agent_role updated
              └─ parent_agent_id updated
                    └─ AgentCommissionRate records updated
                          └─ Activity log: "Role changed: Agent → Agent Leader by Admin X"
```

**Promotion Rules:**
| From | To | Constraint |
|------|----|------------|
| Agent | Agent Leader | No extra constraint |
| Agent | Business Partner | Agent must have company profile |
| Agent Leader | Business Partner | Agent must have company profile |
| Any | Downgrade | Admin can downgrade; see downgrade consequences below |

> **Note**: Role change is **not retroactive** on commissions. Override commissions only apply to sales made **after** the role upgrade date (per DECISION_OUTCOMES.md Decision 4).

**Role Downgrade Consequences** (Decision 20):

When admin selects a lower role (e.g. Agent Leader → Agent, or BP → Agent Leader):

1. **Subordinates preserved**: All agents with `parent_agent_id` pointing to this agent are NOT automatically reassigned. They remain linked.
2. **Override commissions stop**: After the downgrade, `CommissionGenerator` will not create override commissions for this agent on future sales (role check fails). Past pending commissions are untouched.
3. **Admin warning popup**: If the agent being downgraded has direct subordinates, a blocking modal appears before saving:

```
┌──────────────────────────────────────────────────────────┐
│  ⚠ Downgrade Warning                                     │
│                                                          │
│  This agent has 4 subordinate(s).                        │
│                                                          │
│  After downgrading:                                      │
│  • They will no longer earn override commissions         │
│    from their subordinates.                              │
│  • Subordinates remain assigned to this agent            │
│    but will not generate override commissions.           │
│  • Subordinates must be manually reassigned if           │
│    desired.                                              │
│                                                          │
│  [Cancel]                    [Confirm Downgrade →]       │
└──────────────────────────────────────────────────────────┘
```

4. **Payout at current role**: Payout calculations always use the agent's `agent_role` at request time — no retroactive adjustment.

---

#### 4.6.3 Admin Payout Lifecycle (Full)

Admin is responsible for the full payout workflow — from reviewing requests to uploading proof.

```
Agent submits payout request
  └── Payout created: status = pending
        └── Admin: /admin/payouts (filter: Pending)
              └── [View] → /admin/payout/{id}
                    │
                    ├─ Review payout items (commissions included)
                    ├─ Verify agent bank details
                    │
                    ├─ [Approve Payout]  →  status: approved
                    │     └─ (Optional: notify agent via email)
                    │
                    ├─ [Initiate Transfer]  →  status: processing
                    │     └─ Admin manually initiates bank transfer externally
                    │
                    ├─ Upload Bank Transfer Receipt
                    │     └─ [Choose File] → upload PDF/JPG/PNG
                    │
                    ├─ [Mark as Paid]  →  status: paid
                    │     └─ paid_at = now()
                    │           └─ All commission statuses → paid
                    │                 └─ Email to agent: "Your payout of RM X has been transferred"
                    │                       └─ Agent: /agent/payouts/{id} → Download receipt
                    │
                    └─ [Reject Payout]  →  status: rejected
                          └─ Modal: reason (required)
                                └─ Email to agent with reason
                                      └─ Commissions revert to available (can re-request)
```

**Admin Payouts List — columns:**
| Column | Notes |
|--------|-------|
| ID | Reference |
| Agent | Name + role badge |
| Package | Agent / Leader / BP |
| Items | Commission count |
| Amount | RM |
| Status | Colour badge |
| Requested | Date |
| Updated | Last action date |
| Actions | [View] [Quick Mark Paid] |

---

## 5. Agent (Base) Workflow

An individual or company who has registered, been approved, and paid entry fee. Lowest level in the hierarchy. Earns only from their own sales.

### Post-Login Redirect

```
Login → /dashboard → role check → redirect → /agent/dashboard
```

---

### 5.1 Agent Dashboard — `/agent/dashboard`

The dashboard is the agent's home screen. It shows a personal performance snapshot and links to all key sections.

**Row 1 — Headline Stats (4 cards):**
| Card | Data | Trend Indicator |
|------|------|-----------------|
| My Sales This Month | Total RM value of confirmed sales | ▲▼ vs last month |
| My Commission This Month | RM earned (own_sales, pending + paid) | ▲▼ vs last month |
| Pending Payout | RM total across all pending payout requests | ▲▼ vs last month |
| Active Referrals (90d) | Count of unique visits/referrals in last 90 days | ▲▼ vs prev 90d |

**Row 2 — Quick Links (icon cards, tappable):**
| Link Card | Destination |
|-----------|-------------|
| My Sales | `/agent/sales` |
| My Commissions | `/agent/commissions` |
| My Payouts | `/agent/payouts` |
| My Profile | `/agent/profile` |

**Charts Section:**
- **Monthly Sales** — line chart, last 12 months, RM value per month
- **Daily Sales This Month** — bar chart, RM per day (current month)
- **Referral Activity (90 days)** — bar chart showing referral visits per week

**Recent Sales Table (last 5, with link to full list):**
| Column | Notes |
|--------|-------|
| Date | Sale date |
| Description | Product / service name |
| Invoice No. | Reference |
| Amount | RM |
| Commission | RM |
| Status | Pending / Paid badge |

> [View All Sales →] links to `/agent/sales`

**Recent Payouts Widget (last 3, with link):**
| Column | Notes |
|--------|-------|
| ID | Payout reference |
| Amount | RM |
| Status | Status badge (see §5.5 for statuses) |
| Date | Requested date |

> [View All Payouts →] links to `/agent/payouts`

**Performance Summary Bar (bottom of page):**
| Metric | Data |
|--------|------|
| Avg Sale Value | RM (all-time) |
| Best Sales Month | Month name + RM |
| Total Commission Earned | RM all-time |
| Total Payouts Received | RM all-time paid |

**Referral Code Box (prominent, right column or below cards):**
```
┌───────────────────────────────────────┐
│  Your Referral Code                   │
│                                       │
│  ┌────────────────────┐               │
│  │  REF-A7X92K        │  [Copy]       │
│  └────────────────────┘               │
│                                       │
│  Share link:                          │
│  https://yoursite.com?ref=REF-A7X92K  │
│  [Copy Link]  [Share via WhatsApp]    │
│                                       │
│  Used: 34 times   Active: Yes         │
└───────────────────────────────────────┘
```

---

### 5.2 Agent Sales List — `/agent/sales`

Full paginated list of all sales attributed to this agent via their referral code.

**Filter Bar:**
| Filter | Options |
|--------|---------|
| Date Range | From / To date picker |
| Status | All / Pending / Paid |
| Search | Invoice No., buyer email, description |

**Summary Cards (above table):**
| Card | Data |
|------|------|
| Total Sales | RM all-time |
| Sales This Month | RM |
| Pending Commission | RM (unpaid commissions) |

**Sales Table (paginated, 20 per page):**
| Column | Notes |
|--------|-------|
| # | Row number |
| Date | Sale date + time |
| Description | Product / service |
| Invoice No. | Reference (if provided) |
| Buyer Email | Customer email |
| Sale Amount | RM |
| Commission Rate | % |
| Commission | RM |
| Status | Badge: Pending / Paid |
| Actions | [View] |

**Action Buttons:**
| Button | Action |
|--------|--------|
| [View] (row) | Navigate → `/agent/sales/{id}` |

---

#### Screen: `/agent/sales/{id}` — Sale Detail

**Sale Information Card:**
| Field | Value |
|-------|-------|
| Sale ID | Internal reference |
| Invoice No. | External reference |
| Date | Date and time |
| Description | Product / service |
| Buyer Email | Customer |
| Sale Amount | RM |
| Payment Status | — (external, informational) |

**Commission Information Card:**
| Field | Value |
|-------|-------|
| Commission Rate | % applied |
| Rate Source | Agent Rate / Referral Code Rate / System Default |
| Commission Amount | RM |
| Status | Pending / Paid badge |
| Paid At | Date (if paid) |
| Included in Payout | Link to payout ID (if paid) |

**Referral Tracking Card:**
| Field | Value |
|-------|-------|
| Referral Code Used | Code |
| Referral Visit ID | Linked visit (if trackable) |
| IP Address | Visitor IP (informational) |

**Action Buttons:** None — read-only view.

---

### 5.3 Agent Commissions — `/agent/commissions`

View and filter all commissions earned. For a base Agent this is a flat list (no tabs — only own_sales commissions exist).

**Filter Bar:**
| Filter | Options |
|--------|---------|
| Month / Year | Picker (defaults to current month) |
| Status | All / Pending / Paid |

**Summary Cards:**
| Card | Data |
|------|------|
| Total Earned (this month) | RM sum |
| Pending | RM (not yet in a paid payout) |
| Paid | RM (included in paid payout) |
| Available to Request | RM (pending, not yet in any payout request) |

**Commission Table (paginated):**
| Column | Notes |
|--------|-------|
| Date | Sale date |
| Description | Product / service |
| Invoice No. | Reference |
| Sale Amount | RM |
| Rate | % |
| Commission | RM |
| Status | Pending / Paid badge |
| Payout Ref | Link to payout (if paid) |
| Actions | [View Sale] |

> [Request Payout →] button at top right — navigates to `/agent/request-payout`

---

### 5.4 Agent Payout List — `/agent/payouts`

Full paginated history of all payout requests submitted by this agent.

**Payout Status Lifecycle:**
```
pending → approved → processing → paid
                               ↘ rejected (with reason)
```

| Status | Meaning |
|--------|---------|
| `pending` | Agent submitted request; awaiting admin review |
| `approved` | Admin reviewed and confirmed the amount |
| `processing` | Bank transfer has been initiated by admin |
| `paid` | Transfer confirmed; agent can download receipt |
| `rejected` | Admin declined with reason |

**Summary Cards:**
| Card | Data |
|------|------|
| Total Paid (all-time) | RM |
| Pending / In Progress | RM (pending + approved + processing) |
| Last Payment | Date + RM amount |

**Payout Table (paginated, 20 per page):**
| Column | Notes |
|--------|-------|
| # | Row number |
| Payout ID | Reference |
| Commissions | Count of items |
| Amount | RM total |
| Status | Colour-coded badge |
| Requested | Date submitted |
| Updated | Last status change date |
| Paid At | Date (if paid, else "—") |
| Actions | [View] |

**Action Buttons:**
| Button | Action |
|--------|--------|
| [Request New Payout] | Navigate → `/agent/request-payout` |
| [View] (row) | Navigate → `/agent/payouts/{id}` |

---

#### Screen: `/agent/payouts/{id}` — Payout Detail

**Payout Summary Card:**
| Field | Value |
|-------|-------|
| Payout ID | Reference |
| Status | Colour-coded badge + status history timeline |
| Requested Date | |
| Total Amount | RM |
| Commission Count | Number of items |

**Status Timeline (visual stepper):**
```
● Submitted  →  ○ Approved  →  ○ Processing  →  ○ Paid
   (date)          (date)          (date)          (date)
```
If rejected: timeline shows ✗ Rejected with admin's reason.

**Agent Bank Details (read-only, as submitted):**
- Account Name, Bank, Account Number

**Bank Transfer Receipt:**
- [Download Receipt] button (visible once status = paid)
- File uploaded by admin (bank transfer proof)

**Commission Items Table (read-only):**
| Column | Notes |
|--------|-------|
| Date | Sale date |
| Description | Sale description |
| Invoice No. | Reference |
| Sale Amount | RM |
| Commission Rate | % |
| Commission | RM |

**Action Buttons:**
| Button | Condition | Action |
|--------|-----------|--------|
| [Download Receipt] | status = paid AND file uploaded | Download bank transfer file |

---

### 5.5 Request Payout — `/agent/request-payout`

Agent selects which pending commissions to include in a payout request.

```
/agent/request-payout
  │
  ├─ Header: "Available to Request: RM X,XXX.XX"
  │
  ├─ Filter Bar: Date range (optional)
  │
  ├─ Summary Card (updates live as agent selects):
  │   ├─ Selected Items:  N commissions
  │   ├─ Request Date:    Today's date
  │   └─ Total Amount:    RM XXX.XX (sum of selected)
  │
  ├─ Commission Table (checkboxes):
  │   Columns: ☐ | Date | Description | Invoice No. | Sale Amount | Commission | Rate
  │   [Select All] checkbox in header
  │
  └─ [Request Payout] button (disabled if nothing selected)
        └─ Confirmation dialog: "Request RM XXX.XX for N commissions?"
              └─ [Confirm] → POST /request_payout
                    └─ Success screen → [View My Payouts]
```

> **Rule**: Commissions already in a pending/approved/processing payout cannot be selected again.

---

### 5.6 Agent Profile — `/agent/profile`

**Membership Section (top):**
| Field | Value |
|-------|-------|
| Role | Badge: Agent |
| Status | Active / Inactive / Suspended |
| Member Since | Registered date |
| Membership Expires | expires_at date |
| Renewal Due | renewal_due_at date |
| Fee Status | Paid / Pending Verification / Unpaid badge |

**Referral Code Section:**
- Code (monospace, prominent)
- [Copy Code] button
- Commission Rate %
- Total Uses

**Identity / Company Section:**
- Individual: Name, NRIC, Phone, Email, Address
- Company: Company Name, Reg No., Rep Name, Phone, Email, Address

**Documents:**
- [Download IC / Passport Scan]
- [Download Company Registration] (company only)

**Bank Account:**
- Account Name, Bank Name, Account Number, IBAN, SWIFT

**Action Buttons:**
| Button | Action |
|--------|--------|
| [Edit Profile] | Navigate → `/agent/profile/edit` |
| [Download IC File] | GET file |
| [Download Reg File] | GET file (company only) |

---

## 6. Agent Leader Workflow

An Agent Leader has their own sales activity **and** manages a team of base Agents directly below them. They earn override commissions when their team makes sales.

> Agent Leaders inherit **every screen** from the base Agent workflow. Only additions and differences are documented here.

### Post-Login Redirect

```
Login → /dashboard → role check → redirect → /agent/dashboard
  (same route as base Agent — dashboard content adapts based on agent_role)
```

---

### 6.1 Dashboard — `/agent/dashboard` (Leader view)

The dashboard shows two rows of cards: own performance (same as base Agent) + team performance.

**Row 1 — Own Performance (identical to base Agent):**
- My Sales This Month, My Commission This Month, Pending Payout, Active Referrals (90d)

**Row 2 — Team Performance (NEW for Leader):**
| Card | Data | Trend |
|------|------|-------|
| Team Sales This Month | Sum of all direct agents' sales | ▲▼ vs last month |
| Override Commission | RM earned from team's sales (override rate) | ▲▼ vs last month |
| Team Size | Count of active agents directly below | — |
| Team Conversion Rate | Team referrals → Sales % | ▲▼ vs prev month |

**Quick Links (additional for Leader):**
| Link Card | Destination |
|-----------|-------------|
| My Team | `/agent/team` |
| Team Report | `/agent/reports/team` |

**Charts Section (additional):**
- **Team Monthly Sales** — stacked bar chart by agent, last 6 months
- **Override Commission Trend** — line chart, last 12 months

**Top Team Members Widget (this month):**
| Rank | Agent Name | Sales RM | Commission RM |
|------|-----------|----------|---------------|
| 1 | … | … | … |
| 2 | … | … | … |
| 3 | … | … | … |

> [View Full Team →] links to `/agent/team`

---

### 6.2 My Commissions — Two Tabs (Leader)

#### Screen: `/agent/commissions`

**Summary Cards (global, across both tabs):**
| Card | Data |
|------|------|
| Own Sales Commission | RM (pending + paid, this month) |
| Override Commission | RM (from team's sales, this month) |
| Grand Total Pending | RM available to request |
| Grand Total Paid | RM all-time paid |

**Tab 1 — Own Sales** (identical to base Agent commission table)

**Tab 2 — Override Commissions:**
| Column | Notes |
|--------|-------|
| Date | Sale date |
| Agent | Agent who made the sale (name, linked) |
| Sale Description | Product / service |
| Sale Amount | RM |
| Override Rate | % (leader's override rate) |
| Override Commission | RM |
| Status | Pending / Paid badge |
| Payout Ref | Link to payout (if paid) |

**Filter bar applies across both tabs:** Date range, Status

---

### 6.3 My Team — `/agent/team`

Hub for managing and monitoring all directly assigned agents.

**Team Overview Cards:**
| Card | Data |
|------|------|
| Total Members | Count of active agents |
| Team Sales This Month | RM |
| Team Commission Generated | RM (sum of all agents' own_sales commissions) |
| My Override This Month | RM (override commissions earned by leader) |
| Top Performer This Month | Agent name + RM |

**Team Table (paginated):**
| Column | Notes |
|--------|-------|
| # | Row |
| Name | Agent name |
| Profile Type | Individual / Company badge |
| Status | Active / Inactive badge |
| Sales This Month | RM |
| Sales All-Time | RM |
| Commission This Month | RM their commissions |
| My Override | RM override earned by leader from this agent |
| Joined | Date agent activated |
| Actions | [View] |

**Filter Bar:**
- Status: All / Active / Inactive
- Search: name, email

**Action Buttons:**
| Button | Action |
|--------|--------|
| [View] (row) | Navigate → `/agent/team/{id}` |

---

#### Screen: `/agent/team/{id}` — Team Member View (read-only)

A read-only profile + performance view of one agent in the leader's team.

**Profile Summary Card:**
- Name / Company, Role badge, Status badge, Member Since, Profile Type

**Performance Cards:**
| Card | Data |
|------|------|
| Sales This Month | RM |
| Sales Last Month | RM |
| Commission This Month | RM |
| All-Time Sales | RM |

**Recent Sales Table (last 10):**
| Column | Notes |
|--------|-------|
| Date | Sale date |
| Description | Product |
| Amount | RM |
| Commission | RM |
| Status | Pending / Paid |

**Referral Activity:**
- Referral code used
- Total visits (90 days)
- Conversion rate

> Leader cannot edit the member's profile or commissions. View-only.

---

### 6.4 Team Performance Report — `/agent/reports/team`

A dedicated report page for the leader to analyse their team's output.

**Report Filters:**
| Filter | Options |
|--------|---------|
| Period | This Month / Last Month / Custom Date Range / This Year |
| Agent | All / specific agent |

**Summary Section:**
| Metric | Value |
|--------|-------|
| Total Team Sales | RM |
| Total Team Commissions | RM (sum of agents' own_sales) |
| My Override Earned | RM |
| Best Performing Agent | Name + RM |
| Total Team Referrals | Count |
| Team Conversion Rate | % |

**Agent Breakdown Table:**
| Column | Notes |
|--------|-------|
| Agent | Name |
| Sales Count | Number of sales |
| Sales Amount | RM |
| Commission Earned | RM (agent's own) |
| Override to Me | RM (leader earns from this agent) |
| Referrals | Count |
| Conversion % | Referrals → Sales |

**Charts:**
- Agent Sales Comparison — bar chart (agents side by side)
- Monthly Team Sales Trend — line chart per agent (last 6 months)

**Export:**
- [Export CSV] — downloads table as CSV

---

### 6.5 Request Payout — Extended (Leader)

Same screen as base Agent (`/agent/request-payout`) with one addition: commission table includes a **Type** column showing `Own Sale` or `Override` for each row. Leader selects any mix.

---

## 7. Business Partner Workflow

A Business Partner is the highest tier in the agent hierarchy. They manage Agent Leaders (and may have direct agents too). They earn override commissions from **two levels below** — from Agent Leaders and from base Agents under those leaders.

> Business Partners inherit **every screen** from Agent Leader. Only additions and differences are documented here.

### Post-Login Redirect

Same as all agents: `/agent/dashboard` — content adapts based on `agent_role`.

---

### 7.1 Dashboard — `/agent/dashboard` (Business Partner view)

**Row 1 — Own Performance (same as base Agent)**

**Row 2 — Network Summary (expanded scope vs Leader):**
| Card | Data | Trend |
|------|------|-------|
| Network Sales This Month | Sum of ALL agents/leaders in subtree | ▲▼ vs last month |
| Override Commission | Total override earned from entire network | ▲▼ vs last month |
| Network Size | Total agents across all levels | — |
| Agent Leaders | Count of direct leaders | — |

**Row 3 — Network Breakdown:**
| Card | Data |
|------|------|
| Sales by Direct Leaders | RM (leader-level sales only) |
| Sales by Team Agents | RM (base agent level) |
| Top Leader This Month | Leader name + RM |
| Top Agent This Month | Agent name + RM |

**Quick Links (additional for BP):**
| Link Card | Destination |
|-----------|-------------|
| My Network | `/agent/team` (tree view default) |
| Network Report | `/agent/reports/network` |
| Commission Report | `/agent/reports/commissions` |

**Charts Section:**
- **Network Monthly Sales** — stacked bar by level (Leaders vs Agents), last 6 months
- **Override Commission by Level** — stacked bar (Leader override vs Agent override), last 6 months
- **Network Growth** — line chart of active agents over time

---

### 7.2 My Network — `/agent/team` (Business Partner view)

**Default view: Tree** (toggleable to Flat List)

**Tree View:**
```
┌────────────────────────────────────────────────────────────┐
│  My Network                          [Tree] [Flat List]    │
│                                                            │
│  ▼ Agent Leader A          Sales: RM 4,500  Override: RM 225
│    ├── Agent 1             Sales: RM 2,000  Commission: RM 200
│    └── Agent 2             Sales: RM 1,500  Commission: RM 150
│  ▼ Agent Leader B          Sales: RM 2,100  Override: RM 105
│    └── Agent 3             Sales: RM 900    Commission: RM 90
│  ── Agent 4 (direct)       Sales: RM 600    Commission: RM 60
└────────────────────────────────────────────────────────────┘
```

Each row is clickable → navigates to the member's view page.

**Flat List view** (same as Leader's team table, but with extra Role column showing Leader / Agent):
| Column | Notes |
|--------|-------|
| Name | Member name |
| Role | Badge: Agent Leader / Agent |
| Managed By | Direct parent (Leader name, or "Direct" for BP's direct) |
| Sales This Month | RM |
| Commission This Month | RM |
| Override to Me | RM BP earns from this member |
| Status | Active / Inactive |
| Actions | [View] |

---

### 7.3 Network Member View — `/agent/team/{id}`

Same as Agent Leader's team member view, but the BP can view both Leaders and Agents.

For a **Leader** member: shows leader's own performance + their sub-team summary.
For an **Agent** member: shows agent's own performance only.

---

### 7.4 My Commissions — `/agent/commissions` (Business Partner)

**Summary Cards:**
| Card | Data |
|------|------|
| Own Sales Commission (this month) | RM |
| Override — from Leaders (this month) | RM |
| Override — from Agents (this month) | RM |
| Total Pending | RM available to request |
| Total Paid (all-time) | RM |

**Tab 1 — Own Sales** (same as base Agent)

**Tab 2 — Override Commissions (two levels):**
| Column | Notes |
|--------|-------|
| Date | Sale date |
| Agent | Who made the sale |
| Via | Intermediate Leader (or "—" if direct agent) |
| Sale Amount | RM |
| Override Rate | % (BP's override rate) |
| Override Commission | RM |
| Level | Badge: "Via Leader" / "Direct Agent" |
| Status | Pending / Paid badge |
| Payout Ref | Link (if paid) |

---

### 7.5 Network Performance Report — `/agent/reports/network`

**Report Filters:**
| Filter | Options |
|--------|---------|
| Period | This Month / Last Month / Custom / This Year / Last Year |
| Level | All / Agent Leaders only / Agents only |
| Leader | All / specific leader (filters agents under that leader) |

**Summary Section:**
| Metric | Value |
|--------|-------|
| Total Network Sales | RM |
| Network Commission Generated | RM (sum of everyone's own_sales commissions) |
| My Total Override | RM |
| Override from Leaders | RM |
| Override from Agents | RM |
| Best Leader | Name + RM |
| Best Agent | Name + RM |
| Network Conversion Rate | % |
| Active Members | Count |

**Leader Breakdown Table:**
| Column | Notes |
|--------|-------|
| Leader | Name |
| Team Size | Agents under them |
| Leader's Own Sales | RM |
| Team Sales | RM (agents under leader) |
| Total Sales | RM combined |
| Override to Me (from leader) | RM |
| Override to Me (from their agents) | RM |
| Total Override | RM |

**Agent Breakdown Table (full network, paginated):**
| Column | Notes |
|--------|-------|
| Agent | Name |
| Reports To | Leader name (or "Direct") |
| Sales Count | — |
| Sales Amount | RM |
| Commission (their own) | RM |
| Override to Me | RM |

**Charts:**
- Leader vs Agent Sales contribution (donut chart)
- Monthly network sales trend (line per level, last 12 months)

**Export:**
- [Export CSV — Leaders], [Export CSV — Agents], [Export CSV — Full Network]

---

### 7.6 Commission Report — `/agent/reports/commissions`

A detailed breakdown of all override commissions earned by the BP.

**Filter Bar:** Period, Commission Type (Own Sales / Override), Status (Pending / Paid)

**Summary Cards:**
| Card | Data |
|------|------|
| Own Sales Commission | RM |
| Override — Level 1 (from Leaders) | RM |
| Override — Level 2 (from Agents) | RM |
| Total | RM |
| Unpaid | RM |

**Commission Table (paginated):**
| Column | Notes |
|--------|-------|
| Date | Sale date |
| Type | Own Sale / Override L1 / Override L2 badge |
| Agent | Who made the sale |
| Via Leader | Intermediate (if L2) |
| Sale Amount | RM |
| Rate | % |
| Commission | RM |
| Status | Pending / Paid |
| Payout Ref | Link |

**Export:** [Export CSV]

---

### 7.7 Request Payout — Extended (BP)

Same as Agent Leader's payout request flow, but the commission table includes both Override L1 (from leaders) and Override L2 (from agents), each with a Level badge. BP selects any mix.

---

## 8. Complete Screen Inventory

### Public / Unauthenticated Screens

| Screen | Route | Change Status |
|--------|-------|---------------|
| Get Started | `/get-started` | No change |
| Registration Wizard | `/register-as-agent` | **Rebuild** — 5-step wizard |
| Login | `/login` | No change |

### Admin Screens

| Screen | Route | Change Status |
|--------|-------|---------------|
| Dashboard | `/admin/dashboard` | Modify — add second card row (pending payouts, renewals, fee unpaid) |
| Agents List | `/admin/agents/list` | Modify — role/fee/status filters; Quick Approve button; receipt badge |
| Agent View | `/admin/agents/{id}/view` | Modify — hierarchy, fee sections, role badge, [Approve], [Verify Receipt], [Reject] |
| Agent Add | `/admin/agents/add` | Modify — role, parent, commission rate fields |
| Agent Edit | `/admin/agents/{id}/update` | Modify — role upgrade, parent, commission rates |
| Commissions List | `/admin/commissions/list` | Modify — role/type filters, override column |
| Commission Detail | `/admin/commission/detail` | Modify — Own Sales / Override tabs |
| Payouts List | `/admin/payouts` | Modify — status filter, Quick Mark Paid, Approve/Process steps |
| Payout Detail | `/admin/payout/{id}` | Modify — status stepper, Approve/Process/Paid/Reject actions, Type column |
| Payout Create | `/admin/payout/create` | Modify — show override commissions with Type column |
| Payout Edit | `/admin/payout/{id}/update` | No change |
| Partners List | `/admin/partners/list` | Deprecate — hide behind feature flag / redirect |
| System Settings View | `/admin/system-settings` | Modify — role names, fees, override rates |
| System Settings Edit | `/admin/system-settings/update` | Modify — all new fields |

### Agent Screens — Base Agent

| Screen | Route | Change Status |
|--------|-------|---------------|
| Dashboard | `/agent/dashboard` | Modify — referral code box, quick links row, recent payouts widget |
| Sales List | `/agent/sales` | Modify — add pagination, search/filter bar, [View] per row |
| Sale Detail | `/agent/sales/{id}` | **NEW** — commission card, referral tracking card |
| Commission List | `/agent/commissions` | Modify — add Available to Request card, payout ref column |
| Payouts List | `/agent/payouts` | Modify — status lifecycle badges, Updated column |
| Payout Detail | `/agent/payouts/{id}` | Modify — status stepper timeline, [Download Receipt] |
| Request Payout | `/agent/request-payout` | Modify — confirmation dialog, disable already-requested commissions |
| Profile | `/agent/profile` | Modify — membership section, fee status, expiry, referral code box |
| Profile Edit | `/agent/profile/edit` | No change |

### Agent Screens — Leader Only (additions)

| Screen | Route | Change Status |
|--------|-------|---------------|
| Dashboard | `/agent/dashboard` | Modify — Row 2 team cards, team charts, top members widget |
| Commissions | `/agent/commissions` | Modify — Two tabs: Own Sales / Override |
| My Team | `/agent/team` | **NEW** — flat list with team stats |
| Team Member View | `/agent/team/{id}` | **NEW** — read-only member profile + performance |
| Team Report | `/agent/reports/team` | **NEW** — agent breakdown table, charts, CSV export |

### Agent Screens — Business Partner Only (additions)

| Screen | Route | Change Status |
|--------|-------|---------------|
| Dashboard | `/agent/dashboard` | Modify — Row 2+3 network cards, network charts |
| Commissions | `/agent/commissions` | Modify — Override tab shows L1 + L2 with Via column + Level badge |
| My Network | `/agent/team` | Modify — Tree View toggle, Flat List with Leader / Agent badges |
| Network Member View | `/agent/team/{id}` | Modify — Leader shows sub-team summary too |
| Network Report | `/agent/reports/network` | **NEW** — leader breakdown + full agent table, multi-level charts |
| Commission Report | `/agent/reports/commissions` | **NEW** — L1/L2 override breakdown, full table, CSV export |

---

## 9. Visual Workflow Summaries

### A. Full Commission Flow

```
External Website
  └── POST /api/agents/track/sale  (with referral code)
        └── Sale created
              └── CommissionGenerator runs
                    ├── own_sales commission → Agent (pending)
                    ├── override commission → Agent Leader (pending)  [if parent exists]
                    └── override commission → Business Partner (pending) [if grandparent exists]
                          │
                          ├── Agent:  /agent/commissions  → Tab: Own Sales
                          ├── Leader: /agent/commissions  → Tab: Override
                          ├── BP:     /agent/commissions  → Tab: Override
                          └── Admin:  /admin/commissions/list  (all visible)
```

### B. Payout Request Flow (Full Lifecycle)

```
Agent/Leader/BP: /agent/request-payout
  └── Select pending commissions  (own_sales + override, any mix)
        └── [Request Payout] → confirmation dialog
              └── Payout created: status = PENDING
                    └── Agent: /agent/payouts → sees "Pending" row
                          │
                          └── Admin: /admin/payouts → new pending row
                                └── Admin: /admin/payout/{id}
                                      │
                                      ├── Review items  →  [Approve Payout]
                                      │       └── status = APPROVED
                                      │             └── (optional email to agent)
                                      │
                                      ├── Initiate bank transfer externally
                                      │       └── [Mark as Processing]
                                      │             └── status = PROCESSING
                                      │
                                      ├── Upload bank transfer receipt file
                                      │
                                      └── [Mark as Paid]
                                              └── status = PAID
                                                    └── paid_at = now()
                                                          └── All commission statuses → paid
                                                                └── Email to agent: "RM X transferred"
                                                                      └── Agent: /agent/payouts/{id}
                                                                            └── [Download Receipt]

  Alt path: [Reject Payout]
    └── Modal: reason (required)
          └── status = REJECTED
                └── Email to agent with reason
                      └── Commissions revert to available (agent can re-request)
```

### C. Agent Lifecycle (Onboarding → Active → Renewal)

```
Guest → /register-as-agent (6-step wizard)
  ├── Step 1: Referral code → validated / skipped
  ├── Step 2: Package (Agent/Leader RM100 | BP RM3000) → sets agent_role
  ├── Step 3: Profile (Individual or Company) + password fields
  ├── Step 4: Email verification (6-digit code) → User+Agent auto-created on success
  ├── Step 5: T&C checkbox + Payment (Stripe / Manual / Skip)
  └── Step 6: Confirmation shown
        └── Emails → Agent#1 + Referring Agent + BP upline
              │
              └── Agent: status=pending, fee_payment_status=paid|pending_verification|pending(skipped)
                    │
                    ├── First login → /get-started-guide (role-adaptive slide onboarding)
                    │
                    └── Admin: /admin/agents/list → Filter: Pending
                          └── /admin/agents/{id}/view
                                ├── [Verify Bank Transfer] (if manual) → fee_payment_status: paid
                                └── [Approve Agent] → status: active, email to agent
                                      └── Admin optionally upgrades role / sets parent
                                            └── Agent fully active
                                                  │
                                                  └── (N days before expires_at)
                                                        └── System: renewal reminder email
                                                              └── Admin: /admin/agents/list
                                                                    └── Filter: Upcoming Renewals
                                                                          └── [Record Renewal Fee]
                                                                                └── expires_at extended
```

### D. Admin Agent Management Decision Tree

```
Admin: /admin/agents/list
  │
  ├── Filter: Pending Approvals
  │     └── [View] → review docs → [Approve Agent]
  │
  ├── Filter: Upcoming Renewals
  │     └── [View] → [Record Renewal Fee Received]
  │
  ├── Filter: Fee Unpaid
  │     └── [View] → [Record Entry Fee Received]
  │
  └── Any agent → [Edit]
        └── /admin/agents/{id}/update
              ├── Change role: Agent → Agent Leader → Business Partner
              ├── Set / change parent agent
              └── Set custom commission rates (own_sales %, override %)
```

---

## 10. Open Items — Implementation Priority

These workflows depend on the following backend and frontend work:

### Schema / Backend

| Priority | Item | UI Screens Affected |
|----------|------|---------------------|
| P0 | Add `agent_role`, `parent_agent_id` to `agents` table | All role logic, hierarchy display |
| P0 | Add `commission_type` (own_sales/override) to `commissions` | Commission tabs, payout items Type column |
| P0 | Add `earning_agent_id` to `commissions` | Override attribution in tabs |
| P1 | Create `fee_payments` table | Fee recording modal, fee history table |
| P1 | Add `expires_at`, `renewal_due_at`, `fee_payment_status` to `agents` | Profile fee/expiry, agents list filter |
| P1 | Extend `system_settings` (role names, override rates, fee amounts, entry/renewal fees) | System Settings edit form, registration wizard |
| P1 | Add `payment_method`, `stripe_session_id`, `receipt_file` to registration flow | Registration Step 4 |
| P2 | `AgentHierarchy` service (traversal, subtree queries) | My Team screen, dashboard team cards |
| P2 | `CommissionGenerator` hierarchy expansion | Override commission creation |
| P2 | Stripe Checkout integration (`AgentRegistrationController`) | Registration Step 4 |
| P2 | Stripe webhook handler (confirm payment → set `fee_payment_status=paid`) | Post-registration |
| P2 | `FeeService::applyEntryFee` called on Stripe success + admin verification | Agent activation |
| P3 | Renewal reminder scheduled job | Automated lifecycle emails |

### Frontend / Vue

**Registration**
| Priority | Item | Screen |
|----------|------|--------|
| P1 | Registration wizard rebuild — 6-step form with progress indicator | `/register-as-agent` |
| P1 | Step 1: Referral code input + live API validation + agent name preview | Registration |
| P1 | Step 2: Package cards (fees pulled from SystemSetting) + BP forces Company lock | Registration |
| P1 | Step 3: Profile type toggle (Individual / Company) + all fields + doc uploads + email + password fields | Registration |
| P1 | Step 4: Email verification (6-digit code, 15-min expiry, resend after 60s, 3-attempt limit) | Registration |
| P1 | Auto-create User+Agent on email verification success; send "Account Created" email | Registration backend |
| P1 | Step 5: T&C checkbox (must check before pay or skip), Stripe redirect OR manual bank transfer, [Skip Payment] button | Registration |
| P1 | Stripe return URLs: success `/register-as-agent/payment/success`, cancel `/register-as-agent/payment/cancelled` | Registration |
| P1 | Skip Payment → auto-login; dashboard shows persistent "Complete Payment" banner | Registration/Dashboard |
| P1 | Step 6: Confirmation + next-steps instructions + [Log In] CTA | Registration |
| P1 | Cookie `reg_wizard_state` (signed): stores step data excluding passwords; pre-fills on return | Registration |
| P1 | Pre-check email on /get-started or registration entry: existing+password → login; existing no-password → reset | /get-started |
| P1 | Resume path: `/agent/payment/complete` for skipped-payment agents | Agent |
| P1 | Rejected agent: can log in, sees rejection banner + [Request Approval] on dashboard | Agent dashboard |

---

## 11. Gap Resolutions (GAP-01 through GAP-18)

> These gaps were identified after the initial ROLES_WORKFLOW draft and resolved via stakeholder decision. Each section below specifies the exact screen changes, backend behaviour, and any new routes required.

---

### GAP-01 — Password Fields in Registration Step 3

**Resolution**: Add Login Credentials section at the bottom of Step 3 (profile details). This keeps the profile and credentials on one page, reducing wizard step count. See Section 3.1 Step 3C for the exact fields.

**Fields added to Step 3**:
- Login Email (auto-filled from profile email; editable for company profile where company email ≠ login email)
- Password (min 8 chars, strength indicator)
- Confirm Password (must match)

**Pre-check**: Before the wizard is entered, a quick email check is performed. If the email is already a registered user with a set password, redirect to login with message. If registered but no password, redirect to reset-password flow.

---

### GAP-02 — Email Verification Before Payment

**Resolution**: A new Step 4 (Email Verification) is inserted between the profile/credentials step (Step 3) and the payment step (Step 5). Payment cannot be accessed until email is verified.

See Section 3.1 Step 4 for the full wireframe. The 6-digit code is displayed inside the wizard (not a separate URL unless the user closes the browser).

**Edge cases**:
- Code expired (15 min) → user can resend; wizard stays at Step 4
- 3 consecutive wrong codes → force restart from Step 1 (cookie preserved)
- Email changed after code sent → send new code, invalidate previous

---

### GAP-03 — Cookie State + Skip Payment + Resume

**Resolution**:

1. **Cookie Storage**: After Step 3 submission, form state (referral code, package, profile type, all identity fields, login email, bank details) is stored in a signed cookie `reg_wizard_state` (1-hour TTL). Password is never included.

2. **Skip Payment**: At Step 5 (payment), the agent can click [Skip Payment for Now]. Since the User+Agent record was already created at Step 4, this simply auto-logs in the agent and redirects to `/agent/dashboard`.

3. **Resume Payment**: Agent who skipped sees a persistent banner on their dashboard. Clicking [Complete Payment →] navigates to `/agent/payment/complete` — the same payment UI as Step 5, but served to an authenticated agent. After payment, `fee_payment_status` updates to `paid` or `pending_verification`.

4. **Pre-fill on return**: If a user visits `/register-as-agent` while a wizard cookie exists (and they are not logged in), the form pre-fills to the last completed step.

**New routes**:
| Route | Method | Purpose |
|-------|--------|---------|
| `/register-as-agent/payment/success` | GET | Stripe success callback; update fee_payment_status; redirect → Step 6 |
| `/register-as-agent/payment/cancelled` | GET | Stripe cancel callback; redirect → Step 5 with notice |
| `/agent/payment/complete` | GET | Resume payment for skipped applicant (authenticated) |
| `/agent/payment/complete` | POST | Submit manual receipt upload or trigger Stripe redirect |

---

### GAP-04 — Commission Reversal Flow

**Resolution**: Admin can reverse a single sale at a time from the Admin Sales/Commission screens. The reversal creates negative Commission rows for the full earning chain.

#### Admin screens:

**`/admin/commissions/list` or `/admin/commission/detail`** — [Mark as Reversed] button:
- Visible only for admin users
- Enabled only if sale has no active reversal pending
- Only one active reversal per sale at a time

**Reversal flow**:
```
Admin clicks [Mark as Reversed] on a sale/commission row
  └── Modal: "This will reverse the commission for Sale #XXX (RM 500)"
        └── Shows warning if a Payout exists:
            ┌──────────────────────────────────────────────────────┐
            │ ⚠ This sale has an active payout request             │
            │   Payout #42 (status: pending) for Agent John Doe    │
            │   RM 50.00 will be deducted from that payout.        │
            └──────────────────────────────────────────────────────┘
        └── Shows warning if payout was already paid:
            ┌──────────────────────────────────────────────────────┐
            │ ⚠ Payout #38 was already paid on 2026-03-15          │
            │   RM 50.00 reversal will appear on the agent's       │
            │   next payout request.                               │
            └──────────────────────────────────────────────────────┘
        └── [Confirm Reversal] → RefundService::reverseSale()
```

**Backend (`RefundService::reverseSale`)**:
- Find all Commission rows for the Sale (own_sales + all override rows for each earner)
- For each: create a new Commission row with `is_reversal=true`, `original_commission_id=original.id`, `amount=-original.amount`, `status='cancelled'`
- If a pending Payout includes any of these commissions:
  - Add a note to the Payout's `admin_notes`: "Reversal applied: Sale #XXX reversed on {date}. RM X deducted."
  - Recalculate payout total (sum of eligible non-cancelled commissions)
  - Insert an Inbox notification to the agent (see GAP-11)
- If payout was paid: reversal commissions remain in the agent's balance as negative — they will offset the next payout request

**Agent view of reversals**:
- `/agent/commissions` — reversed rows are visible with a `Reversed` badge and negative RM value
- `/agent/sales` — reversed sales have a `Reversed` badge on the row
- `/agent/sales/{id}` — Sale Detail shows "⚠ This sale was reversed on {date}" with reason

---

### GAP-05 — Suspended Agent UI

**Resolution**: Suspended agents can log in but are restricted. They see a prominent suspension banner on every page.

**Dashboard banner** (shown when `status = 'suspended'`):
```
┌─────────────────────────────────────────────────────────┐
│  🔒 Your account is suspended.                          │
│                                                         │
│  Reason: {suspension_reason or "Contact support"}       │
│                                                         │
│  [Appeal Suspension →]   [Contact Support]              │
└─────────────────────────────────────────────────────────┘
```

**Restrictions when suspended**:
- Cannot submit a new payout request (Request Payout button hidden; `/agent/request-payout` redirects to dashboard with notice)
- Can still view: sales, commissions, past payouts (read-only)
- Can still edit profile

**[Appeal Suspension] action**:
- Opens a modal: "Describe your appeal (optional):" textarea
- Submit → POST `/agent/appeal-suspension`
- System sends an email to the admin (Agent #1 email) with subject: "Agent {Name} has appealed suspension — Account #{ID}"
- System creates an Inbox notification for the agent: "Your appeal has been submitted. Our team will review it."

**Missed renewal fee**:
- If suspension reason is `expired_membership`, the banner also shows: "Your membership fee is overdue. [Renew Now →]"
- [Renew Now] → same `/agent/payment/complete` page but shows renewal fee

**Admin override (GAP-13)**: Admin can change status to any value at any time from `/admin/agents/{id}/update`. The status dropdown includes: Active, Inactive, Suspended, Banned, Expired, Pending.

---

### GAP-06 — Payout Auto-Select + Admin Cancel + Notification System

**Resolution**:

#### Payout Request (agent side):
- System automatically selects **all eligible pending commissions** for the agent when they visit `/agent/request-payout`
- Agent cannot deselect individual commissions — the total is fixed
- The screen shows the full list with a summary: "RM X,XXX.XX from N commissions will be requested"
- **Min payout threshold (GAP-12)**: If total eligible < `min_payout_amount` (SystemSetting, default RM 1), the [Request Payout] button is disabled with message: "Minimum payout amount is RM {min}."
- Agent adds an optional **Note** field (GAP-16): max 500 chars — submitted with the request

**New fields**:
- `payouts.agent_note` — varchar(500), nullable — agent's note at request time
- `payouts.admin_note` — text, nullable — admin's note when cancelling or processing

#### Admin cancellation of payout:
Admin can cancel a pending payout from `/admin/payout/{id}`:
- [Cancel Payout] button (shown while status = pending or approved)
- Modal: "Reason for cancellation (required):" textarea [50–500 chars]
- On confirm: `payout.status → cancelled`, commissions revert to available
- System inserts an Inbox notification to agent (see GAP-11):
  > Subject: "Payout Request Cancelled"
  > Body: "Your payout request #[ID] for RM [amount] has been cancelled by admin. Reason: [admin_note]. The commissions have been returned to your available balance."

---

### GAP-07 — Sidebar Navigation per Role

**Resolution**: The sidebar adapts based on the authenticated user's role. Icons are from Lucide or Heroicons. All routes are relative.

#### Admin Sidebar
```
┌─────────────────────────┐
│  🏠 Dashboard           │  /admin/dashboard
│  👥 Agents              │  /admin/agents/list
│  💰 Commissions         │  /admin/commissions/list
│  💳 Payouts             │  /admin/payouts
│  ⚙  System Settings     │  /admin/system-settings
│  📋 Activity Log        │  /admin/activity-log  (GAP-08)
│  🏢 Partners (deprecated)│  hidden / greyed
└─────────────────────────┘
```

#### Agent Sidebar (base agent)
```
┌─────────────────────────┐
│  🏠 Dashboard           │  /agent/dashboard
│  📊 My Sales            │  /agent/sales
│  💎 Commissions         │  /agent/commissions
│  💳 Payouts             │  /agent/payouts
│  🔗 My Referral         │  /agent/referral  (GAP-15)
│  👤 My Profile          │  /agent/profile
└─────────────────────────┘
```

#### Agent Sidebar (Agent Leader additions)
```
│  👥 My Team             │  /agent/team
│  📈 Team Report         │  /agent/reports/team
```

#### Agent Sidebar (Business Partner additions)
```
│  🌐 My Network          │  /agent/team  (tree view default)
│  📊 Network Report      │  /agent/reports/network
│  📋 Commission Report   │  /agent/reports/commissions
```

#### Inbox indicator (all agent roles)
```
│  🔔 Inbox         [3]  │  /agent/inbox  (badge shows unread count)
```

**Implementation notes**:
- Sidebar component reads `$page.props.auth.agent.agent_role` to show/hide sections
- Inbox badge count passed as Inertia shared prop: `unread_inbox_count`
- Active link highlighted based on current URL
- Mobile: sidebar collapses to a bottom navigation bar (5 primary items)

---

### GAP-08 — Admin Activity Log Screen

**Screen**: `/admin/activity-log`

**Purpose**: Full searchable audit trail of all system actions.

**Filter Bar:**
| Filter | Options |
|--------|---------|
| Date Range | From / To date picker |
| Actor | All / specific user (search by name) |
| Action | All / create / update / delete / approve / reject / reverse / fee_payment / login |
| Target | Search by model type (Agent, Commission, Payout, etc.) |

**Table Columns:**
| Column | Notes |
|--------|-------|
| Timestamp | Date + time (local) |
| Actor | User name + role badge |
| Action | Badge: create / update / delete / approve / etc. |
| Target | Model type + ID + short description |
| Details | Expandable diff: before → after (JSON) |

**Action Buttons:**
- [Export CSV] — downloads filtered log as CSV

**Sidebar link**: Added as "📋 Activity Log" in admin sidebar (see GAP-07).

---

### GAP-09 — Account Auto-Creation + Rejected Agent Login + Notifications

**Resolution** (consolidated):

1. **Account auto-created before payment**: At Step 4 (email verification success), User + Agent records are created with `status=pending`. See Section 3.1 Step 4.

2. **Account Created email**: System sends `AccountCreatedNotification` mailable immediately after record creation. Contains: login URL, username (email), reminder that they still need to complete payment and await admin approval.

3. **Rejected agent UX**: An agent with `status=rejected` can log in. Their dashboard shows:
   ```
   ┌──────────────────────────────────────────────────────┐
   │  ❌ Your application was rejected.                   │
   │                                                      │
   │  Reason: {rejection_reason}                          │
   │                                                      │
   │  [Update Your Details →]   [Request Approval →]     │
   └──────────────────────────────────────────────────────┘
   ```
   - [Update Your Details] → profile edit page so they can correct errors
   - [Request Approval] → POST `/agent/request-approval` → resets `status=pending`, notifies admin (creates Inbox notification for Agent#1)

4. **Approval always from Admin**: The approval action (`/admin/agents/{id}/approve`) is always performed by a user with `admin` role. The target email for all approval notifications is Agent#1's email.

5. **Upper leader/BP notification**: When agent is approved, the system creates Inbox notifications (see GAP-11) for:
   - The agent themselves: "Your account has been approved. Welcome!"
   - Their parent agent (if any): "A new agent [Name] has been added to your team."

---

### GAP-10 — Terms & Conditions Checkbox

**Resolution**: A T&C acceptance checkbox is shown in Step 5 (payment step), before the payment method options appear. The [Pay with Stripe], [Complete Registration] (manual), and [Skip Payment] buttons are all disabled until the checkbox is checked.

**Stored**: `agents.tc_accepted_at` timestamp field (nullable). Set when user submits Step 5 (or skip).

**Display**: Link to T&C opens in a new tab. Checkbox label: "I have read and agree to the Terms & Conditions."

---

### GAP-11 — Notification / Inbox System

**Purpose**: Every significant system event creates a notification entry in the agent's inbox, visible at `/agent/inbox`. Email notifications continue as before; the inbox supplements them for in-app awareness.

#### Database Schema

```sql
CREATE TABLE agent_notifications (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agent_id        BIGINT UNSIGNED NOT NULL,  -- recipient agent
    type            VARCHAR(100) NOT NULL,      -- e.g. 'payout_cancelled', 'account_approved'
    subject         VARCHAR(255) NOT NULL,
    body            TEXT NOT NULL,
    is_read         BOOLEAN DEFAULT FALSE,
    read_at         TIMESTAMP NULL,
    related_model   VARCHAR(100) NULL,          -- e.g. 'Payout', 'Agent'
    related_id      BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE
);
```

#### Events that create inbox notifications

| Event | Type | Recipient(s) | Body |
|-------|------|-------------|------|
| Registration approved | `account_approved` | Agent | "Welcome! Your account has been approved." |
| Registration rejected | `account_rejected` | Agent | "Your application was rejected. Reason: {reason}." |
| Payout request received | `payout_received` | Agent#1 (admin inbox) | "Agent {Name} has requested payout #ID for RM X." |
| Payout cancelled by admin | `payout_cancelled` | Agent | "Payout #ID was cancelled. Reason: {reason}. Commissions returned to balance." |
| Payout marked as paid | `payout_paid` | Agent | "Payout #ID for RM X has been transferred to your account." |
| New agent under BP/Leader | `new_team_member` | Parent agent | "New agent {Name} has joined your team." |
| Commission reversal | `commission_reversed` | Agent + parent earners | "A commission from Sale #X has been reversed. RM {amount} deducted." |
| Appeal submitted | `appeal_submitted` | Agent | "Your suspension appeal has been received. We will review it shortly." |
| Account created (registration) | `account_created` | Agent | "Your account has been created. Complete payment to activate." |
| Renewal reminder | `renewal_reminder` | Agent | "Your membership expires in {N} days. Renew now." |
| Request Approval submitted | `approval_requested` | Agent#1 | "Agent {Name} has requested re-approval of their application." |

#### Screen: `/agent/inbox`

```
┌─────────────────────────────────────────────────────────┐
│  Inbox  [Unread: 3]             [Mark all read]         │
│                                                         │
│  ┌─────────────────────────────────────────────────┐   │
│  │  🔔  Payout #42 Cancelled                       │   │
│  │  Today 14:32  |  UNREAD                         │   │
│  │  Reason: Documents require re-verification...   │   │
│  │  [View Payout →]                                │   │
│  └─────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────┐   │
│  │  ✓  Account Approved                            │   │
│  │  Yesterday 09:15  |  Read                       │   │
│  │  Welcome! Your application has been approved.   │   │
│  └─────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
```

**Inbox Tabs:**
```
─────────────────────────────────────────────────────────
  [ Unread (3) ]  |  [ Pending (1) ]  |  [ Archived ]
─────────────────────────────────────────────────────────
```
- **Unread**: All notifications with `status = 'unread'` — default view
- **Pending**: Notifications with `status = 'pending'` — action-required items (appeals, approval requests, manual receipt verifications). These remain in Pending until admin/agent takes action.
- **Archived**: Notifications with `status = 'archived'` — dismissed/actioned items
- Bulk actions: [Mark All Read], [Archive Selected]
- `AgentNotification.status` enum: `unread`, `read`, `pending`, `archived`

**Behaviour**:
- Clicking a notification marks it as read (`status → 'read'`)
- Clicking "action-required" type notifications moves them to Pending
- If notification has a `related_model`, a [View →] link is shown
- Unread count (Unread + Pending combined) shown as badge on sidebar Inbox icon
- Inbox for Agent#1 shows admin-relevant events (new registrations, payout requests, appeals)

**Email parallel**: Every `AgentNotification` row created by `NotificationService::notify()` also dispatches a queued email job (`InboxNotificationEmail` mailable). Email failure must not block or revert the in-app notification. This is automatic — no separate call needed from callers of `NotificationService`.

---

### GAP-12 — Minimum Payout Threshold

**Resolution**: A `min_payout_amount` setting in `system_settings` (decimal, default RM 1.00) controls the minimum eligible amount before an agent can submit a payout request.

**System Settings screen**: Add to Fee Management section: "Minimum Payout Amount: RM [ 1.00 ]"

**SystemSettingsSeeder**: seed `min_payout_amount = 1.00`

**Agent Request Payout screen**: If eligible balance < `min_payout_amount`, [Request Payout] is disabled with message: "Minimum payout amount is RM {min}. Your available balance is RM {available}."

---

### GAP-13 — Admin Status Override + First-Login Onboarding + Email Pre-Check

#### 13.1 Admin Status Override
Admin can change an agent's status to any value at any time from `/admin/agents/{id}/update`. The status dropdown on the agent edit form includes: `Active`, `Inactive`, `Suspended`, `Banned`, `Expired`, `Pending`, `Rejected`.

Changes are activity-logged: "Status changed from {old} → {new} by Admin {name}."

#### 13.2 Admin Creates Agent — Account Created Email
When admin manually creates an agent via `/admin/agents/add`, a User account is also created with a temporary password, and the agent receives an `AccountCreatedByAdminNotification` email containing: their login email, a password-reset link (forces them to set their own password on first login).

#### 13.3 Email Pre-Check on Get Started Page
On the `/get-started` page, a simple email field is shown before the [Register Now] button:

```
┌───────────────────────────────────────────────────┐
│  Enter your email to get started                  │
│  [ _____________________________ ] [Continue →]   │
└───────────────────────────────────────────────────┘
```

On Continue → system checks the email:

| Condition | Action |
|-----------|--------|
| Email not found → | Redirect to `/register-as-agent` with email pre-filled |
| Email found + verified + password → | "You already have an account. [Log in →]" |
| Email found + password exists + NOT verified → | "Your email is not verified. [Reset Password →]" |
| Email found + no password → | "Finish setting up your account. [Set Password →]" (reset password flow) |

#### 13.4 Reset Password also Marks Email Verified
When a user successfully resets their password via the standard `/reset-password/{token}` flow, their `email_verified_at` is set to `now()` if not already set. This prevents the "not verified" loop for self-registered users who never verified.

#### 13.5 First-Login Onboarding — `/get-started-guide`
On first login (detected by `agent.first_login_at IS NULL`), the authenticated agent is redirected to `/get-started-guide` before reaching their dashboard.

**Screen**: Full-page slideshow (5–6 slides, auto-advance or manual tap). Each slide has an illustration, a headline, a short paragraph, and a [Next] button. The last slide has [Go to My Dashboard].

**Slide content by role**:

**Base Agent slides:**
1. Welcome — "You're now part of the Penurwill network!"
2. Your Referral Code — shows their code + copy button + share link
3. How Sales Work — "When someone signs up using your referral link and makes a purchase, you earn a commission."
4. Track Your Commissions — "Visit My Commissions to see all your earnings."
5. Request Payout — "Once you've accumulated earnings, visit Request Payout to cash out."
6. [Go to Dashboard →]

**Agent Leader additions (slides 3–5 replaced/expanded):**
3. Your Team — "You earn override commissions from all agents below you."
4. My Team Screen — "Visit My Team to see your agents' performance."
5. Team Reports — "Use Team Reports to analyse who is performing best."

**Business Partner additions (slides 3–5 replaced/expanded):**
3. Your Network — "You earn override commissions from Agent Leaders and their Agents."
4. My Network — "Visit My Network for a full tree view of your downline."
5. Network Reports — "Use Network Report and Commission Report to track every level."

**After completion**: `agent.first_login_at = now()` saved; future logins go directly to dashboard.

---

### GAP-15 — Referral Code Stats Page

**Screen**: `/agent/referral`

**Purpose**: Detailed view of an agent's referral code performance.

**Referral Code Card (top):**
```
┌────────────────────────────────────────────────────────┐
│  Your Referral Code                                    │
│                                                        │
│  REF-A7X92K  [Copy]   Status: Active                  │
│                                                        │
│  Share link: https://yoursite.com?ref=REF-A7X92K      │
│  [Copy Link]  [Share via WhatsApp]                    │
│                                                        │
│  Commission Rate: 10%   |   Expires: Never            │
└────────────────────────────────────────────────────────┘
```

**Stats Cards (date range: last 30 days by default, configurable):**
| Card | Formula | Notes |
|------|---------|-------|
| Total Visits | COUNT(`agent_visits` WHERE `referral_code_id` = this code) | Raw traffic count |
| Conversions | COUNT(`sales` WHERE `referral_code_id` = this code) | Visits that led to a sale |
| Conversion Rate | Conversions ÷ Total Visits × 100 | % (shown as 0% if no visits) |
| Avg Days to Convert | AVG(`sale.created_at` - linked `agent_visit.created_at`) | In days (rounded to 1 decimal) |
| Total Commission Earned | SUM(`commissions.amount` WHERE `earning_agent_id` = this agent AND source via this code) | RM |

**Attribution**: A visit is "converted" if a Sale exists linked via `referral_code_id`. **No time window cutoff** — attribution is permanent unless the sale is reversed. After reversal, the Sale still links but the commission amount is net zero.

**Visits Table (paginated, 20/page):**
| Column | Notes |
|--------|-------|
| Date/Time | Visit timestamp |
| IP Address | Anonymised (show first 2 octets: `192.168.x.x`) |
| Browser / Device | User agent simplified (Mobile / Desktop) |
| Referral Source | UTM medium / source if tracked (else "—") |
| Converted | ✓ Sale / — No sale |
| Days to Convert | Number (if converted, else "—") |
| Sale Amount | RM (if converted) |
| Commission | RM (if converted, else "—") |

**Filter Bar:** Date range (From/To), Converted (All / Yes / No)

**Sidebar link**: "🔗 My Referral" added to agent sidebar (see GAP-07).

---

### GAP-16 — Payout Request Notes Field

**Resolution**: Agent can add an optional note (max 500 characters) when submitting a payout request.

**UI change on `/agent/request-payout`**:
```
┌────────────────────────────────────────────────────────┐
│  Note (optional):                                      │
│  [ _______________________________________________  ]  │
│  [ _______________________________________________  ]  │
│  Max 500 characters                                    │
└────────────────────────────────────────────────────────┘
```

**Storage**: `payouts.agent_note` varchar(500) nullable.

**Admin view** (`/admin/payout/{id}`): Shows "Agent Note:" section if `agent_note` is present. Admin can also add their own internal note (`payouts.admin_note`) when approving, cancelling, or rejecting.

---

### GAP-17 — Empty States per Screen

**Resolution**: Every list/table screen must display a meaningful empty state when no records exist or no filter results match.

**Standard empty state component** (`EmptyState.vue`):
- Illustration (simple SVG icon relevant to the context)
- Headline: e.g., "No Sales Yet"
- Subtext: e.g., "Your sales will appear here once you make your first referral."
- Optional CTA button: e.g., [Copy Referral Link]

**Per-screen empty states:**

| Screen | Empty State Headline | Subtext | CTA |
|--------|---------------------|---------|-----|
| `/agent/sales` | No Sales Yet | Sales will appear when referrals convert. | [Copy Referral Link] |
| `/agent/commissions` | No Commissions Yet | Commissions are created when your referrals make purchases. | — |
| `/agent/payouts` | No Payouts Yet | Request your first payout once you have eligible commissions. | [Request Payout] |
| `/agent/team` | No Team Members Yet | You'll see agents here once they register under you. | — |
| `/agent/inbox` | All Caught Up! | No notifications at this time. | — |
| `/agent/referral` — visits table | No Visits Yet | Share your referral link to start tracking visits. | [Copy Link] |
| `/admin/agents/list` | No Agents Found | Adjust your filters or add a new agent. | [Add Agent] |
| `/admin/commissions/list` | No Commissions Found | Commissions appear when agents make sales. | — |
| `/admin/payouts` | No Payout Requests | Payout requests will appear here. | — |
| `/admin/activity-log` | No Activity Found | No actions match the selected filters. | — |

**Filter empty state**: When filters are applied and return no results, show: "No results for your current filters. [Clear Filters]"

---

### GAP-18 — Error Pages

**Resolution**: Custom error pages for the most common error codes, consistent with the app's design system.

#### 403 — Forbidden
```
┌────────────────────────────────────────────────────────┐
│                      🚫                                │
│              Access Denied                             │
│                                                        │
│  You don't have permission to access this page.        │
│                                                        │
│  [← Go Back]     [Home →]                              │
└────────────────────────────────────────────────────────┘
```

#### 404 — Not Found
```
┌────────────────────────────────────────────────────────┐
│                      🔍                                │
│              Page Not Found                            │
│                                                        │
│  The page you're looking for doesn't exist.            │
│  It may have been moved or deleted.                    │
│                                                        │
│  [← Go Back]     [Home →]                              │
└────────────────────────────────────────────────────────┘
```

#### 419 — Session Expired
```
┌────────────────────────────────────────────────────────┐
│                      ⏱                                │
│              Session Expired                           │
│                                                        │
│  Your session has expired for security reasons.        │
│  Please log in again to continue.                      │
│                                                        │
│  [Log In Again →]                                      │
└────────────────────────────────────────────────────────┘
```

#### 500 — Server Error
```
┌────────────────────────────────────────────────────────┐
│                      ⚡                                │
│              Something Went Wrong                      │
│                                                        │
│  We encountered an unexpected error. Our team has      │
│  been notified. Please try again later.                │
│                                                        │
│  [← Go Back]     [Home →]                              │
└────────────────────────────────────────────────────────┘
```

**Implementation**: Create `resources/js/Pages/Errors/` directory with `403.vue`, `404.vue`, `419.vue`, `500.vue`. Register in `bootstrap/app.php` via Inertia's `renderWhenUnauthorized`, or use Laravel's `render` method in `Handler.php` to return `Inertia::render('Errors/{code}')`.

---

### Screen Inventory Updates (from Gaps)

| Screen | Route | Change Status |
|--------|-------|---------------|
| Get Started (with email pre-check) | `/get-started` | **Modify** — add email pre-check field |
| Referral Stats | `/agent/referral` | **NEW** — full visit + conversion breakdown |
| Agent Inbox | `/agent/inbox` | **NEW** — notification inbox |
| First Login Guide | `/get-started-guide` | **NEW** — role-adaptive slide onboarding |
| Payment Resume | `/agent/payment/complete` | **NEW** — payment for skipped applicants |
| Admin Activity Log | `/admin/activity-log` | **NEW** — full audit trail |
| Stripe Success Handler | `/register-as-agent/payment/success` | **NEW** — backend-only redirect |
| Stripe Cancel Handler | `/register-as-agent/payment/cancelled` | **NEW** — redirect → Step 5 |
| Error 403 | — | **NEW** — custom error page |
| Error 404 | — | **NEW** — custom error page |
| Error 419 | — | **NEW** — custom error page |
| Error 500 | — | **NEW** — custom error page |
| Reset Password (marks verified) | `/reset-password/{token}` | **Modify** — set email_verified_at on success |

**Admin**
| Priority | Item | Screen |
|----------|------|--------|
| P1 | Agents List: Pending tab with fee status badge, Quick Approve button | `/admin/agents/list` |
| P1 | Agent View: pending banner, [Approve], [Reject], [Verify Receipt], receipt download | `/admin/agents/{id}/view` |
| P2 | Agent View: role & hierarchy section, fee status card, fee payment history table | `/admin/agents/{id}/view` |
| P2 | Agent View: commission rates section (own_sales %, override %) | `/admin/agents/{id}/view` |
| P2 | Agent Edit: role selector, parent agent dropdown, commission rate fields | `/admin/agents/{id}/update` |
| P2 | Payout Detail: status stepper (Pending→Approved→Processing→Paid), Approve/Process/Reject buttons | `/admin/payout/{id}` |
| P2 | Payouts List: status filter, Updated column, Quick Mark Paid | `/admin/payouts` |
| P2 | Admin Dashboard: second card row (pending payouts, agents by role, renewals, fees outstanding) | `/admin/dashboard` |
| P3 | Commission Detail: Override tab (shown for Leader/BP agents) | `/admin/commission/detail` |

**Agent — Base**
| Priority | Item | Screen |
|----------|------|--------|
| P1 | Dashboard: referral code box, quick-link cards row, recent payouts widget | `/agent/dashboard` |
| P1 | Sales List: pagination, filter bar (date range, status, search), [View] per row | `/agent/sales` |
| P1 | Sale Detail page — new route `/agent/sales/{id}` | NEW |
| P1 | Payout List: status lifecycle badges (pending/approved/processing/paid/rejected), Updated column | `/agent/payouts` |
| P1 | Payout Detail: status stepper timeline, [Download Receipt] button | `/agent/payouts/{id}` |
| P1 | Request Payout: confirmation dialog, disable already-in-request commissions | `/agent/request-payout` |
| P2 | Profile: membership section (role badge, fee status, expiry/renewal) | `/agent/profile` |
| P2 | Commissions: "Available to Request" card, payout ref column | `/agent/commissions` |

**Agent — Leader additions**
| Priority | Item | Screen |
|----------|------|--------|
| P2 | Dashboard: Row 2 team cards, team bar chart, top members widget | `/agent/dashboard` |
| P2 | Commissions: Two tabs (Own Sales / Override) conditional on `agent_role` | `/agent/commissions` |
| P2 | My Team page (new) — flat list + overview cards | NEW `/agent/team` |
| P2 | Team Member View (new) — read-only performance page | NEW `/agent/team/{id}` |
| P3 | Team Performance Report (new) — agent breakdown, charts, CSV export | NEW `/agent/reports/team` |

**Agent — Business Partner additions**
| Priority | Item | Screen |
|----------|------|--------|
| P2 | Dashboard: Row 2+3 network cards, stacked charts | `/agent/dashboard` |
| P2 | Commissions: Override tab shows L1 + L2 with Via + Level badge | `/agent/commissions` |
| P2 | My Network: Tree View / Flat List toggle | `/agent/team` |
| P3 | Network Report (new) — leader + agent breakdown, multi-level charts, CSV | NEW `/agent/reports/network` |
| P3 | Commission Report (new) — L1/L2 breakdown, full paginated table, CSV | NEW `/agent/reports/commissions` |

---

## 12. Gap Resolutions — Round 2 (2026-05-03)

> Items G01–G23 resolved by project owner. Backend decisions in DECISION_OUTCOMES.md (Decisions 18–27).

---

### GAP-19 — Commission Reversal Time Limit

**Resolution**: Admin can only reverse sales within `reversal_time_limit` days (SystemSetting, default 60). Outside the window, [Mark as Reversed] is disabled with tooltip: "Reversal window expired ({N} days)."

**UI change on `/admin/commission/detail`** or `/admin/commissions/list`:
- [Mark as Reversed] button: enabled only if `sale.created_at >= today - reversal_time_limit`
- Disabled state shows: "Reversal window closed (exceeded {N}-day limit)"

**System Settings edit**: Add field "Commission Reversal Window (days): [ 60 ]" in the Fee Management section.

---

### GAP-20 — Clawback from Already-Paid Commissions

**Resolution**: No separate clawback screen. Reversal rows (negative amounts) are automatically included in the agent's next payout request.

**Agent Request Payout screen update**:
```
┌──────────────────────────────────────────────────────────┐
│  Available to Request                                    │
│                                                          │
│  Eligible commissions:    RM 500.00  (5 items)          │
│  Pending reversals:     - RM 120.00  (1 reversal)       │
│  ─────────────────────────────────────────────────       │
│  Net payout amount:       RM 380.00                     │
│                                                          │
│  ⚠ A commission reversal from Sale #42 has been        │
│    deducted from this request.                           │
└──────────────────────────────────────────────────────────┘
```
- If net total ≤ 0, [Request Payout] is disabled: "Net payout is zero or negative due to pending reversals. Contact admin."
- Reversal items shown in the commission table with a "REVERSAL" badge and negative amount in red.

---

### GAP-21 — Admin Reject After Stripe Payment

**Resolution**: No automated refund in the system (yet). Rejection of a fee-paid agent shows a warning popup.

**Admin `/admin/agents/{id}/view` — Reject flow (when fee is paid)**:
```
┌──────────────────────────────────────────────────────────┐
│  ⚠ Fee Payment on Record                                │
│                                                          │
│  This agent has a completed payment:                     │
│  • Amount: RM 100.00                                     │
│  • Method: Stripe (Session ID: cs_xxxx)                 │
│  • Date: 2026-04-28                                      │
│                                                          │
│  Please process a manual refund via the Stripe           │
│  dashboard before or after rejection.                    │
│  Stripe Dashboard → Payments → Search Session ID        │
│                                                          │
│  [Cancel]          [Confirm Rejection Anyway →]          │
└──────────────────────────────────────────────────────────┘
```

**Future TODO**: Auto-trigger Stripe refund via Cashier on rejection. `fee_payments.payment_reference` stores the Stripe Checkout Session ID for this purpose.

---

### GAP-22 — Admin-Created Agent Fee Flow

**Resolution**: When admin creates an agent directly, the fee is optional. Admin approval always overrides fee status.

**Admin `/admin/agents/add` — Fee section**:
```
┌──────────────────────────────────────────────────────────┐
│  Fee Payment (optional)                                  │
│                                                          │
│  ○  Agent paid via bank transfer — upload receipt:      │
│     [ Choose File ] (PDF/JPG/PNG)                       │
│                                                          │
│  ○  No payment collected                                │
│                                                          │
│  Note: Clicking [Approve Agent] will activate the       │
│  agent regardless of fee status. Fee will be marked     │
│  as "Waived" if no payment is recorded.                 │
└──────────────────────────────────────────────────────────┘
```

**Approval button behaviour** (Section 4.6.1 update):
| Fee Status | [Approve Agent] Action |
|------------|----------------------|
| `paid` (Stripe or verified manual) | Normal approval: `FeeService::applyEntryFee()` |
| `pending_verification` (receipt uploaded) | Mark as verified + apply fee |
| `pending` (no payment) | Set `fee_payment_status = 'waived'`; no `fee_payments` row |

---

### GAP-23 — Scheduler Monitor in Admin Dashboard

**Resolution**: Admin dashboard shows scheduler health status and failed job counts.

**Admin Dashboard — new "System Health" section**:

```
┌──────────────────────────────────────────────────────────┐
│  System Health                                           │
│                                                          │
│  Scheduler Jobs:                                         │
│  ┌─────────────────────┬────────────┬──────────────────┐│
│  │  Job                │ Last Run   │ Status           ││
│  ├─────────────────────┼────────────┼──────────────────┤│
│  │  ProcessRenewals    │  2h ago    │  ✓ OK            ││
│  └─────────────────────┴────────────┴──────────────────┘│
│                                                          │
│  ⚠ ProcessRenewals has not run in 26 hours!             │  ← alert banner
│  Check that the Laravel scheduler is running.           │                                                          │
│  Failed Jobs: 2  [View Failed Jobs →]                   │
└──────────────────────────────────────────────────────────┘
```

**Alert conditions**:
- **STALE**: `scheduler_logs` latest row for a job type has `ran_at < now() - 24h`
- **NEVER RAN**: No `scheduler_logs` row exists for that job type
- **FAILED**: Latest row has `status = 'failed'`

**Failed Jobs link**: points to a filtered view of Laravel's `failed_jobs` table (read-only admin screen, or external Horizon/Telescope if available).
