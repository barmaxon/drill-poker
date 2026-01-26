<template>
    <div class="modal-overlay" @click.self="$emit('close')">
        <div class="modal card">
            <h2>{{ t('scenarios.copyScenario') }}</h2>

            <form @submit.prevent="save">
                <div class="form-group">
                    <label class="label" for="copy-name">{{ t('scenarios.name') }}</label>
                    <input
                        id="copy-name"
                        v-model="form.name"
                        type="text"
                        class="input"
                        required
                    />
                </div>

                <div class="form-group">
                    <label class="label">{{ t('scenarios.positions') }}</label>
                    <MultiSelect
                        v-model="form.positions"
                        :options="positions"
                        :groups="positionGroups"
                        :available-for-all="availablePositionsForName"
                        placeholder="Select positions..."
                    />
                </div>

                <div class="form-group">
                    <label class="label" for="copy-stackDepth">{{ t('scenarios.stackDepth') }}</label>
                    <input
                        id="copy-stackDepth"
                        v-model.number="form.stackDepth"
                        type="number"
                        class="input"
                        min="1"
                        max="200"
                    />
                </div>

                <div class="form-group">
                    <label class="label" for="copy-description">{{ t('scenarios.description') }}</label>
                    <textarea
                        id="copy-description"
                        v-model="form.description"
                        class="input"
                        rows="2"
                        placeholder="Optional description..."
                    ></textarea>
                </div>

                <div class="grid-preview">
                    <label class="label">{{ t('scenarios.rangeGrid') }}</label>
                    <p class="hint">{{ t('scenarios.rangeGridCopied') }}</p>
                </div>

                <div v-if="!hasChanges" class="warning-message">
                    {{ t('scenarios.changeField') }}
                </div>

                <div class="modal-actions">
                    <button type="button" @click="$emit('close')" class="btn btn-secondary">
                        {{ t('common.cancel') }}
                    </button>
                    <button type="submit" class="btn btn-primary" :disabled="!canSave || saving">
                        {{ saving ? t('scenarios.creating') : t('scenarios.createCopy') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { reactive, computed, ref, onMounted } from 'vue';
import { useScenarioStore } from '../stores/scenarios';
import MultiSelect from './MultiSelect.vue';
import { useI18n } from '../composables/useI18n';

const { t } = useI18n();

const props = defineProps({
    scenario: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close', 'saved']);

const scenarioStore = useScenarioStore();
const saving = ref(false);

const positions = ['UTG', 'UTG+1', 'UTG+2', 'LJ', 'HJ', 'CO', 'BTN', 'SB', 'BB'];

const positionGroups = {
    early: ['UTG', 'UTG+1', 'UTG+2'],
    middle: ['LJ', 'HJ'],
    late: ['CO', 'BTN'],
    blinds: ['SB', 'BB'],
};

// Get original values
const originalName = props.scenario.name || '';
const originalPositions = props.scenario.positions || [props.scenario.position] || [];
const originalStackDepth = props.scenario.stack_depth || props.scenario.stackDepth || 100;
const originalDescription = props.scenario.description || '';

const form = reactive({
    name: originalName + ' (Copy)',
    positions: [...originalPositions],
    stackDepth: originalStackDepth,
    description: originalDescription,
});

// Check if any field has changed from original
const hasChanges = computed(() => {
    if (form.name !== originalName) return true;
    if (form.stackDepth !== originalStackDepth) return true;
    if (form.description !== originalDescription) return true;

    // Check positions array
    if (form.positions.length !== originalPositions.length) return true;
    const sortedOriginal = [...originalPositions].sort();
    const sortedCurrent = [...form.positions].sort();
    for (let i = 0; i < sortedOriginal.length; i++) {
        if (sortedOriginal[i] !== sortedCurrent[i]) return true;
    }

    return false;
});

const canSave = computed(() => {
    return hasChanges.value && form.name && form.positions.length > 0;
});

// Get available positions for the current name (positions not yet used by scenarios with this name)
const availablePositionsForName = computed(() => {
    if (!form.name) return null;

    const nameLower = form.name.toLowerCase();
    const suggestion = scenarioStore.nameSuggestions.find(
        s => s.name.toLowerCase() === nameLower
    );

    if (!suggestion) return null;

    return suggestion.availablePositions?.length > 0 ? suggestion.availablePositions : null;
});

const save = async () => {
    if (!canSave.value) return;

    saving.value = true;
    try {
        const data = {
            name: form.name,
            positions: form.positions,
            stackDepth: form.stackDepth,
            limpers: props.scenario.limpers || 0,
            description: form.description,
            grid: { ...props.scenario.grid },
        };

        await scenarioStore.createScenario(data);
        emit('saved');
        emit('close');
    } finally {
        saving.value = false;
    }
};

onMounted(() => {
    scenarioStore.fetchNameSuggestions();
});
</script>

<style scoped>
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
}

.modal {
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal h2 {
    margin-bottom: var(--spacing-lg);
}

.form-group {
    margin-bottom: var(--spacing-lg);
}

.grid-preview {
    margin-bottom: var(--spacing-lg);
}

.grid-preview .hint {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
    margin-top: var(--spacing-xs);
}

.warning-message {
    background-color: rgba(234, 179, 8, 0.1);
    border: 1px solid var(--color-warning, #eab308);
    color: var(--color-warning, #eab308);
    padding: var(--spacing-sm) var(--spacing-md);
    border-radius: var(--radius-md);
    margin-bottom: var(--spacing-md);
    font-size: 0.875rem;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--spacing-sm);
    margin-top: var(--spacing-lg);
}
</style>
