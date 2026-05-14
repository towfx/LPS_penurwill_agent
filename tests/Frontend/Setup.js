import { config } from '@vue/test-utils';
import { vi } from 'vitest';

// Mock Ziggy's route function
global.route = vi.fn((name) => name);

// Mock Inertia's $page
config.global.mocks = {
    $page: {
        props: {
            auth: {
                user: null,
            },
        },
    },
    route: global.route,
};

// Mock @inertiajs/vue3
vi.mock('@inertiajs/vue3', async () => {
    const actual = await vi.importActual('@inertiajs/vue3');
    return {
        ...actual,
        usePage: () => config.global.mocks.$page,
        useForm: (data) => {
            const form = {
                ...data,
                post: vi.fn(),
                get: vi.fn(),
                put: vi.fn(),
                delete: vi.fn(),
                processing: false,
                errors: {},
                reset: vi.fn(),
                clearErrors: vi.fn(),
                setError: vi.fn(),
                wasSuccessful: false,
                recentlySuccessful: false,
                transform: vi.fn((transformer) => {
                    // Just return the same form for chaining
                    return form;
                }),
            };
            return form;
        },
        router: {
            post: vi.fn(),
            visit: vi.fn(),
            get: vi.fn(),
            put: vi.fn(),
            delete: vi.fn(),
        },
        Head: {
            template: '<div><slot /></div>',
        },
        Link: {
            template: '<a><slot /></a>',
        },
    };
});

// Mock VueDatePicker
vi.mock('@vuepic/vue-datepicker', () => ({
    VueDatePicker: {
        template: '<input type="date" />',
        props: ['modelValue']
    }
}));



