// Ranks in order (high to low)
export const RANKS = ['A', 'K', 'Q', 'J', 'T', '9', '8', '7', '6', '5', '4', '3', '2'];

// Suits
export const SUITS = ['s', 'h', 'd', 'c']; // spades, hearts, diamonds, clubs

// Suit symbols for display
export const SUIT_SYMBOLS = {
    s: '♠',
    h: '♥',
    d: '♦',
    c: '♣',
};

// Actions
export const ACTIONS = {
    FOLD: 'fold',
    CALL: 'call',
    RAISE: 'raise',
};

// Hand strength order (1 = strongest, 169 = weakest)
export const HAND_STRENGTH_ORDER = [
    'AA', 'KK', 'QQ', 'AKs', 'JJ', 'AQs', 'KQs', 'AJs', 'KJs', 'TT',
    'AKo', 'ATs', 'QJs', 'KTs', 'QTs', 'JTs', '99', 'AQo', 'A9s', 'KQo',
    '88', 'K9s', 'T9s', 'A8s', 'Q9s', 'J9s', 'AJo', 'A5s', '77', 'A7s',
    'KJo', 'A4s', 'A3s', 'A6s', 'QJo', '66', 'K8s', 'T8s', 'A2s', '98s',
    'J8s', 'ATo', 'Q8s', 'K7s', 'KTo', '55', 'JTo', '87s', 'QTo', '44',
    '22', '33', 'K6s', '97s', 'K5s', '76s', 'T7s', 'K4s', 'K3s', 'K2s',
    'Q7s', '86s', '65s', 'J7s', '54s', 'Q6s', '75s', '96s', 'Q5s', '64s',
    'Q4s', 'Q3s', 'T6s', '53s', 'Q2s', 'J6s', '85s', 'J5s', 'J4s', 'J3s',
    '95s', 'J2s', '63s', 'T5s', '52s', 'T4s', 'T3s', '43s', 'T2s', '74s',
    '94s', '84s', '42s', '93s', '92s', '83s', '73s', '82s', '72s', '62s',
    // Off-suit hands lower half
    'A9o', 'K9o', 'Q9o', 'J9o', 'T9o', 'A8o', 'K8o', 'Q8o', 'J8o', 'T8o',
    '98o', 'A7o', 'K7o', 'Q7o', 'J7o', 'T7o', '97o', '87o', 'A6o', 'K6o',
    'Q6o', 'J6o', 'T6o', '96o', '86o', '76o', 'A5o', 'K5o', 'Q5o', 'J5o',
    'T5o', '95o', '85o', '75o', '65o', 'A4o', 'K4o', 'Q4o', 'J4o', 'T4o',
    '94o', '84o', '74o', '64o', '54o', 'A3o', 'K3o', 'Q3o', 'J3o', 'T3o',
    '93o', '83o', '73o', '63o', '53o', '43o', 'A2o', 'K2o', 'Q2o', 'J2o',
    'T2o', '92o', '82o', '72o', '62o', '52o', '42o', '32o',
];

// Create a map for quick strength lookup
export const HAND_STRENGTH_MAP = Object.fromEntries(
    HAND_STRENGTH_ORDER.map((hand, index) => [hand, index + 1])
);

/**
 * Get the hand notation for a grid position
 * @param {number} row - Row index (0-12)
 * @param {number} col - Column index (0-12)
 * @returns {string} Hand notation (e.g., 'AKs', 'AKo', 'AA')
 */
export function getHandAtPosition(row, col) {
    const rank1 = RANKS[row];
    const rank2 = RANKS[col];

    if (row === col) {
        // Pocket pair (diagonal)
        return rank1 + rank2;
    } else if (row < col) {
        // Suited (above diagonal)
        return rank1 + rank2 + 's';
    } else {
        // Offsuit (below diagonal)
        return rank2 + rank1 + 'o';
    }
}

/**
 * Get the grid position for a hand notation
 * @param {string} hand - Hand notation (e.g., 'AKs', 'AKo', 'AA')
 * @returns {{ row: number, col: number }} Grid position
 */
