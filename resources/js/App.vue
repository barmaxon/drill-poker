<template>
    <div class="app-layout">
        <AppHeader />
        <main class="app-main">
            <router-view />
        </main>
        <ToastContainer />
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import AppHeader from './components/AppHeader.vue';
import ToastContainer from './components/ToastContainer.vue';
import { useAuthStore } from './stores/auth';
import { useI18n } from './composables/useI18n';

const authStore = useAuthStore();
const { setLocale, locale } = useI18n();

onMounted(() => {
    authStore.fetchUser();
    setLocale(locale.value);
});
</script>

<style scoped>
.app-layout {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.app-main {
    flex: 1;
    padding: var(--spacing-lg);
}
</style>
