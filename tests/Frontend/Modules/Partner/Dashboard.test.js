import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import Dashboard from '../../../../resources/js/Pages/Partner/Dashboard.vue';
import { config } from '@vue/test-utils';

describe('Partner Module: Dashboard', () => {
    const mockProps = {
        agent: {
            status: 'active',
            fee_payment_status: 'paid',
        },
        stats: {
            salesThisMonth: 10000,
            salesChange: 20,
            commThisMonth: 1000,
            commChange: 15,
            referrals90: 50,
            refChange: 10,
            conversionRate: 10.5,
            conversionChange: 2.5,
        },
        salesByDay: { '2024-01-01': 2000 },
        referralsByDay: { '2024-01-01': 10 },
        conversionRateByDay: { '2024-01-01': 10.5 },
        recentSales: [],
        performance: {
            avgSaleValue: 200,
            totalPayouts: 5000,
            pendingPayouts: 500,
        },
    };

    it('renders the partner dashboard', () => {
        config.global.mocks.$page.props = {
            ...config.global.mocks.$page.props,
            ...mockProps,
            agentContext: {},
            systemSettings: { role_name_business_partner: 'Business Partner' },
        };

        const wrapper = mount(Dashboard);

        expect(wrapper.text()).toContain('Agent Dashboard'); // It uses the same title usually
        expect(wrapper.text()).toContain('RM 10,000');
    });
});
