import { createEmptyGrid, getHandsUpToStrength, ACTIONS } from '../utils/hands';

// Create a tight opening range (top ~15% of hands)
function createTightRange() {
    const grid = createEmptyGrid(ACTIONS.FOLD);
    const raiseHands = getHandsUpToStrength(25); // Top 25 hands
    for (const hand of raiseHands) {
        grid[hand] = ACTIONS.RAISE;
    }
    return grid;
}

// Create a loose BTN range (top ~40% of hands)
function createLooseBtnRange() {
    const grid = createEmptyGrid(ACTIONS.FOLD);
    const raiseHands = getHandsUpToStrength(68); // Top ~40%
    for (const hand of raiseHands) {
        grid[hand] = ACTIONS.RAISE;
    }
    return grid;
}

// Create a 3-bet calling range
function create3BetCallingRange() {
    const grid = createEmptyGrid(ACTIONS.FOLD);
    // Premium hands - raise (4-bet)
    const raiseHands = ['AA', 'KK', 'QQ', 'AKs', 'AKo'];
    for (const hand of raiseHands) {
        grid[hand] = ACTIONS.RAISE;
    }
    // Strong hands - call
    const callHands = ['JJ', 'TT', '99', 'AQs', 'AQo', 'AJs', 'KQs', 'ATs'];
    for (const hand of callHands) {
        grid[hand] = ACTIONS.CALL;
    }
    return grid;
}

export const mockRanges = [
    {
        id: 1,
        name: 'UTG Open (20bb)',
        description: 'Tight opening range from UTG with 20bb stack',
        defaultAction: ACTIONS.FOLD,
        grid: createTightRange(),
        createdAt: '2024-01-15T10:00:00Z',
    },
    {
        id: 2,
        name: 'BTN Open (30bb)',
        description: 'Loose opening range from button with 30bb stack',
        defaultAction: ACTIONS.FOLD,
        grid: createLooseBtnRange(),
        createdAt: '2024-01-16T14:30:00Z',
    },
    {
        id: 3,
        name: 'vs 3-Bet (BTN vs BB)',
        description: 'Defending vs 3-bet when opening from BTN',
        defaultAction: ACTIONS.FOLD,
        grid: create3BetCallingRange(),
        createdAt: '2024-01-17T09:15:00Z',
    },
];

export default mockRanges;
