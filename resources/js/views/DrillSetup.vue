<template>
    <div class="drill-setup-page container">
        <h1>Start Drill</h1>

        <div v-if="drillStore.suggestions.length > 0" class="suggestions-section">
            <h3>Suggested Drills</h3>
            <div class="suggestion-cards">
                <div
                    v-for="s in drillStore.suggestions"
                    :key="s.scenario.id"
                    class="suggestion-card card"
                    :class="s.type"
                >
                    <div class="card-badge">{{ getBadgeText(s.type) }}</div>
                    <div class="scenario-name">{{ s.scenario.name }}</div>
                    <div class="scenario-meta">
                        {{ formatPositions(s.scenario.positions) }} · {{ s.scenario.stack_depth }}bb
                    </div>
                    <div class="reason">{{ s.reason }}</div>
                    <div v-if="s.accuracy !== undefined" class="accuracy">{{ s.accuracy }}%</div>

                    <div v-if="s.scenario.groups?.length" class="groups-section">
                        <div class="groups-header">Groups</div>
                        <div class="groups-list">
                            <button
                                v-for="g in s.scenario.groups"
                                :key="g.id"
                                class="btn btn-sm btn-secondary"
                                @click="selectGroup(g.id)"
                            >
                                {{ g.name }}
                            </button>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button
                            class="btn btn-sm btn-primary"
                            @click="selectScenario(s.scenario.id)"
                        >
                            Drill This
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="setup-form card">
            <div class="form-group">
                <label class="label">Select Scenario or Group</label>
                <div class="selection-tabs">
                    <button
                        :class="['tab', { active: selectionType === 'scenario' }]"
                        @click="selectionType = 'scenario'"
                    >
                        Single Scenario
                    </button>
                    <button
                        :class="['tab', { active: selectionType === 'group' }]"
                        @click="selectionType = 'group'"
                    >
                        Scenario Group
                    </button>
                    <button
                        :class="['tab', { active: selectionType === 'global' }]"
                        @click="selectionType = 'global'"
                    >
                        Global Drill
                    </button>
                </div>

                <select v-if="selectionType === 'scenario'" v-model="selectedScenario" class="input">
                    <option value="">Select a scenario...</option>
                    <option v-for="s in scenarios" :key="s.id" :value="s.id">
                        {{ s.name }} ({{ formatPositions(s.positions) }}, {{ s.stack_depth }}bb)
                    </option>
                </select>

                <select v-else-if="selectionType === 'group'" v-model="selectedGroup" class="input">
                    <option value="">Select a group...</option>
                    <option v-for="g in groups" :key="g.id" :value="g.id">
                        {{ g.name }} ({{ g.scenarios?.length || 0 }} scenarios)
                        {{ g.is_active === false ? '[Inactive]' : '' }}
                    </option>
                </select>

                <div v-else-if="selectionType === 'global'" class="global-info">
                    <p class="global-description">
                        Drills from all active groups, prioritizing scenarios with lowest accuracy.
                    </p>
                    <div class="global-stats">
                        <span class="stat-item">
                            <strong>{{ activeGroupCount }}</strong> active groups
                        </span>
                        <span class="stat-item">
                            <strong>{{ activeScenarioCount }}</strong> scenarios
                        </span>
                    </div>
                    <p v-if="activeGroupCount === 0" class="warning-text">
                        No active groups found. Mark groups as active in the Groups page to use Global Drill.
                    </p>
                </div>
            </div>

            <div class="form-group">
                <label class="label">Timer Settings</label>
                <div class="timer-options">
                    <label class="checkbox-label">
                        <input type="checkbox" v-model="useTimer" />
                        <span>Enable countdown timer</span>
                    </label>
                    <div v-if="useTimer" class="timer-input">
                        <input
                            type="number"
                            v-model.number="timerSeconds"
                            class="input"
                            min="1"
                            max="60"
                        />
                        <span>seconds per hand</span>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="label">Hand Limit (optional)</label>
                <input
                    type="number"
                    v-model.number="handLimit"
                    class="input"
                    placeholder="Leave empty for unlimited"
                    min="1"
                />
            </div>

            <div v-if="drillStore.error" class="error-message">
                {{ drillStore.error }}
            </div>

            <button
                @click="startDrill"
                class="btn btn-primary btn-lg"
                :disabled="!canStart"
            >
                Start Drilling
            </button>
        </div>

        <div class="alternative-drills card">
            <h3>Other Drill Modes</h3>
            <router-link to="/drill/range-construction" class="drill-mode-link">
                <div class="drill-mode-info">
                    <strong>Range Construction</strong>
                    <span>Draw the entire range from memory</span>
                </div>
                <span class="arrow">&rarr;</span>
            </router-link>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useScenarioStore } from '../stores/scenarios';
