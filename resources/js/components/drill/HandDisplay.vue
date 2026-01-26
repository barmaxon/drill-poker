<template>
    <div class="hand-display">
        <div class="card" :class="getSuitClass(card1.suit)">
            <span class="rank">{{ card1.rank }}</span>
            <span class="suit">{{ getSuitSymbol(card1.suit) }}</span>
        </div>
        <div class="card" :class="getSuitClass(card2.suit)">
            <span class="rank">{{ card2.rank }}</span>
            <span class="suit">{{ getSuitSymbol(card2.suit) }}</span>
        </div>
    </div>
</template>

<script setup>
import { SUIT_SYMBOLS } from '../../utils/hands';

defineProps({
    card1: {
        type: Object,
        required: true,
    },
    card2: {
        type: Object,
        required: true,
    },
});

const getSuitSymbol = (suit) => SUIT_SYMBOLS[suit] || suit;

const getSuitClass = (suit) => `suit-${suit}`;
</script>

<style scoped>
.hand-display {
    display: flex;
    gap: var(--spacing-md);
}

.card {
    width: 100px;
    height: 140px;
    background: white;
    border-radius: var(--radius-lg);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-xs);
    box-shadow: var(--shadow-lg);
    font-weight: 700;
}

.rank {
    font-size: 2.5rem;
    line-height: 1;
}

.suit {
    font-size: 2rem;
    line-height: 1;
}

.suit-s {
    color: #1a1a2e;
}

.suit-h {
    color: #ef4444;
}

.suit-d {
    color: #3b82f6;
}

.suit-c {
    color: #22c55e;
}
</style>
