<template>
    <div class="range-grid" @mouseup="stopDragging" @mouseleave="stopDragging">
        <div class="grid-header">
            <div class="corner-cell"></div>
            <div v-for="rank in ranks" :key="rank" class="header-cell">{{ rank }}</div>
        </div>
        <div v-for="(rowRank, rowIndex) in ranks" :key="rowRank" class="grid-row">
            <div class="row-header">{{ rowRank }}</div>
            <RangeCell
                v-for="(colRank, colIndex) in ranks"
                :key="`${rowIndex}-${colIndex}`"
                :hand="getHandAtPosition(rowIndex, colIndex)"
                :action="grid[getHandAtPosition(rowIndex, colIndex)]"
                :is-selected="isSelected(rowIndex, colIndex)"
                @mousedown="startDragging(rowIndex, colIndex)"
                @mouseenter="handleMouseEnter(rowIndex, colIndex)"
                @click="handleClick(rowIndex, colIndex)"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import RangeCell from './RangeCell.vue';
import { RANKS, getHandAtPosition } from '../../utils/hands';

const props = defineProps({
    grid: {
        type: Object,
        required: true,
    },
    brushAction: {
        type: String,
        default: 'raise',
    },
});

const emit = defineEmits(['update:cell', 'update:cells']);

const ranks = RANKS;

const isDragging = ref(false);
const selectedCells = ref(new Set());

const isSelected = (row, col) => {
    return selectedCells.value.has(`${row}-${col}`);
};

const startDragging = (row, col) => {
    isDragging.value = true;
    selectedCells.value = new Set([`${row}-${col}`]);
};

const handleMouseEnter = (row, col) => {
    if (!isDragging.value) return;

    // Brush mode: add each cell the mouse passes through
    selectedCells.value.add(`${row}-${col}`);
};

const stopDragging = () => {
    if (isDragging.value && selectedCells.value.size > 0) {
        // Apply brush action to all selected cells
        const hands = [];
        for (const cellKey of selectedCells.value) {
            const [row, col] = cellKey.split('-').map(Number);
            hands.push(getHandAtPosition(row, col));
        }
        emit('update:cells', hands, props.brushAction);
    }

    isDragging.value = false;
    selectedCells.value = new Set();
};

const handleClick = (row, col) => {
    if (!isDragging.value) {
        const hand = getHandAtPosition(row, col);
        emit('update:cell', hand, props.brushAction);
    }
};
</script>

<style scoped>
.range-grid {
    display: inline-block;
    background: var(--color-bg-secondary);
    border-radius: var(--radius-lg);
    padding: var(--spacing-sm);
    user-select: none;
}

.grid-header {
    display: flex;
}

.corner-cell {
    width: 32px;
    height: 32px;
}

.header-cell {
    width: 40px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.75rem;
    color: var(--color-text-secondary);
}

.grid-row {
    display: flex;
}

.row-header {
    width: 32px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.75rem;
    color: var(--color-text-secondary);
}
</style>
