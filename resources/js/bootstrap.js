import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// CSRF: send token on every request (read from meta at request time)
const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
window.axios.interceptors.request.use((config) => {
    const token = csrfToken();
    if (token) config.headers.set('X-CSRF-TOKEN', token);
    return config;
});

// On 419 (CSRF token mismatch): reload to get a fresh token instead of asking user to clear cookies
window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 419) {
            const msg = 'Session expired or invalid. Reloading to refresh…';
            if (typeof window.toast !== 'undefined') {
                window.toast(msg);
            } else {
                console.warn(msg);
            }
            window.location.reload();
        }
        return Promise.reject(error);
    }
);
