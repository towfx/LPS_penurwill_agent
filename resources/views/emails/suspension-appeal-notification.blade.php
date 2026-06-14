<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $template->getFilledTitle() }}</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Geist', 'Figtree', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif; background-color: #eae1d0;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #eae1d0; padding: 20px 0;">
        <tr>
            <td align="center">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #7a9b7d; padding: 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 700;">{{ $template->getFilledTitle() }}</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <div style="margin: 0 0 20px 0; color: #374151; font-size: 16px; line-height: 1.6;">
                                {!! $template->getFilled('body_main') !!}
                            </div>
                            
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f3f4f6; border-radius: 8px; padding: 20px; margin: 20px 0;">
                                <tr>
                                    <td>
                                        <div style="color: #374151; font-size: 14px; font-style: italic; line-height: 1.6;">
                                            {!! nl2br($template->getFilled('body_appeal')) !!}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <div style="margin: 20px 0 0 0; color: #374151; font-size: 14px; line-height: 1.6;">
                                {!! $template->getFilled('footer_message') !!}
                            </div>
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
