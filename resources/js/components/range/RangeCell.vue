<template>
    <div
        :class="['range-cell', `action-${action}`, { selected: isSelected }]"
        @mousedown.prevent="$emit('mousedown')"
        @mouseenter="$emit('mouseenter')"
        @click="$emit('click')"
    >
        <span class="hand-label">{{ shortLabel }}</span>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    hand: {
        type: String,
        required: true,
    },
    action: {
        type: String,
        default: 'fold',
    },
    isSelected: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['mousedown', 'mouseenter', 'click']);

// Short label (without 's' or 'o' for pairs, with for others)
const shortLabel = computed(() => {
    const hand = props.hand;
    // Pocket pairs: AA, KK, etc
    if (hand.length === 2) {
        return hand;
    }
    // Suited/offsuit: show abbreviated
    return hand;
});
</script>

<style scoped>
.range-cell {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 1px;
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: all var(--transition-fast);
    font-size: 0.7rem;
    font-weight: 500;
}

.range-cell:hover {
    transform: scale(1.05);
    z-index: 1;
}

.range-cell.selected {
    outline: 2px solid var(--color-primary);
    outline-offset: -2px;
}

.action-fold {
    background-color: var(--color-fold);
    color: var(--color-text-primary);
}

.action-call {
    background-color: var(--color-call);
    color: white;
}

.action-raise {
    background-color: var(--color-raise);
    color: white;
}

.hand-label {
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}
</style>
