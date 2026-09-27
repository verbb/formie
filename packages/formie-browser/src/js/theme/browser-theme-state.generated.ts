// Generated from src/config/browser-theme-state.json. Do not edit by hand.
export const BROWSER_THEME_STATE_DEFAULTS = {
    errors: ["formie-errors"],
    successes: ["formie-successes"],
    message: ["formie-message"],
    messageError: ["formie-message-error"],
    messageSuccess: ["formie-message-success"],
    tabError: ["formie-tab-error"],
    tabCurrent: ["formie-tab-current"],
    tabComplete: ["formie-tab-complete"],
    tabLinkCurrent: [],
    tabLinkInactive: [],
    pageHidden: ["formie-page-hidden"],
    conditionalHidden: ["formie-conditionally-hidden"],
    rowHidden: ["formie-row-hidden"],
    loading: ["formie-loading"],
    success: ["formie-success"],
    error: ["formie-error"],
    fieldLayoutError: ["formie-field-has-error"],
    fieldControlError: ["formie-input-error"],
    fieldErrors: ["formie-field-errors"],
    fieldError: ["formie-field-error"],
} as const;

export type BrowserThemeStateKey = keyof typeof BROWSER_THEME_STATE_DEFAULTS;
