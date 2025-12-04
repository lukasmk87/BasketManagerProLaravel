/**
 * Easing functions for animation interpolation
 * All functions take t (0-1) and return eased value (0-1)
 * Phase 11.2 - TacticBoard Animation Easing
 */

export const easingFunctions = {
    // Linear (no easing)
    linear: (t) => t,

    // Ease In (accelerate) - Quadratic
    easeIn: (t) => t * t,

    // Ease In Cubic
    easeInCubic: (t) => t * t * t,

    // Ease In Quart
    easeInQuart: (t) => t * t * t * t,

    // Ease Out (decelerate) - Quadratic
    easeOut: (t) => 1 - (1 - t) * (1 - t),

    // Ease Out Cubic
    easeOutCubic: (t) => 1 - Math.pow(1 - t, 3),

    // Ease Out Quart
    easeOutQuart: (t) => 1 - Math.pow(1 - t, 4),

    // Ease In-Out (accelerate then decelerate) - Quadratic
    easeInOut: (t) => t < 0.5
        ? 2 * t * t
        : 1 - Math.pow(-2 * t + 2, 2) / 2,

    // Ease In-Out Cubic
    easeInOutCubic: (t) => t < 0.5
        ? 4 * t * t * t
        : 1 - Math.pow(-2 * t + 2, 3) / 2,

    // Bounce (bounces at the end)
    bounce: (t) => {
        const n1 = 7.5625;
        const d1 = 2.75;

        if (t < 1 / d1) {
            return n1 * t * t;
        } else if (t < 2 / d1) {
            return n1 * (t -= 1.5 / d1) * t + 0.75;
        } else if (t < 2.5 / d1) {
            return n1 * (t -= 2.25 / d1) * t + 0.9375;
        } else {
            return n1 * (t -= 2.625 / d1) * t + 0.984375;
        }
    },

    // Elastic (spring-like effect)
    elastic: (t) => {
        const c4 = (2 * Math.PI) / 3;

        if (t === 0) return 0;
        if (t === 1) return 1;

        return Math.pow(2, -10 * t) * Math.sin((t * 10 - 0.75) * c4) + 1;
    },

    // Back (overshoot at the end)
    backOut: (t) => {
        const c1 = 1.70158;
        const c3 = c1 + 1;

        return 1 + c3 * Math.pow(t - 1, 3) + c1 * Math.pow(t - 1, 2);
    },

    // Back In (overshoot at the start)
    backIn: (t) => {
        const c1 = 1.70158;
        const c3 = c1 + 1;

        return c3 * t * t * t - c1 * t * t;
    },
};

/**
 * Options for easing selection dropdown
 */
export const easingOptions = [
    { value: 'linear', label: 'Linear', description: 'Konstante Geschwindigkeit' },
    { value: 'easeIn', label: 'Ease In', description: 'Langsam starten' },
    { value: 'easeOut', label: 'Ease Out', description: 'Langsam stoppen' },
    { value: 'easeInOut', label: 'Ease In-Out', description: 'Langsam starten und stoppen' },
    { value: 'easeInCubic', label: 'Ease In (Kubisch)', description: 'Stärker beschleunigen' },
    { value: 'easeOutCubic', label: 'Ease Out (Kubisch)', description: 'Stärker abbremsen' },
    { value: 'bounce', label: 'Bounce', description: 'Abprallen am Ende' },
    { value: 'elastic', label: 'Elastic', description: 'Federeffekt' },
    { value: 'backOut', label: 'Überschwingen', description: 'Überschwingt am Ende' },
];

/**
 * Get an easing function by name
 * Falls back to linear if name is not found
 */
export const getEasingFunction = (name) => {
    return easingFunctions[name] || easingFunctions.linear;
};

/**
 * Apply easing to a value
 * @param {number} start - Start value
 * @param {number} end - End value
 * @param {number} t - Progress (0-1)
 * @param {string} easingName - Name of easing function
 * @returns {number} Eased value
 */
export const applyEasing = (start, end, t, easingName = 'linear') => {
    const easingFn = getEasingFunction(easingName);
    const easedT = easingFn(t);
    return start + (end - start) * easedT;
};
