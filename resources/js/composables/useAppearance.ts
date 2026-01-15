import { computed, ref, watch } from 'vue';

export type AppearanceMode = 'light' | 'dark' | 'system';
export type AppearancePalette = 'standard' | 'catppuccin' | 'paper';

// For internal resolved state
export type ResolvedAppearance = 'light' | 'dark' | 'catppuccin' | 'paper';

const PALETTES = ['catppuccin', 'paper'] as const;

export function updateTheme(mode: AppearanceMode, palette: AppearancePalette) {
    if (typeof window === 'undefined') {
        return;
    }

    const doc = document.documentElement;

    // Remove existing palette classes
    doc.classList.remove(...PALETTES.map((t) => `theme-${t}`));

    // Apply palette class
    if (palette !== 'standard') {
        doc.classList.add(`theme-${palette}`);
    }

    // Handle dark mode resolution
    let resolvedIsDark = false;
    if (mode === 'system') {
        resolvedIsDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    } else {
        resolvedIsDark = mode === 'dark';
    }

    doc.classList.toggle('dark', resolvedIsDark);
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }
    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const getStoredAppearance = (): AppearanceMode => {
    if (typeof window === 'undefined') return 'system';
    const val = localStorage.getItem('appearance');
    return (val === 'light' || val === 'dark' || val === 'system') ? val : 'system';
};

const getStoredPalette = (): AppearancePalette => {
    if (typeof window === 'undefined') return 'standard';
    const val = localStorage.getItem('palette');
    // Fallback for removed themes (nord, tokyonight, kanagawa)
    if (val === 'nord' || val === 'tokyonight' || val === 'kanagawa') return 'standard';
    return (val === 'standard' || val === 'catppuccin' || val === 'paper') ? val : 'standard';
};

const prefersDark = (): boolean => {
    if (typeof window === 'undefined') return false;
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
};

// Global state
const appearance = ref<AppearanceMode>(getStoredAppearance());
const palette = ref<AppearancePalette>(getStoredPalette());

if (typeof window !== 'undefined') {
    // Initial update
    updateTheme(appearance.value, palette.value);

    // Watchers for immediate reactivity
    watch([appearance, palette], ([newMode, newPalette]) => {
        updateTheme(newMode, newPalette);
    });

    // System theme change listener
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (appearance.value === 'system') {
            updateTheme('system', palette.value);
        }
    });
}

export function useAppearance() {
    const resolvedAppearance = computed<ResolvedAppearance>(() => {
        if (palette.value !== 'standard') return palette.value;
        if (appearance.value === 'system') return prefersDark() ? 'dark' : 'light';
        return appearance.value as'light' | 'dark';
    });

    const isDark = computed(() => {
        if (appearance.value === 'system') return prefersDark();
        return appearance.value === 'dark';
    });

    function updateAppearance(mode: AppearanceMode) {
        appearance.value = mode;
        localStorage.setItem('appearance', mode);
        setCookie('appearance', mode);
    }

    function updatePalette(newPalette: AppearancePalette) {
        palette.value = newPalette;
        localStorage.setItem('palette', newPalette);
        setCookie('palette', newPalette);
    }

    return {
        appearance,
        palette,
        resolvedAppearance,
        isDark,
        updateAppearance,
        updatePalette,
    };
}
