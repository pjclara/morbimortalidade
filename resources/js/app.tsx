import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { route as routeFn } from 'ziggy-js';
import { initializeTheme } from './hooks/use-appearance';

declare global {
    const route: typeof routeFn;
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

function shouldRedirectToLogin(response: any): boolean {
    return response?.status === 401 || response?.status === 419;
}

function setupSessionRedirect(): void {
    document.addEventListener('inertia:exception', (event) => {
        const detail = (event as CustomEvent).detail;
        const response = detail?.exception?.response || detail?.exception;

        if (shouldRedirectToLogin(response)) {
            window.location.href = route('login');
        }
    });

    document.addEventListener('inertia:invalid', (event) => {
        const response = (event as CustomEvent).detail?.response;

        if (shouldRedirectToLogin(response)) {
            window.location.href = route('login');
        }
    });
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);

        setupSessionRedirect();
        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
