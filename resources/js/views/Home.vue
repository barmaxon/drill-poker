<template>
    <div class="home container">
        <div class="hero">
            <h1>{{ t('home.heroTitle') }}</h1>
            <p class="hero-subtitle">
                {{ t('home.heroSubtitle') }}
            </p>
            <div class="hero-actions">
                <router-link to="/drill" class="btn btn-primary btn-lg">{{ t('home.startDrilling') }}</router-link>
                <router-link to="/scenarios" class="btn btn-secondary btn-lg">{{ t('home.manageScenarios') }}</router-link>
            </div>
        </div>

        <div class="features">
            <div class="feature card">
                <div class="feature-icon">🎯</div>
                <h3>{{ t('home.featureRandomization') }}</h3>
                <p>{{ t('home.featureRandomizationDesc') }}</p>
            </div>
            <div class="feature card">
                <div class="feature-icon">📊</div>
                <h3>{{ t('home.featureStats') }}</h3>
                <p>{{ t('home.featureStatsDesc') }}</p>
            </div>
            <div class="feature card">
                <div class="feature-icon">⚡</div>
                <h3>{{ t('home.featureDrills') }}</h3>
                <p>{{ t('home.featureDrillsDesc') }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useI18n } from '../composables/useI18n';

const { t } = useI18n();
</script>

<style scoped>
.home {
    padding-top: var(--spacing-xl);
}

.hero {
    text-align: center;
    margin-bottom: var(--spacing-xl);
    padding: var(--spacing-xl);
}

.hero h1 {
    font-size: 2.5rem;
    margin-bottom: var(--spacing-md);
}

.hero-subtitle {
    font-size: 1.125rem;
    color: var(--color-text-secondary);
    max-width: 600px;
    margin: 0 auto var(--spacing-lg);
}

.hero-actions {
    display: flex;
    gap: var(--spacing-md);
    justify-content: center;
}

.features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--spacing-lg);
}

.feature {
    text-align: center;
}

.feature-icon {
    font-size: 2.5rem;
    margin-bottom: var(--spacing-md);
}

.feature h3 {
    margin-bottom: var(--spacing-sm);
}

.feature p {
    color: var(--color-text-secondary);
}
</style>
