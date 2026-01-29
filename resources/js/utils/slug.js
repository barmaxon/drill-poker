/**
 * Slug generation utilities - mirrors backend SlugGeneratorService.
 */

/**
 * Position group definitions.
 */
const POSITION_GROUPS = {
    early: ['UTG', 'UTG+1'],
    middle: ['UTG+2', 'LJ'],
    late: ['HJ', 'CO', 'BTN'],
    blinds: ['SB', 'BB'],
};

/**
 * Convert a name to a URL-friendly slug.
 * @param {string} name - The scenario name
 * @returns {string} Slugified name
 */
function slugifyName(name) {
    if (!name) return 'scenario';

    // Convert to lowercase, replace non-alphanumeric with hyphens
    let slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-');

    // Remove leading/trailing hyphens
    slug = slug.replace(/^-+|-+$/g, '');

    return slug || 'scenario';
}

/**
 * Get position group name for the given positions.
 * @param {string[]} positions - Array of position strings
 * @returns {string} Position group name
 */
function getPositionGroupName(positions) {
    if (!positions || positions.length === 0) return 'unknown';

    // Sort for consistent comparison
    const sorted = [...positions].sort();

    // Check predefined groups
    for (const [groupName, groupPositions] of Object.entries(POSITION_GROUPS)) {
        const sortedGroup = [...groupPositions].sort();
        if (
            sorted.length === sortedGroup.length &&
            sorted.every((pos, i) => pos === sortedGroup[i])
        ) {
            return groupName;
        }
    }

    // Custom: join position slugs (UTG+1 -> utg1, etc.)
    return sorted
        .map(pos => pos.toLowerCase().replace('+', ''))
        .join('-');
}

/**
 * Generate a slug preview for display during editing.
 * This mirrors the backend slug generation algorithm.
 *
 * @param {string} name - Scenario name
 * @param {string[]} positions - Array of positions
 * @param {number} stackDepth - Stack depth in big blinds
 * @returns {string} Generated slug preview
 */
export function generateSlugPreview(name, positions, stackDepth) {
    const nameSlug = slugifyName(name);
    const positionGroup = getPositionGroupName(positions);
    const stack = stackDepth || 100;

    return `${nameSlug}-${positionGroup}-${stack}bb`;
}
