<template>
    <div class="auth-page">
        <div class="auth-card card">
            <h2>{{ t('auth.register') }}</h2>
            <form @submit.prevent="handleRegister" class="auth-form">
                <div class="form-group">
                    <label class="label" for="name">{{ t('auth.name') }}</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="input"
                        required
                    />
                </div>
                <div class="form-group">
                    <label class="label" for="email">{{ t('auth.email') }}</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="input"
                        placeholder="your@email.com"
                        required
                    />
                </div>
                <div class="form-group">
                    <label class="label" for="password">{{ t('auth.password') }}</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        class="input"
                        placeholder="••••••••"
                        required
                        minlength="8"
                    />
                </div>
                <div class="form-group">
                    <label class="label" for="password_confirmation">{{ t('auth.confirmPassword') }}</label>
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        class="input"
                        placeholder="••••••••"
                        required
                    />
                </div>
                <div v-if="error" class="error-message">{{ error }}</div>
                <button type="submit" class="btn btn-primary btn-lg btn-full" :disabled="loading">
                    {{ loading ? t('common.loading') : t('auth.registerButton') }}
                </button>
            </form>
            <p class="auth-footer">
                {{ t('auth.hasAccount') }}
                <router-link to="/login">{{ t('auth.login') }}</router-link>
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useI18n } from '../composables/useI18n';

const router = useRouter();
const authStore = useAuthStore();
const { t } = useI18n();

const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});
const loading = ref(false);
const error = ref('');

const handleRegister = async () => {
    loading.value = true;
    error.value = '';

    const result = await authStore.register(
        form.name,
        form.email,
        form.password,
        form.password_confirmation
    );

    loading.value = false;

    if (result.success) {
        router.push('/');
    } else {
        error.value = result.error || 'Registration failed';
    }
};
</script>

<style scoped>
.auth-page {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: calc(100vh - 200px);
}

.auth-card {
    width: 100%;
    max-width: 400px;
}

.auth-card h2 {
    text-align: center;
    margin-bottom: var(--spacing-lg);
}

.auth-form {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
}

.form-group {
    display: flex;
    flex-direction: column;
}

.btn-full {
    width: 100%;
}

.error-message {
    color: var(--color-error);
    font-size: 0.875rem;
    text-align: center;
}

.auth-footer {
    text-align: center;
    margin-top: var(--spacing-lg);
    color: var(--color-text-secondary);
}
</style>
