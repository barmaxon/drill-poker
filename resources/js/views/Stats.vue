<template>
    <div class="stats-page container">
        <h1>Statistics</h1>

        <!-- View Tabs -->
        <div class="view-tabs">
            <button
                :class="['tab', { active: activeView === 'overall' }]"
                @click="activeView = 'overall'"
            >
                Overall
            </button>
            <button
                :class="['tab', { active: activeView === 'groups' }]"
                @click="activeView = 'groups'"
            >
                By Group
            </button>
            <button
                :class="['tab', { active: activeView === 'scenarios' }]"
                @click="activeView = 'scenarios'"
            >
                By Scenario
            </button>
        </div>

        <!-- Overall View -->
        <div v-if="activeView === 'overall'" class="view-content">
            <div class="stats-grid">
                <div class="stat-card card">
                    <h3>Total Hands</h3>
                    <div class="stat-value">{{ overallStats.totalHands }}</div>
                </div>
                <div class="stat-card card">
                    <h3>Accuracy</h3>
                    <div class="stat-value">{{ overallStats.accuracy }}%</div>
                </div>
                <div class="stat-card card">
                    <h3>Mistakes</h3>
                    <div class="stat-value">{{ overallStats.totalMistakes }} <span class="stat-detail">({{ overallStats.borderMistakes }} border)</span></div>
                </div>
                <div class="stat-card card">
                    <h3>Sessions</h3>
                    <div class="stat-value">{{ overallStats.sessions }}</div>
                </div>
            </div>

            <div class="stats-sections">
                <section class="card">
                    <h2>Overall Performance Heatmap</h2>
                    <HandHeatmap :data="overallHeatmap" />
                </section>

                <section class="card">
                    <h2>Problem Hands</h2>
                    <div class="problem-hands-list">
                        <div
                            v-for="hand in problemHands"
                            :key="hand.hand"
                            class="problem-hand"
                        >
                            <span class="hand-name">{{ hand.hand }}</span>
                            <span class="hand-accuracy">{{ hand.accuracy }}%</span>
                            <span class="hand-mistakes">{{ hand.mistakes }} mistakes</span>
                        </div>
                        <p v-if="problemHands.length === 0" class="text-secondary">
                            No problem hands yet. Start drilling to see your weak spots.
                        </p>
                    </div>
                </section>
            </div>
        </div>

        <!-- Groups View -->
        <div v-if="activeView === 'groups'" class="view-content">
            <div class="groups-stats-list">
                <div
                    v-for="group in groupStats"
                    :key="group.id"
                    class="group-stat-card card"
                >
                    <div class="group-header">
                        <h3>{{ group.name }}</h3>
                        <span class="scenario-count">{{ group.scenarioCount }} scenarios</span>
                    </div>
                    <div class="group-stats">
                        <div class="mini-stat">
                            <span class="mini-label">Hands</span>
                            <span class="mini-value">{{ group.totalHands }}</span>
                        </div>
                        <div class="mini-stat">
                            <span class="mini-label">Accuracy</span>
                            <span class="mini-value" :class="getAccuracyClass(group.accuracy)">
                                {{ group.accuracy }}%
                            </span>
                        </div>
                        <div class="mini-stat">
                            <span class="mini-label">Mistakes</span>
                            <span class="mini-value">{{ group.totalMistakes }} <span class="stat-detail">({{ group.borderMistakes }} border)</span></span>
                        </div>
                    </div>
                    <div class="group-scenarios">
                        <div
                            v-for="scenario in group.scenarios"
                            :key="scenario.id"
                            class="scenario-row"
                            @click="selectScenario(scenario)"
                        >
                            <div class="scenario-info-row">
                                <span class="scenario-name">{{ scenario.name }}</span>
                                <span class="scenario-meta">{{ formatPositions(scenario.positions) }} · {{ scenario.stackDepth }}bb</span>
                            </div>
                            <span class="scenario-accuracy" :class="getAccuracyClass(scenario.accuracy)">
                                {{ scenario.accuracy }}%
                            </span>
                        </div>
                    </div>
                </div>

                <div v-if="groupStats.length === 0" class="empty-state card">
                    <p>No group stats yet. Create groups and start drilling.</p>
                </div>
            </div>
        </div>

        <!-- Scenarios View -->
        <div v-if="activeView === 'scenarios'" class="view-content">
            <div class="scenario-selector">
                <label class="label">Select Scenario</label>
                <select v-model="selectedScenarioId" class="input">
                    <option value="">All Scenarios</option>
                    <option v-for="s in scenarios" :key="s.id" :value="s.id">
                        {{ s.name }} ({{ formatPositions(s.positions) }}, {{ s.stack_depth }}bb)
                    </option>
                </select>
            </div>

            <div v-if="selectedScenarioId" class="scenario-detail">
                <div class="stats-grid">
                    <div class="stat-card card">
                        <h3>Hands Drilled</h3>
                        <div class="stat-value">{{ selectedScenarioStats.totalHands }}</div>
                    </div>
                    <div class="stat-card card">
                        <h3>Accuracy</h3>
                        <div class="stat-value" :class="getAccuracyClass(selectedScenarioStats.accuracy)">
                            {{ selectedScenarioStats.accuracy }}%
                        </div>
                    </div>
                    <div class="stat-card card">
                        <h3>Mistakes</h3>
                        <div class="stat-value">{{ selectedScenarioStats.totalMistakes }} <span class="stat-detail">({{ selectedScenarioStats.borderMistakes }} border)</span></div>
                    </div>
                </div>

                <div class="stats-sections">
                    <section class="card">
                        <h2>{{ selectedScenario?.name }} - Heatmap</h2>
                        <HandHeatmap :data="selectedScenarioHeatmap" />
                    </section>

                    <section class="card">
                        <h2>Problem Hands</h2>
                        <div class="problem-hands-list">
                            <div
                                v-for="hand in selectedScenarioProblemHands"
                                :key="hand.hand"
                                class="problem-hand"
                            >
                                <span class="hand-name">{{ hand.hand }}</span>
                                <span class="hand-accuracy">{{ hand.accuracy }}%</span>
                                <span class="hand-mistakes">{{ hand.mistakes }} mistakes</span>
                            </div>
                            <p v-if="selectedScenarioProblemHands.length === 0" class="text-secondary">
                                No problem hands for this scenario yet.
                            </p>
                        </div>
                    </section>
                </div>
            </div>

            <div v-else class="scenarios-overview">
                <div class="scenarios-stats-list">
                    <div
                        v-for="scenario in scenarioStats"
                        :key="scenario.id"
                        class="scenario-stat-card card"
                        @click="selectedScenarioId = scenario.id"
                    >
                        <div class="scenario-info">
                            <h3>{{ scenario.name }}</h3>
                            <div class="scenario-badges">
                                <span class="badge">{{ formatPositions(scenario.positions) }}</span>
                                <span class="badge">{{ scenario.stackDepth }}bb</span>
                            </div>
                        </div>
                        <div class="scenario-stats">
                            <div class="mini-stat">
                                <span class="mini-value">{{ scenario.totalHands }}</span>
                                <span class="mini-label">hands</span>
                            </div>
                            <div class="mini-stat">
                                <span class="mini-value" :class="getAccuracyClass(scenario.accuracy)">
                                    {{ scenario.accuracy }}%
                                </span>
                                <span class="mini-label">accuracy</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useStatsStore } from '../stores/stats';
