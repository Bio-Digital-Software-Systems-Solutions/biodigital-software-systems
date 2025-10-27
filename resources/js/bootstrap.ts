import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Import Ziggy route function for client-side routing
import { route as ziggyRoute } from 'ziggy-js';
window.route = ziggyRoute;
