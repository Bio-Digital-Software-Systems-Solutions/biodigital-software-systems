import '../css/app.css';
import './bootstrap';
import './i18n';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { ToastProvider } from './Components/ui/toast';
import { ConfirmDialogProvider } from './Components/ui/confirm-dialog';
import { router } from '@inertiajs/react';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        // Update CSRF token when available from Inertia props
        if (props.initialPage.props.csrf_token && window.updateCsrfToken) {
            window.updateCsrfToken(props.initialPage.props.csrf_token as string);
        }

        root.render(
            <ToastProvider>
                <ConfirmDialogProvider>
                    <App {...props} />
                </ConfirmDialogProvider>
            </ToastProvider>
        );
    },
    progress: false, // Progress indicators disabled for instant navigation
});

// Listen for Inertia page loads to update CSRF token
router.on('navigate', (event) => {
    if (event.detail.page.props.csrf_token && window.updateCsrfToken) {
        window.updateCsrfToken(event.detail.page.props.csrf_token as string);
    }
});