export function getPositionForHand(hand) {
    const rank1 = hand[0];
    const rank2 = hand[1];
    const suited = hand[2] === 's';

    const r1Index = RANKS.indexOf(rank1);
    const r2Index = RANKS.indexOf(rank2);

    if (r1Index === r2Index) {
        // Pocket pair
        return { row: r1Index, col: r1Index };
    } else if (suited) {
        // Suited - higher rank is row, lower is column
        return {
            row: Math.min(r1Index, r2Index),
            col: Math.max(r1Index, r2Index),
        };
    } else {
        // Offsuit - lower rank is row, higher is column
        return {
            row: Math.max(r1Index, r2Index),
            col: Math.min(r1Index, r2Index),
        };
    }
}

/**
 * Generate all 169 unique starting hands
 * @returns {string[]} Array of hand notations
 */
export function getAllHands() {
    const hands = [];
    for (let row = 0; row < 13; row++) {
        for (let col = 0; col < 13; col++) {
            hands.push(getHandAtPosition(row, col));
        }
    }
    return hands;
}

/**
 * Get hands up to a certain strength (for range slider)
 * @param {number} strength - Strength threshold (1-169)
 * @returns {string[]} Array of hands within the threshold
 */
export function getHandsUpToStrength(strength) {
    return HAND_STRENGTH_ORDER.slice(0, strength);
}

/**
 * Create an empty 13x13 grid with default action
 * @param {string} defaultAction - Default action for all cells
 * @returns {Object} Grid object with hand -> action mapping
 */
export function createEmptyGrid(defaultAction = ACTIONS.FOLD) {
    const grid = {};
    for (let row = 0; row < 13; row++) {
        for (let col = 0; col < 13; col++) {
            const hand = getHandAtPosition(row, col);
            grid[hand] = defaultAction;
        }
    }
    return grid;
}

/**
 * Get random cards for a given hand
 * @param {string} hand - Hand notation (e.g., 'AKs', 'AKo', 'AA')
 * @returns {{ card1: { rank: string, suit: string }, card2: { rank: string, suit: string } }}
 */
export function getRandomCardsForHand(hand) {
    const rank1 = hand[0];
    const rank2 = hand[1];
    const isPair = rank1 === rank2;
    const isSuited = hand[2] === 's';

    const availableSuits = [...SUITS];

    // Pick first suit randomly
    const suit1Index = Math.floor(Math.random() * availableSuits.length);
    const suit1 = availableSuits[suit1Index];

    let suit2;
    if (isPair || !isSuited) {
        // For pairs and offsuit, pick a different suit
        availableSuits.splice(suit1Index, 1);
        suit2 = availableSuits[Math.floor(Math.random() * availableSuits.length)];
    } else {
        // For suited, same suit
        suit2 = suit1;
    }

    return {
        card1: { rank: rank1, suit: suit1 },
        card2: { rank: rank2, suit: suit2 },
    };
}

/**
 * Check if a hand is a border hand (adjacent to a different action)
 * @param {string} hand - Hand notation
 * @param {Object} grid - Grid object with hand -> action mapping
 * @returns {boolean}
 */
export function isBorderHand(hand, grid) {
    const pos = getPositionForHand(hand);
    const currentAction = grid[hand];

    // Check all 8 adjacent positions
    const adjacentOffsets = [
        [-1, -1], [-1, 0], [-1, 1],
        [0, -1],          [0, 1],
        [1, -1], [1, 0], [1, 1],
    ];

    for (const [dRow, dCol] of adjacentOffsets) {
        const newRow = pos.row + dRow;
        const newCol = pos.col + dCol;

        if (newRow >= 0 && newRow < 13 && newCol >= 0 && newCol < 13) {
            const adjacentHand = getHandAtPosition(newRow, newCol);
            if (grid[adjacentHand] !== currentAction) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Get all border hands in a range
 * @param {Object} grid - Grid object with hand -> action mapping
 * @returns {string[]} Array of border hand notations
 */
export function getBorderHands(grid) {
    return getAllHands().filter(hand => isBorderHand(hand, grid));
}
