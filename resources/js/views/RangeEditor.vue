<template>
    <div class="range-editor-page container">
        <div class="page-header">
            <h1>{{ isEditing ? t('ranges.edit') : t('ranges.create') }}</h1>
        </div>

        <div class="editor-layout">
            <div class="editor-sidebar card">
                <div class="form-group">
                    <label class="label" for="name">{{ t('scenarios.name') }}</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="input"
                        :placeholder="t('ranges.namePlaceholder')"
                    />
                </div>

                <div class="form-group">
                    <label class="label" for="description">{{ t('scenarios.description') }}</label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        class="input"
                        rows="3"
                        :placeholder="t('ranges.descriptionPlaceholder')"
                    ></textarea>
                </div>

                <div class="form-group">
                    <label class="label">{{ t('ranges.defaultAction') }}</label>
                    <div class="action-buttons">
                        <button
                            v-for="action in actions"
                            :key="action.value"
                            :class="['btn', `btn-${action.value}`, { active: form.defaultAction === action.value }]"
                            @click="form.defaultAction = action.value"
                        >
                            {{ action.label }}
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="label">{{ t('scenarios.brushAction') }}</label>
                    <div class="action-buttons">
                        <button
                            v-for="action in actions"
                            :key="action.value"
                            :class="['btn', `btn-${action.value}`, { active: brushAction === action.value }]"
                            @click="brushAction = action.value"
                        >
                            {{ action.label }}
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="label">{{ t('ranges.strengthSlider') }}</label>
                    <RangeSlider
                        v-model="sliderValue"
                        :action="brushAction"
                        @fill="fillByStrength"
                    />
                </div>

                <div class="form-actions">
                    <button @click="save" class="btn btn-primary" :disabled="saving">
                        {{ saving ? t('common.saving') : t('ranges.saveRange') }}
                    </button>
                    <router-link to="/ranges" class="btn btn-secondary">{{ t('common.cancel') }}</router-link>
                </div>
            </div>

            <div class="editor-main">
                <RangeGrid
                    :grid="form.grid"
                    :brush-action="brushAction"
                    @update:cell="updateCell"
                    @update:cells="updateCells"
                />
                <RangeLegend />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useRangeStore } from '../stores/ranges';
import { useI18n } from '../composables/useI18n';
import RangeGrid from '../components/range/RangeGrid.vue';
import RangeSlider from '../components/range/RangeSlider.vue';
import RangeLegend from '../components/range/RangeLegend.vue';
import { ACTIONS, createEmptyGrid, getHandsUpToStrength, getPositionForHand } from '../utils/hands';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const rangeStore = useRangeStore();

const isEditing = computed(() => !!route.params.id);

const actions = computed(() => [
    { value: ACTIONS.FOLD, label: t('actions.fold') },
    { value: ACTIONS.CALL, label: t('actions.call') },
    { value: ACTIONS.RAISE, label: t('actions.raise') },
]);

const form = reactive({
    name: '',
    description: '',
    defaultAction: ACTIONS.FOLD,
    grid: createEmptyGrid(ACTIONS.FOLD),
});

const brushAction = ref(ACTIONS.RAISE);
const sliderValue = ref(0);
const saving = ref(false);

const updateCell = (hand, action) => {
    form.grid[hand] = action;
};

const updateCells = (hands, action) => {
    for (const hand of hands) {
        form.grid[hand] = action;
    }
};

const fillByStrength = (strength, action) => {
    // Reset grid to default action first
    form.grid = createEmptyGrid(form.defaultAction);

    // Fill hands up to the strength threshold
    const handsToFill = getHandsUpToStrength(strength);
    for (const hand of handsToFill) {
        form.grid[hand] = action;
    }
};

const save = async () => {
    saving.value = true;
    try {
        if (isEditing.value) {
            await rangeStore.updateRange(route.params.id, form);
        } else {
            await rangeStore.createRange(form);
        }
        router.push('/ranges');
    } finally {
        saving.value = false;
    }
};

onMounted(async () => {
    if (isEditing.value) {
        const range = await rangeStore.getRange(route.params.id);
        if (range) {
            form.name = range.name;
            form.description = range.description || '';
            form.defaultAction = range.defaultAction || ACTIONS.FOLD;
            form.grid = { ...range.grid };
        }
    }
});
</script>

<style scoped>
.page-header {
    margin-bottom: var(--spacing-lg);
}

.editor-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: var(--spacing-lg);
    align-items: start;
}

.editor-sidebar {
    position: sticky;
    top: var(--spacing-lg);
}

.form-group {
    margin-bottom: var(--spacing-lg);
}

.form-group textarea {
    resize: vertical;
}

.action-buttons {
    display: flex;
    gap: var(--spacing-sm);
}

.action-buttons .btn {
    flex: 1;
    opacity: 0.6;
}

.action-buttons .btn.active {
    opacity: 1;
    box-shadow: 0 0 0 2px var(--color-primary);
}

.form-actions {
    display: flex;
    gap: var(--spacing-sm);
    margin-top: var(--spacing-xl);
}

.editor-main {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
}

@media (max-width: 900px) {
    .editor-layout {
        grid-template-columns: 1fr;
    }

    .editor-sidebar {
        position: static;
    }
}
</style>
