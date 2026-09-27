import type { BrowserThemeStateKey } from '#theme/browser-theme-state.generated';

export type BrowserThemeClassMap = Partial<Record<BrowserThemeStateKey, string[] | string>> & Record<string, string[] | string>;

/** @deprecated Use BrowserThemeClassMap. */
export type ThemeClassMap = BrowserThemeClassMap;
