/**
 * LocalStorage cache utilities with TTL support.
 */

const STACK_SIZE_KEY = 'poker_study_stack_size';
const STACK_SIZE_TTL = 2 * 60 * 60 * 1000; // 2 hours in milliseconds
const DEFAULT_STACK_SIZE = 100;

const DRILL_SELECTION_KEY = 'poker_study_drill_selection';
const DRILL_SELECTION_TTL = 7 * 24 * 60 * 60 * 1000; // 1 week in milliseconds

const DRILL_SETTINGS_KEY = 'poker_study_drill_settings';
const DRILL_SETTINGS_TTL = 30 * 24 * 60 * 60 * 1000; // 30 days in milliseconds

/**
 * Get a cached value with TTL support.
 * Returns null if expired or not found.
 */
function getCachedValue(key) {
    try {
        const item = localStorage.getItem(key);
        if (!item) return null;

        const { value, expiry } = JSON.parse(item);
        if (Date.now() > expiry) {
            localStorage.removeItem(key);
            return null;
        }

        return value;
    } catch {
        return null;
    }
}

/**
 * Set a cached value with TTL.
 */
function setCachedValue(key, value, ttl) {
    try {
        const item = {
            value,
            expiry: Date.now() + ttl,
        };
        localStorage.setItem(key, JSON.stringify(item));
    } catch {
        // Ignore localStorage errors (e.g., quota exceeded)
    }
}

/**
 * Get cached stack size or default (100bb).
 * @returns {number} The cached stack size or 100
 */
export function getCachedStackSize() {
    const cached = getCachedValue(STACK_SIZE_KEY);
    return cached !== null ? cached : DEFAULT_STACK_SIZE;
}

/**
 * Cache the stack size for 2 hours.
 * @param {number} value - The stack size to cache
 */
export function setCachedStackSize(value) {
    setCachedValue(STACK_SIZE_KEY, value, STACK_SIZE_TTL);
}

/**
 * Get cached drill selection (scenario or group).
 * @returns {{ type: 'scenario'|'group', id: number }|null}
 */
export function getCachedDrillSelection() {
    return getCachedValue(DRILL_SELECTION_KEY);
}

/**
 * Cache the drill selection for 1 week.
 * @param {'scenario'|'group'} type - Selection type
 * @param {number} id - Scenario or group ID
 */
export function setCachedDrillSelection(type, id) {
    setCachedValue(DRILL_SELECTION_KEY, { type, id }, DRILL_SELECTION_TTL);
}

/**
 * Get cached drill settings (timer, hand limit).
 * @returns {{ useTimer: boolean, timerSeconds: number, handLimit: number|null }|null}
 */
export function getCachedDrillSettings() {
    return getCachedValue(DRILL_SETTINGS_KEY);
}

/**
 * Cache drill settings for 30 days.
 * @param {Object} settings - Drill settings
 * @param {boolean} settings.useTimer - Whether timer is enabled
 * @param {number} settings.timerSeconds - Timer duration
 * @param {number|null} settings.handLimit - Hand limit or null for unlimited
 */
export function setCachedDrillSettings(settings) {
    setCachedValue(DRILL_SETTINGS_KEY, settings, DRILL_SETTINGS_TTL);
}
