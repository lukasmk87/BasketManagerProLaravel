import { ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const THEME_KEY = 'theme';

// Global reactive state (shared between all components)
const theme = ref('light');
const isDark = ref(false);

let initialized = false;

/**
 * Composable for dark mode management
 *
 * @example
 * const { theme, isDark, setTheme, toggleTheme, cycleTheme } = useDarkMode();
 *
 * setTheme('dark');   // Force dark mode
 * setTheme('light');  // Force light mode
 * setTheme('system'); // Follow system preference
 *
 * toggleTheme();      // Quick toggle between light/dark
 * cycleTheme();       // Cycle: light -> dark -> system -> light
 */
export function useDarkMode() {
    const page = usePage();

    /**
     * Apply theme to HTML element
     */
    const applyTheme = (newTheme) => {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        let shouldBeDark = false;
        if (newTheme === 'dark') {
            shouldBeDark = true;
        } else if (newTheme === 'system') {
            shouldBeDark = prefersDark;
        }

        isDark.value = shouldBeDark;

        if (shouldBeDark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    };

    /**
     * Set theme and persist to localStorage
     */
    const setTheme = (newTheme) => {
        if (!['light', 'dark', 'system'].includes(newTheme)) {
            console.warn(`[useDarkMode] Invalid theme: ${newTheme}`);
            return;
        }

        theme.value = newTheme;
        localStorage.setItem(THEME_KEY, newTheme);
        applyTheme(newTheme);
    };

    /**
     * Toggle between light and dark (ignores system)
     */
    const toggleTheme = () => {
        setTheme(isDark.value ? 'light' : 'dark');
    };

    /**
     * Cycle through: light -> dark -> system -> light
     */
    const cycleTheme = () => {
        const cycle = { light: 'dark', dark: 'system', system: 'light' };
        setTheme(cycle[theme.value] || 'light');
    };

    /**
     * Initialize from server theme (for server-sync)
     */
    const initializeFromServer = (serverTheme) => {
        if (serverTheme && ['light', 'dark', 'system'].includes(serverTheme)) {
            theme.value = serverTheme;
            localStorage.setItem(THEME_KEY, serverTheme);
            applyTheme(serverTheme);
        }
    };

    /**
     * Initialize (runs once)
     */
    const initialize = () => {
        if (initialized) return;
        initialized = true;

        // Priority: Server theme > localStorage > default 'system'
        const serverTheme = page.props?.auth?.user?.theme;
        const localTheme = localStorage.getItem(THEME_KEY);

        if (serverTheme && ['light', 'dark', 'system'].includes(serverTheme)) {
            // Use server theme and sync to localStorage
            theme.value = serverTheme;
            localStorage.setItem(THEME_KEY, serverTheme);
        } else if (localTheme && ['light', 'dark', 'system'].includes(localTheme)) {
            // Fallback to localStorage
            theme.value = localTheme;
        } else {
            // Default
            theme.value = 'light';
        }

        applyTheme(theme.value);

        // Listen for system preference changes
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addEventListener('change', () => {
            if (theme.value === 'system') {
                applyTheme('system');
            }
        });
    };

    onMounted(() => {
        initialize();
    });

    return {
        theme,
        isDark,
        setTheme,
        toggleTheme,
        cycleTheme,
        initializeFromServer,
    };
}
