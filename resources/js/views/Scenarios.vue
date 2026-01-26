<template>
    <div class="scenarios-page container">
        <div class="page-header">
            <h1>Scenarios</h1>
            <router-link to="/scenarios/create" class="btn btn-primary">Create Scenario</router-link>
        </div>

        <div class="search-bar">
            <input
                v-model="searchQuery"
                type="text"
                class="input"
                placeholder="Search scenarios by name, description, or position..."
            />
        </div>

        <div class="scenarios-list">
            <div
                v-for="scenario in filteredScenarios"
                :key="scenario.id"
                class="scenario-card card"
            >
                <div class="scenario-info">
                    <h3>{{ scenario.name }}</h3>
                    <p v-if="scenario.description" class="scenario-desc">{{ scenario.description }}</p>
                    <div class="scenario-details">
                        <span
                            v-for="pos in getPositions(scenario)"
                            :key="pos"
                            class="badge position-badge"
                        >
                            {{ pos }}
                        </span>
                        <span class="badge">{{ scenario.stack_depth || scenario.stackDepth }}bb</span>
                        <span class="badge hands-count">{{ countHands(scenario.grid) }} hands</span>
                    </div>
                    <div v-if="scenario.slug" class="scenario-slug">
                        <code>{{ scenario.slug }}</code>
                    </div>
                </div>
                <div class="scenario-actions">
                    <router-link :to="`/scenarios/${scenario.id}/edit`" class="btn btn-secondary">Edit</router-link>
                    <button @click="openCopyModal(scenario)" class="btn btn-secondary">Copy</button>
                    <button @click="deleteScenario(scenario.id)" class="btn btn-secondary">Delete</button>
                </div>
            </div>

            <div v-if="scenarioStore.scenarios.length === 0" class="empty-state card">
                <p>No scenarios yet. Create your first scenario to start drilling.</p>
                <router-link to="/scenarios/create" class="btn btn-primary">Create Scenario</router-link>
            </div>

            <div v-else-if="filteredScenarios.length === 0" class="empty-state card">
                <p>No scenarios match your search.</p>
            </div>
        </div>

        <CopyScenarioModal
            v-if="copyModalScenario"
            :scenario="copyModalScenario"
            @close="copyModalScenario = null"
            @saved="onCopySaved"
        />
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useScenarioStore } from '../stores/scenarios';
import { ACTIONS } from '../utils/hands';
import CopyScenarioModal from '../components/CopyScenarioModal.vue';

const scenarioStore = useScenarioStore();

// Copy modal state
const copyModalScenario = ref(null);

const openCopyModal = (scenario) => {
    copyModalScenario.value = scenario;
};

const onCopySaved = () => {
    // Refresh the scenarios list
    scenarioStore.fetchScenarios();
};

const searchQuery = ref('');
const debouncedQuery = ref('');
let debounceTimer = null;

// Debounce search query (300ms delay)
watch(searchQuery, (newVal) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        debouncedQuery.value = newVal;
    }, 300);
});

const filteredScenarios = computed(() => {
    const query = debouncedQuery.value.toLowerCase().trim();
    if (!query) {
        return scenarioStore.scenarios;
    }

    return scenarioStore.scenarios.filter(s => {
        const name = (s.name || '').toLowerCase();
        const description = (s.description || '').toLowerCase();
        const positions = getPositions(s).join(' ').toLowerCase();

        return name.includes(query) ||
               description.includes(query) ||
               positions.includes(query);
    });
});

const getPositions = (scenario) => {
    // Support both old single position and new positions array
    if (scenario.positions && Array.isArray(scenario.positions)) {
        return scenario.positions;
    }
    return scenario.position ? [scenario.position] : [];
};

const countHands = (grid) => {
    if (!grid) return 0;
    return Object.values(grid).filter(action => action !== ACTIONS.FOLD).length;
};

const deleteScenario = async (id) => {
    if (confirm('Are you sure you want to delete this scenario?')) {
        await scenarioStore.deleteScenario(id);
    }
};

onMounted(() => {
    scenarioStore.fetchScenarios();
});
</script>

<style scoped>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-lg);
}

.search-bar {
    margin-bottom: var(--spacing-lg);
}

.search-bar .input {
    width: 100%;
    max-width: 400px;
}

.scenarios-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
}

.scenario-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.scenario-info h3 {
    margin-bottom: var(--spacing-xs);
}

.scenario-desc {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
    margin-bottom: var(--spacing-sm);
}

.scenario-details {
    display: flex;
    flex-wrap: wrap;
    gap: var(--spacing-sm);
}

.badge {
    display: inline-block;
    padding: var(--spacing-xs) var(--spacing-sm);
    background-color: var(--color-bg-tertiary);
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    color: var(--color-text-secondary);
}

.badge.position-badge {
    background-color: var(--color-primary);
    color: white;
}

.badge.hands-count {
    background-color: var(--color-success, #22c55e);
    color: white;
}

.scenario-slug {
    margin-top: var(--spacing-sm);
}

.scenario-slug code {
    font-size: 0.75rem;
    color: var(--color-text-secondary);
    background-color: var(--color-bg-tertiary);
    padding: 2px 6px;
    border-radius: var(--radius-sm);
}

.scenario-actions {
    display: flex;
    gap: var(--spacing-sm);
}

.empty-state {
    text-align: center;
    padding: var(--spacing-xl);
    color: var(--color-text-secondary);
}

.empty-state .btn {
    margin-top: var(--spacing-md);
}
</style>
