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
                            
                            <!-- Success Message -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #d1fae5; border-left: 4px solid #065f46; border-radius: 4px; padding: 16px; margin: 20px 0;">
                                <tr>
                                    <td>
                                        <p style="margin: 0; color: #065f46; font-size: 14px; font-weight: 600;">
                                            ✓ Payment Completed
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Payout Details Card -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f3f4f6; border-radius: 8px; padding: 20px; margin: 20px 0;">
                                <tr>
                                    <td>
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="padding: 8px 0;">
                                                    <strong style="color: #162d25; font-size: 14px;">Payout ID:</strong>
                                                    <span style="color: #374151; font-size: 14px; margin-left: 10px;">#{{ $payout->id ?? '[PAYOUT_ID]' }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0;">
                                                    <strong style="color: #162d25; font-size: 14px;">Amount Paid:</strong>
                                                    <span style="color: #162d25; font-size: 18px; font-weight: 700; margin-left: 10px;">RM {{ isset($payout) ? number_format($payout->amount, 2) : '[PAYOUT_AMOUNT]' }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0;">
                                                    <strong style="color: #162d25; font-size: 14px;">Paid Date:</strong>
                                                    <span style="color: #374151; font-size: 14px; margin-left: 10px;">{{ isset($payout) && $payout->paid_at ? $payout->paid_at->format('d M Y, h:i A') : '[PAYOUT_PAID_AT]' }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <div style="margin: 20px 0 0 0; color: #374151; font-size: 14px; line-height: 1.6;">
                                {!! $template->getFilled('body_notes') !!}
                            </div>
                            
                            @if(!isset($payout) || $payout->bank_transfer_file)
                            <div style="margin: 20px 0 0 0; color: #6b7280; font-size: 14px; line-height: 1.6;">
                                {!! $template->getFilled('body_bank_file') !!}
                            </div>
                            @endif
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
