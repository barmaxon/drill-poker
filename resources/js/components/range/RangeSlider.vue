<template>
    <div class="range-slider">
        <div class="slider-header">
            <span>Top {{ displayPercentage }}% of hands</span>
            <span class="hand-count">{{ modelValue }} / 169 hands</span>
        </div>
        <input
            type="range"
            :value="modelValue"
            @input="handleInput"
            min="0"
            max="169"
            step="1"
            class="slider-input"
        />
        <div class="slider-actions">
            <button @click="applyStrength" class="btn btn-primary">
                Fill Range
            </button>
            <button @click="clearRange" class="btn btn-secondary">
                Clear
            </button>
        </div>
        <div class="slider-preview" v-if="modelValue > 0">
            <span class="preview-label">Hands included:</span>
            <span class="preview-hands">{{ previewHands }}</span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { HAND_STRENGTH_ORDER } from '../../utils/hands';

const props = defineProps({
    modelValue: {
        type: Number,
        default: 0,
    },
    action: {
        type: String,
        default: 'raise',
    },
});

const emit = defineEmits(['update:modelValue', 'fill']);

const displayPercentage = computed(() => {
    return Math.round((props.modelValue / 169) * 100);
});

const previewHands = computed(() => {
    if (props.modelValue === 0) return '';
    const hands = HAND_STRENGTH_ORDER.slice(0, Math.min(props.modelValue, 10));
    let result = hands.join(', ');
    if (props.modelValue > 10) {
        result += `, ... (${props.modelValue - 10} more)`;
    }
    return result;
});

const handleInput = (event) => {
    emit('update:modelValue', parseInt(event.target.value));
};

const applyStrength = () => {
    emit('fill', props.modelValue, props.action);
};

const clearRange = () => {
    emit('update:modelValue', 0);
    emit('fill', 0, props.action);
};
</script>

<style scoped>
.range-slider {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
}

.slider-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.875rem;
}

.hand-count {
    color: var(--color-text-secondary);
}

.slider-input {
    width: 100%;
    height: 8px;
    border-radius: var(--radius-full);
    background: var(--color-bg-tertiary);
    appearance: none;
    cursor: pointer;
}

.slider-input::-webkit-slider-thumb {
    appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--color-primary);
    cursor: pointer;
    transition: transform var(--transition-fast);
}

.slider-input::-webkit-slider-thumb:hover {
    transform: scale(1.1);
}

.slider-input::-moz-range-thumb {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--color-primary);
    cursor: pointer;
    border: none;
}

.slider-actions {
    display: flex;
    gap: var(--spacing-sm);
}

.slider-preview {
    font-size: 0.75rem;
    color: var(--color-text-secondary);
    padding: var(--spacing-sm);
    background: var(--color-bg-tertiary);
    border-radius: var(--radius-sm);
}

.preview-label {
    display: block;
    margin-bottom: var(--spacing-xs);
    font-weight: 500;
}

.preview-hands {
    word-break: break-word;
}
</style>
