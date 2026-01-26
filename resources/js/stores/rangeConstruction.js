import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '../services/api';

const USE_MOCK = import.meta.env.VITE_USE_MOCK === 'true';

export const useRangeConstructionStore = defineStore('rangeConstruction', () => {
    const scenario = ref(null);
    const isActive = ref(false);
    const startTime = ref(null);
    const result = ref(null);
    const stats = ref(null);
    const loading = ref(false);
    const error = ref(null);

    async function startSession(scenarioId) {
        loading.value = true;
        error.value = null;
        result.value = null;

        try {
            if (USE_MOCK) {
                // Mock response
                scenario.value = {
                    id: scenarioId,
                    name: 'Mock Scenario',
                    positions: ['UTG', 'CO'],
                    stackDepth: 100,
                    limpers: 0,
                };
            } else {
                const response = await api.post('/range-drill/start', { scenarioId });
                scenario.value = response.data.scenario;
            }

            isActive.value = true;
            startTime.value = Date.now();
            return { success: true };
        } catch (err) {
            console.error('Range drill start error:', err);
            error.value = err.response?.data?.message || err.message || 'Failed to start drill';
            return { success: false, error: error.value };
        } finally {
            loading.value = false;
        }
    }

    async function submitRange(userGrid) {
        loading.value = true;
        error.value = null;

        const timeSeconds = startTime.value
            ? Math.round((Date.now() - startTime.value) / 1000)
            : null;

        try {
            if (USE_MOCK) {
                // Mock response
                result.value = {
                    accuracy: 75,
                    correctCount: 127,
                    incorrectCount: 42,
                    totalCells: 169,
                    differences: [],
                    correctGrid: {},
                    stats: {
                        totalAttempts: 1,
                        bestAccuracy: 75,
                        avgAccuracy: 75,
                    },
                };
            } else {
                const response = await api.post('/range-drill/submit', {
                    scenarioId: scenario.value.id,
                    userGrid,
                    timeSeconds,
                });
                result.value = response.data;
            }

            isActive.value = false;
            return { success: true, result: result.value };
        } catch (err) {
            console.error('Range drill submit error:', err);
            error.value = err.response?.data?.message || err.message || 'Failed to submit range';
            return { success: false, error: error.value };
        } finally {
            loading.value = false;
        }
    }

    async function fetchStats() {
        loading.value = true;
        error.value = null;

        try {
            if (USE_MOCK) {
                stats.value = {
                    overall: {
                        totalAttempts: 0,
                        avgAccuracy: 0,
                        scenariosAttempted: 0,
                    },
                    scenarios: [],
                };
            } else {
                const response = await api.get('/range-drill/stats');
                stats.value = response.data;
            }
            return stats.value;
        } catch (err) {
            console.error('Range drill stats error:', err);
            error.value = err.response?.data?.message || err.message || 'Failed to fetch stats';
            return null;
        } finally {
            loading.value = false;
        }
    }

    function reset() {
        scenario.value = null;
        isActive.value = false;
        startTime.value = null;
        result.value = null;
        error.value = null;
    }

    return {
        scenario,
        isActive,
        startTime,
        result,
        stats,
        loading,
        error,
        startSession,
        submitRange,
        fetchStats,
        reset,
    };
});
