<template>
    <header class="app-header">
        <div class="container header-content">
            <router-link to="/" class="logo">
                <span class="logo-icon">♠</span>
                <span class="logo-text">Poker Study</span>
            </router-link>

            <nav class="nav-links">
                <router-link to="/drill" class="nav-link">Drill</router-link>
                <router-link to="/scenarios" class="nav-link">Scenarios</router-link>
                <router-link to="/groups" class="nav-link">Groups</router-link>
                <router-link to="/stats" class="nav-link">Stats</router-link>
            </nav>

            <div class="header-actions">
                <template v-if="authStore.isAuthenticated">
                    <span class="user-name">{{ authStore.user?.name }}</span>
                    <button @click="logout" class="btn btn-secondary">Logout</button>
                </template>
                <template v-else>
                    <router-link to="/login" class="btn btn-secondary">Login</router-link>
                    <router-link to="/register" class="btn btn-primary">Register</router-link>
                </template>
            </div>
        </div>
    </header>
</template>

<script setup>
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();

const logout = async () => {
    await authStore.logout();
    router.push('/login');
};
</script>

<style scoped>
.app-header {
    background-color: var(--color-bg-secondary);
    border-bottom: 1px solid var(--color-border);
    padding: var(--spacing-md) 0;
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--spacing-lg);
}

.logo {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--color-text-primary);
}

.logo:hover {
    color: var(--color-text-primary);
}

.logo-icon {
    font-size: 1.5rem;
}

.nav-links {
    display: flex;
    gap: var(--spacing-md);
}

.nav-link {
    padding: var(--spacing-sm) var(--spacing-md);
    color: var(--color-text-secondary);
    border-radius: var(--radius-md);
    transition: all var(--transition-fast);
}

.nav-link:hover,
.nav-link.router-link-active {
    color: var(--color-text-primary);
    background-color: var(--color-bg-tertiary);
}

.header-actions {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
}

.user-name {
    color: var(--color-text-secondary);
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .header-content {
        flex-wrap: wrap;
    }

    .nav-links {
        order: 3;
        width: 100%;
        justify-content: center;
        margin-top: var(--spacing-sm);
    }
}
</style>
