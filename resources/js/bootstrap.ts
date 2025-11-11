import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

// Configure CSRF token for axios requests
const token = document.head.querySelector('meta[name="csrf-token"]') as HTMLMetaElement;
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

// For SPA authentication, also support XSRF tokens from cookies
window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';

// Global function to update CSRF token
window.updateCsrfToken = (newToken: string) => {
    // Update axios default header
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = newToken;

    // Update meta tag in document head
    const metaToken = document.head.querySelector('meta[name="csrf-token"]') as HTMLMetaElement;
    if (metaToken) {
        metaToken.content = newToken;
    }
};

// Proactive CSRF token refresh function
window.refreshCsrfToken = async () => {
    try {
        const response = await window.axios.get('/api/csrf-token');
        if (response.data.csrf_token && window.updateCsrfToken) {
            window.updateCsrfToken(response.data.csrf_token);
        }
    } catch (error) {
        // Silently fail - don't disrupt user experience
        console.debug('CSRF token refresh failed:', error);
    }
};

// Refresh CSRF token every 30 minutes to prevent expiration
setInterval(window.refreshCsrfToken, 30 * 60 * 1000);

// Ziggy route() function is provided globally via @routes directive in app.blade.php
