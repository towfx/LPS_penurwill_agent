import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import Dashboard from '../../../../resources/js/Pages/Admin/Dashboard.vue';
import { config } from '@vue/test-utils';

describe('Admin Module: Dashboard', () => {
    const mockProps = {
        stats: {
            revenueThisMonth: 100000,
            revenueChange: 15,
            activeAgents: 50,
            agentsChange: 5,
            commissionsPaid: 15000,
            commissionsChange: 10,
            conversionRate: 8.5,
            conversionChange: 1.2,
        },
        monthlyRevenue: { 'Jan': 80000, 'Feb': 100000 },
        topAgents: [
            { name: 'Agent Smith', revenue: 5000 },
            { name: 'Agent Doe', revenue: 4000 },
        ],
        commissionDistribution: {
            pending: 5000,
            completed: 15000,
            cancelled: 500,
        },
        referralsByDay: { '2024-01-01': 10 },
        salesByDay: { '2024-01-01': 5 },
        recentActivity: [],
        quickActions: {
            pendingPayouts: 5,
            pendingPayoutsAmount: 2000,
            activeAgentsCount: 50,
            totalAgents: 60,
        },
        systemHealth: {
            avgConversionRate: 8.5,
            avgCommissionRate: 10,
            totalReferrals: 1000,
            totalSales: 85,
        },
        schedulerAlerts: [],
        failedJobsCount: 0,
    };

    it('renders the admin dashboard with overview stats', () => {
        config.global.mocks.$page.props = {
            ...config.global.mocks.$page.props,
            ...mockProps,
        };

        const wrapper = mount(Dashboard);

        expect(wrapper.text()).toContain('Admin Dashboard');
        expect(wrapper.text()).toContain('RM 100,000');
        expect(wrapper.text()).toContain('50'); // Active Agents
    });

    it('shows scheduler warning when jobs are stale', () => {
        config.global.mocks.$page.props.schedulerAlerts = [
            { job: 'App\\Jobs\\ProcessCommissions', state: 'stale', last_ran: '2024-01-01 00:00:00' }
        ];

        const wrapper = mount(Dashboard);

        expect(wrapper.text()).toContain('Scheduler may not be running');
    });
});
