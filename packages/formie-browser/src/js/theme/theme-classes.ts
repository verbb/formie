import type { ThemeClassMap } from '#contracts/theme';

type NormalizedThemeClassMap = Record<string, string[]>;

const themeRegistry = new WeakMap<Element, NormalizedThemeClassMap>();
const THEME_ROOT_SELECTORS = '[data-formie-form], [data-formie], form';

function splitClasses(value: string[] | string | null | undefined): string[] {
    if (!value) {
        return [];
    }

    const parts = Array.isArray(value) ? value : [value];

    return parts
        .flatMap((item) => {
            return String(item).split(/\s+/);
        })
        .map((item) => {
            return item.trim();
        })
        .filter(Boolean);
}

function uniqueClasses(classes: string[]): string[] {
    return Array.from(new Set(classes));
}

function resolveThemeClassMap(source: Element | null): NormalizedThemeClassMap {
    if (!source) {
        return {};
    }

    const directMatch = themeRegistry.get(source);
    if (directMatch) {
        return directMatch;
    }

    // Most browser helpers only know the current field/page node, so theme lookup
    // walks back to the registered form root and reuses its flat class map.
    const root = source.closest(THEME_ROOT_SELECTORS);
    if (!root) {
        return {};
    }

    return themeRegistry.get(root) || {};
}

export function normalizeThemeClassMap(theme?: ThemeClassMap | null): NormalizedThemeClassMap {
    const normalized: NormalizedThemeClassMap = {};

    Object.entries(theme || {}).forEach(([key, value]) => {
        const classes = uniqueClasses(splitClasses(value));

        if (classes.length) {
            normalized[key] = classes;
        }
    });

    return normalized;
}

export function registerThemeClassMap(target: Element, theme?: ThemeClassMap | null, form?: HTMLFormElement | null): NormalizedThemeClassMap {
    const normalized = normalizeThemeClassMap(theme);
    const resolvedForm = form || (target instanceof HTMLFormElement ? target : target.querySelector('form'));

    // Store the same normalized map against both the mount target and the actual
    // form element so browser helpers can resolve classes from either surface.
    themeRegistry.set(target, normalized);

    if (resolvedForm) {
        themeRegistry.set(resolvedForm, normalized);
    }

    return normalized;
}

export function getThemeClasses(source: Element | null, key: string): string[] {
    return resolveThemeClassMap(source)[key] || [];
}

export function addThemeClasses(target: Element, source: Element | null, ...keys: string[]): void {
    const classes = uniqueClasses(keys.flatMap((key) => {
        return getThemeClasses(source, key);
    }));

    if (classes.length) {
        target.classList.add(...classes);
    }
}

export function removeThemeClasses(target: Element, source: Element | null, ...keys: string[]): void {
    const classes = uniqueClasses(keys.flatMap((key) => {
        return getThemeClasses(source, key);
    }));

    if (classes.length) {
        target.classList.remove(...classes);
    }
}

export function toggleThemeClasses(target: Element, source: Element | null, key: string, enabled: boolean): void {
    getThemeClasses(source, key).forEach((className) => {
        target.classList.toggle(className, enabled);
    });
}
