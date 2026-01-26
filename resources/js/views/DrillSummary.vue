<template>
    <div class="drill-summary-page container">
        <h1>Drill Complete</h1>

        <div class="summary-grid">
            <div class="summary-card card">
                <h3>Total Hands</h3>
                <div class="value">{{ summary.totalHands }}</div>
            </div>
            <div class="summary-card card">
                <h3>Correct</h3>
                <div class="value correct">{{ summary.correctCount }}</div>
            </div>
            <div class="summary-card card">
                <h3>Accuracy</h3>
                <div class="value">{{ summary.accuracy }}%</div>
            </div>
            <div class="summary-card card">
                <h3>Border Mistakes</h3>
                <div class="value error">{{ summary.borderMistakes }}</div>
            </div>
        </div>

        <div v-if="summary.comparison" class="comparison-section card">
            <h2>Progress</h2>
            <div class="comparison-grid">
                <div class="comparison-item">
                    <span class="comparison-label">Before</span>
                    <span class="comparison-value">{{ summary.comparison.overallBefore }}%</span>
                </div>
                <div class="comparison-item">
                    <span class="comparison-label">After</span>
                    <span class="comparison-value">{{ summary.comparison.overallAfter }}%</span>
                </div>
                <div class="comparison-item change">
                    <span class="comparison-label">Change</span>
                    <span
                        class="comparison-value"
                        :class="changeClass"
                    >
                        {{ summary.comparison.change >= 0 ? '+' : '' }}{{ summary.comparison.change }}%
                    </span>
                </div>
            </div>
        </div>

        <div class="mistakes-section card" v-if="summary.mistakes?.length > 0">
            <h2>Mistakes</h2>
            <div class="mistakes-list">
                <div
                    v-for="(mistake, index) in summary.mistakes"
                    :key="index"
                    class="mistake-item"
                >
                    <span class="hand">{{ mistake.hand }}</span>
                    <div class="scenario-info">
                        <span class="scenario-name">{{ mistake.scenario }}</span>
                        <span class="scenario-details">
                            {{ formatPositions(mistake.positions) }} · {{ mistake.stackDepth }}bb
                        </span>
                    </div>
                    <span class="your-action">You: {{ mistake.userAction }}</span>
                    <span class="correct-action">Correct: {{ mistake.correctAction }}</span>
                    <span v-if="mistake.isBorder" class="border-badge">Border</span>
                </div>
            </div>
        </div>

        <div v-if="drilledScenarios.length > 0" class="view-ranges-section">
            <h3>View Ranges</h3>
            <div class="range-links">
                <a
                    v-for="scenario in drilledScenarios"
                    :key="scenario.id"
                    :href="`/scenarios/${scenario.id}/edit`"
                    target="_blank"
                    class="range-link"
                >
                    <span class="scenario-name">{{ scenario.name }}</span>
                    <span class="scenario-meta">{{ formatPositions(scenario.positions) }} · {{ scenario.stackDepth }}bb</span>
                    <span class="external-icon">↗</span>
                </a>
            </div>
        </div>

        <div class="summary-actions">
            <router-link to="/drill" class="btn btn-primary btn-lg">Drill Again</router-link>
            <router-link to="/stats" class="btn btn-secondary btn-lg">View Stats</router-link>
            <router-link to="/" class="btn btn-secondary btn-lg">Home</router-link>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useDrillStore } from '../stores/drill';

const drillStore = useDrillStore();

const summary = computed(() => drillStore.sessionSummary || {
    totalHands: 0,
    correctCount: 0,
    accuracy: 0,
    borderMistakes: 0,
    mistakes: [],
    comparison: null,
});

const changeClass = computed(() => {
    if (!summary.value.comparison) return '';
    const change = summary.value.comparison.change;
    if (change > 0) return 'positive';
    if (change < 0) return 'negative';
    return 'neutral';
});

// Get unique scenarios that were drilled
const drilledScenarios = computed(() => {
    const seen = new Map();

    // Get from answers (has scenario info per answer)
    for (const answer of drillStore.answers) {
        if (answer.scenarioId && !seen.has(answer.scenarioId)) {
            seen.set(answer.scenarioId, {
                id: answer.scenarioId,
                name: answer.scenario,
                positions: answer.positions,
                stackDepth: answer.stackDepth,
            });
        }
    }

    // If single scenario drill and no answers yet, get from config
    if (seen.size === 0 && drillStore.config?.type === 'scenario' && drillStore.config?.scenarioId) {
        seen.set(drillStore.config.scenarioId, {
            id: drillStore.config.scenarioId,
            name: drillStore.currentScenario?.name || 'Scenario',
            positions: drillStore.currentScenario?.positions,
            stackDepth: drillStore.currentScenario?.stackDepth,
        });
    }

    return Array.from(seen.values());
});

const formatPositions = (positions) => {
    if (!positions || !Array.isArray(positions)) return '';
    return positions.join(', ');
};
</script>

<style scoped>
.drill-summary-page h1 {
    text-align: center;
    margin-bottom: var(--spacing-xl);
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-xl);
}

.summary-card {
    text-align: center;
}

.summary-card h3 {
    color: var(--color-text-secondary);
    font-size: 0.875rem;
    margin-bottom: var(--spacing-sm);
}

.summary-card .value {
    font-size: 2.5rem;
    font-weight: 700;
}

.summary-card .value.correct {
    color: var(--color-success);
}

.summary-card .value.error {
    color: var(--color-error);
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
    min-width: 60px;
}

.mistake-item .scenario-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.mistake-item .scenario-name {
    font-weight: 500;
}

.mistake-item .scenario-details {
    font-size: 0.75rem;
    color: var(--color-text-secondary);
}

.mistake-item .your-action {
    color: var(--color-error);
}

.mistake-item .correct-action {
    color: var(--color-success);
}

.border-badge {
    padding: var(--spacing-xs) var(--spacing-sm);
    background: var(--color-warning);
    color: var(--color-bg-primary);
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    font-weight: 600;
}

.summary-actions {
    display: flex;
    justify-content: center;
    gap: var(--spacing-md);
}

.comparison-section {
    margin-bottom: var(--spacing-xl);
}

.comparison-section h2 {
    margin-bottom: var(--spacing-md);
    text-align: center;
}

.comparison-grid {
    display: flex;
    justify-content: center;
    gap: var(--spacing-xl);
}

.comparison-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--spacing-xs);
}

.comparison-label {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
}

.comparison-value {
    font-size: 1.5rem;
    font-weight: 700;
}

.comparison-value.positive {
    color: var(--color-success);
}

.comparison-value.negative {
    color: var(--color-error);
}

.comparison-value.neutral {
    color: var(--color-text-secondary);
}

.view-ranges-section {
    margin-bottom: var(--spacing-xl);
    text-align: center;
}

.view-ranges-section h3 {
    color: var(--color-text-secondary);
    font-size: 0.875rem;
    margin-bottom: var(--spacing-md);
}

.range-links {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: var(--spacing-sm);
}

.range-link {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-sm);
    padding: var(--spacing-sm) var(--spacing-md);
    background: var(--color-bg-tertiary);
    border-radius: var(--radius-md);
    text-decoration: none;
    transition: background var(--transition-fast);
}

.range-link:hover {
    background: var(--color-bg-hover);
}

.range-link .scenario-name {
    color: var(--color-primary);
    font-weight: 500;
}

.range-link .scenario-meta {
    font-size: 0.75rem;
    color: var(--color-text-secondary);
}

.external-icon {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
    opacity: 0.7;
}
</style>
