import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';
import Sales from '../../../../resources/js/Pages/Agent/Sales.vue';
import { config } from '@vue/test-utils';

describe('Agent Module: Sales', () => {
    const mockSales = [
        {
            id: 1,
            sale_date: '2024-01-01 10:00:00',
            description: 'Test Sale',
            invoice_number: 'INV-001',
            amount: 100,
            commission: { amount: 10, status: 'pending' },
        }
    ];

    const mockProps = {
        sales: mockSales,
        filters: { status: 'pending' },
        agent: { agent_role: 'agent' },
    };

    it('renders the sales list', () => {
        config.global.mocks.$page.props = {
            ...config.global.mocks.$page.props,
            systemSettings: { role_name_agent: 'Agent' },
        };

        const wrapper = mount(Sales, {
            props: mockProps
        });

        expect(wrapper.text()).toContain('My Sales');
        expect(wrapper.text()).toContain('Test Sale');
        expect(wrapper.text()).toContain('RM 100');
        expect(wrapper.text()).toContain('Pending');
    });

    it('shows source agent column for leaders', () => {
        const leaderProps = {
            ...mockProps,
            agent: { agent_role: 'agent_leader' },
            sales: [
                {
                    ...mockSales[0],
                    source_agent: { name: 'Subordinate Agent', agent_role: 'agent' }
                }
            ]
        };

        const wrapper = mount(Sales, {
            props: leaderProps
        });

        expect(wrapper.text()).toContain('Source Agent');
        expect(wrapper.text()).toContain('Subordinate Agent');
    });
});
