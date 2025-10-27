import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Import Ziggy route function and routes configuration for client-side routing
import { route as ziggyRoute } from 'ziggy-js';
import { Ziggy } from './ziggy';

// @ts-ignore - Ziggy configuration
window.route = (name?: any, params?: any, absolute?: any, config = Ziggy) => ziggyRoute(name, params, absolute, config);
