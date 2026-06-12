import type { ThemeClassMap } from '#contracts/theme';
type NormalizedThemeClassMap = Record<string, string[]>;
export declare function normalizeThemeClassMap(theme?: ThemeClassMap | null): NormalizedThemeClassMap;
export declare function registerThemeClassMap(target: Element, theme?: ThemeClassMap | null, form?: HTMLFormElement | null): NormalizedThemeClassMap;
export declare function getThemeClasses(source: Element | null, key: string): string[];
export declare function addThemeClasses(target: Element, source: Element | null, ...keys: string[]): void;
export declare function removeThemeClasses(target: Element, source: Element | null, ...keys: string[]): void;
export declare function toggleThemeClasses(target: Element, source: Element | null, key: string, enabled: boolean): void;
export {};
//# sourceMappingURL=theme-classes.d.ts.map