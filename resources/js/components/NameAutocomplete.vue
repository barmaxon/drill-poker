<template>
    <div class="name-autocomplete" ref="containerRef">
        <input
            type="text"
            class="input"
            :value="modelValue"
            @input="onInput"
            @focus="showDropdown = true"
            @keydown.down.prevent="navigateDown"
            @keydown.up.prevent="navigateUp"
            @keydown.enter.prevent="selectHighlighted"
            @keydown.escape="showDropdown = false"
            :placeholder="placeholder"
        />

        <div
            v-if="showDropdown && filteredSuggestions.length > 0"
            class="dropdown"
        >
            <div
                v-for="(suggestion, index) in filteredSuggestions"
                :key="suggestion.name"
                class="dropdown-item"
                :class="{ highlighted: index === highlightedIndex }"
                @mousedown.prevent="selectSuggestion(suggestion)"
                @mouseenter="highlightedIndex = index"
            >
                <span class="suggestion-name">{{ suggestion.name }}</span>
                <span class="suggestion-meta">
                    {{ t('common.positionsAvailable', { count: suggestion.availablePositions.length }) }}
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useI18n } from '../composables/useI18n';

const { t } = useI18n();

const props = defineProps({
    modelValue: {
        type: String,
        required: true,
    },
    suggestions: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: 'Enter scenario name...',
    },
});

const emit = defineEmits(['update:modelValue', 'select']);

const containerRef = ref(null);
const showDropdown = ref(false);
const highlightedIndex = ref(-1);

const filteredSuggestions = computed(() => {
    if (!props.modelValue) {
        return props.suggestions;
    }

    const query = props.modelValue.toLowerCase();
    return props.suggestions.filter(s =>
        s.name.toLowerCase().includes(query)
    );
});

// Reset highlight when suggestions change
watch(filteredSuggestions, () => {
    highlightedIndex.value = -1;
});

const onInput = (event) => {
    emit('update:modelValue', event.target.value);
    showDropdown.value = true;
};

const selectSuggestion = (suggestion) => {
    emit('update:modelValue', suggestion.name);
    emit('select', suggestion);
    showDropdown.value = false;
};

const navigateDown = () => {
    if (highlightedIndex.value < filteredSuggestions.value.length - 1) {
        highlightedIndex.value++;
    }
};

const navigateUp = () => {
    if (highlightedIndex.value > 0) {
        highlightedIndex.value--;
    }
};

const selectHighlighted = () => {
    if (highlightedIndex.value >= 0 && highlightedIndex.value < filteredSuggestions.value.length) {
        selectSuggestion(filteredSuggestions.value[highlightedIndex.value]);
    }
};

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        showDropdown.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
.name-autocomplete {
    position: relative;
}

.dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    margin-top: var(--spacing-xs);
    background-color: var(--color-bg-secondary);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    max-height: 200px;
    overflow-y: auto;
    z-index: 100;
}

.dropdown-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-sm) var(--spacing-md);
    cursor: pointer;
    transition: background-color 0.15s;
}

.dropdown-item:hover,
.dropdown-item.highlighted {
    background-color: var(--color-bg-tertiary);
}

.suggestion-name {
    font-weight: 500;
}

.suggestion-meta {
    font-size: 0.75rem;
    color: var(--color-text-secondary);
}
</style>
