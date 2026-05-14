import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';
import Dashboard from '../../../../resources/js/Pages/Agent/Dashboard.vue';
import { config } from '@vue/test-utils';

describe('Agent Module: Dashboard', () => {
    const mockProps = {
        agent: {
            status: 'active',
            fee_payment_status: 'paid',
        },
        stats: {
            salesThisMonth: 5000,
            salesChange: 10,
            commThisMonth: 500,
            commChange: 5,
            referrals90: 20,
            refChange: 2,
            conversionRate: 5.5,
            conversionChange: 0.5,
        },
        salesByDay: { '2024-01-01': 1000 },
        referralsByDay: { '2024-01-01': 5 },
        conversionRateByDay: { '2024-01-01': 5.5 },
        recentSales: [],
        performance: {
            avgSaleValue: 100,
            totalPayouts: 1000,
            pendingPayouts: 200,
        },
        systemSettings: {
            role_name_agent: 'Agent',
        },
    };

    it('renders the agent dashboard with stats', () => {
        // Set page props for this test
        config.global.mocks.$page.props = {
            ...config.global.mocks.$page.props,
            ...mockProps,
            agentContext: {}, // Ensure this exists
            systemSettings: { role_name_agent: 'Agent' },
        };

        const wrapper = mount(Dashboard);

        expect(wrapper.text()).toContain('Agent Dashboard');
        expect(wrapper.text()).toContain('RM 5,000');
        expect(wrapper.text()).toContain('RM 500');
    });

    it('shows suspension banner when agent is suspended', () => {
        config.global.mocks.$page.props.agentContext = {
            agent_status: 'suspended'
        };
        config.global.mocks.$page.props.agent = {
            status: 'suspended',
            suspension_reason: 'Account under review'
        };

        const wrapper = mount(Dashboard);

        expect(wrapper.text()).toContain('Your account has been suspended');
        expect(wrapper.text()).toContain('Account under review');
    });
});