import { useScenarioStore } from '../stores/scenarios';
import { useGroupStore } from '../stores/groups';
import HandHeatmap from '../components/stats/HandHeatmap.vue';

const statsStore = useStatsStore();
const scenarioStore = useScenarioStore();
const groupStore = useGroupStore();

const activeView = ref('overall');
const selectedScenarioId = ref('');

// Overall stats
const overallStats = ref({ totalHands: 0, accuracy: 0, totalMistakes: 0, borderMistakes: 0, sessions: 0 });
const overallHeatmap = ref({});
const problemHands = ref([]);

// Scenarios
const scenarios = computed(() => scenarioStore.scenarios);
const selectedScenario = computed(() =>
    scenarios.value.find(s => s.id === parseInt(selectedScenarioId.value))
);

// Stats by scenario
const scenarioStats = ref([]);
const selectedScenarioStats = ref({ totalHands: 0, accuracy: 0, totalMistakes: 0, borderMistakes: 0 });
const selectedScenarioHeatmap = ref({});
const selectedScenarioProblemHands = ref([]);

// Stats by group
const groupStats = ref([]);

const getAccuracyClass = (accuracy) => {
    if (accuracy >= 80) return 'accuracy-good';
    if (accuracy >= 60) return 'accuracy-ok';
    return 'accuracy-bad';
};

const formatPositions = (positions) => {
    if (!positions || !Array.isArray(positions)) return '';
    return positions.join(', ');
};

const selectScenario = (scenario) => {
    activeView.value = 'scenarios';
    selectedScenarioId.value = scenario.id;
};

const loadOverallStats = async () => {
    overallStats.value = await statsStore.fetchOverview();
    overallHeatmap.value = await statsStore.fetchHeatmapData();
    problemHands.value = await statsStore.fetchProblemHands();
};

const loadScenarioStats = async () => {
    scenarioStats.value = await statsStore.fetchScenarioStats();
};

