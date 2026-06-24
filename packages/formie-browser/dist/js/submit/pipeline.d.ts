import type { FormAction } from '#contracts/common';
import type { FormSubmitResult } from '#contracts/schema';
import { EventBus } from '#events/event-bus';
import type { FormieValidator } from '#validation/validator';
export type SubmitPipelineContext = {
    form: HTMLFormElement;
    action: FormAction;
    formData: FormData;
    abort: (reason?: string) => void;
    isAborted: () => boolean;
    abortReason: () => string | undefined;
};
export declare function runSubmitPipeline(form: HTMLFormElement, action: FormAction, bus: EventBus, options?: {
    validator?: FormieValidator | null;
    validateOnSubmit?: boolean;
    preflightOnly?: boolean;
}): Promise<FormSubmitResult>;
//# sourceMappingURL=pipeline.d.ts.map