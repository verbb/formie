import type { FormSubmitResult } from '#contracts/schema';
export declare function clearFieldErrors(form: HTMLFormElement): void;
export declare function clearFormErrors(form: HTMLFormElement): void;
export declare function clearFormSuccess(form: HTMLFormElement): void;
export declare function clearAriaInvalid(form: HTMLFormElement): void;
export declare function renderFieldErrors(form: HTMLFormElement, fieldErrors: Record<string, string[]>): void;
export declare function renderFormErrors(form: HTMLFormElement, formErrors: string[]): void;
export declare function renderFormNotice(form: HTMLFormElement, message: string): void;
export declare function renderFormSuccess(form: HTMLFormElement, message: string): void;
export declare function applySubmitResultUi(form: HTMLFormElement, result: FormSubmitResult): void;
//# sourceMappingURL=submit-result-ui.d.ts.map