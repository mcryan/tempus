import axios from 'axios';

// Set Axios defaults
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;
axios.defaults.headers.common['Accept'] = 'application/json';

// Get the CSRF token from the meta tag
const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// If token exists, set it in axios defaults
if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
} 