import { useGroupStore } from '../stores/groups';
import { useDrillStore } from '../stores/drill';
import { getCachedDrillSelection, setCachedDrillSelection, getCachedDrillSettings, setCachedDrillSettings } from '../utils/cache';

const router = useRouter();
const scenarioStore = useScenarioStore();
const groupStore = useGroupStore();
const drillStore = useDrillStore();

const selectionType = ref('scenario');
const selectedScenario = ref('');
const selectedGroup = ref('');
const useTimer = ref(true);
const timerSeconds = ref(10);
const handLimit = ref(null);

const scenarios = computed(() => scenarioStore.scenarios);
const groups = computed(() => groupStore.groups);

const formatPositions = (positions) => {
    if (!positions || !Array.isArray(positions)) return '';
    return positions.join(', ');
};

const getBadgeText = (type) => {
    switch (type) {
        case 'declining': return 'Declining';
        case 'low': return 'Low Stats';
        case 'new': return 'New';
        default: return type;
    }
};

const selectGroup = (groupId) => {
    selectionType.value = 'group';
    selectedGroup.value = groupId;
};

const selectScenario = (scenarioId) => {
    selectionType.value = 'scenario';
    selectedScenario.value = scenarioId;
};

// Compute active groups and scenarios for global drill
const activeGroups = computed(() => groups.value.filter(g => g.is_active !== false));
const activeGroupCount = computed(() => activeGroups.value.length);
const activeScenarioCount = computed(() => {
    const scenarioIds = new Set();
    for (const group of activeGroups.value) {
        for (const scenario of (group.scenarios || [])) {
            scenarioIds.add(scenario.id);
        }
    }
    return scenarioIds.size;
});

const canStart = computed(() => {
    if (selectionType.value === 'scenario') {
        return !!selectedScenario.value;
    }
    if (selectionType.value === 'global') {
        return activeScenarioCount.value > 0;
    }
    return !!selectedGroup.value;
});

const startDrill = async () => {
    const config = {
        type: selectionType.value,
        scenarioId: selectionType.value === 'scenario' ? selectedScenario.value : null,
        groupId: selectionType.value === 'group' ? selectedGroup.value : null,
        useTimer: useTimer.value,
        timerSeconds: timerSeconds.value,
        handLimit: handLimit.value || null,
    };

    // Cache selection for next time (not for global)
    if (selectionType.value !== 'global') {
        const selectedId = selectionType.value === 'scenario' ? selectedScenario.value : selectedGroup.value;
        if (selectedId) {
            setCachedDrillSelection(selectionType.value, selectedId);
        }
    }

    // Cache drill settings
    setCachedDrillSettings({
        useTimer: useTimer.value,
        timerSeconds: timerSeconds.value,
        handLimit: handLimit.value,
    });

    const result = await drillStore.startSession(config);
    if (result?.success) {
        router.push('/drill/active');
    }
};

onMounted(async () => {
    await Promise.all([
        scenarioStore.fetchScenarios(),
        groupStore.fetchGroups(),
        drillStore.fetchSuggestions(),
    ]);

    // Restore cached selection
    const cached = getCachedDrillSelection();
    if (cached) {
        selectionType.value = cached.type;
        if (cached.type === 'scenario') {
            // Verify scenario still exists
            if (scenarios.value.some(s => s.id === cached.id)) {
                selectedScenario.value = cached.id;
            }
        } else {
            // Verify group still exists
            if (groups.value.some(g => g.id === cached.id)) {
                selectedGroup.value = cached.id;
            }
        }
    }

    // Restore cached drill settings
    const cachedSettings = getCachedDrillSettings();
    if (cachedSettings) {
        useTimer.value = cachedSettings.useTimer;
        timerSeconds.value = cachedSettings.timerSeconds;
        handLimit.value = cachedSettings.handLimit;
    }
});
</script>

<style scoped>
.drill-setup-page h1 {
    margin-bottom: var(--spacing-lg);
}

.suggestions-section {
    margin-bottom: var(--spacing-xl);
}

