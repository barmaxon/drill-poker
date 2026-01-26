import { ref, computed } from 'vue';
import api from '../services/api';

const translations = ref({});
const currentLocale = ref(localStorage.getItem('locale') || 'en');
const loading = ref(false);

export function useI18n() {
    const t = (key, params = {}) => {
        const keys = key.split('.');
        let value = translations.value;

        for (const k of keys) {
            if (value && typeof value === 'object' && k in value) {
                value = value[k];
            } else {
                return key; // Return key if translation not found
            }
        }

        if (typeof value !== 'string') {
            return key;
        }

        // Replace params like {name} with values
        return value.replace(/\{(\w+)\}/g, (_, param) => params[param] ?? `{${param}}`);
    };

    const setLocale = async (locale) => {
        if (locale === currentLocale.value && Object.keys(translations.value).length > 0) {
            return;
        }

        loading.value = true;
        try {
            const response = await api.get(`/translations/${locale}`);
            translations.value = response.data;
            currentLocale.value = locale;
            localStorage.setItem('locale', locale);
            document.documentElement.lang = locale;
        } catch (error) {
            console.error('Failed to load translations:', error);
        } finally {
            loading.value = false;
        }
    };

    const locale = computed(() => currentLocale.value);

    // Load translations on first use
    if (Object.keys(translations.value).length === 0) {
        setLocale(currentLocale.value);
    }

    return {
        t,
        locale,
        setLocale,
        loading: computed(() => loading.value),
    };
}
