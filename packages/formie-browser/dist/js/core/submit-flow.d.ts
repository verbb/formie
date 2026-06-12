import type { FormAction } from '#contracts/common';
import type { FormSubmitResult } from '#contracts/schema';
import { EventBus } from '#events/event-bus';
import { FormieValidator } from '#validation/validator';
export declare function shouldRefreshTokensAfterSubmit(result: FormSubmitResult): boolean;
export declare function shouldKeepSubmitLoading(result: FormSubmitResult | null): boolean;
export declare function clearSubmitFeedback(form: HTMLFormElement): void;
export declare function executeAjaxSubmitFlow(params: {
    id?: string;
    target: Element;
    form: HTMLFormElement;
    bus: EventBus;
    validator: FormieValidator | null;
    validateOnSubmit?: boolean;
    action: FormAction;
    submitter?: HTMLElement | null;
    waitForSubmitDelay: (form: HTMLFormElement) => Promise<void>;
    onRefreshTokensAfterSubmit: (result: FormSubmitResult) => Promise<void>;
    dispatchSubmitResult: (result: FormSubmitResult) => void;
}): Promise<FormSubmitResult>;
//# sourceMappingURL=submit-flow.d.ts.map