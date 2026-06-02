export type ValidationInput = HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;
export type ValidationRule = {
    type: string;
    fieldId?: string | null;
    fieldHandle?: string | null;
    min?: number | null;
    max?: number | null;
    [key: string]: unknown;
};
export type ValidationRuleValue = ValidationRule | boolean;
export type ValidationRules = Record<string, ValidationRuleValue>;
export type ValidationConfig = {
    live: boolean;
    errorMessage?: string;
    fieldContainerErrorClass: string[];
    inputErrorClass: string[];
    messagesClass: string[];
    messageClass: string[];
    fieldsSelector: string;
    patterns: Record<string, RegExp>;
};
export type ValidationContext = {
    t: (message: string, replacements?: Record<string, string | number>) => string;
    input: ValidationInput;
    label: string;
    field: HTMLElement | null;
    form: HTMLFormElement;
    config: ValidationConfig;
    rules: ValidationRules;
    getRule: (rule: string) => ValidationRuleValue | false;
};
export type ValidationRuleDefinition = {
    rule: (ctx: ValidationContext) => boolean;
    message?: (ctx: ValidationContext) => string;
};
export type ValidationError = {
    input: ValidationInput;
    field: HTMLElement | null;
    validator: string;
    message: string;
    handle: string | null;
    result: boolean;
};
//# sourceMappingURL=types.d.ts.map