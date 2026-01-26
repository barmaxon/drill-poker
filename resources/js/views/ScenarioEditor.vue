<template>
    <div class="scenario-editor-page container">
        <div class="page-header">
            <h1>{{ isEditing ? 'Edit Scenario' : 'Create Scenario' }}</h1>
        </div>

        <div class="editor-layout">
            <div class="editor-sidebar card">
                <div class="form-group">
                    <label class="label" for="name">Name</label>
                    <NameAutocomplete
                        id="name"
                        v-model="form.name"
                        :suggestions="scenarioStore.nameSuggestions"
                        placeholder="e.g., Open Raise"
                        @select="onNameSelect"
                    />
                </div>

                <div class="form-group">
                    <label class="label">Positions</label>
                    <MultiSelect
                        v-model="form.positions"
                        :options="positions"
                        :groups="positionGroups"
                        :available-for-all="availablePositionsForName"
                        placeholder="Select positions..."
                    />
                </div>

                <div class="form-group">
                    <label class="label" for="stackDepth">Stack Depth (bb)</label>
                    <input
                        id="stackDepth"
                        v-model.number="form.stackDepth"
                        type="number"
                        class="input"
                        min="1"
                        max="200"
                    />
                </div>

                <div class="form-group">
                    <label class="label" for="description">Description</label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        class="input"
                        rows="2"
                        placeholder="Optional description..."
                    ></textarea>
                </div>

                <div class="form-group slug-preview">
                    <label class="label">Slug Preview</label>
                    <code class="slug-value">{{ slugPreview }}</code>
                </div>

                <hr class="divider" />

                <div class="form-group">
                    <label class="label">Brush Action</label>
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
                    <p class="hint">Hold <kbd>Ctrl</kbd> for Fold, <kbd>Alt</kbd> for Call</p>
                </div>

                <div class="form-group">
                    <label class="label">Quick Fill (Top X hands)</label>
                    <RangeSlider
                        v-model="sliderValue"
                        :action="brushAction"
                        @fill="fillByStrength"
                    />
                </div>

                <div class="form-actions">
                    <button @click="save" class="btn btn-primary" :disabled="saving || !canSave">
                        {{ saving ? 'Saving...' : 'Save Scenario' }}
                    </button>
                    <router-link to="/scenarios" class="btn btn-secondary">Cancel</router-link>
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
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useScenarioStore } from '../stores/scenarios';
import RangeGrid from '../components/range/RangeGrid.vue';
import RangeSlider from '../components/range/RangeSlider.vue';
import RangeLegend from '../components/range/RangeLegend.vue';
import MultiSelect from '../components/MultiSelect.vue';
import NameAutocomplete from '../components/NameAutocomplete.vue';
import { ACTIONS, createEmptyGrid, getHandsUpToStrength } from '../utils/hands';
import { getCachedStackSize, setCachedStackSize } from '../utils/cache';
import { generateSlugPreview } from '../utils/slug';

const route = useRoute();
const router = useRouter();
const scenarioStore = useScenarioStore();

const isEditing = computed(() => !!route.params.id);

const positions = ['UTG', 'UTG+1', 'UTG+2', 'LJ', 'HJ', 'CO', 'BTN', 'SB', 'BB'];

const positionGroups = {
    early: ['UTG', 'UTG+1', 'UTG+2'],
    middle: ['LJ', 'HJ'],
    late: ['CO', 'BTN'],
    blinds: ['SB', 'BB'],
};

const actions = [
    { value: ACTIONS.FOLD, label: 'Fold' },
    { value: ACTIONS.CALL, label: 'Call' },
    { value: ACTIONS.RAISE, label: 'Raise' },
];

const form = reactive({
    name: '',
    positions: [],
    stackDepth: getCachedStackSize(),
    limpers: 0,
    description: '',
    grid: createEmptyGrid(ACTIONS.FOLD),
});

const brushAction = ref(ACTIONS.RAISE);
const sliderValue = ref(0);
const saving = ref(false);

// Track modifier keys for brush action override
const ctrlHeld = ref(false);
const altHeld = ref(false);

// Computed brush action based on modifier keys
const effectiveBrushAction = computed(() => {
    if (ctrlHeld.value) return ACTIONS.FOLD;
    if (altHeld.value) return ACTIONS.CALL;
    return brushAction.value;
});

// Slug preview computed from form values
const slugPreview = computed(() => {
    return generateSlugPreview(form.name, form.positions, form.stackDepth);
});

