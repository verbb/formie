import type { FormAction } from '#contracts/common';
import type { FormSubmitResult } from '#contracts/schema';
import { EventBus } from '#events/event-bus';
import { runSubmitPipeline } from '#submit/pipeline';
import { createDebug } from '#utils/debug';
import { FormieValidator } from '#validation/validator';
import {
    applySubmitResultUi,
    clearAriaInvalid,
    clearFieldErrors,
    clearFormErrors,
    clearFormSuccess,
} from './submit-result-ui';
import { applySubmitResultState, clearSubmitLoading, setSubmitLoading } from './submit-result-state';

const debug = createDebug('general', 'submit-flow');

export function shouldRefreshTokensAfterSubmit(result: FormSubmitResult): boolean {
    if (!result.ok && result.stage === 'validate') {
        return false;
    }

    return true;
}

export function shouldKeepSubmitLoading(result: FormSubmitResult | null): boolean {
    if (!result) {
        return false;
    }

    if (result.keepSubmitLoading === true) {
        return true;
    }

    if (result.ok && result.redirect?.url && result.redirect.target !== 'new-tab') {
        return true;
    }

    return false;
}

export function clearSubmitFeedback(form: HTMLFormElement): void {
    clearFieldErrors(form);
    clearFormErrors(form);
    clearFormSuccess(form);
    clearAriaInvalid(form);
}

export async function executeAjaxSubmitFlow(params: {
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
}): Promise<FormSubmitResult> {
    const {
        id,
        target,
        form,
        bus,
        validator,
        validateOnSubmit,
        action,
        submitter,
        waitForSubmitDelay,
        onRefreshTokensAfterSubmit,
        dispatchSubmitResult,
    } = params;

    clearSubmitFeedback(form);
    setSubmitLoading(form, submitter || null);

    let result: FormSubmitResult = {
        ok: false,
        code: 'SUBMIT_ERROR',
        message: 'Submission failed.',
        formErrors: ['Submission failed.'],
    };

    try {
        await waitForSubmitDelay(form);
        result = await runSubmitPipeline(form, action, bus, {
            validator,
            validateOnSubmit,
        });
        applySubmitResultUi(form, result);
        // Let captcha modules refresh one-time tokens before payment follow-up
        // handlers (e.g. Stripe confirm) trigger an internal resubmit.
        dispatchSubmitResult(result);
        applySubmitResultState(form, result, action);
        if (shouldRefreshTokensAfterSubmit(result)) {
            await onRefreshTokensAfterSubmit(result);
        }
    } catch (error) {
        result = {
            ok: false,
            code: 'SUBMIT_ERROR',
            message: error instanceof Error ? error.message : 'Submission failed.',
            formErrors: [error instanceof Error ? error.message : 'Submission failed.'],
        };
        applySubmitResultUi(form, result);
        dispatchSubmitResult(result);
        debug.warn('Submit failed with exception.', {
            id,
            action,
            target,
            error: error instanceof Error ? error.message : error,
        });
    } finally {
        if (!shouldKeepSubmitLoading(result)) {
            clearSubmitLoading(form);
        }
    }

    return result;
}
