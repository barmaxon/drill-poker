<template>
    <div class="language-switcher">
        <button
            v-for="lang in languages"
            :key="lang.code"
            :class="['lang-btn', { active: locale === lang.code }]"
            @click="setLocale(lang.code)"
        >
            {{ lang.label }}
        </button>
    </div>
</template>

<script setup>
import { useI18n } from '../composables/useI18n';

const { locale, setLocale } = useI18n();

const languages = [
    { code: 'en', label: 'EN' },
    { code: 'ru', label: 'RU' },
];
</script>

<style scoped>
.language-switcher {
    display: flex;
    gap: 4px;
}

.lang-btn {
    padding: 4px 8px;
    font-size: 0.75rem;
    font-weight: 500;
    background: transparent;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-sm);
    color: var(--color-text-secondary);
    cursor: pointer;
    transition: all 0.15s;
}

.lang-btn:hover {
    border-color: var(--color-primary);
    color: var(--color-primary);
}

.lang-btn.active {
    background: var(--color-primary);
    border-color: var(--color-primary);
    color: white;
}
</style>
