export type BrowserValidationRule = {
    message?: string;
    messages?: Record<string, string>;
    type: string;
    fieldId?: string | null;
    fieldHandle?: string | null;
    min?: number | null;
    max?: number | null;
    minDate?: string | null;
    maxDate?: string | null;
    [key: string]: unknown;
};
export type BrowserValidationContext = {
    label?: string;
    comparison?: unknown;
    comparisonLabel?: string;
    empty?: boolean;
};
export type BrowserRule = (value: unknown, rule: BrowserValidationRule, context: BrowserValidationContext) => string | null;
export declare function registerBrowserValidationRule(type: string, rule: BrowserRule): () => void;
export declare function browserValueEmpty(value: unknown): boolean;
export declare function validateBrowserValue(value: unknown, rule: BrowserValidationRule, context?: BrowserValidationContext): string | null;
//# sourceMappingURL=validation.d.ts.map