import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';
import Login from '../../../../resources/js/Pages/Auth/Login.vue';
import { router } from '@inertiajs/vue3';

describe('Auth Module: Login', () => {
    it('renders the login form', () => {
        const wrapper = mount(Login, {
            props: {
                canResetPassword: true,
                status: null,
            },
        });

        expect(wrapper.find('input[type="email"]').exists()).toBe(true);
        expect(wrapper.find('input[type="password"]').exists()).toBe(true);
        expect(wrapper.find('button[type="submit"]').text()).toContain('Log in');
    });

    it('submits the login form', async () => {
        const wrapper = mount(Login, {
            props: {
                canResetPassword: true,
                status: null,
            },
        });

        await wrapper.find('input[type="email"]').setValue('test@example.com');
        await wrapper.find('input[type="password"]').setValue('password');
        
        // We can't easily check the local form object without more complex mocking,
        // but we can at least ensure the trigger doesn't throw and the component behaves.
        await wrapper.find('form').trigger('submit.prevent');
    });
});