.suggestions-section h3 {
    margin-bottom: var(--spacing-md);
    color: var(--color-text-secondary);
}

.suggestion-cards {
    display: flex;
    gap: var(--spacing-md);
    overflow-x: auto;
    padding-bottom: var(--spacing-sm);
}

.suggestion-card {
    flex: 0 0 220px;
    position: relative;
    padding: var(--spacing-md);
    transition: transform 0.2s;
    display: flex;
    flex-direction: column;
}

.suggestion-card:hover {
    transform: translateY(-2px);
}

.suggestion-card.declining {
    border-left: 3px solid var(--color-error, #ef4444);
}

.suggestion-card.low {
    border-left: 3px solid var(--color-warning, #eab308);
}

.suggestion-card.new {
    border-left: 3px solid var(--color-success, #22c55e);
}

.card-badge {
    display: inline-block;
    padding: 2px 8px;
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-radius: var(--radius-sm);
    margin-bottom: var(--spacing-sm);
}

.suggestion-card.declining .card-badge {
    background: rgba(239, 68, 68, 0.15);
    color: var(--color-error, #ef4444);
}

.suggestion-card.low .card-badge {
    background: rgba(234, 179, 8, 0.15);
    color: var(--color-warning, #eab308);
}

.suggestion-card.new .card-badge {
    background: rgba(34, 197, 94, 0.15);
    color: var(--color-success, #22c55e);
}

.scenario-name {
    font-weight: 600;
    margin-bottom: var(--spacing-xs);
}

.scenario-meta {
    font-size: 0.75rem;
    color: var(--color-text-secondary);
    margin-bottom: var(--spacing-xs);
}

.reason {
    font-size: 0.75rem;
    color: var(--color-text-secondary);
    margin-bottom: var(--spacing-xs);
}

.accuracy {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: var(--spacing-sm);
}

.groups-section {
    margin-top: var(--spacing-sm);
}

.groups-header {
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--color-text-secondary);
    margin-bottom: var(--spacing-xs);
}

.groups-list {
    display: flex;
    flex-wrap: wrap;
    gap: var(--spacing-xs);
}

.groups-list .btn-sm {
    padding: 4px 8px;
    font-size: 0.7rem;
}

.card-footer {
    display: flex;
    justify-content: flex-end;
    margin-top: auto;
    padding-top: var(--spacing-sm);
}

.card-footer .btn-sm {
    padding: 4px 8px;
    font-size: 0.7rem;
}

.setup-form {
    max-width: 500px;
}

.form-group {
    margin-bottom: var(--spacing-lg);
}

.selection-tabs {
    display: flex;
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-md);
}

.tab {
    flex: 1;
    padding: var(--spacing-sm) var(--spacing-md);
    background: var(--color-bg-tertiary);
    border: none;
    border-radius: var(--radius-md);
    color: var(--color-text-secondary);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.tab.active {
    background: var(--color-primary);
    color: white;
}

.timer-options {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    cursor: pointer;
}

.checkbox-label input {
    width: 18px;
    height: 18px;
}

.timer-input {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.timer-input input {
    width: 80px;
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

.global-info {
    background: var(--color-bg-tertiary);
    border-radius: var(--radius-md);
    padding: var(--spacing-md);
}

.global-description {
    margin-bottom: var(--spacing-sm);
    color: var(--color-text-secondary);
    font-size: 0.875rem;
}

.global-stats {
    display: flex;
    gap: var(--spacing-lg);
}

.stat-item {
    font-size: 0.875rem;
}

.stat-item strong {
    color: var(--color-primary);
}

.warning-text {
    margin-top: var(--spacing-sm);
    color: var(--color-warning, #eab308);
    font-size: 0.875rem;
}

.alternative-drills {
    max-width: 500px;
    margin-top: var(--spacing-xl);
}

.alternative-drills h3 {
    margin-bottom: var(--spacing-md);
    color: var(--color-text-secondary);
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.drill-mode-link {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-md);
    background: var(--color-bg-tertiary);
    border-radius: var(--radius-md);
    text-decoration: none;
    color: inherit;
    transition: background var(--transition-fast);
}

.drill-mode-link:hover {
    background: var(--color-bg-hover);
}

.drill-mode-info {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-xs);
}

.drill-mode-info strong {
    color: var(--color-text-primary);
}

.drill-mode-info span {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
}

.drill-mode-link .arrow {
    font-size: 1.25rem;
    color: var(--color-text-secondary);
}
</style>
