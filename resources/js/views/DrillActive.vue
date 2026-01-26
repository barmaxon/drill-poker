<template>
    <div class="drill-active-page">
        <div class="drill-header">
            <div class="drill-progress">
                <span>{{ t('drill.hand') }} {{ drillStore.currentHandNumber }}</span>
                <span v-if="drillStore.handLimit"> / {{ drillStore.handLimit }}</span>
            </div>
            <div class="drill-stats">
                <span class="stat correct">{{ drillStore.correctCount }} {{ t('drill.correct') }}</span>
                <span class="stat incorrect">{{ drillStore.incorrectCount }} {{ t('drill.wrong') }}</span>
            </div>
            <button @click="endDrill" class="btn btn-secondary">{{ t('drill.end') }}</button>
        </div>

        <div class="drill-main">
            <div class="scenario-header">
                <h2 class="scenario-name">{{ currentScenario?.name }}</h2>
                <p v-if="currentScenario?.description" class="scenario-description">{{ currentScenario?.description }}</p>
            </div>
            <div class="scenario-info">
                <span class="position-badge">{{ currentScenario?.position }}</span>
                <span class="stack-badge">{{ currentScenario?.stackDepth }}bb</span>
            </div>

            <HandDisplay
                v-if="currentHand"
                :card1="currentHand.card1"
                :card2="currentHand.card2"
            />

            <div class="hand-notation">{{ currentHand?.notation }}</div>

            <DrillTimer
                v-if="drillStore.useTimer && !showFeedback"
                :seconds="drillStore.timerSeconds"
                :key="drillStore.currentHandNumber"
                @timeout="handleTimeout"
            />

            <div class="action-buttons">
                <button
                    @click="submitAnswer('fold')"
                    class="btn btn-fold btn-lg action-btn"
                    :disabled="showFeedback"
                >
                    {{ t('actions.fold') }} <span class="shortcut">({{ t('drill.shortcutFold') }})</span>
                </button>
                <button
                    @click="submitAnswer('call')"
                    class="btn btn-call btn-lg action-btn"
                    :disabled="showFeedback"
                >
                    {{ t('actions.call') }} <span class="shortcut">({{ t('drill.shortcutCall') }})</span>
                </button>
                <button
                    @click="submitAnswer('raise')"
                    class="btn btn-raise btn-lg action-btn"
                    :disabled="showFeedback"
                >
                    {{ t('actions.raise') }} <span class="shortcut">({{ t('drill.shortcutRaise') }})</span>
                </button>
            </div>
        </div>

        <DrillFeedback
            v-if="showFeedback"
            :is-correct="lastResult?.correct"
            :user-action="lastResult?.userAction"
            :correct-action="lastResult?.correctAction"
            :mistake-type="lastResult?.mistakeType"
            @continue="nextHand"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useDrillStore } from '../stores/drill';
import HandDisplay from '../components/drill/HandDisplay.vue';
import DrillTimer from '../components/drill/DrillTimer.vue';
import DrillFeedback from '../components/drill/DrillFeedback.vue';
import { useI18n } from '../composables/useI18n';

const { t } = useI18n();
const router = useRouter();
const drillStore = useDrillStore();

const showFeedback = ref(false);
const lastResult = ref(null);

const currentHand = computed(() => drillStore.currentHand);
const currentScenario = computed(() => drillStore.currentScenario);

const submitAnswer = async (action) => {
    if (showFeedback.value) return;

    const result = await drillStore.submitAnswer(action);
    lastResult.value = result;
    showFeedback.value = true;
};

const handleTimeout = () => {
    submitAnswer('fold'); // Auto-fold on timeout
};

const nextHand = async () => {
    showFeedback.value = false;
    lastResult.value = null;

    const hasMore = await drillStore.nextHand();
    if (!hasMore) {
        await drillStore.endSession();
        router.push('/drill/summary');
    }
};

const endDrill = async () => {
    await drillStore.endSession();
    router.push('/drill/summary');
};

// Keyboard shortcuts
const handleKeydown = (e) => {
    if (showFeedback.value) {
        if (e.key === ' ' || e.key === 'Enter') {
            nextHand();
        }
        return;
    }

    switch (e.key.toLowerCase()) {
        case 'f':
            submitAnswer('fold');
            break;
        case 'c':
            submitAnswer('call');
            break;
        case 'r':
            submitAnswer('raise');
            break;
    }
};

onMounted(() => {
    if (!drillStore.isActive) {
        router.push('/drill');
        return;
    }
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});
</script>

<style scoped>
.drill-active-page {
    min-height: calc(100vh - 100px);
    display: flex;
    flex-direction: column;
}

.drill-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-md) var(--spacing-lg);
    background: var(--color-bg-secondary);
    border-bottom: 1px solid var(--color-border);
}

.drill-progress {
    font-size: 1.125rem;
    font-weight: 600;
}

.drill-stats {
    display: flex;
    gap: var(--spacing-lg);
}

.stat {
    font-weight: 500;
}

.stat.correct {
    color: var(--color-success);
}

.stat.incorrect {
    color: var(--color-error);
}

.drill-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-lg);
    padding: var(--spacing-xl);
}

.scenario-header {
    text-align: center;
}

.scenario-name {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
}

.scenario-description {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
    margin: var(--spacing-xs) 0 0 0;
}

.scenario-info {
    display: flex;
    gap: var(--spacing-sm);
}

.position-badge,
.stack-badge {
    padding: var(--spacing-xs) var(--spacing-md);
    background: var(--color-bg-tertiary);
    border-radius: var(--radius-full);
    font-size: 0.875rem;
    font-weight: 500;
}

.hand-notation {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--color-text-secondary);
}

.action-buttons {
    display: flex;
    gap: var(--spacing-md);
    margin-top: var(--spacing-lg);
}

.action-btn {
    min-width: 120px;
}

.shortcut {
    opacity: 0.7;
    font-size: 0.75rem;
}
</style>
