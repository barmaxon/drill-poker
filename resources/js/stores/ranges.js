import { defineStore } from 'pinia';
import { ref } from 'vue';
import { mockRanges } from '../mocks/mockRanges';

export const useRangeStore = defineStore('ranges', () => {
    const ranges = ref([]);
    const loading = ref(false);

    async function fetchRanges() {
        loading.value = true;
        try {
            // TODO: Replace with real API call in Phase 5
            ranges.value = mockRanges;
        } finally {
            loading.value = false;
        }
    }

    async function getRange(id) {
        // TODO: Replace with real API call in Phase 5
        return mockRanges.find(r => r.id === parseInt(id)) || null;
    }

    async function createRange(data) {
        // TODO: Replace with real API call in Phase 5
        const newRange = {
            id: Date.now(),
            ...data,
            createdAt: new Date().toISOString(),
        };
        ranges.value.push(newRange);
        return newRange;
    }

    async function updateRange(id, data) {
        // TODO: Replace with real API call in Phase 5
        const index = ranges.value.findIndex(r => r.id === parseInt(id));
        if (index !== -1) {
            ranges.value[index] = { ...ranges.value[index], ...data };
            return ranges.value[index];
        }
        return null;
    }

    async function deleteRange(id) {
        // TODO: Replace with real API call in Phase 5
        const index = ranges.value.findIndex(r => r.id === parseInt(id));
        if (index !== -1) {
            ranges.value.splice(index, 1);
        }
    }

    return {
        ranges,
        loading,
        fetchRanges,
        getRange,
        createRange,
        updateRange,
        deleteRange,
    };
});
