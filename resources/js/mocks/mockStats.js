import { getAllHands } from '../utils/hands';
import { mockScenarios, mockGroups } from './mockScenarios';

export const mockStats = {
    totalHands: 523,
    accuracy: 78,
    totalMistakes: 115,
    borderMistakes: 42,
    sessions: 12,
};

// Generate mock stats per scenario
export const mockScenarioStats = mockScenarios.map((scenario, index) => {
    const totalHands = 40 + Math.floor(Math.random() * 60);
    const accuracy = 65 + Math.floor(Math.random() * 25);
    const totalMistakes = Math.floor(totalHands * (100 - accuracy) / 100);
    const borderMistakes = Math.floor(totalMistakes * 0.35); // ~35% are border mistakes
    return {
        id: scenario.id,
        name: scenario.name,
        position: scenario.position,
        stackDepth: scenario.stackDepth,
        totalHands,
        accuracy,
        totalMistakes,
        borderMistakes,
    };
});

// Generate mock stats per group
export const mockGroupStats = mockGroups.map(group => {
    const groupScenarios = mockScenarioStats.filter(s =>
        group.scenarios.includes(s.id)
    );
    const totalHands = groupScenarios.reduce((sum, s) => sum + s.totalHands, 0);
    const weightedAccuracy = groupScenarios.length > 0
        ? Math.round(groupScenarios.reduce((sum, s) => sum + s.accuracy * s.totalHands, 0) / totalHands)
        : 0;
    const totalMistakes = groupScenarios.reduce((sum, s) => sum + s.totalMistakes, 0);
    const borderMistakes = groupScenarios.reduce((sum, s) => sum + s.borderMistakes, 0);

    return {
        id: group.id,
        name: group.name,
        scenarioCount: group.scenarios.length,
        totalHands,
        accuracy: weightedAccuracy || 0,
        totalMistakes,
        borderMistakes,
        scenarios: groupScenarios.map(s => ({
            id: s.id,
            name: s.name,
            accuracy: s.accuracy,
        })),
    };
});

// Generate detailed stats for a specific scenario
export function generateScenarioDetail(scenarioId) {
    const scenario = mockScenarioStats.find(s => s.id === parseInt(scenarioId));
    if (!scenario) return null;

    // Generate heatmap specific to this scenario
    const heatmap = {};
    const hands = getAllHands();
    for (const hand of hands) {
        const accuracy = Math.floor(Math.random() * 50) + 50;
        const total = Math.floor(Math.random() * 10) + 2;
        const correct = Math.floor((accuracy / 100) * total);
        heatmap[hand] = { total, correct, accuracy };
    }

    // Make some hands worse for this scenario
    const problemList = ['A5s', 'K9s', 'QTs', 'J9s'].slice(0, 2 + Math.floor(Math.random() * 2));
    for (const hand of problemList) {
        heatmap[hand] = { total: 8, correct: 3, accuracy: 38 };
    }

    // Problem hands for this scenario
    const problemHands = problemList.map((hand, i) => ({
        hand,
        accuracy: 35 + i * 5,
        mistakes: 5 - i,
        total: 10,
    }));

    return {
        summary: {
            totalHands: scenario.totalHands,
            accuracy: scenario.accuracy,
            totalMistakes: scenario.totalMistakes,
            borderMistakes: scenario.borderMistakes,
        },
        heatmap,
        problemHands,
    };
}

// Generate mock heatmap data (accuracy per hand)
export const mockHeatmapData = (() => {
    const data = {};
    const hands = getAllHands();

    for (const hand of hands) {
        // Random accuracy between 50-100%
        const accuracy = Math.floor(Math.random() * 50) + 50;
        const total = Math.floor(Math.random() * 20) + 5;
        const correct = Math.floor((accuracy / 100) * total);

        data[hand] = {
            total,
            correct,
            accuracy,
        };
    }

    // Make some hands notably worse
    const problemHandsList = ['A5s', 'K9s', 'QTs', 'J9s', 'T8s', '87s', '76s'];
    for (const hand of problemHandsList) {
        data[hand] = {
            total: 15,
            correct: 6,
            accuracy: 40,
        };
    }

    return data;
})();

export const mockProblemHands = [
    { hand: 'A5s', accuracy: 45, mistakes: 11, total: 20 },
    { hand: 'K9s', accuracy: 50, mistakes: 8, total: 16 },
    { hand: 'QTs', accuracy: 52, mistakes: 10, total: 21 },
    { hand: 'J9s', accuracy: 55, mistakes: 9, total: 20 },
    { hand: 'T8s', accuracy: 58, mistakes: 7, total: 17 },
    { hand: '87s', accuracy: 60, mistakes: 6, total: 15 },
    { hand: '76s', accuracy: 61, mistakes: 7, total: 18 },
    { hand: 'A4s', accuracy: 63, mistakes: 5, total: 14 },
    { hand: 'K8s', accuracy: 65, mistakes: 5, total: 14 },
    { hand: '98s', accuracy: 67, mistakes: 4, total: 12 },
];

export default {
    mockStats,
    mockHeatmapData,
    mockProblemHands,
    mockScenarioStats,
    mockGroupStats,
    generateScenarioDetail,
};
