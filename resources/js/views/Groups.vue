<template>
    <div class="groups-page container">
        <div class="page-header">
            <h1>{{ t('groups.title') }}</h1>
            <button @click="openCreateModal" class="btn btn-primary">{{ t('groups.create') }}</button>
        </div>

        <div class="groups-list">
            <div
                v-for="group in groupStore.groups"
                :key="group.id"
                class="group-card card"
            >
                <div class="group-info">
                    <h3>{{ group.name }}</h3>
                    <p class="text-secondary">{{ group.scenarios?.length || 0 }} {{ t('groups.scenarios') }}</p>
                </div>
                <div class="group-actions">
                    <button @click="openEditModal(group)" class="btn btn-secondary">{{ t('common.edit') }}</button>
                    <button @click="deleteGroup(group.id)" class="btn btn-secondary">{{ t('common.delete') }}</button>
                </div>
            </div>

            <div v-if="groupStore.groups.length === 0" class="empty-state card">
                <p>{{ t('groups.noGroups') }}</p>
            </div>
        </div>

        <GroupModal
            v-if="showModal"
            :group="editingGroup"
            @close="closeModal"
            @saved="onSaved"
        />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useGroupStore } from '../stores/groups';
import GroupModal from '../components/GroupModal.vue';
import { useI18n } from '../composables/useI18n';

const { t } = useI18n();
const groupStore = useGroupStore();

const showModal = ref(false);
const editingGroup = ref(null);

const openCreateModal = () => {
    editingGroup.value = null;
    showModal.value = true;
};

const openEditModal = (group) => {
    editingGroup.value = group;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingGroup.value = null;
};

const onSaved = () => {
    // Refresh handled by store reactivity
};

const deleteGroup = async (id) => {
    if (confirm(t('groups.deleteConfirm'))) {
        await groupStore.deleteGroup(id);
    }
};

onMounted(() => {
    groupStore.fetchGroups();
});
</script>

<style scoped>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-lg);
}

.groups-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
}

.group-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.group-info h3 {
    margin-bottom: var(--spacing-xs);
}

.group-actions {
    display: flex;
    gap: var(--spacing-sm);
}

.empty-state {
    text-align: center;
    padding: var(--spacing-xl);
    color: var(--color-text-secondary);
}
</style>
