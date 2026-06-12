import type { ValidationConfig, ValidationContext, ValidationError, ValidationInput, ValidationRules } from '#validation/types';
type ValidatorDefinition = {
    validate: (ctx: ValidationContext) => boolean;
    errorMessage?: (ctx: ValidationContext) => string;
};
type ValidateOptions = {
    includeHiddenPages?: boolean;
};
export declare class FormieValidator {
    form: HTMLFormElement;
    errors: ValidationError[];
    validators: Record<string, ValidatorDefinition>;
    boundListeners: boolean;
    activated: WeakSet<ValidationInput>;
    submitted: boolean;
    initialValues: WeakMap<ValidationInput, string | boolean>;
    onBlur: EventListener;
    onChange: EventListener;
    onInput: EventListener;
    config: ValidationConfig;
    constructor(form: HTMLFormElement, config?: Partial<ValidationConfig>);
    init(): void;
    inputs(inputOrSelector?: Element | null): ValidationInput[];
    getInputValue(input: ValidationInput): string | boolean;
    isDirty(input: ValidationInput): boolean;
    shouldShowError(input: ValidationInput): boolean;
    isValid(inputOrSelector?: Element | null, options?: ValidateOptions): boolean;
    validate(inputOrSelector?: Element | null, options?: ValidateOptions): ValidationError[];
    removeAllErrors(): void;
    removeError(input: ValidationInput): void;
    showError(input: ValidationInput, validatorName: string, errorMessage: string): void;
    getValidatorCallbackOptions(input: ValidationInput): ValidationContext;
    getErrorMessage(input: ValidationInput, validatorName: string, validator: ValidatorDefinition, opts: ValidationContext): string;
    getErrors(): ValidationError[];
    getFieldErrors(errors?: ValidationError[]): Record<string, string[]>;
    getRule(field: HTMLElement | null, rule: string): ValidationRules[string] | false;
    parseValidationRules(ruleString: string | null | undefined): ValidationRules;
    destroy(): void;
    isVisible(element: ValidationInput, options?: ValidateOptions): boolean;
    blurHandler(event: Event): void;
    changeHandler(event: Event): void;
    inputHandler(event: Event): void;
    submit(inputOrSelector?: Element | null, { final }?: {
        final?: boolean;
    }): ValidationError[];
    resetLiveState(): void;
    addEventListeners(): void;
    removeEventListeners(): void;
    emitEvent(target: Document | Element, type: string, detail?: Record<string, unknown>): void;
    addValidator(name: string, validatorFunction: (ctx: ValidationContext) => boolean, errorMessage?: (ctx: ValidationContext) => string): void;
    removeValidator(name: string): void;
}
export {};
//# sourceMappingURL=validator.d.ts.map