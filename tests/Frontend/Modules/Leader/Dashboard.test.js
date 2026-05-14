import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import Dashboard from '../../../../resources/js/Pages/Agent/Dashboard.vue';
import { config } from '@vue/test-utils';

describe('Leader Module: Dashboard', () => {
    it('renders the dashboard with leader role', () => {
        config.global.mocks.$page.props = {
            ...config.global.mocks.$page.props,
            agent: { agent_role: 'agent_leader', status: 'active' },
            stats: { salesThisMonth: 0, salesChange: 0, commThisMonth: 0, commChange: 0, referrals90: 0, refChange: 0, conversionRate: 0, conversionChange: 0 },
            salesByDay: {}, referralsByDay: {}, conversionRateByDay: {},
            recentSales: [],
            performance: { avgSaleValue: 0, totalPayouts: 0, pendingPayouts: 0 },
            agentContext: {},
            systemSettings: { role_name_leader: 'Team Leader' },
        };

        const wrapper = mount(Dashboard);

        expect(wrapper.text()).toContain('Agent Dashboard');
    });
});
