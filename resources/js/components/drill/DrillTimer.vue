<template>
    <div class="drill-timer" :class="{ warning: timeLeft <= 3 }">
        <div class="timer-circle">
            <svg viewBox="0 0 100 100">
                <circle
                    class="timer-bg"
                    cx="50"
                    cy="50"
                    r="45"
                    fill="none"
                    stroke-width="8"
                />
                <circle
                    class="timer-progress"
                    cx="50"
                    cy="50"
                    r="45"
                    fill="none"
                    stroke-width="8"
                    :stroke-dasharray="circumference"
                    :stroke-dashoffset="dashOffset"
                />
            </svg>
            <span class="timer-text">{{ timeLeft }}</span>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    seconds: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(['timeout']);

const timeLeft = ref(props.seconds);
const circumference = 2 * Math.PI * 45;
let interval = null;

const dashOffset = computed(() => {
    const progress = timeLeft.value / props.seconds;
    return circumference * (1 - progress);
});

const startTimer = () => {
    timeLeft.value = props.seconds;
    interval = setInterval(() => {
        timeLeft.value--;
        if (timeLeft.value <= 0) {
            clearInterval(interval);
            emit('timeout');
        }
    }, 1000);
};

const stopTimer = () => {
    if (interval) {
        clearInterval(interval);
        interval = null;
    }
};

watch(() => props.seconds, () => {
    stopTimer();
    startTimer();
});

onMounted(() => {
    startTimer();
});

onUnmounted(() => {
    stopTimer();
});
</script>

<style scoped>
.drill-timer {
    display: flex;
    justify-content: center;
}

.timer-circle {
    position: relative;
    width: 80px;
    height: 80px;
}

.timer-circle svg {
    transform: rotate(-90deg);
    width: 100%;
    height: 100%;
}

.timer-bg {
    stroke: var(--color-bg-tertiary);
}

.timer-progress {
    stroke: var(--color-primary);
    transition: stroke-dashoffset 1s linear;
}

.drill-timer.warning .timer-progress {
    stroke: var(--color-error);
}

.timer-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 1.5rem;
    font-weight: 700;
}

.drill-timer.warning .timer-text {
    color: var(--color-error);
    animation: pulse 0.5s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: translate(-50%, -50%) scale(1); }
    50% { transform: translate(-50%, -50%) scale(1.1); }
}
</style>
