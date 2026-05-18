<?php

use App\Models\Agent;

return [
    'agent' => [
        'slug'            => 'agent',
        'agent_role'      => Agent::ROLE_AGENT,
        'spatie_role'     => 'agent',
        'role_name_key'   => 'agent',
        'profile_type'    => null,
        'fee_setting_key' => 'agent',
        'description'     => 'Start as an agent, earn commissions on your sales',
        'features'        => [
            'Individual or company profile',
            'Earn own-sales commissions',
            'Eligible for role upgrade to Leader',
        ],
        'icon'            => 'Users',
    ],
    'agent_leader' => [
        'slug'            => 'agent_leader',
        'agent_role'      => Agent::ROLE_AGENT_LEADER,
        'spatie_role'     => 'agent_leader',
        'role_name_key'   => 'agent_leader',
        'profile_type'    => null,
        'fee_setting_key' => 'leader',
        'description'     => 'Start as an agent, earn commissions on your sales',
        'features'        => [
            'Individual or company profile',
            'Earn own-sales commissions',
            'Override commissions from team agents',
        ],
        'icon'            => 'Users',
    ],
    'business_partner' => [
        'slug'            => 'business_partner',
        'agent_role'      => Agent::ROLE_BUSINESS_PARTNER,
        'spatie_role'     => 'business_partner',
        'role_name_key'   => 'business_partner',
        'profile_type'    => 'company',
        'fee_setting_key' => 'business_partner',
        'description'     => 'Build and manage a team of leaders and agents',
        'features'        => [
            'Company profile required',
            'Earn override commissions from leaders and agents',
            'Full network management tools',
        ],
        'icon'            => 'Building2',
    ],
];
