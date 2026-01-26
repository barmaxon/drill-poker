import { createEmptyGrid, getHandsUpToStrength, ACTIONS } from '../utils/hands';

// Helper to create a raise range with top X hands
function createRaiseRange(topHands) {
    const grid = createEmptyGrid(ACTIONS.FOLD);
    const hands = getHandsUpToStrength(topHands);
    for (const hand of hands) {
        grid[hand] = ACTIONS.RAISE;
    }
    return grid;
}

export const mockScenarios = [
    {
        id: 1,
        name: 'UTG Open 20bb',
        position: 'UTG',
        stackDepth: 20,
        limpers: 0,
        description: 'Tight opening range from UTG with 20bb effective stack',
        grid: createRaiseRange(25), // Top ~15%
    },
    {
        id: 2,
        name: 'BTN Open 30bb',
        position: 'BTN',
        stackDepth: 30,
        limpers: 0,
        description: 'Loose opening range from button with 30bb effective stack',
        grid: createRaiseRange(68), // Top ~40%
    },
    {
        id: 3,
        name: 'BTN Open vs 1 Limper',
        position: 'BTN',
        stackDepth: 25,
        limpers: 1,
        description: 'Button opening range over one limper',
        grid: createRaiseRange(55), // Slightly tighter
    },
    {
        id: 4,
        name: 'CO Open 25bb',
        position: 'CO',
        stackDepth: 25,
        limpers: 0,
        description: 'Opening range from cutoff with 25bb stack',
        grid: createRaiseRange(45), // Top ~27%
    },
    {
        id: 5,
        name: 'SB Open 20bb',
        position: 'SB',
        stackDepth: 20,
        limpers: 0,
        description: 'Small blind opening range when folded to',
        grid: createRaiseRange(60), // Wide from SB
    },
];

export const mockGroups = [
    {
        id: 1,
        name: 'Late Position Opens',
        scenarios: [2, 3, 4],
    },
    {
        id: 2,
        name: 'Short Stack (15-25bb)',
        scenarios: [1, 4, 5],
    },
    {
        id: 3,
        name: 'Full Drill',
        scenarios: [1, 2, 3, 4, 5],
    },
];

export default { mockScenarios, mockGroups };