const loadSelectedScenarioStats = async (scenarioId) => {
    const stats = await statsStore.fetchScenarioDetail(scenarioId);
    selectedScenarioStats.value = stats.summary;
    selectedScenarioHeatmap.value = stats.heatmap;
    selectedScenarioProblemHands.value = stats.problemHands;
};

const loadGroupStats = async () => {
    groupStats.value = await statsStore.fetchGroupStats();
};

watch(selectedScenarioId, (newId) => {
    if (newId) {
        loadSelectedScenarioStats(newId);
    }
});

watch(activeView, (view) => {
    if (view === 'groups') {
        loadGroupStats();
    } else if (view === 'scenarios') {
        loadScenarioStats();
    }
});

onMounted(async () => {
    await scenarioStore.fetchScenarios();
    await groupStore.fetchGroups();
    await loadOverallStats();
});
</script>

<style scoped>
.stats-page h1 {
    margin-bottom: var(--spacing-lg);
}

.view-tabs {
    display: flex;
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-lg);
}

.tab {
    padding: var(--spacing-sm) var(--spacing-lg);
    background: var(--color-bg-tertiary);
    border: none;
    border-radius: var(--radius-md);
    color: var(--color-text-secondary);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.tab:hover {
    background: var(--color-bg-hover);
}

.tab.active {
    background: var(--color-primary);
    color: white;
}

.view-content {
    animation: fadeIn var(--transition-normal);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-xl);
}

.stat-card {
    text-align: center;
}

.stat-card h3 {
    color: var(--color-text-secondary);
    font-size: 0.875rem;
    margin-bottom: var(--spacing-sm);
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-primary);
}

.stat-detail {
    font-size: 0.875rem;
    font-weight: 400;
    color: var(--color-text-secondary);
}

.stats-sections {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: var(--spacing-lg);
}

.stats-sections h2 {
    margin-bottom: var(--spacing-md);
}

/* Problem hands */
.problem-hands-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
}

.problem-hand {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-sm);
    background-color: var(--color-bg-tertiary);
    border-radius: var(--radius-sm);
}

.hand-name {
    font-weight: 600;
}

.hand-accuracy {
    color: var(--color-error);
}

.hand-mistakes {
    color: var(--color-text-muted);
    font-size: 0.875rem;
}

/* Group stats */
.groups-stats-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-lg);
}

.group-stat-card {
    padding: var(--spacing-lg);
}

.group-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-md);
}

.group-header h3 {
    margin: 0;
}

.scenario-count {
    color: var(--color-text-muted);
    font-size: 0.875rem;
}

.group-stats {
    display: flex;
    gap: var(--spacing-xl);
    margin-bottom: var(--spacing-md);
    padding-bottom: var(--spacing-md);
    border-bottom: 1px solid var(--color-border);
}

.mini-stat {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.mini-label {
    font-size: 0.75rem;
    color: var(--color-text-muted);
}

.mini-value {
    font-size: 1.25rem;
    font-weight: 600;
}

.group-scenarios {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xs);
}

.scenario-row {
    display: flex;
    justify-content: space-between;
    padding: var(--spacing-sm);
    background: var(--color-bg-tertiary);
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: background var(--transition-fast);
}

.scenario-row:hover {
    background: var(--color-bg-hover);
}

.scenario-info-row {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.scenario-meta {
    font-size: 0.75rem;
    color: var(--color-text-secondary);
}

/* Scenario stats */
.scenario-selector {
    margin-bottom: var(--spacing-lg);
    max-width: 400px;
}

.scenarios-stats-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: var(--spacing-md);
}

.scenario-stat-card {
    cursor: pointer;
    transition: transform var(--transition-fast);
}

.scenario-stat-card:hover {
    transform: translateY(-2px);
}

.scenario-info h3 {
    margin-bottom: var(--spacing-xs);
}

.scenario-badges {
    display: flex;
    gap: var(--spacing-xs);
    margin-bottom: var(--spacing-md);
}

.badge {
    padding: var(--spacing-xs) var(--spacing-sm);
    background: var(--color-bg-tertiary);
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    color: var(--color-text-secondary);
}

.scenario-stats {
    display: flex;
    gap: var(--spacing-lg);
}

.scenario-stats .mini-stat {
    flex-direction: row;
    align-items: baseline;
    gap: var(--spacing-xs);
}

/* Accuracy colors */
.accuracy-good {
    color: var(--color-success);
}

.accuracy-ok {
    color: var(--color-warning);
}

.accuracy-bad {
    color: var(--color-error);
}

.empty-state {
    text-align: center;
    padding: var(--spacing-xl);
    color: var(--color-text-secondary);
}

@media (max-width: 900px) {
    .stats-sections {
        grid-template-columns: 1fr;
    }

    .group-stats {
        flex-wrap: wrap;
    }
}
</style>
