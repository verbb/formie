import type { FormAction, SubmitStage } from '#contracts/common';
import type { FormSubmitResult } from '#contracts/schema';
import { EventBus } from '#events/event-bus';
import { submitForm } from '#transport/forms-api';
import { createDebug } from '#utils/debug';
import { getFormPages, getValidationScope } from '#validation/scope';
import type { FormieValidator } from '#validation/validator';

export type SubmitPipelineContext = {
    form: HTMLFormElement;
    action: FormAction;
    formData: FormData;
    abort: (reason?: string) => void;
    isAborted: () => boolean;
    abortReason: () => string | undefined;
};

type StageRunner = (ctx: SubmitPipelineContext) => Promise<FormSubmitResult | null>;

const STAGES: SubmitStage[] = ['prepare', 'normalize', 'validate', 'screen', 'authorize', 'dispatch', 'finalize'];
const PREFLIGHT_STAGES: SubmitStage[] = ['prepare', 'normalize', 'validate', 'screen', 'authorize'];
const debug = createDebug('general', 'pipeline');

function getAbortedResult(stage: SubmitStage, reason?: string): FormSubmitResult {
    return {
        ok: false,
        stage,
        code: 'ABORTED',
        message: reason || 'Submission aborted.',
        formErrors: [reason || 'Submission aborted.'],
    };
}

function isSubmittableControl(element: Element): element is HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement {
    return element instanceof HTMLInputElement
        || element instanceof HTMLSelectElement
        || element instanceof HTMLTextAreaElement;
}

function shouldIncludeControl(control: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement): boolean {
    if (!control.name || control.disabled) {
        return false;
    }

    if (control instanceof HTMLInputElement) {
        if (control.type === 'submit' || control.type === 'button' || control.type === 'reset' || control.type === 'image') {
            return false;
        }

        if ((control.type === 'checkbox' || control.type === 'radio') && !control.checked) {
            return false;
        }

        if (control.type === 'file' && (!control.files || control.files.length === 0)) {
            return false;
        }
    }

    return true;
}

function appendControlValue(
    formData: FormData,
    control: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement,
): void {
    if (control instanceof HTMLInputElement) {
        if (control.type === 'file') {
            Array.from(control.files || []).forEach((file) => {
                formData.append(control.name, file);
            });

            return;
        }

        formData.append(control.name, control.value);
        return;
    }

    if (control instanceof HTMLSelectElement && control.multiple) {
        Array.from(control.selectedOptions).forEach((option) => {
            formData.append(control.name, option.value);
        });

        return;
    }

    formData.append(control.name, control.value);
}

function appendControlsFromRoot(formData: FormData, form: HTMLFormElement): void {
    form.querySelectorAll('input, select, textarea').forEach((node) => {
        const control = isSubmittableControl(node) ? node : null;

        // Form-level transport inputs live outside page wrappers and must always
        // ride along, even when the visible page payload is partial.
        if (!control || control.closest('[data-formie-page]')) {
            return;
        }

        if (!shouldIncludeControl(control)) {
            return;
        }

        appendControlValue(formData, control);
    });
}

function appendControlsFromPage(formData: FormData, page: HTMLElement): Set<string> {
    const fieldNames = new Set<string>();

    page.querySelectorAll('input, select, textarea').forEach((node) => {
        const control = isSubmittableControl(node) ? node : null;

        if (!control || !control.name || control.disabled) {
            return;
        }

        if (
            control instanceof HTMLInputElement
            && (control.type === 'submit' || control.type === 'button' || control.type === 'reset' || control.type === 'image')
        ) {
            return;
        }

        if (control.name.startsWith('fields[')) {
            fieldNames.add(control.name);
        }

        if (!shouldIncludeControl(control)) {
            return;
        }

        appendControlValue(formData, control);
    });

    return fieldNames;
}

function appendMissingFieldClears(formData: FormData, fieldNames: Set<string>): void {
    fieldNames.forEach((name) => {
        // Unchecked checkbox/radio groups disappear from FormData by default.
        // Appending an empty value lets the backend treat them as an intentional clear.
        if (!formData.has(name)) {
            formData.append(name, '');
        }
    });
}

function buildSubmitFormData(form: HTMLFormElement, action: FormAction): FormData {
    const pages = getFormPages(form);
    const currentPage = pages.find((page) => {
        return !page.hasAttribute('data-formie-page-hidden');
    }) || null;

    if (!pages.length || !currentPage) {
        const formData = new FormData(form);
        formData.set('submitAction', action);

        return formData;
    }

    // Multi-page AJAX submits only send the current page plus root transport
    // inputs so page transitions behave like the server-rendered workflow.
    const formData = new FormData();
    appendControlsFromRoot(formData, form);
    const fieldNames = appendControlsFromPage(formData, currentPage);
    appendMissingFieldClears(formData, fieldNames);
    formData.set('submitAction', action);

    return formData;
}

