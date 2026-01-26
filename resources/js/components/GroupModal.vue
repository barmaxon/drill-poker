<template>
    <div class="modal-overlay" @click.self="$emit('close')">
        <div class="modal card">
            <h2>{{ isEditing ? 'Edit Group' : 'Create Group' }}</h2>

            <form @submit.prevent="save">
                <div class="form-group">
                    <label class="label" for="group-name">Group Name</label>
                    <input
                        id="group-name"
                        v-model="form.name"
                        type="text"
                        class="input"
                        placeholder="e.g., Late Position Opens"
                        required
                    />
                </div>

                <div class="form-group">
                    <label class="checkbox-label active-toggle">
                        <input type="checkbox" v-model="form.isActive" />
                        <span>Active (included in Global Drill)</span>
                    </label>
                </div>

                <div class="form-group">
                    <label class="label">Select Scenarios</label>
                    <input
                        v-model="searchQuery"
                        type="text"
                        class="input search-input"
                        placeholder="Search scenarios..."
                    />
                    <div class="scenario-list">
                        <label
                            v-for="scenario in filteredScenarios"
                            :key="scenario.id"
                            class="scenario-checkbox"
                        >
                            <input
                                type="checkbox"
                                :value="scenario.id"
                                v-model="form.scenarioIds"
                            />
                            <span class="scenario-info">
                                <span class="scenario-name">{{ scenario.name }}</span>
                                <span class="scenario-slug">{{ scenario.slug }}</span>
                                <span class="scenario-details">
                                    {{ getPositions(scenario) }} · {{ scenario.stack_depth || scenario.stackDepth }}bb
                                </span>
                                <span v-if="scenario.description" class="scenario-desc">
                                    {{ truncate(scenario.description, 60) }}
                                </span>
                            </span>
                        </label>
                        <p v-if="scenarioStore.scenarios.length === 0" class="empty-message">
                            No scenarios available. Create scenarios first.
                        </p>
                        <p v-else-if="filteredScenarios.length === 0" class="empty-message">
                            No scenarios match your search.
                        </p>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" @click="$emit('close')" class="btn btn-secondary">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" :disabled="!form.name">
                        {{ isEditing ? 'Save Changes' : 'Create Group' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { reactive, computed, ref, watch, onMounted } from 'vue';
import { useScenarioStore } from '../stores/scenarios';
import { useGroupStore } from '../stores/groups';

const props = defineProps({
    group: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'saved']);

const scenarioStore = useScenarioStore();
const groupStore = useGroupStore();

const isEditing = computed(() => !!props.group);

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
    const terms = query.split(/\s+/).filter(Boolean);
    return scenarioStore.scenarios.filter(s => {
        const searchable = [
            s.name || '',
            s.slug || '',
            Array.isArray(s.positions) ? s.positions.join(' ') : (s.position || ''),
        ].join(' ').toLowerCase();
        return terms.every(term => searchable.includes(term));
    });
});

const form = reactive({
    name: props.group?.name || '',
    isActive: props.group?.is_active ?? true,
    scenarioIds: props.group?.scenarios?.map(s => s.id) || [],
});

const getPositions = (scenario) => {
    if (scenario.positions && Array.isArray(scenario.positions)) {
        return scenario.positions.join(', ');
    }
    return scenario.position || '';
};

const truncate = (text, limit) => {
    if (!text || text.length <= limit) return text;
    return text.slice(0, limit) + '...';
};

const save = async () => {
    let groupId;
    if (isEditing.value) {
        await groupStore.updateGroup(props.group.id, { name: form.name, isActive: form.isActive });
        groupId = props.group.id;
    } else {
        const newGroup = await groupStore.createGroup({ name: form.name, isActive: form.isActive });
        groupId = newGroup.id;
    }
    // Sync scenarios to backend
    await groupStore.syncScenarios(groupId, form.scenarioIds);
    emit('saved');
    emit('close');
};

onMounted(() => {
    scenarioStore.fetchScenarios();
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
    max-height: 80vh;
    overflow-y: auto;
}

.modal h2 {
    margin-bottom: var(--spacing-lg);
}

.form-group {
    margin-bottom: var(--spacing-lg);
}

.checkbox-label.active-toggle {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    cursor: pointer;
}

.checkbox-label.active-toggle input {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.search-input {
    margin-bottom: var(--spacing-sm);
}

.empty-message {
    text-align: center;
    color: var(--color-text-secondary);
    font-size: 0.875rem;
    font-style: italic;
    padding: var(--spacing-md);
    margin: 0;
}

.scenario-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
    max-height: 300px;
    overflow-y: auto;
    padding: var(--spacing-sm);
    background: var(--color-bg-tertiary);
    border-radius: var(--radius-md);
}

.scenario-checkbox {
    display: flex;
    align-items: flex-start;
    gap: var(--spacing-sm);
    padding: var(--spacing-sm);
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: background var(--transition-fast);
}

.scenario-checkbox:hover {
    background: var(--color-bg-hover);
}

.scenario-checkbox input {
    margin-top: 4px;
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.scenario-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.scenario-name {
    font-weight: 500;
}

.scenario-slug {
    font-size: 0.7rem;
    font-family: monospace;
    color: var(--color-text-secondary);
    opacity: 0.7;
}

.scenario-details {
    font-size: 0.75rem;
    color: var(--color-text-secondary);
}

.scenario-desc {
    font-size: 0.7rem;
    color: var(--color-text-secondary);
    opacity: 0.7;
    font-style: italic;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--spacing-sm);
    margin-top: var(--spacing-lg);
}
</style>
