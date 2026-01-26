<template>
    <div class="range-legend">
        <div class="legend-item">
            <div class="legend-color fold"></div>
            <span>{{ t('legend.fold') }}</span>
        </div>
        <div class="legend-item">
            <div class="legend-color call"></div>
            <span>{{ t('legend.call') }}</span>
        </div>
        <div class="legend-item">
            <div class="legend-color raise"></div>
            <span>{{ t('legend.raise') }}</span>
        </div>
        <div class="legend-divider"></div>
        <div class="legend-note">
            <span>{{ t('legend.pocketPairs') }}</span>
        </div>
        <div class="legend-note">
            <span>{{ t('legend.suitedHands') }}</span>
        </div>
        <div class="legend-note">
            <span>{{ t('legend.offsuitHands') }}</span>
        </div>
    </div>
</template>

<script setup>
import { useI18n } from '../../composables/useI18n';

const { t } = useI18n();
</script>

<style scoped>
.range-legend {
    display: flex;
    flex-wrap: wrap;
    gap: var(--spacing-md);
    padding: var(--spacing-md);
    background: var(--color-bg-secondary);
    border-radius: var(--radius-md);
    font-size: 0.875rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.legend-color {
    width: 20px;
    height: 20px;
    border-radius: var(--radius-sm);
}

.legend-color.fold {
    background-color: var(--color-fold);
}

.legend-color.call {
    background-color: var(--color-call);
}

.legend-color.raise {
    background-color: var(--color-raise);
}

.legend-divider {
    width: 1px;
    background: var(--color-border);
    align-self: stretch;
}

.legend-note {
    color: var(--color-text-secondary);
}
</style>
