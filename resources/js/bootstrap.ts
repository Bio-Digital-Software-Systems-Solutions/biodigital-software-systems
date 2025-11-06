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

// Ziggy route() function is provided globally via @routes directive in app.blade.php
