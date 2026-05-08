<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Email Verification</title></head>
<body style="font-family:sans-serif;background:#eae1d0;padding:20px;">
<table role="presentation" width="600" style="background:#fff;border-radius:8px;padding:40px;margin:0 auto;">
<tr><td>
    <h2 style="color:#162d25;margin-top:0;">Verify your email</h2>
    <p style="color:#374151;">Use the code below to verify your email address. It expires in 15 minutes.</p>

    <div style="text-align:center;margin:32px 0;">
        <span style="display:inline-block;font-size:36px;font-weight:700;letter-spacing:12px;color:#162d25;background:#eae1d0;border-radius:8px;padding:16px 32px;">{{ $verification->code }}</span>
    </div>

    <p style="color:#6b7280;font-size:13px;">If you did not request this, you can safely ignore this email.</p>
    <p style="color:#6b7280;font-size:12px;margin-top:32px;border-top:1px solid #e5e7eb;padding-top:16px;">{{ config('app.name') }} — automated message, do not reply.</p>
</td></tr>
</table>
</body>
</html>
