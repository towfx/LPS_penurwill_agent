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
                'default' => '<p>Your payout has been processed and paid successfully.</p>',
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
