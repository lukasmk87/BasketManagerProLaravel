/**
 * Official FIBA Basketball Court Dimensions (2024)
 * All measurements in meters
 *
 * Reference: FIBA Official Basketball Rules 2024
 * https://assets.fiba.basketball/image/upload/documents-corporate-fiba-official-rules-2024-official-basketball-rules-and-basketball-equipment.pdf
 */

export const FIBA_COURT = {
    // Court dimensions
    fullLength: 28,
    width: 15,
    halfLength: 14,

    // Three-point line
    threePointRadius: 6.75,
    threePointCornerDistance: 0.90, // Distance from sideline

    // Paint/Key area (rectangular since 2010)
    paintWidth: 4.9,
    paintLength: 5.8,

    // Circles
    freeThrowCircleRadius: 1.8,
    centerCircleRadius: 1.8,
    restrictedAreaRadius: 1.25,

    // Basket & Backboard
    basketFromBaseline: 1.575,
    backboardFromBaseline: 1.2,
    backboardWidth: 1.8,
    rimRadius: 0.225, // 45cm diameter / 2

    // Line width (5cm = 0.05m)
    lineWidth: 0.05,
};

/**
 * Calculate uniform scale and offset for aspect-ratio preservation
 * This ensures circles remain circular and proportions are maintained
 *
 * @param {number} containerWidth - Available container width in pixels
 * @param {number} containerHeight - Available container height in pixels
 * @param {number} courtWidth - Court width in meters (15m for FIBA)
 * @param {number} courtLength - Court length in meters (28m full, 14m half)
 * @returns {{scale: number, offsetX: number, offsetY: number, actualWidth: number, actualHeight: number}}
 */
export function calculateUniformScale(containerWidth, containerHeight, courtWidth, courtLength) {
    const containerRatio = containerWidth / containerHeight;
    const courtRatio = courtWidth / courtLength;

    let scale, offsetX = 0, offsetY = 0;

    if (containerRatio > courtRatio) {
        // Container is wider than court ratio - letterbox left/right
        scale = containerHeight / courtLength;
        offsetX = (containerWidth - courtWidth * scale) / 2;
    } else {
        // Container is taller than court ratio - letterbox top/bottom
        scale = containerWidth / courtWidth;
        offsetY = (containerHeight - courtLength * scale) / 2;
    }

    return {
        scale,
        offsetX,
        offsetY,
        actualWidth: courtWidth * scale,
        actualHeight: courtLength * scale,
    };
}

/**
 * Convert meters to scaled pixels
 * @param {number} meters - Value in meters
 * @param {number} scale - Scale factor from calculateUniformScale
 * @returns {number} - Value in pixels
 */
export function toPixels(meters, scale) {
    return meters * scale;
}
