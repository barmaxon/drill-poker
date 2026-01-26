import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '../services/api';
import { mockScenarios } from '../mocks/mockScenarios';

const USE_MOCK = import.meta.env.VITE_USE_MOCK === 'true';

export const useScenarioStore = defineStore('scenarios', () => {
    const scenarios = ref([]);
    const loading = ref(false);
    const nameSuggestions = ref([]);

    async function fetchScenarios() {
        loading.value = true;
        try {
            if (USE_MOCK) {
                scenarios.value = mockScenarios;
                return;
            }
            const response = await api.get('/scenarios');
            scenarios.value = response.data;
        } finally {
            loading.value = false;
        }
    }

    async function getScenario(id) {
        if (USE_MOCK) {
            return mockScenarios.find(s => s.id === parseInt(id)) || null;
        }
        try {
            const response = await api.get(`/scenarios/${id}`);
            return response.data;
        } catch {
            return null;
        }
    }

    async function createScenario(data) {
        if (USE_MOCK) {
            const newScenario = { id: Date.now(), ...data };
            scenarios.value.push(newScenario);
            return newScenario;
        }
        const response = await api.post('/scenarios', data);
        scenarios.value.push(response.data);
        return response.data;
    }

    async function updateScenario(id, data) {
        if (USE_MOCK) {
            const index = scenarios.value.findIndex(s => s.id === parseInt(id));
            if (index !== -1) {
                scenarios.value[index] = { ...scenarios.value[index], ...data };
                return scenarios.value[index];
            }
            return null;
        }
        const response = await api.put(`/scenarios/${id}`, data);
        const index = scenarios.value.findIndex(s => s.id === parseInt(id));
        if (index !== -1) {
            scenarios.value[index] = response.data;
        }
        return response.data;
    }

    async function deleteScenario(id) {
        if (USE_MOCK) {
            const index = scenarios.value.findIndex(s => s.id === parseInt(id));
            if (index !== -1) {
                scenarios.value.splice(index, 1);
            }
            return;
        }
        await api.delete(`/scenarios/${id}`);
        const index = scenarios.value.findIndex(s => s.id === parseInt(id));
        if (index !== -1) {
            scenarios.value.splice(index, 1);
        }
    }

    async function fetchNameSuggestions() {
        if (USE_MOCK) {
            // Generate mock suggestions from existing scenarios
            const nameMap = new Map();
            for (const s of mockScenarios) {
                const lower = s.name.toLowerCase();
                if (!nameMap.has(lower)) {
                    nameMap.set(lower, {
                        name: s.name,
                        usedPositions: [],
                        availablePositions: ['UTG', 'UTG+1', 'UTG+2', 'LJ', 'HJ', 'CO', 'BTN', 'SB', 'BB'],
                        stackDepths: [],
                        scenarioCount: 0,
                    });
                }
                const entry = nameMap.get(lower);
                entry.usedPositions.push(s.position);
                entry.stackDepths.push(s.stackDepth);
                entry.scenarioCount++;
                entry.availablePositions = entry.availablePositions.filter(p => p !== s.position);
            }
            nameSuggestions.value = Array.from(nameMap.values());
            return nameSuggestions.value;
        }
        try {
            const response = await api.get('/scenarios/name-suggestions');
            nameSuggestions.value = response.data;
            return response.data;
        } catch {
            return [];
        }
    }

    return {
        scenarios,
        loading,
        nameSuggestions,
        fetchScenarios,
        getScenario,
        createScenario,
        updateScenario,
        deleteScenario,
        fetchNameSuggestions,
    };
});
