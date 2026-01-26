<template>
    <div class="range-legend">
        <div class="legend-item">
            <div class="legend-color fold"></div>
            <span>Fold</span>
        </div>
        <div class="legend-item">
            <div class="legend-color call"></div>
            <span>Call</span>
        </div>
        <div class="legend-item">
            <div class="legend-color raise"></div>
            <span>Raise</span>
        </div>
        <div class="legend-divider"></div>
        <div class="legend-note">
            <span>Diagonal: Pocket pairs</span>
        </div>
        <div class="legend-note">
            <span>Above: Suited hands</span>
        </div>
        <div class="legend-note">
            <span>Below: Offsuit hands</span>
        </div>
    </div>
</template>

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
