import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '../services/api';
import {
    mockStats,
    mockHeatmapData,
    mockProblemHands,
    mockScenarioStats,
    mockGroupStats,
    generateScenarioDetail,
} from '../mocks/mockStats';

const USE_MOCK = import.meta.env.VITE_USE_MOCK === 'true';

export const useStatsStore = defineStore('stats', () => {
    const overview = ref(null);
    const heatmapData = ref({});
    const problemHands = ref([]);
    const scenarioStats = ref([]);
    const groupStats = ref([]);
    const loading = ref(false);

    async function fetchOverview() {
        loading.value = true;
        try {
            if (USE_MOCK) {
                overview.value = mockStats;
                return mockStats;
            }
            const response = await api.get('/stats/overview');
            overview.value = response.data;
            return response.data;
        } finally {
            loading.value = false;
        }
    }

    async function fetchHeatmapData() {
        if (USE_MOCK) {
            heatmapData.value = mockHeatmapData;
            return mockHeatmapData;
        }
        // Heatmap is aggregated from all scenario stats
        // For now, return mock data until we implement aggregation
        heatmapData.value = mockHeatmapData;
        return mockHeatmapData;
    }

    async function fetchProblemHands() {
        if (USE_MOCK) {
            problemHands.value = mockProblemHands;
            return mockProblemHands;
        }
        const response = await api.get('/stats/problem-hands');
        problemHands.value = response.data;
        return response.data;
    }

    async function fetchScenarioHands(scenarioId) {
        if (USE_MOCK) {
            return {};
        }
        const response = await api.get(`/stats/scenarios/${scenarioId}`);
        return response.data.heatmap || {};
    }

    async function fetchScenarioStats() {
        if (USE_MOCK) {
            scenarioStats.value = mockScenarioStats;
            return mockScenarioStats;
        }
        const response = await api.get('/stats/scenarios');
        scenarioStats.value = response.data;
        return response.data;
    }

    async function fetchGroupStats() {
        if (USE_MOCK) {
            groupStats.value = mockGroupStats;
            return mockGroupStats;
        }
        const response = await api.get('/stats/groups');
        groupStats.value = response.data;
        return response.data;
    }

    async function fetchScenarioDetail(scenarioId) {
        if (USE_MOCK) {
            return generateScenarioDetail(scenarioId);
        }
        const response = await api.get(`/stats/scenarios/${scenarioId}`);
        return response.data;
    }

    return {
        overview,
        heatmapData,
        problemHands,
        scenarioStats,
        groupStats,
        loading,
        fetchOverview,
        fetchHeatmapData,
        fetchProblemHands,
        fetchScenarioHands,
        fetchScenarioStats,
        fetchGroupStats,
        fetchScenarioDetail,
    };
});
