import type { FormAction } from '#contracts/common';
import type { FormSubmitResult } from '#contracts/schema';
export declare function setFormHiddenState(form: HTMLFormElement, hidden: boolean): void;
export declare function setSubmitLoading(form: HTMLFormElement, submitter?: HTMLElement | null): void;
export declare function clearSubmitLoading(form: HTMLFormElement): void;
export declare function applyPageState(form: HTMLFormElement, nextPageId: string): void;
export declare function applySubmitResultState(form: HTMLFormElement, result: FormSubmitResult, action: FormAction): void;
//# sourceMappingURL=submit-result-state.d.ts.map