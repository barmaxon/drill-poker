<template>
    <div class="drill-feedback" :class="{ correct: isCorrect, incorrect: !isCorrect }">
        <div class="feedback-content">
            <div class="feedback-icon">
                {{ isCorrect ? '✓' : '✗' }}
            </div>
            <div class="feedback-text">
                <h3>{{ isCorrect ? t('drillFeedback.correct') : t('drillFeedback.incorrect') }}</h3>
                <p v-if="!isCorrect" class="feedback-detail">
                    {{ t('drillFeedback.youChose') }} <span class="action user">{{ userAction }}</span>,
                    {{ t('drillFeedback.correctWas') }} <span class="action correct-action">{{ correctAction }}</span>
                </p>
                <span v-if="mistakeType === 'border'" class="border-badge">
                    {{ t('drillFeedback.borderHand') }}
                </span>
            </div>
            <button @click="$emit('continue')" class="btn btn-primary btn-lg continue-btn">
                {{ t('drillFeedback.continue') }} <span class="shortcut">({{ t('drillFeedback.shortcut') }})</span>
            </button>
        </div>
    </div>
</template>

<script setup>
import { useI18n } from '../../composables/useI18n';

const { t } = useI18n();

defineProps({
    isCorrect: {
        type: Boolean,
        required: true,
    },
    userAction: {
        type: String,
        default: '',
    },
    correctAction: {
        type: String,
        default: '',
    },
    mistakeType: {
        type: String,
        default: null,
    },
});

defineEmits(['continue']);
</script>

<style scoped>
.drill-feedback {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: var(--spacing-xl);
    animation: slideUp var(--transition-normal);
}

.drill-feedback.correct {
    background: linear-gradient(to top, rgba(34, 197, 94, 0.95), rgba(34, 197, 94, 0.8));
}

.drill-feedback.incorrect {
    background: linear-gradient(to top, rgba(239, 68, 68, 0.95), rgba(239, 68, 68, 0.8));
}

.feedback-content {
    max-width: 600px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    gap: var(--spacing-lg);
}

.feedback-icon {
    font-size: 3rem;
    font-weight: bold;
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
}

.feedback-text {
    flex: 1;
}

.feedback-text h3 {
    font-size: 1.5rem;
    margin-bottom: var(--spacing-xs);
}

.feedback-detail {
    margin-bottom: var(--spacing-sm);
}

.action {
    font-weight: 600;
    text-transform: capitalize;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--radius-sm);
    background: rgba(255, 255, 255, 0.2);
}

.border-badge {
    display: inline-block;
    padding: var(--spacing-xs) var(--spacing-sm);
    background: var(--color-warning);
    color: var(--color-bg-primary);
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    font-weight: 600;
}

.continue-btn {
    white-space: nowrap;
}

.shortcut {
    opacity: 0.7;
    font-size: 0.75rem;
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
</style>
