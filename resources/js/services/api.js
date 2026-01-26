import axios from 'axios';
import { useToastStore } from '../stores/toast';

const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
    withXSRFToken: true,
});

// Add request interceptor to include CSRF token
api.interceptors.request.use(async (config) => {
    // Ensure CSRF cookie is set for state-changing requests
    if (['post', 'put', 'patch', 'delete'].includes(config.method)) {
        await axios.get('/sanctum/csrf-cookie');
    }
    return config;
});

// Add response interceptor for error handling
api.interceptors.response.use(
    (response) => response,
    (error) => {
        // Try to show toast, but don't fail if store isn't ready
        try {
            const toastStore = useToastStore();

            if (error.response?.status === 401) {
                // Don't redirect for /user endpoint (expected to fail when not logged in)
                // Don't redirect if already on login/register page
                const isUserEndpoint = error.config?.url === '/user';
                const isAuthPage = ['/login', '/register'].includes(window.location.pathname);

                if (!isUserEndpoint && !isAuthPage) {
                    window.location.href = '/login';
                }
            } else if (error.response?.status === 422) {
                // Validation errors
                const errors = error.response.data?.errors;
                if (errors) {
                    const firstField = Object.keys(errors)[0];
                    toastStore.error(errors[firstField][0]);
                } else {
                    toastStore.error(error.response.data?.message || 'Validation failed');
                }
            } else if (error.response?.status >= 400) {
                // Other errors
                toastStore.error(error.response.data?.message || 'An error occurred');
            }
        } catch (e) {
            console.error('Toast error:', e);
        }

        return Promise.reject(error);
    }
);

export default api;
