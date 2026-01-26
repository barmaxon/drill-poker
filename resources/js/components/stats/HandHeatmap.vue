<template>
    <div class="hand-heatmap">
        <div class="heatmap-header">
            <div class="corner-cell"></div>
            <div v-for="rank in ranks" :key="rank" class="header-cell">{{ rank }}</div>
        </div>
        <div v-for="(rowRank, rowIndex) in ranks" :key="rowRank" class="heatmap-row">
            <div class="row-header">{{ rowRank }}</div>
            <div
                v-for="(colRank, colIndex) in ranks"
                :key="`${rowIndex}-${colIndex}`"
                class="heatmap-cell"
                :style="getCellStyle(rowIndex, colIndex)"
                :title="getCellTooltip(rowIndex, colIndex)"
            >
                <span class="cell-label">{{ getShortHand(rowIndex, colIndex) }}</span>
            </div>
        </div>
        <div class="heatmap-legend">
            <span class="legend-label">Accuracy:</span>
            <div class="legend-gradient"></div>
            <div class="legend-labels">
                <span>0%</span>
                <span>50%</span>
                <span>100%</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { RANKS, getHandAtPosition } from '../../utils/hands';

const props = defineProps({
    data: {
        type: Object,
        default: () => ({}),
    },
});

const ranks = RANKS;

const getShortHand = (row, col) => {
    const hand = getHandAtPosition(row, col);
    // Remove 's' or 'o' suffix for shorter display
    return hand.length > 2 ? hand.substring(0, 2) : hand;
};

const getCellData = (row, col) => {
    const hand = getHandAtPosition(row, col);
    return props.data[hand] || { total: 0, correct: 0, accuracy: 50 };
};

const getCellStyle = (row, col) => {
    const data = getCellData(row, col);
    const accuracy = data.accuracy;

    // Color from red (0%) through yellow (50%) to green (100%)
    let r, g, b;
    if (accuracy < 50) {
        // Red to yellow
        r = 239;
        g = Math.round((accuracy / 50) * 180);
        b = 68 - Math.round((accuracy / 50) * 68);
    } else {
        // Yellow to green
        r = Math.round((1 - (accuracy - 50) / 50) * 205);
        g = 180 + Math.round(((accuracy - 50) / 50) * 17);
        b = Math.round(((accuracy - 50) / 50) * 94);
    }

    const opacity = data.total > 0 ? 0.8 : 0.3;

    return {
        backgroundColor: `rgba(${r}, ${g}, ${b}, ${opacity})`,
    };
};

const getCellTooltip = (row, col) => {
    const hand = getHandAtPosition(row, col);
    const data = getCellData(row, col);
    return `${hand}: ${data.accuracy}% (${data.correct}/${data.total})`;
};
</script>

<style scoped>
.hand-heatmap {
    display: inline-block;
    background: var(--color-bg-secondary);
    border-radius: var(--radius-lg);
    padding: var(--spacing-sm);
}

.heatmap-header {
    display: flex;
}

.corner-cell {
    width: 28px;
    height: 28px;
}

.header-cell {
    width: 36px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.7rem;
    color: var(--color-text-secondary);
}

.heatmap-row {
    display: flex;
}

.row-header {
    width: 28px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.7rem;
    color: var(--color-text-secondary);
}

.heatmap-cell {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 1px;
    border-radius: var(--radius-sm);
    cursor: default;
    transition: transform var(--transition-fast);
}

.heatmap-cell:hover {
    transform: scale(1.1);
    z-index: 1;
}

.cell-label {
    font-size: 0.6rem;
    font-weight: 500;
    color: white;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
}

.heatmap-legend {
    margin-top: var(--spacing-md);
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    font-size: 0.75rem;
}

.legend-label {
    color: var(--color-text-secondary);
}

.legend-gradient {
    width: 150px;
    height: 12px;
    border-radius: var(--radius-sm);
    background: linear-gradient(to right, #ef4444, #eab308, #22c55e);
}

.legend-labels {
    display: flex;
    width: 150px;
    justify-content: space-between;
    color: var(--color-text-muted);
    font-size: 0.65rem;
}
</style>
