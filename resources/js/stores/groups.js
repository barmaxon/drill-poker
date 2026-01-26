import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '../services/api';
import { mockGroups } from '../mocks/mockScenarios';

const USE_MOCK = import.meta.env.VITE_USE_MOCK === 'true';

export const useGroupStore = defineStore('groups', () => {
    const groups = ref([]);
    const loading = ref(false);

    async function fetchGroups() {
        loading.value = true;
        try {
            if (USE_MOCK) {
                groups.value = mockGroups;
                return;
            }
            const response = await api.get('/groups');
            groups.value = response.data;
        } finally {
            loading.value = false;
        }
    }

    async function getGroup(id) {
        if (USE_MOCK) {
            return mockGroups.find(g => g.id === parseInt(id)) || null;
        }
        try {
            const response = await api.get(`/groups/${id}`);
            return response.data;
        } catch {
            return null;
        }
    }

    async function createGroup(data) {
        if (USE_MOCK) {
            const newGroup = { id: Date.now(), ...data, is_active: data.isActive ?? true, scenarios: [] };
            groups.value.push(newGroup);
            return newGroup;
        }
        const response = await api.post('/groups', data);
        groups.value.push(response.data);
        return response.data;
    }

    async function updateGroup(id, data) {
        if (USE_MOCK) {
            const index = groups.value.findIndex(g => g.id === parseInt(id));
            if (index !== -1) {
                const updateData = { ...data };
                if (data.isActive !== undefined) {
                    updateData.is_active = data.isActive;
                }
                groups.value[index] = { ...groups.value[index], ...updateData };
                return groups.value[index];
            }
            return null;
        }
        const response = await api.put(`/groups/${id}`, data);
        const index = groups.value.findIndex(g => g.id === parseInt(id));
        if (index !== -1) {
            groups.value[index] = response.data;
        }
        return response.data;
    }

    async function deleteGroup(id) {
        if (USE_MOCK) {
            const index = groups.value.findIndex(g => g.id === parseInt(id));
            if (index !== -1) {
                groups.value.splice(index, 1);
            }
            return;
        }
        await api.delete(`/groups/${id}`);
        const index = groups.value.findIndex(g => g.id === parseInt(id));
        if (index !== -1) {
            groups.value.splice(index, 1);
        }
    }

    async function syncScenarios(groupId, scenarioIds) {
        if (USE_MOCK) {
            const group = groups.value.find(g => g.id === parseInt(groupId));
            if (group) {
                group.scenarios = scenarioIds;
            }
            return;
        }
        const response = await api.post(`/groups/${groupId}/scenarios`, {
            scenarioIds,
        });
        const index = groups.value.findIndex(g => g.id === parseInt(groupId));
        if (index !== -1) {
            groups.value[index] = response.data;
        }
    }

    return {
        groups,
        loading,
        fetchGroups,
        getGroup,
        createGroup,
        updateGroup,
        deleteGroup,
        syncScenarios,
    };
});
