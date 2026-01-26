<template>
    <div class="ranges-page container">
        <div class="page-header">
            <h1>Ranges</h1>
            <router-link to="/ranges/create" class="btn btn-primary">Create Range</router-link>
        </div>

        <div class="ranges-grid">
            <div
                v-for="range in rangeStore.ranges"
                :key="range.id"
                class="range-card card"
            >
                <h3>{{ range.name }}</h3>
                <p class="text-secondary">{{ range.description || 'No description' }}</p>
                <div class="range-actions">
                    <router-link :to="`/ranges/${range.id}/edit`" class="btn btn-secondary">
                        Edit
                    </router-link>
                </div>
            </div>

            <div v-if="rangeStore.ranges.length === 0" class="empty-state">
                <p>No ranges yet. Create your first range to get started.</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRangeStore } from '../stores/ranges';

const rangeStore = useRangeStore();

onMounted(() => {
    rangeStore.fetchRanges();
});
</script>

<style scoped>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-lg);
}

.ranges-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: var(--spacing-lg);
}

.range-card h3 {
    margin-bottom: var(--spacing-sm);
}

.range-card p {
    margin-bottom: var(--spacing-md);
}

.range-actions {
    display: flex;
    gap: var(--spacing-sm);
}

.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: var(--spacing-xl);
    color: var(--color-text-secondary);
}
</style>
