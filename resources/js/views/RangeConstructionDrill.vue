<template>
    <div class="range-construction-page container">
        <!-- Phase: Setup -->
        <div v-if="phase === 'setup'" class="setup-phase">
            <h1>{{ t('rangeConstruction.title') }}</h1>
            <p class="description">{{ t('rangeConstruction.description') }}</p>

            <div class="setup-form card">
                <div class="form-group">
                    <label class="label">{{ t('rangeConstruction.selectScenario') }}</label>
                    <select v-model="selectedScenarioId" class="input">
                        <option value="">{{ t('drill.selectScenario') }}</option>
                        <option v-for="s in scenarios" :key="s.id" :value="s.id">
                            {{ s.name }} ({{ formatPositions(s.positions) }}, {{ s.stack_depth }}bb)
                        </option>
                    </select>
                </div>

                <div v-if="store.error" class="error-message">
                    {{ store.error }}
                </div>

                <button
                    @click="startDrill"
                    class="btn btn-primary btn-lg"
                    :disabled="!selectedScenarioId || store.loading"
                >
                    {{ store.loading ? t('rangeConstruction.loading') : t('rangeConstruction.startDrawing') }}
                </button>
            </div>

            <div v-if="store.stats" class="stats-section card">
                <h2>{{ t('rangeConstruction.yourStats') }}</h2>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value">{{ store.stats.overall.totalAttempts }}</div>
                        <div class="stat-label">{{ t('rangeConstruction.totalAttempts') }}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">{{ store.stats.overall.avgAccuracy }}%</div>
                        <div class="stat-label">{{ t('rangeConstruction.avgAccuracy') }}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">{{ store.stats.overall.scenariosAttempted }}</div>
                        <div class="stat-label">{{ t('rangeConstruction.scenariosAttempted') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Phase: Active Drawing -->
        <div v-else-if="phase === 'active'" class="active-phase">
            <div class="drill-header">
                <div class="scenario-info">
                    <h2>{{ store.scenario.name }}</h2>
                    <div class="scenario-meta">
                        {{ formatPositions(store.scenario.positions) }} · {{ store.scenario.stackDepth }}bb
                    </div>
                </div>
                <div class="timer">{{ formatTime(elapsedTime) }}</div>
            </div>

            <div class="brush-actions">
                <button
                    v-for="action in actions"
                    :key="action.value"
                    :class="['btn', `btn-${action.value}`, { active: brushAction === action.value }]"
                    @click="brushAction = action.value"
                >
                    {{ action.label }} ({{ action.key }})
                </button>
            </div>
            <p class="hint">{{ t('rangeConstruction.hint') }}</p>

            <div class="slider-section">
                <label class="label">{{ t('scenarios.quickFill') }}</label>
                <RangeSlider
                    v-model="sliderValue"
                    :action="brushAction"
                    @fill="fillByStrength"
                />
            </div>

            <div class="grid-container">
                <RangeGrid
                    :grid="userGrid"
                    :brush-action="effectiveBrushAction"
                    @update:cell="updateCell"
                    @update:cells="updateCells"
                />
            </div>

            <RangeLegend />

            <div class="action-buttons">
                <button @click="clearGrid" class="btn btn-secondary">{{ t('rangeConstruction.clear') }}</button>
                <button @click="submitRange" class="btn btn-primary btn-lg" :disabled="store.loading">
                    {{ store.loading ? t('rangeConstruction.submitting') : t('rangeConstruction.submit') }}
                </button>
            </div>
        </div>

        <!-- Phase: Results -->
        <div v-else-if="phase === 'results'" class="results-phase">
            <h1>{{ t('rangeConstruction.results') }}</h1>

            <div class="accuracy-display">
                <div class="accuracy-value" :class="accuracyClass">
                    {{ store.result.accuracy }}%
                </div>
                <div class="accuracy-label">{{ t('stats.accuracy') }}</div>
                <div class="accuracy-details">
                    {{ store.result.correctCount }} / {{ store.result.totalCells }} {{ t('stats.hands').toLowerCase() }}
                </div>
            </div>

            <div class="grid-comparison">
                <div class="grid-section">
                    <h3>{{ t('rangeConstruction.yourRange') }}</h3>
                    <RangeGrid :grid="userGrid" :brush-action="''" readonly />
                </div>
                <div class="grid-section">
                    <h3>{{ t('rangeConstruction.correctRange') }}</h3>
                    <RangeGrid :grid="store.result.correctGrid" :brush-action="''" readonly />
                </div>
            </div>

            <div v-if="store.result.differences.length > 0" class="mistakes-section card">
                <h2>{{ t('rangeConstruction.mistakes') }} ({{ store.result.differences.length }})</h2>
                <div class="mistakes-list">
                    <div
                        v-for="diff in limitedDifferences"
                        :key="diff.hand"
                        class="mistake-item"
                    >
                        <span class="hand">{{ diff.hand }}</span>
                        <span class="your-action">{{ t('drillSummary.yourAnswer') }} {{ diff.userAction }}</span>
                        <span class="correct-action">{{ t('drillSummary.correctAnswer') }} {{ diff.correctAction }}</span>
                    </div>
                    <p v-if="store.result.differences.length > 20" class="more-mistakes">
                        ... {{ t('rangeConstruction.moreMistakes', { count: store.result.differences.length - 20 }) }}
                    </p>
                </div>
            </div>

            <div class="result-stats card">
                <h3>{{ t('rangeConstruction.yourProgress') }}</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value">{{ store.result.stats.totalAttempts }}</div>
                        <div class="stat-label">{{ t('rangeConstruction.totalAttempts') }}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">{{ store.result.stats.bestAccuracy }}%</div>
                        <div class="stat-label">{{ t('rangeConstruction.bestAccuracy') }}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">{{ store.result.stats.avgAccuracy }}%</div>
                        <div class="stat-label">{{ t('rangeConstruction.avgAccuracy') }}</div>
                    </div>
                </div>
            </div>

            <div class="action-buttons">
                <button @click="tryAgain" class="btn btn-primary btn-lg">{{ t('rangeConstruction.tryAgain') }}</button>
                <button @click="goToSetup" class="btn btn-secondary btn-lg">{{ t('rangeConstruction.differentScenario') }}</button>
                <router-link to="/drill" class="btn btn-secondary btn-lg">{{ t('rangeConstruction.backToDrills') }}</router-link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRangeConstructionStore } from '../stores/rangeConstruction';
import { useScenarioStore } from '../stores/scenarios';
import RangeGrid from '../components/range/RangeGrid.vue';
import RangeLegend from '../components/range/RangeLegend.vue';
import RangeSlider from '../components/range/RangeSlider.vue';
import { ACTIONS, createEmptyGrid, getHandsUpToStrength } from '../utils/hands';
import { useI18n } from '../composables/useI18n';

const { t } = useI18n();
const store = useRangeConstructionStore();
const scenarioStore = useScenarioStore();

const phase = ref('setup'); // 'setup', 'active', 'results'
const selectedScenarioId = ref('');
const brushAction = ref(ACTIONS.RAISE);
const userGrid = ref(createEmptyGrid(ACTIONS.FOLD));
const elapsedTime = ref(0);
const sliderValue = ref(0);
const ctrlHeld = ref(false);
const altHeld = ref(false);
let timerInterval = null;

const scenarios = computed(() => scenarioStore.scenarios);

const actions = computed(() => [
    { value: ACTIONS.FOLD, label: t('actions.fold'), key: 'F' },
    { value: ACTIONS.CALL, label: t('actions.call'), key: 'C' },
    { value: ACTIONS.RAISE, label: t('actions.raise'), key: 'R' },
]);

const limitedDifferences = computed(() => {
    if (!store.result?.differences) return [];
    return store.result.differences.slice(0, 20);
});

const accuracyClass = computed(() => {
    if (!store.result) return '';
    const acc = store.result.accuracy;
    if (acc >= 90) return 'excellent';
    if (acc >= 70) return 'good';
    if (acc >= 50) return 'fair';
    return 'poor';
});

// Ctrl = Fold, Alt = Call, otherwise use selected brush
const effectiveBrushAction = computed(() => {
    if (ctrlHeld.value) return ACTIONS.FOLD;
    if (altHeld.value) return ACTIONS.CALL;
    return brushAction.value;
});

const formatPositions = (positions) => {
    if (!positions || !Array.isArray(positions)) return '';
    return positions.join(', ');
};

const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const startDrill = async () => {
    const result = await store.startSession(selectedScenarioId.value);
    if (result.success) {
        userGrid.value = createEmptyGrid(ACTIONS.FOLD);
        elapsedTime.value = 0;
        phase.value = 'active';
        startTimer();
    }
};

const startTimer = () => {
    timerInterval = setInterval(() => {
        elapsedTime.value++;
    }, 1000);
};

const stopTimer = () => {
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
};

const updateCell = (hand) => {
    userGrid.value[hand] = effectiveBrushAction.value;
};

const updateCells = (hands) => {
    for (const hand of hands) {
        userGrid.value[hand] = effectiveBrushAction.value;
    }
};

const clearGrid = () => {
    userGrid.value = createEmptyGrid(ACTIONS.FOLD);
};

const fillByStrength = (percent, action) => {
    const hands = getHandsUpToStrength(percent);
    for (const hand of hands) {
        userGrid.value[hand] = action;
    }
};

const submitRange = async () => {
    stopTimer();
    const result = await store.submitRange(userGrid.value);
    if (result.success) {
        phase.value = 'results';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const tryAgain = async () => {
    const result = await store.startSession(store.scenario.id);
    if (result.success) {
        userGrid.value = createEmptyGrid(ACTIONS.FOLD);
        elapsedTime.value = 0;
        phase.value = 'active';
        startTimer();
    }
};

const goToSetup = () => {
    store.reset();
    phase.value = 'setup';
};

// Keyboard shortcuts for brush actions
const handleKeyDown = (e) => {
    if (phase.value !== 'active') return;

    // Track modifier keys
    if (e.key === 'Control') {
        ctrlHeld.value = true;
        return;
    }
    if (e.key === 'Alt') {
        altHeld.value = true;
        e.preventDefault();
        return;
    }

    switch (e.key.toLowerCase()) {
        case 'f':
            brushAction.value = ACTIONS.FOLD;
            break;
        case 'c':
            brushAction.value = ACTIONS.CALL;
            break;
        case 'r':
            brushAction.value = ACTIONS.RAISE;
            break;
    }
};

const handleKeyUp = (e) => {
    if (e.key === 'Control') {
        ctrlHeld.value = false;
    }
    if (e.key === 'Alt') {
        altHeld.value = false;
    }
};

const handleBlur = () => {
    ctrlHeld.value = false;
    altHeld.value = false;
};

onMounted(async () => {
    await scenarioStore.fetchScenarios();
    await store.fetchStats();
    window.addEventListener('keydown', handleKeyDown);
    window.addEventListener('keyup', handleKeyUp);
    window.addEventListener('blur', handleBlur);
});

onUnmounted(() => {
    stopTimer();
    window.removeEventListener('keydown', handleKeyDown);
    window.removeEventListener('keyup', handleKeyUp);
    window.removeEventListener('blur', handleBlur);
});
</script>

<style scoped>
.range-construction-page h1 {
    margin-bottom: var(--spacing-lg);
}

.description {
    color: var(--color-text-secondary);
    margin-bottom: var(--spacing-xl);
}

.setup-form {
    max-width: 500px;
    margin-bottom: var(--spacing-xl);
}

.form-group {
    margin-bottom: var(--spacing-lg);
}

.error-message {
    background-color: rgba(239, 68, 68, 0.1);
    border: 1px solid var(--color-error, #ef4444);
    color: var(--color-error, #ef4444);
    padding: var(--spacing-sm) var(--spacing-md);
    border-radius: var(--radius-md);
    margin-bottom: var(--spacing-md);
    font-size: 0.875rem;
}

.stats-section h2 {
    margin-bottom: var(--spacing-md);
}

.stats-grid {
    display: flex;
    gap: var(--spacing-xl);
}

.stat-item {
    text-align: center;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-primary);
}

.stat-label {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
}

/* Active Phase */
.drill-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: var(--spacing-lg);
}

.scenario-info h2 {
    margin-bottom: var(--spacing-xs);
}

.scenario-meta {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
}

.timer {
    font-size: 1.5rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    color: var(--color-text-secondary);
}

.brush-actions {
    display: flex;
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-lg);
}

.brush-actions .btn {
    flex: 1;
    max-width: 150px;
    opacity: 0.6;
}

.brush-actions .btn.active {
    opacity: 1;
    box-shadow: 0 0 0 2px var(--color-primary);
}

.hint {
    font-size: 0.75rem;
    color: var(--color-text-secondary);
    margin-bottom: var(--spacing-md);
}

.hint kbd {
    padding: 2px 6px;
    background: var(--color-bg-tertiary);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-sm);
    font-family: monospace;
    font-size: 0.7rem;
}

.slider-section {
    margin-bottom: var(--spacing-lg);
}

.slider-section .label {
    display: block;
    margin-bottom: var(--spacing-sm);
    font-size: 0.875rem;
    color: var(--color-text-secondary);
}

.grid-container {
    margin-bottom: var(--spacing-md);
}

.action-buttons {
    display: flex;
    gap: var(--spacing-md);
    margin-top: var(--spacing-lg);
    justify-content: center;
}

/* Results Phase */
.accuracy-display {
    text-align: center;
    margin-bottom: var(--spacing-xl);
}

.accuracy-value {
    font-size: 4rem;
    font-weight: 700;
}

.accuracy-value.excellent {
    color: var(--color-success, #22c55e);
}

.accuracy-value.good {
    color: var(--color-primary);
}

.accuracy-value.fair {
    color: var(--color-warning, #eab308);
}

.accuracy-value.poor {
    color: var(--color-error, #ef4444);
}

.accuracy-label {
    font-size: 1rem;
    color: var(--color-text-secondary);
    margin-top: var(--spacing-xs);
}

.accuracy-details {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
    margin-top: var(--spacing-xs);
}

.grid-comparison {
    display: flex;
    gap: var(--spacing-xl);
    justify-content: center;
    margin-bottom: var(--spacing-xl);
}

.grid-section {
    flex: 0 0 auto;
}

.grid-section h3 {
    text-align: center;
    margin-bottom: var(--spacing-md);
    color: var(--color-text-secondary);
}

.mistakes-section {
    margin-bottom: var(--spacing-xl);
}

.mistakes-section h2 {
    margin-bottom: var(--spacing-md);
}

.mistakes-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
    max-height: 300px;
    overflow-y: auto;
}

.mistake-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
    padding: var(--spacing-sm) var(--spacing-md);
    background: var(--color-bg-tertiary);
    border-radius: var(--radius-md);
}

.mistake-item .hand {
    font-weight: 600;
    min-width: 50px;
}

.mistake-item .your-action {
    color: var(--color-error);
}

.mistake-item .correct-action {
    color: var(--color-success);
}

.more-mistakes {
    text-align: center;
    color: var(--color-text-secondary);
    font-style: italic;
    margin-top: var(--spacing-sm);
}

.result-stats {
    max-width: 400px;
    margin: 0 auto var(--spacing-xl);
}

.result-stats h3 {
    text-align: center;
    margin-bottom: var(--spacing-md);
}

@media (max-width: 800px) {
    .grid-comparison {
        flex-direction: column;
        align-items: center;
    }
}
</style>
