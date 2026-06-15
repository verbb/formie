export type ErrorAriaLivePreference = 'polite' | 'assertive' | 'off';
export declare function getErrorAriaLivePreference(form: HTMLFormElement): ErrorAriaLivePreference;
export declare function resolveValidationErrorAriaLive(preference: ErrorAriaLivePreference, submitted: boolean): 'polite' | 'assertive' | null;
export declare function resolveSubmitErrorAriaLive(preference: ErrorAriaLivePreference): 'polite' | 'assertive' | null;
export declare function applyErrorAriaLive(element: HTMLElement, ariaLive: 'polite' | 'assertive' | null): void;
//# sourceMappingURL=error-aria-live.d.ts.map