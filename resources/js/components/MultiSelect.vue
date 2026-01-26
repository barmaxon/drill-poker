<template>
    <div class="multi-select">
        <div class="selected-tags" v-if="modelValue.length > 0">
            <span
                v-for="item in modelValue"
                :key="item"
                class="tag"
            >
                {{ item }}
                <button
                    type="button"
                    class="tag-remove"
                    @click="removeItem(item)"
                    :aria-label="`Remove ${item}`"
                >
                    &times;
                </button>
            </span>
        </div>
        <div v-else class="no-selection">
            {{ placeholder }}
        </div>

        <div v-if="groups" class="group-buttons">
            <button
                type="button"
                class="group-btn all-btn"
                :class="{ active: isAllSelected }"
                @click="toggleAll"
            >
                {{ t('common.all') }}
            </button>
            <button
                v-for="(positions, groupName) in groups"
                :key="groupName"
                type="button"
                class="group-btn"
                :class="{ active: isGroupSelected(positions) }"
                @click="toggleGroup(positions)"
            >
                {{ groupName }}
            </button>
        </div>

        <div class="checkbox-list">
            <label
                v-for="option in options"
                :key="option"
                class="checkbox-item"
                :class="{ checked: modelValue.includes(option) }"
            >
                <input
                    type="checkbox"
                    :value="option"
                    :checked="modelValue.includes(option)"
                    @change="toggleItem(option)"
                />
                <span class="checkbox-label">{{ option }}</span>
            </label>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from '../composables/useI18n';

const { t } = useI18n();

const props = defineProps({
    modelValue: {
        type: Array,
        required: true,
    },
    options: {
        type: Array,
        required: true,
    },
    placeholder: {
        type: String,
        default: 'Select items...',
    },
    groups: {
        type: Object,
        default: null,
    },
    availableForAll: {
        type: Array,
        default: null,
    },
});

const emit = defineEmits(['update:modelValue']);

const toggleItem = (item) => {
    const current = [...props.modelValue];
    const index = current.indexOf(item);

    if (index === -1) {
        current.push(item);
    } else {
        current.splice(index, 1);
    }

    emit('update:modelValue', current);
};

const removeItem = (item) => {
    const current = props.modelValue.filter(i => i !== item);
    emit('update:modelValue', current);
};

const isAllSelected = computed(() => {
    return props.options.every(opt => props.modelValue.includes(opt));
});

const toggleAll = () => {
    // Select availableForAll if provided, otherwise all options
    const toSelect = props.availableForAll || props.options;
    emit('update:modelValue', [...toSelect]);
};

const isGroupSelected = (positions) => {
    return positions.every(pos => props.modelValue.includes(pos));
};

const toggleGroup = (positions) => {
    const allSelected = isGroupSelected(positions);
    let current = [...props.modelValue];

    if (allSelected) {
        // Remove all positions in this group
        current = current.filter(pos => !positions.includes(pos));
    } else {
        // Add all positions in this group (avoid duplicates)
        for (const pos of positions) {
            if (!current.includes(pos)) {
                current.push(pos);
            }
        }
    }

    emit('update:modelValue', current);
};
</script>

<style scoped>
.multi-select {
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background-color: var(--color-bg-secondary);
    padding: var(--spacing-sm);
}

.selected-tags {
    display: flex;
    flex-wrap: wrap;
    gap: var(--spacing-xs);
    margin-bottom: var(--spacing-sm);
    padding-bottom: var(--spacing-sm);
    border-bottom: 1px solid var(--color-border);
}

.no-selection {
    color: var(--color-text-secondary);
    font-size: 0.875rem;
    margin-bottom: var(--spacing-sm);
    padding-bottom: var(--spacing-sm);
    border-bottom: 1px solid var(--color-border);
}

.group-buttons {
    display: flex;
    gap: var(--spacing-xs);
    margin-bottom: var(--spacing-sm);
    padding-bottom: var(--spacing-sm);
    border-bottom: 1px solid var(--color-border);
}

.group-btn {
    flex: 1;
    padding: var(--spacing-xs) var(--spacing-sm);
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: capitalize;
    background-color: rgba(255, 255, 255, 0.1);
    border: none;
    border-radius: 9999px;
    color: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    transition: all 0.15s;
}

.group-btn:hover {
    background-color: rgba(255, 255, 255, 0.15);
    color: rgba(255, 255, 255, 0.7);
}

.group-btn.active {
    background-color: var(--color-primary);
    color: white;
}

.group-btn.all-btn {
    background-color: transparent;
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: rgba(255, 255, 255, 0.7);
}

.group-btn.all-btn:hover {
    border-color: rgba(255, 255, 255, 0.5);
    color: white;
}

.group-btn.all-btn.active {
    background-color: var(--color-success, #22c55e);
    border-color: var(--color-success, #22c55e);
    color: white;
}

.tag {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-xs);
    padding: var(--spacing-xs) var(--spacing-sm);
    background-color: var(--color-primary);
    color: white;
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    font-weight: 500;
}

.tag-remove {
    background: none;
    border: none;
    color: inherit;
    cursor: pointer;
    padding: 0;
    font-size: 1rem;
    line-height: 1;
    opacity: 0.7;
}

.tag-remove:hover {
    opacity: 1;
}

.checkbox-list {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--spacing-xs);
}

.checkbox-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: background-color 0.15s;
}

.checkbox-item:hover {
    background-color: var(--color-bg-tertiary);
}

.checkbox-item.checked {
    background-color: var(--color-primary-light, rgba(var(--color-primary-rgb), 0.1));
}

.checkbox-item input {
    cursor: pointer;
}

.checkbox-label {
    font-size: 0.875rem;
}

@media (max-width: 400px) {
    .checkbox-list {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
