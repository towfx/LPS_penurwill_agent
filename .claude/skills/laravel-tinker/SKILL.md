---
name: laravel-tinker
description: "Use when the user wants to inspect, debug, or manipulate the Laravel app via php artisan tinker. Examples: 'find controller for this URL', 'check what a model returns', 'seed one record manually', 'test a service method without writing a test'."
---

# Laravel Tinker Skill

`php artisan tinker` = live PHP REPL against your full Laravel app. No test overhead, no temp files.

## Wrapping PHP Code Properly

### Single line (safe for simple expressions)
```bash
php artisan tinker --execute="expression_here"
```

### Multi-line (use single quotes outside, double inside for strings)
```bash
php artisan tinker --execute="
\$user = App\Models\User::find(1);
print_r(\$user->toArray());
"
```

> **Rules:**
> - Outer shell uses double quotes `"..."` — escape `$` as `\$` to prevent shell variable expansion
> - Use `print_r()` or `var_dump()` — `dd()` works but dumps then exits cleanly
> - Semicolons required on every statement
> - Chain expressions with newlines inside the `--execute` string
> - For heredoc-safe multiline, use a temp PHP file: `php artisan tinker --execute="$(cat /tmp/t.php)"`

---

## Problems Tinker Solves

### 1. Find Which Controller Handles a URL
```bash
php artisan tinker --execute="
\$route = app('router')->getRoutes()->match(app('request')->create('/your-path', 'GET'));
print_r(\$route->getAction());
"
```

### 2. Inspect a Model — Columns, Attributes, Casts
```bash
php artisan tinker --execute="
\$m = new App\Models\Agent();
print_r(\$m->getFillable());
print_r(\$m->getCasts());
"
```

### 3. Test a Query Without Writing a Test
```bash
php artisan tinker --execute="
\$results = App\Models\Commission::where('status', 'pending')->with('agent')->get();
print_r(\$results->toArray());
"
```

### 4. Check Config / Env Values at Runtime
```bash
php artisan tinker --execute="
echo config('mail.mailers.smtp.host') . PHP_EOL;
echo env('APP_ENV') . PHP_EOL;
"
```

### 5. Manually Trigger a Queued Job
```bash
php artisan tinker --execute="
dispatch(new App\Jobs\YourJob(\$params));
echo 'Job dispatched';
"
```

### 6. Fire an Event and See Listeners React
```bash
php artisan tinker --execute="
event(new App\Events\AgentApproved(App\Models\Agent::find(1)));
"
```

### 7. Test a Mailable — Render to HTML
```bash
php artisan tinker --execute="
\$mail = new App\Mail\AgentRegisteredNotification(App\Models\Agent::find(1));
echo \$mail->render();
" > /tmp/email_preview.html && open /tmp/email_preview.html
```

### 8. Check User Roles & Permissions (Spatie)
```bash
php artisan tinker --execute="
\$user = App\Models\User::find(1);
print_r(\$user->getRoleNames()->toArray());
print_r(\$user->getAllPermissions()->pluck('name')->toArray());
"
```

### 9. Manually Seed One Record
```bash
php artisan tinker --execute="
App\Models\Agent::create([
    'individual_name' => 'Test Agent',
    'individual_email' => 'test@mail.com',
    'profile_type' => 'individual',
    'status' => 'active',
    'agent_role' => 'agent',
    'fee_payment_status' => 'paid',
]);
echo 'Done';
"
```

### 10. Test Service / Helper Class Logic
```bash
php artisan tinker --execute="
\$service = app(App\Services\FeeService::class);
\$result = \$service->calculateFee(App\Models\Agent::find(1));
var_dump(\$result);
"
```

---

## Creative Uses

### Snapshot — Count All Tables
```bash
php artisan tinker --execute="
foreach (DB::select('SHOW TABLES') as \$t) {
    \$t = (array)\$t;
    \$name = array_values(\$t)[0];
    \$count = DB::table(\$name)->count();
    echo \"{$name}: {\$count}\n\";  // use escape in real shell
}
"
```
> Tip: store output to a file for before/after migration comparison.

### Schema Diff — Describe Any Table
```bash
php artisan tinker --execute="print_r(DB::select('DESCRIBE table_name'));"
```

### Live Route Inspector — All Routes for a Controller
```bash
php artisan tinker --execute="
\$routes = collect(app('router')->getRoutes()->getRoutes())
    ->filter(fn(\$r) => str_contains(\$r->getActionName(), 'AgentRegistrationController'))
    ->map(fn(\$r) => \$r->uri() . ' [' . implode(',', \$r->methods()) . '] => ' . \$r->getActionName());
print_r(\$routes->values()->toArray());
"
```

### Impersonate — Generate Sanctum Token for User
```bash
php artisan tinker --execute="
\$user = App\Models\User::where('email', 'agent@mail.com')->first();
echo \$user->createToken('tinker-debug')->plainTextToken;
"
```

### Audit — Last 10 Activity Logs
```bash
php artisan tinker --execute="
\$logs = App\Models\ActivityLog::latest()->take(10)->get(['action','description','created_at']);
print_r(\$logs->toArray());
"
```

### Repair — Bulk-update Records Safely
```bash
php artisan tinker --execute="
DB::transaction(function() {
    \$count = App\Models\Agent::where('status', 'expired')
        ->whereNull('expires_at')
        ->update(['expires_at' => now()]);
    echo \"Updated: {\$count}\n\";
});
"
```

### Validate — Check If Specific Migration Ran
```bash
php artisan tinker --execute="
\$ran = DB::table('migrations')->where('migration', 'like', '%create_subscriptions%')->exists();
echo \$ran ? 'YES — ran' : 'NO — not ran';
"
```

### Profile — Time a Query
```bash
php artisan tinker --execute="
\$start = microtime(true);
App\Models\Commission::with('agent')->where('status','pending')->get();
echo round((microtime(true) - \$start) * 1000, 2) . 'ms';
"
```

---

## Gotchas

| Problem | Fix |
|---|---|
| `\$var` expands in shell | Escape as `\\\$var` or use single-quoted heredoc |
| `dd()` works but halts | Use `print_r()` / `var_export()` for non-halting output |
| Model not found | Check namespace — always use full `App\Models\ClassName` |
| Stale config | Run `php artisan config:clear` before tinker session |
| Queue not dispatching | Ensure `QUEUE_CONNECTION=sync` in `.env` for instant execution in tinker |

## Must do

When this skill invoked preset me with full path:
 - Contoller+Method with line number
 - model used
 - View files