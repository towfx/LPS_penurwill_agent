<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $notification->subject }}</title>
</head>
<body style="margin:0;padding:0;background-color:#eae1d0;font-family:Arial,sans-serif;">
<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color:#eae1d0;padding:40px 20px;">
    <tr>
        <td align="center">
            <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="background-color:#ffffff;border-radius:8px;overflow:hidden;max-width:600px;width:100%;">

                {{-- Header --}}
                <tr>
                    <td style="background-color:#162d25;padding:24px 32px;">
                        <p style="margin:0;color:#bc9c5f;font-size:18px;font-weight:bold;letter-spacing:0.5px;">
                            {{ config('app.name') }}
                        </p>
                    </td>
                </tr>

                {{-- Body --}}
                <tr>
                    <td style="padding:32px;">
                        <h2 style="margin:0 0 16px;color:#162d25;font-size:20px;">
                            {{ $notification->subject }}
                        </h2>
                        <p style="margin:0 0 24px;color:#374151;font-size:15px;line-height:1.6;">
                            {{ $notification->body }}
                        </p>
                        <table role="presentation" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="border-radius:6px;background-color:#162d25;">
                                    <a href="{{ config('app.url') }}/agent/inbox"
                                       style="display:inline-block;padding:12px 24px;color:#ffffff;font-size:14px;font-weight:bold;text-decoration:none;border-radius:6px;">
                                        View in Inbox
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="padding:20px 32px;border-top:1px solid #e5e7eb;">
                        <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.5;">
                            This is an automated notification from {{ config('app.name') }}. Please do not reply to this email.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