function isFinalSubmitAttempt(form: HTMLFormElement, action: FormAction): boolean {
    if (action !== 'submit') {
        return false;
    }

    const pages = getFormPages(form);

    if (!pages.length) {
        return true;
    }

    const currentPage = pages.find((page) => {
        return !page.hasAttribute('data-formie-page-hidden');
    }) || pages[pages.length - 1];

    return currentPage === pages[pages.length - 1];
}

export async function runSubmitPipeline(
    form: HTMLFormElement,
    action: FormAction,
    bus: EventBus,
    options: {
        validator?: FormieValidator | null;
        validateOnSubmit?: boolean;
        preflightOnly?: boolean;
    } = {},
): Promise<FormSubmitResult> {
    debug.log('Starting submit pipeline.', {
        action,
        preflightOnly: options.preflightOnly === true,
    });
    let aborted = false;
    let abortReason: string | undefined;
    let dispatchResult: FormSubmitResult | null = null;
    const finalSubmitAttempt = isFinalSubmitAttempt(form, action);

    const context: SubmitPipelineContext = {
        form,
        action,
        formData: buildSubmitFormData(form, action),
        abort: (reason?: string) => {
            aborted = true;
            abortReason = reason;
            debug.warn('Pipeline aborted.', { reason });
        },
        isAborted: () => aborted,
        abortReason: () => abortReason,
    };

    const runners: Record<SubmitStage, StageRunner> = {
        prepare: async(ctx) => {
            const submitAction = ctx.form.querySelector('input[name="submitAction"]') as HTMLInputElement | null;

            if (submitAction) {
                submitAction.value = ctx.action;
            }

            ctx.formData.set('submitAction', ctx.action);
            return null;
        },
        normalize: async() => {
            return null;
        },
        validate: async(ctx) => {
            if (ctx.action !== 'submit') {
                // Back/save actions bypass final validation so they can persist
                // navigation intent without forcing the whole form valid.
                return null;
            }

            if (options.validateOnSubmit === false) {
                return null;
            }

            if (options.validator) {
                const { scope, final } = getValidationScope(ctx.form);
                const errors = options.validator.submit(final ? ctx.form : scope, { final });

                if (errors.length > 0) {
                    const firstErrorInput = errors[0]?.input;

                    if (firstErrorInput) {
                        firstErrorInput.scrollIntoView({ behavior: 'smooth', block: 'center' });

                        try {
                            firstErrorInput.focus({ preventScroll: true });
                        } catch {
                            firstErrorInput.focus();
                        }
                    }

                    return {
                        ok: false,
                        stage: 'validate',
                        code: 'VALIDATION_FAILED',
                        message: options.validator.config.errorMessage || 'Validation failed.',
                        fieldErrors: options.validator.getFieldErrors(errors),
                        formErrors: [options.validator.config.errorMessage || 'Validation failed.'],
                    };
                }

                return null;
            }

            if (!ctx.form.checkValidity()) {
                const invalidField = ctx.form.querySelector(':invalid') as HTMLElement | null;
                invalidField?.focus();

                return {
                    ok: false,
                    stage: 'validate',
                    code: 'VALIDATION_FAILED',
                    message: 'Validation failed.',
                    formErrors: ['Validation failed.'],
                };
            }

            return null;
        },
        screen: async() => {
            return null;
        },
        authorize: async() => {
            return null;
        },
        dispatch: async(ctx) => {
            // Rehydrate just-in-time so provider hooks can mutate hidden inputs
            // during `screen` / `authorize` and still be submitted.
            ctx.formData = buildSubmitFormData(ctx.form, ctx.action);
            const result = await submitForm(ctx.form, ctx.formData);
            dispatchResult = result;
            // Always return dispatch result so stage after-hooks can react to both
            // success and failure outcomes (e.g. provider token/element resets).
            return result;
        },
        finalize: async(resultCtx) => {
            if (!dispatchResult) {
                return null;
            }

            // Redirects happen after the dispatch response is normalized so the
            // rest of the browser client can react to one result shape first.
            if (dispatchResult.ok && dispatchResult.redirect?.url) {
                if (dispatchResult.redirect.target === 'new-tab') {
                    window.open(dispatchResult.redirect.url, '_blank');
                } else {
                    window.location.href = dispatchResult.redirect.url;
                }
            }

            return null;
        },
    };

    {
        const emitReport = await bus.emitSafe('formie:submit:before', context);
        if (emitReport.failed.length > 0) {
            debug.warn('Submit before listeners failed.', {
                eventName: emitReport.eventName,
                failed: emitReport.failed.length,
            });
        }
    }

    if (finalSubmitAttempt) {
        const emitReport = await bus.emitSafe('formie:submit:final:before', context);
        if (emitReport.failed.length > 0) {
            debug.warn('Final submit before listeners failed.', {
                eventName: emitReport.eventName,
                failed: emitReport.failed.length,
            });
        }
    }

    const stages = options.preflightOnly ? PREFLIGHT_STAGES : STAGES;

    for (const stage of stages) {
        debug.log('Stage start.', { stage, action });
        if (aborted) {
            debug.warn('Stage skipped due to abort.', { stage, reason: abortReason });
            return getAbortedResult(stage, abortReason);
        }

        {
            const emitReport = await bus.emitSafe(`formie:stage:${stage}:before`, {
                ...context,
                stage,
            });
            if (emitReport.failed.length > 0) {
                debug.warn('Stage before listeners failed.', {
                    stage,
                    failed: emitReport.failed.length,
                });
            }
        }

        if (aborted) {
            const abortedResult = getAbortedResult(stage, abortReason);
            {
                const emitReport = await bus.emitSafe('formie:submit:after', abortedResult);
                if (emitReport.failed.length > 0) {
                    debug.warn('Submit after listeners failed (abort before stage).', {
                        stage,
                        failed: emitReport.failed.length,
                    });
                }
            }
            if (finalSubmitAttempt) {
                const emitReport = await bus.emitSafe('formie:submit:final:after', abortedResult);
                if (emitReport.failed.length > 0) {
                    debug.warn('Final submit after listeners failed (abort before stage).', {
                        stage,
                        failed: emitReport.failed.length,
                    });
                }
            }
            debug.warn('Aborted after stage before-hooks.', { stage, reason: abortReason });
            return abortedResult;
        }

        const stageResult = await runners[stage](context);
        debug.log('Stage runner complete.', {
            stage,
            hasResult: !!stageResult,
            ok: stageResult ? stageResult.ok : undefined,
            code: stageResult?.code,
        });

        {
            const emitReport = await bus.emitSafe(`formie:stage:${stage}:after`, {
                ...context,
                stage,
                result: stageResult,
            });
            if (emitReport.failed.length > 0) {
                debug.warn('Stage after listeners failed.', {
                    stage,
                    failed: emitReport.failed.length,
                });
            }
        }

        if (aborted) {
            const abortedResult = getAbortedResult(stage, abortReason);
            {
                const emitReport = await bus.emitSafe('formie:submit:after', abortedResult);
                if (emitReport.failed.length > 0) {
                    debug.warn('Submit after listeners failed (abort after stage).', {
                        stage,
                        failed: emitReport.failed.length,
                    });
                }
            }
            if (finalSubmitAttempt) {
                const emitReport = await bus.emitSafe('formie:submit:final:after', abortedResult);
                if (emitReport.failed.length > 0) {
                    debug.warn('Final submit after listeners failed (abort after stage).', {
                        stage,
                        failed: emitReport.failed.length,
                    });
                }
            }
            debug.warn('Aborted after stage after-hooks.', { stage, reason: abortReason });
            return abortedResult;
        }

        // The first failing stage short-circuits the pipeline but still emits the
        // matching stage after-hook so modules can clean up their own state.
        if (stageResult && !stageResult.ok) {
            {
                const emitReport = await bus.emitSafe('formie:submit:after', stageResult);
                if (emitReport.failed.length > 0) {
                    debug.warn('Submit after listeners failed (failed stage).', {
                        stage,
                        failed: emitReport.failed.length,
                    });
                }
            }
            if (finalSubmitAttempt) {
                const emitReport = await bus.emitSafe('formie:submit:final:after', stageResult);
                if (emitReport.failed.length > 0) {
                    debug.warn('Final submit after listeners failed (failed stage).', {
                        stage,
                        failed: emitReport.failed.length,
                    });
                }
            }
            debug.warn('Pipeline short-circuited by failed stage.', {
                stage,
                code: stageResult.code,
                message: stageResult.message,
            });
            return stageResult;
        }
    }

    const successResult: FormSubmitResult = dispatchResult || {
        ok: true,
        stage: options.preflightOnly ? 'authorize' : 'finalize',
        message: options.preflightOnly ? 'Submission preflight completed.' : 'Submission completed.',
    };

    {
        const emitReport = await bus.emitSafe('formie:submit:after', successResult);
        if (emitReport.failed.length > 0) {
            debug.warn('Submit after listeners failed (success).', {
                failed: emitReport.failed.length,
            });
        }
    }
    if (finalSubmitAttempt) {
        const emitReport = await bus.emitSafe('formie:submit:final:after', successResult);
        if (emitReport.failed.length > 0) {
            debug.warn('Final submit after listeners failed (success).', {
                failed: emitReport.failed.length,
            });
        }
    }
    debug.log('Pipeline completed.', {
        ok: successResult.ok,
        stage: successResult.stage,
        code: successResult.code,
    });

    return successResult;
}
