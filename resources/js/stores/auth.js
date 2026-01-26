import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '../services/api';

const USE_MOCK = import.meta.env.VITE_USE_MOCK === 'true';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const loading = ref(false);
    const error = ref(null);

    const isAuthenticated = computed(() => !!user.value);

    async function login(email, password) {
        loading.value = true;
        error.value = null;

        try {
            if (USE_MOCK) {
                user.value = { id: 1, name: 'Test User', email, locale: 'en' };
                return { success: true };
            }

            const response = await api.post('/login', { email, password });
            user.value = response.data.user;
            return { success: true };
        } catch (err) {
            const errors = err.response?.data?.errors;
            if (errors) {
                const firstField = Object.keys(errors)[0];
                error.value = errors[firstField][0];
            } else {
                error.value = err.response?.data?.message || 'Login failed';
            }
            return { success: false, error: error.value };
        } finally {
            loading.value = false;
        }
    }

    async function register(name, email, password, password_confirmation) {
        loading.value = true;
        error.value = null;

        try {
            if (USE_MOCK) {
                user.value = { id: 1, name, email, locale: 'en' };
                return { success: true };
            }

            const response = await api.post('/register', {
                name,
                email,
                password,
                password_confirmation,
            });
            user.value = response.data.user;
            return { success: true };
        } catch (err) {
            // Extract first validation error or fall back to message
            const errors = err.response?.data?.errors;
            if (errors) {
                const firstField = Object.keys(errors)[0];
                error.value = errors[firstField][0];
            } else {
                error.value = err.response?.data?.message || 'Registration failed';
            }
            return { success: false, error: error.value };
        } finally {
            loading.value = false;
        }
    }

    async function logout() {
        try {
            if (!USE_MOCK) {
                await api.post('/logout');
            }
        } finally {
            user.value = null;
        }
    }

    async function fetchUser() {
        if (USE_MOCK) {
            user.value = { id: 1, name: 'Test User', email: 'test@example.com', locale: 'en' };
            return;
        }

        try {
            const response = await api.get('/user');
            user.value = response.data;
        } catch {
            user.value = null;
        }
    }

    return {
        user,
        loading,
        error,
        isAuthenticated,
        login,
        register,
        logout,
        fetchUser,
    };
});
