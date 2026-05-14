import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';
import Welcome from '../../../resources/js/Pages/Welcome.vue';

describe('Welcome.vue', () => {
    it('renders the welcome message', () => {
        const wrapper = mount(Welcome, {
            props: {
                canLogin: true,
                canRegister: true,
                laravelVersion: '11.0.0',
                phpVersion: '8.3.0',
            },
        });

        expect(wrapper.text()).toContain('Documentation');
        expect(wrapper.text()).toContain('Laravel v11.0.0');
        expect(wrapper.text()).toContain('PHP v8.3.0');
    });

    it('shows login and register links when canLogin is true', () => {
        const wrapper = mount(Welcome, {
            props: {
                canLogin: true,
                canRegister: true,
                laravelVersion: '11.0.0',
                phpVersion: '8.3.0',
            },
        });

        expect(wrapper.text()).toContain('Log in');
        expect(wrapper.text()).toContain('Register');
    });
});