// Validation
const canSave = computed(() => {
    return form.name && form.positions.length > 0;
});

// Get available positions for the current name (positions not yet used by scenarios with this name)
const availablePositionsForName = computed(() => {
    if (!form.name) return null; // No name = select all positions

    const nameLower = form.name.toLowerCase();
    const suggestion = scenarioStore.nameSuggestions.find(
        s => s.name.toLowerCase() === nameLower
    );

    if (!suggestion) return null; // New name = select all positions

    // Return positions not yet used by this scenario name
    return suggestion.availablePositions?.length > 0 ? suggestion.availablePositions : null;
});

const updateCell = (hand, action) => {
    form.grid[hand] = effectiveBrushAction.value;
};

const updateCells = (hands, action) => {
    for (const hand of hands) {
        form.grid[hand] = effectiveBrushAction.value;
    }
};

const fillByStrength = (strength, action) => {
    // Reset grid to fold first
    form.grid = createEmptyGrid(ACTIONS.FOLD);

    // Fill hands up to the strength threshold
    const handsToFill = getHandsUpToStrength(strength);
    for (const hand of handsToFill) {
        form.grid[hand] = action;
    }
};

const onNameSelect = (suggestion) => {
    // When selecting a suggestion, keep the canonical name case
    form.name = suggestion.name;
};

const save = async () => {
    saving.value = true;
    try {
        const data = {
            name: form.name,
            positions: form.positions,
            stackDepth: form.stackDepth,
            limpers: form.limpers,
            description: form.description,
            grid: form.grid,
        };

        if (isEditing.value) {
            await scenarioStore.updateScenario(route.params.id, data);
        } else {
            await scenarioStore.createScenario(data);
        }

        // Cache stack size for future scenarios
        setCachedStackSize(form.stackDepth);

        router.push('/scenarios');
    } finally {
        saving.value = false;
    }
};

// Keyboard event handlers for modifier keys
const handleKeyDown = (event) => {
    if (event.key === 'Control') {
        ctrlHeld.value = true;
    } else if (event.key === 'Alt') {
        altHeld.value = true;
        event.preventDefault(); // Prevent browser menu
    }
};

const handleKeyUp = (event) => {
    if (event.key === 'Control') {
        ctrlHeld.value = false;
    } else if (event.key === 'Alt') {
        altHeld.value = false;
    }
};

// Reset modifier states when window loses focus
const handleBlur = () => {
    ctrlHeld.value = false;
    altHeld.value = false;
};

onMounted(async () => {
    // Fetch name suggestions
    scenarioStore.fetchNameSuggestions();

    // Add keyboard listeners for modifier keys
    window.addEventListener('keydown', handleKeyDown);
    window.addEventListener('keyup', handleKeyUp);
    window.addEventListener('blur', handleBlur);

    if (isEditing.value) {
        const scenario = await scenarioStore.getScenario(route.params.id);
        if (scenario) {
            form.name = scenario.name;
            form.positions = scenario.positions || [scenario.position];
            form.stackDepth = scenario.stack_depth || scenario.stackDepth;
            form.limpers = scenario.limpers || 0;
            form.description = scenario.description || '';
            form.grid = { ...scenario.grid };
        }
    }
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
    window.removeEventListener('keyup', handleKeyUp);
    window.removeEventListener('blur', handleBlur);
});
</script>

<style scoped>
.page-header {
    margin-bottom: var(--spacing-lg);
}

.editor-layout {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: var(--spacing-lg);
    align-items: start;
}

.editor-sidebar {
    position: sticky;
    top: var(--spacing-lg);
}

.form-group {
    margin-bottom: var(--spacing-md);
}

.form-group textarea {
    resize: vertical;
}

.slug-preview {
    background-color: var(--color-bg-tertiary);
    padding: var(--spacing-sm);
    border-radius: var(--radius-sm);
}

.slug-value {
    display: block;
    font-size: 0.875rem;
    color: var(--color-text-secondary);
    word-break: break-all;
}

.hint {
    font-size: 0.75rem;
    color: var(--color-text-secondary);
    margin-top: var(--spacing-xs);
}

.hint kbd {
    display: inline-block;
    padding: 2px 6px;
    font-size: 0.7rem;
    font-family: inherit;
    background-color: var(--color-bg-tertiary);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-sm);
}

.divider {
    border: none;
    border-top: 1px solid var(--color-border);
    margin: var(--spacing-lg) 0;
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
    margin-top: var(--spacing-lg);
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
