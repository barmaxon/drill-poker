import { mockScenarios, mockGroups } from './mockScenarios';
import {
    getRandomCardsForHand,
    isBorderHand,
    HAND_STRENGTH_ORDER,
} from '../utils/hands';

class MockDrillService {
    constructor() {
        this.config = null;
        this.activeScenarios = [];
        this.handWeights = {};
    }

    async startSession(config) {
        this.config = config;

        // Get scenarios based on config
        if (config.type === 'scenario') {
            const scenario = mockScenarios.find(s => s.id === parseInt(config.scenarioId));
            this.activeScenarios = scenario ? [scenario] : [];
        } else {
            // Group - get scenarios in this group
            const group = mockGroups.find(g => g.id === parseInt(config.groupId));
            if (group) {
                this.activeScenarios = mockScenarios.filter(s => group.scenarios.includes(s.id));
            } else {
                this.activeScenarios = [...mockScenarios];
            }
        }

        // Initialize hand weights (all start at 1.0)
        this.handWeights = {};
        for (const hand of HAND_STRENGTH_ORDER) {
            this.handWeights[hand] = 1.0;
        }

        return { sessionId: Date.now() };
    }

    async getNextHand() {
        if (this.activeScenarios.length === 0) {
            return null;
        }

        // Pick random scenario
        const scenario = this.activeScenarios[
            Math.floor(Math.random() * this.activeScenarios.length)
        ];

        // Use the grid directly from the scenario
        if (!scenario.grid) {
            return null;
        }

        // Weighted random hand selection
        const hand = this.selectWeightedHand(scenario.grid);
        const cards = getRandomCardsForHand(hand);

        return {
            hand: {
                notation: hand,
                card1: cards.card1,
                card2: cards.card2,
            },
            scenario: {
                id: scenario.id,
                name: scenario.name,
                position: scenario.position,
                stackDepth: scenario.stackDepth,
                limpers: scenario.limpers,
                grid: scenario.grid,
            },
        };
    }

    selectWeightedHand(grid) {
        // Calculate total weight
        let totalWeight = 0;
        const weightedHands = [];

        for (const hand of HAND_STRENGTH_ORDER) {
            const weight = this.handWeights[hand] || 1.0;
            // Boost border hands
            const isBorder = isBorderHand(hand, grid);
            const finalWeight = isBorder ? weight * 2.0 : weight;

            totalWeight += finalWeight;
            weightedHands.push({ hand, weight: finalWeight });
        }

        // Random selection based on weights
        let random = Math.random() * totalWeight;
        for (const { hand, weight } of weightedHands) {
            random -= weight;
            if (random <= 0) {
                return hand;
            }
        }

        // Fallback
        return HAND_STRENGTH_ORDER[0];
    }

    async submitAnswer(handNotation, userAction, scenario) {
        // Get the grid from the scenario
        const grid = scenario.grid;
        const correctAction = grid[handNotation] || 'fold';
        const correct = userAction === correctAction;

        // Determine mistake type
        let mistakeType = null;
        if (!correct) {
            const isBorder = isBorderHand(handNotation, grid);
            mistakeType = isBorder ? 'border' : 'normal';

            // Adjust weight for next time
            if (mistakeType === 'border') {
                this.handWeights[handNotation] = Math.min(
                    10.0,
                    (this.handWeights[handNotation] || 1.0) + 0.8
                );
            } else {
                this.handWeights[handNotation] = Math.min(
                    10.0,
                    (this.handWeights[handNotation] || 1.0) + 0.5
                );
            }
        } else {
            // Correct - reduce weight
            this.handWeights[handNotation] = Math.max(
                0.1,
                (this.handWeights[handNotation] || 1.0) - 0.3
            );
        }

        return {
            correct,
            userAction,
            correctAction,
            mistakeType,
        };
    }

    async endSession() {
        this.config = null;
        this.activeScenarios = [];
        this.handWeights = {};
        return { success: true };
    }
}

export const mockDrillService = new MockDrillService();
export default mockDrillService;
