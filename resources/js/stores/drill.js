import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '../services/api';
import { mockDrillService } from '../mocks/mockDrillService';

const USE_MOCK = import.meta.env.VITE_USE_MOCK === 'true';

export const useDrillStore = defineStore('drill', () => {
    const sessionId = ref(null);
    const isActive = ref(false);
    const config = ref(null);

    const currentHand = ref(null);
    const currentScenario = ref(null);
    const currentHandNumber = ref(0);
    const handLimit = ref(null);

    const correctCount = ref(0);
    const incorrectCount = ref(0);
    const answers = ref([]);

    const sessionSummary = ref(null);
    const error = ref(null);

    const suggestions = ref([]);
    const suggestionsLoading = ref(false);

    const useTimer = computed(() => config.value?.useTimer || false);
    const timerSeconds = computed(() => config.value?.timerSeconds || 10);

    async function startSession(sessionConfig) {
        error.value = null;
        config.value = sessionConfig;
        handLimit.value = sessionConfig.handLimit;
        currentHandNumber.value = 0;
        correctCount.value = 0;
        incorrectCount.value = 0;
        answers.value = [];
        sessionSummary.value = null;

        try {
            if (USE_MOCK) {
                sessionId.value = Date.now();
                await mockDrillService.startSession(sessionConfig);
            } else {
                const response = await api.post('/drills/start', sessionConfig);
                sessionId.value = response.data.sessionId;
            }

            isActive.value = true;
            await nextHand();
            return { success: true };
        } catch (err) {
            console.error('Drill start error:', err);
            const errors = err.response?.data?.errors;
            if (errors) {
                const firstField = Object.keys(errors)[0];
                error.value = errors[firstField][0];
            } else {
                error.value = err.response?.data?.message || err.message || 'Failed to start drill';
            }
            isActive.value = false;
            return { success: false, error: error.value };
        }
    }

    async function nextHand() {
        if (handLimit.value && currentHandNumber.value >= handLimit.value) {
            return false;
        }

        let result;
        if (USE_MOCK) {
            result = await mockDrillService.getNextHand();
        } else {
            const response = await api.post('/drills/next-hand', {
                sessionId: sessionId.value,
            });
            result = response.data;
        }

        if (!result || result.complete) {
            return false;
        }

        currentHand.value = result.hand;
        currentScenario.value = result.scenario;
        currentHandNumber.value++;
        return true;
    }

    async function submitAnswer(action) {
        let result;
        if (USE_MOCK) {
            result = await mockDrillService.submitAnswer(
                currentHand.value.notation,
                action,
                currentScenario.value
            );
        } else {
            const response = await api.post('/drills/answer', {
                sessionId: sessionId.value,
                hand: currentHand.value.notation,
                scenarioId: currentScenario.value.id,
                action,
            });
            result = response.data;
        }

        if (result.correct) {
            correctCount.value++;
        } else {
            incorrectCount.value++;
        }

        answers.value.push({
            hand: currentHand.value.notation,
            scenarioId: currentScenario.value.id,
            scenario: currentScenario.value.name,
            position: currentScenario.value.position,
            positions: currentScenario.value.positions,
            stackDepth: currentScenario.value.stackDepth,
            userAction: action,
            correctAction: result.correctAction,
            correct: result.correct,
            isBorder: result.mistakeType === 'border',
            mistakeType: result.mistakeType,
        });

        return result;
    }

    async function endSession() {
        isActive.value = false;

        if (USE_MOCK) {
            await mockDrillService.endSession();

            const totalHands = currentHandNumber.value;
            const borderMistakes = answers.value.filter(a => !a.correct && a.isBorder).length;
            const accuracy = totalHands > 0
                ? Math.round((correctCount.value / totalHands) * 100)
                : 0;

            sessionSummary.value = {
                totalHands,
                correctCount: correctCount.value,
                incorrectCount: incorrectCount.value,
                accuracy,
                borderMistakes,
                mistakes: answers.value.filter(a => !a.correct),
            };
        } else {
            const response = await api.post('/drills/end', {
                sessionId: sessionId.value,
            });
            sessionSummary.value = {
                ...response.data,
                mistakes: answers.value.filter(a => !a.correct),
                comparison: response.data.comparison || null,
            };
        }

        return sessionSummary.value;
    }

    function reset() {
        sessionId.value = null;
        isActive.value = false;
        config.value = null;
        currentHand.value = null;
        currentScenario.value = null;
        currentHandNumber.value = 0;
        handLimit.value = null;
        correctCount.value = 0;
        incorrectCount.value = 0;
        answers.value = [];
        sessionSummary.value = null;
    }

    async function fetchSuggestions() {
        suggestionsLoading.value = true;
        try {
            if (USE_MOCK) {
                // Return empty suggestions for mock mode
                suggestions.value = [];
                return;
            }
            const response = await api.get('/drills/suggestions');
            suggestions.value = response.data;
        } catch (err) {
            console.error('Failed to fetch suggestions:', err);
            suggestions.value = [];
        } finally {
            suggestionsLoading.value = false;
        }
    }

    return {
        sessionId,
        isActive,
        config,
        currentHand,
        currentScenario,
        currentHandNumber,
        handLimit,
        correctCount,
        incorrectCount,
        answers,
        sessionSummary,
        error,
        suggestions,
        suggestionsLoading,
        useTimer,
        timerSeconds,
        startSession,
        nextHand,
        submitAnswer,
        endSession,
        reset,
        fetchSuggestions,
    };
});
