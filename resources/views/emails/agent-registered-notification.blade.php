<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>New Agent Registered</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Geist', 'Figtree', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif; background-color: #eae1d0;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #eae1d0; padding: 20px 0;">
        <tr>
            <td align="center">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #7a9b7d; padding: 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 700;">New Agent Registered</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="margin: 0 0 20px 0; color: #374151; font-size: 16px; line-height: 1.6;">
                                @if($agent->partner)
                                    A new agent has been registered under {{ $agent->partner->company_name }}.
                                @else
                                    A new agent has been registered.
                                @endif
                            </p>
                            
                            <!-- Agent Details Card -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f3f4f6; border-radius: 8px; padding: 20px; margin: 20px 0;">
                                <tr>
                                    <td>
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding: 8px 0;">
                                                    <strong style="color: #162d25; font-size: 14px;">Agent Name:</strong>
                                                    <span style="color: #374151; font-size: 14px; margin-left: 10px;">{{ $agent->name }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0;">
                                                    <strong style="color: #162d25; font-size: 14px;">Profile Type:</strong>
                                                    <span style="color: #374151; font-size: 14px; margin-left: 10px;">{{ ucfirst($agent->profile_type) }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0;">
                                                    <strong style="color: #162d25; font-size: 14px;">Phone:</strong>
                                                    <span style="color: #374151; font-size: 14px; margin-left: 10px;">
                                                        @if($agent->profile_type === 'individual')
                                                            {{ $agent->individual_phone ?? 'N/A' }}
                                                        @else
                                                            {{ $agent->company_phone ?? 'N/A' }}
                                                        @endif
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0;">
                                                    <strong style="color: #162d25; font-size: 14px;">Email:</strong>
                                                    <span style="color: #374151; font-size: 14px; margin-left: 10px;">
                                                        @if($agent->profile_type === 'individual')
                                                            {{ $agent->individual_email ?? 'N/A' }}
                                                        @else
                                                            {{ $agent->company_email_address ?? 'N/A' }}
                                                        @endif
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 20px 0 0 0; color: #374151; font-size: 14px; line-height: 1.6;">
                                Please login to {{ config('app.name') }} to view more details about this agent.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 20px 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; color: #6b7280; font-size: 12px;">
                                This is an automated notification. Please do not reply to this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
