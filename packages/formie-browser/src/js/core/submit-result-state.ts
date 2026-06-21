import type { FormAction } from '#contracts/common';
import type { FormSubmitResult } from '#contracts/schema';
import { dispatchFormieDomEvent } from '#core/dom-events';
import { dispatchPageClientEventForSubmit, dispatchResolvedClientEvents } from '#core/page-client-event';
import { syncPageTabErrors } from '#core/page-tab-errors';
import { addThemeClasses, removeThemeClasses, toggleThemeClasses } from '#theme/theme-classes';
import { createDebug } from '#utils/debug';
import { getFormStateEventName, normalizeFormieEventName } from '#utils/event-names';
import type { FormieValidator } from '#validation/validator';
import { syncSubmitReadiness } from '#validation/submit-readiness';

type FormWithValidationApi = HTMLFormElement & {
    formieValidation?: FormieValidator;
};

const STALE_SUBMISSION_STATE_CODE = 'STALE_SUBMISSION_STATE';
const DEFAULT_FINAL_SUBMIT_RESET_DELAY_MS = 1500;
const finalSubmitResetTimers = new WeakMap<HTMLFormElement, number>();
const originalSubmitterMarkup = new WeakMap<HTMLElement, string>();
const debug = createDebug('general', 'submit-result');

type ResetSubmissionStateOptions = {
    preserveHiddenState?: boolean;
};

function setHiddenInputValue(form: HTMLFormElement, name: string, value: string): void {
    let input = form.querySelector(`input[name="${name}"]`) as HTMLInputElement | null;

    if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        form.appendChild(input);
    }

    input.value = value;
}

function markInternalNavigation(form: HTMLFormElement, reason: string): void {
    form.setAttribute('data-formie-internal-navigation', reason);
}

function removeHiddenInput(form: HTMLFormElement, name: string): void {
    const input = form.querySelector(`input[name="${name}"]`) as HTMLInputElement | null;
    input?.remove();
}

function stripQueryParam(urlValue: string, paramName: string): string {
    try {
        const url = new URL(urlValue, window.location.href);
        url.searchParams.delete(paramName);

        return url.toString();
    } catch {
        return urlValue;
    }
}

function isSameOriginUrl(urlValue: string): boolean {
    try {
        const url = new URL(urlValue, window.location.href);
        return url.origin === window.location.origin;
    } catch {
        return false;
    }
}

function getPageElements(form: HTMLFormElement): HTMLElement[] {
    return Array.from(form.querySelectorAll('[data-formie-page]')) as HTMLElement[];
}

function getTabElements(form: HTMLFormElement): HTMLElement[] {
    return Array.from(form.querySelectorAll('[data-formie-tab]')) as HTMLElement[];
}

function getProgressPercent(form: HTMLFormElement, currentPageIndex: number, totalPages: number): number {
    if (currentPageIndex < 0 || totalPages < 1) {
        return 0;
    }

    const mode = form.dataset.formieProgressCalculation === 'page-position' ? 'page-position' : 'completion';

    if (mode === 'page-position') {
        return Math.round(((currentPageIndex + 1) / totalPages) * 100);
    }

    return Math.round((currentPageIndex / totalPages) * 100);
}

function getProgressState(progress: number): 'start' | 'middle' | 'end' {
    if (progress <= 0) {
        return 'start';
    }

    if (progress >= 100) {
        return 'end';
    }

    return 'middle';
}

function getConfiguredSubmitAction(form: HTMLFormElement): string {
    return (form.dataset.formieSubmitAction || '').trim();
}

function getResolvedSubmitAction(form: HTMLFormElement, result: FormSubmitResult): string {
    const fromResponse = result.meta?.effectiveSubmitAction;

    if (typeof fromResponse === 'string' && fromResponse.trim() !== '') {
        return fromResponse.trim();
    }

    return getConfiguredSubmitAction(form);
}

function shouldHideFormOnSuccess(form: HTMLFormElement): boolean {
    const rawValue = form.dataset.formieSubmitActionFormHide;

    if (rawValue === undefined) {
        return false;
    }

    const normalized = rawValue.trim().toLowerCase();
    return normalized === 'true' || normalized === '1' || normalized === '';
}

export function setFormHiddenState(form: HTMLFormElement, hidden: boolean): void {
    const sections = [
        '[data-formie-form-header]',
        '[data-formie-form-navigation]',
        '[data-formie-form-body]',
        '[data-formie-form-footer]',
    ];

    form.toggleAttribute('data-formie-form-hidden', hidden);

    sections.forEach((selector) => {
        form.querySelectorAll(selector).forEach((node) => {
            const element = node as HTMLElement;

            if (hidden) {
                element.hidden = true;
            } else {
                element.hidden = false;
            }
        });
    });
}

function clearPendingFinalSubmitReset(form: HTMLFormElement): void {
    const timerId = finalSubmitResetTimers.get(form);

    if (typeof timerId === 'number') {
        window.clearTimeout(timerId);
        finalSubmitResetTimers.delete(form);
    }
}

function setSubmitterLoadingText(submitter: HTMLElement, loadingText: string): void {
    if (!originalSubmitterMarkup.has(submitter)) {
        originalSubmitterMarkup.set(submitter, submitter.innerHTML);
    }

    submitter.textContent = loadingText;
}

function restoreSubmitterLoadingText(submitter: HTMLElement): void {
    const originalMarkup = originalSubmitterMarkup.get(submitter);

    if (originalMarkup === undefined) {
        return;
    }

    submitter.innerHTML = originalMarkup;
    originalSubmitterMarkup.delete(submitter);
}

function updateProgressUi(form: HTMLFormElement, progress: number): void {
    const progressBar = form.querySelector('[data-formie-progress-bar]') as HTMLElement | null;
    const progressValue = form.querySelector('[data-formie-progress-value]') as HTMLElement | null;

    if (!progressBar) {
        return;
    }

    progressBar.style.width = `${progress}%`;
    progressBar.setAttribute('aria-valuenow', `${progress}`);
    progressBar.setAttribute('data-formie-progress-state', getProgressState(progress));

    if (progressValue) {
        progressValue.textContent = `${progress}%`;
        progressValue.setAttribute('data-formie-progress-value', `${progress}`);
    }
}

function applyCompletedProgressState(form: HTMLFormElement): void {
    updateProgressUi(form, 100);
}

function scheduleFinalSubmitReset(form: HTMLFormElement): void {
    clearPendingFinalSubmitReset(form);
    const preserveHiddenState = getConfiguredSubmitAction(form) === 'message' && shouldHideFormOnSuccess(form);

    if (DEFAULT_FINAL_SUBMIT_RESET_DELAY_MS < 1) {
        resetSubmissionState(form, { preserveHiddenState });
        return;
    }

    // Successful final submits often leave a transient success state visible.
    // Delay the reset so success messaging/progress can remain on screen briefly.
    const timerId = window.setTimeout(() => {
        finalSubmitResetTimers.delete(form);
        resetSubmissionState(form, { preserveHiddenState });
    }, DEFAULT_FINAL_SUBMIT_RESET_DELAY_MS);

    finalSubmitResetTimers.set(form, timerId);
}

function applySubmitterLoadingState(form: HTMLFormElement, submitter?: HTMLElement | null): void {
    if (!submitter) {
        return;
    }

    const indicator = (form.dataset.formieLoadingIndicator || '').trim();
    if (!indicator) {
        return;
    }

    submitter.setAttribute('data-formie-loading-indicator', indicator);

    if (indicator === 'spinner') {
        toggleThemeClasses(submitter, form, 'loading', true);
        restoreSubmitterLoadingText(submitter);
        submitter.removeAttribute('data-formie-loading-text');
        return;
    }

    if (indicator === 'text') {
        const configuredText = (form.dataset.formieLoadingIndicatorText || '').trim();
        const fallbackText = submitter.textContent?.trim() || '';
        const loadingText = configuredText || fallbackText;

        submitter.setAttribute('data-formie-loading-text', loadingText);
        setSubmitterLoadingText(submitter, loadingText);
        return;
    }

    restoreSubmitterLoadingText(submitter);
    submitter.removeAttribute('data-formie-loading-text');
}

function getActionButtons(form: HTMLFormElement): HTMLElement[] {
    return Array.from(form.querySelectorAll('[data-formie-action]')) as HTMLElement[];
}

export function setSubmitLoading(form: HTMLFormElement, submitter?: HTMLElement | null): void {
    if (form.getAttribute('data-formie-loading') === 'true') {
        return;
    }

    form.setAttribute('data-formie-loading', 'true');

    const buttons = getActionButtons(form);
    buttons.forEach((button) => {
        if ('disabled' in button) {
            // Remember pre-existing disabled buttons so clearSubmitLoading only
            // restores buttons that the browser client disabled for this submit cycle.
            if ((button as HTMLButtonElement).disabled) {
                button.setAttribute('data-formie-was-disabled', 'true');
            } else {
                button.removeAttribute('data-formie-was-disabled');
            }

            (button as HTMLButtonElement).disabled = true;
        }
    });

    if (submitter) {
        submitter.setAttribute('data-formie-loading', 'true');
        applySubmitterLoadingState(form, submitter);
    }
}

export function clearSubmitLoading(form: HTMLFormElement): void {
    form.removeAttribute('data-formie-loading');

    const buttons = getActionButtons(form);
    buttons.forEach((button) => {
        if ('disabled' in button) {
            const element = button as HTMLButtonElement;
            const wasDisabled = element.getAttribute('data-formie-was-disabled') === 'true';
            element.disabled = wasDisabled;
        }

        restoreSubmitterLoadingText(button);
        button.removeAttribute('data-formie-was-disabled');
        button.removeAttribute('data-formie-loading');
        toggleThemeClasses(button, form, 'loading', false);
        button.removeAttribute('data-formie-loading-indicator');
        button.removeAttribute('data-formie-loading-text');
    });

    if (form.dataset.formieDisableSubmitUntilValid === 'true') {
        const formWithValidation = form as FormWithValidationApi;

        if (formWithValidation.formieValidation) {
            syncSubmitReadiness(form, formWithValidation.formieValidation);
        }
    }
}

export function applyPageState(form: HTMLFormElement, nextPageId: string): void {
    const pages = getPageElements(form);
    const tabs = getTabElements(form);
    const currentPageIndex = pages.findIndex((page) => {
        return page.getAttribute('data-formie-page-id') === nextPageId;
    });

    pages.forEach((page) => {
        const pageId = page.getAttribute('data-formie-page-id');

        if (pageId === nextPageId) {
            page.removeAttribute('data-formie-page-hidden');
            removeThemeClasses(page, form, 'pageHidden');
        } else {
            page.setAttribute('data-formie-page-hidden', 'true');
            addThemeClasses(page, form, 'pageHidden');
        }
    });

    tabs.forEach((tab, index) => {
        const isCurrent = tab.getAttribute('data-formie-page-id') === nextPageId;
        const isComplete = currentPageIndex > -1 && index < currentPageIndex;

        toggleThemeClasses(tab, form, 'tabCurrent', isCurrent);
        toggleThemeClasses(tab, form, 'tabComplete', isComplete);

        const tabLink = tab.querySelector('[data-formie-tab-link]');

        if (tabLink) {
            toggleThemeClasses(tabLink, form, 'tabLinkCurrent', isCurrent);

            if (isCurrent) {
                removeThemeClasses(tabLink, form, 'tabLinkInactive');
            } else {
                addThemeClasses(tabLink, form, 'tabLinkInactive');
            }
        }

        if (isCurrent) {
            tab.setAttribute('aria-current', 'page');
        } else {
            tab.removeAttribute('aria-current');
        }

        if (isComplete) {
            tab.setAttribute('data-formie-tab-complete', 'true');
        } else {
            tab.removeAttribute('data-formie-tab-complete');
        }
    });

    if (currentPageIndex > -1 && pages.length > 0) {
        const progress = getProgressPercent(form, currentPageIndex, pages.length);
        updateProgressUi(form, progress);
    }

    // Keep the hidden continuity input in sync with the visible page so refreshes,
    // follow-up submits, and server recovery land on the same page.
    setHiddenInputValue(form, 'pageId', nextPageId);
    syncPageTabErrors(form);

    if (form.dataset.formieDisableSubmitUntilValid === 'true') {
        const formWithValidation = form as FormWithValidationApi;

        if (formWithValidation.formieValidation) {
            syncSubmitReadiness(form, formWithValidation.formieValidation);
        }
    }
}

function syncSubmissionIdentity(form: HTMLFormElement, result: FormSubmitResult): void {
    const submissionUid = result.meta?.submissionUid;
    if (typeof submissionUid === 'string' && submissionUid.trim() !== '') {
        setHiddenInputValue(form, 'submissionUid', submissionUid);
    }

    const continuationToken = (result.meta?.session as { continuation?: { continuationToken?: unknown } } | undefined)
        ?.continuation?.continuationToken;
    if (typeof continuationToken === 'string' && continuationToken.trim() !== '') {
        setHiddenInputValue(form, 'continuationToken', continuationToken);
    } else {
        removeHiddenInput(form, 'continuationToken');
    }
}

function clearResumeTokenState(form: HTMLFormElement): void {
    const action = form.getAttribute('action');

    if (action) {
        form.setAttribute('action', stripQueryParam(action, 'resumeToken'));
    }

    try {
        const url = new URL(window.location.href);

        if (!url.searchParams.has('resumeToken')) {
            return;
        }

        url.searchParams.delete('resumeToken');
        window.history.replaceState({}, document.title, `${url.pathname}${url.search}${url.hash}`);
    } catch {
        // Ignore malformed URLs and keep the user on the recovery path.
    }
}

function applyResumeTokenState(form: HTMLFormElement, result: FormSubmitResult): void {
    const resumeUrl = result.meta?.resumeUrl;

    if (typeof resumeUrl !== 'string' || resumeUrl.trim() === '') {
        return;
    }

    const normalizedResumeUrl = resumeUrl.trim();

    if (!isSameOriginUrl(normalizedResumeUrl)) {
        return;
    }

    const action = form.getAttribute('action');

    if (action) {
        form.setAttribute('action', normalizedResumeUrl);
    }

    // Save/resume updates both the form action and the browser URL so retries and
    // reloads continue through the newly-issued resume token.
    try {
        const url = new URL(normalizedResumeUrl, window.location.href);
        window.history.replaceState({}, document.title, `${url.pathname}${url.search}${url.hash}`);
    } catch {
        // Ignore malformed URLs and preserve the current state.
    }
}

function resetSubmissionState(form: HTMLFormElement, options: ResetSubmissionStateOptions = {}): void {
    const formWithValidationApi = form as FormWithValidationApi;
    const validator = formWithValidationApi.formieValidation;
    const firstPageId = getPageElements(form)[0]?.getAttribute('data-formie-page-id');

    clearPendingFinalSubmitReset(form);
    // A full state reset is broader than the native form reset: it also clears
    // hidden continuity inputs, resume-token state, page UI, and live validation.
    form.reset();
    if (!options.preserveHiddenState) {
        setFormHiddenState(form, false);
    }
    removeHiddenInput(form, 'submissionId');
    removeHiddenInput(form, 'submissionUid');
    removeHiddenInput(form, 'continuationToken');
    removeHiddenInput(form, 'pageId');
    clearResumeTokenState(form);
    validator?.resetLiveState();

    if (firstPageId) {
        applyPageState(form, firstPageId);
        form.dispatchEvent(new CustomEvent(getFormStateEventName('reset'), { bubbles: true }));
        return;
    }

    syncPageTabErrors(form);
    form.dispatchEvent(new CustomEvent(getFormStateEventName('reset'), { bubbles: true }));
}

function shouldResetSubmissionState(result: FormSubmitResult): boolean {
    return result.code === STALE_SUBMISSION_STATE_CODE || result.meta?.resetState === true;
}

function dispatchSubmitDataEvents(form: HTMLFormElement, result: FormSubmitResult): { hasPaymentFollowUpEvent: boolean } {
    const submitData = result.submitData;
    const dispatchedEvents = new Set<string>();
    let hasPaymentFollowUpEvent = false;

    if (Array.isArray(submitData) && submitData.length > 0) {
        const events = submitData.filter((item): item is { event: string; data?: unknown } =>
            typeof item === 'object' && item !== null && 'event' in item && typeof (item as { event?: unknown }).event === 'string'
        );

        for (const eventData of events) {
            const eventName = normalizeFormieEventName(eventData.event);
            dispatchedEvents.add(eventName);
            debug.log('Dispatching submitData event.', {
                eventName,
            });

            // Payment follow-up flows (3DS challenge, provider redirects, etc.)
            // should be handled by provider event listeners rather than the generic
            // redirect fallback in submit result handling.
            if (eventName.startsWith('formie:payment:')) {
                hasPaymentFollowUpEvent = true;
            }

            form.dispatchEvent(new CustomEvent(eventName, {
                bubbles: true,
                detail: { data: eventData.data },
            }));
        }
    }

    // Fallback: some payment action-required responses can carry action payloads
    // as `paymentAction`/`paymentDecision` metadata without submitData entries.
    const meta = (result.meta || {}) as Record<string, unknown>;
    const paymentAction = (
        (meta.paymentAction && typeof meta.paymentAction === 'object' ? meta.paymentAction : null)
        || (meta.paymentDecision && typeof meta.paymentDecision === 'object'
            ? ((meta.paymentDecision as Record<string, unknown>).action as Record<string, unknown> | null)
            : null)
    ) as Record<string, unknown> | null;

    const actionEvent = paymentAction ? String(paymentAction.event || '') : '';
    const actionPayload = paymentAction ? paymentAction.payload : undefined;
    const normalizedActionEvent = normalizeFormieEventName(actionEvent);

    if (normalizedActionEvent && !dispatchedEvents.has(normalizedActionEvent)) {
        if (normalizedActionEvent.startsWith('formie:payment:')) {
            hasPaymentFollowUpEvent = true;
        }

        form.dispatchEvent(new CustomEvent(normalizedActionEvent, {
            bubbles: true,
            detail: { data: actionPayload },
        }));
        debug.log('Dispatching fallback payment action event.', {
            eventName: normalizedActionEvent,
        });
    }

    return { hasPaymentFollowUpEvent };
}

export function applySubmitResultState(form: HTMLFormElement, result: FormSubmitResult, action: FormAction): void {
    debug.log('Applying submit result state.', {
        ok: result.ok,
        action,
        code: result.code,
        hasRedirect: !!result.redirect?.url,
        hasSubmitData: Array.isArray(result.submitData) && result.submitData.length > 0,
    });
    if (shouldResetSubmissionState(result)) {
        resetSubmissionState(form);
        debug.log('Resetting state due to stale/reset marker.');
        return;
    }

    // Dispatch payment/redirect events from submitData (e.g. formie:payment:mollie:redirect)
    const eventDispatchResult = dispatchSubmitDataEvents(form, result);

    // Fallback: redirect when backend returns redirectUrl for payment (e.g. Mollie, GoCardless)
    if (!result.ok && result.redirect?.url && !eventDispatchResult.hasPaymentFollowUpEvent) {
        debug.log('Applying redirect fallback for failed result.', {
            url: result.redirect.url,
            target: result.redirect.target,
        });
        clearPendingFinalSubmitReset(form);
        if (result.redirect.target === 'new-tab') {
            window.open(result.redirect.url, '_blank');
        } else {
            markInternalNavigation(form, 'redirect');
            window.location.href = result.redirect.url;
        }
        return;
    }

    syncSubmissionIdentity(form, result);

    if (!result.ok) {
        debug.log('Non-redirect failure; keeping current form state.');
        clearPendingFinalSubmitReset(form);
        return;
    }

    if (Array.isArray(result.clientEvents) && result.clientEvents.length > 0) {
        dispatchResolvedClientEvents(form, result.clientEvents);
    } else {
        dispatchPageClientEventForSubmit(form, action);
    }

    if (result.nextPage?.id) {
        // Advancing pages is treated as a fresh validation cycle for the next page.
        clearPendingFinalSubmitReset(form);
        const formWithValidationApi = form as FormWithValidationApi;
        const validator = formWithValidationApi.formieValidation;
        validator?.resetLiveState();
        applyPageState(form, result.nextPage.id);
        dispatchFormieDomEvent(form, 'formie:page:navigate:after', {
            pageId: result.nextPage.id,
        });
        debug.log('Advanced to next page.', {
            nextPageId: result.nextPage.id,
        });
        return;
    }

    if (action === 'save') {
        clearPendingFinalSubmitReset(form);
        applyResumeTokenState(form, result);
        debug.log('Applied save/resume token state.');
        return;
    }

    if (action === 'submit' && !result.redirect?.url) {
        const configuredSubmitAction = getResolvedSubmitAction(form, result);
        const preserveHiddenState = configuredSubmitAction === 'message' && shouldHideFormOnSuccess(form);

        if (configuredSubmitAction === 'reload') {
            clearPendingFinalSubmitReset(form);
            markInternalNavigation(form, 'reload');
            window.location.reload();
            return;
        }

        if (configuredSubmitAction === 'reset') {
            resetSubmissionState(form);
            return;
        }

        // Always reset values/state after a successful terminal AJAX submit so a
        // fresh interaction starts from canonical empty state.
        clearPendingFinalSubmitReset(form);
        resetSubmissionState(form, { preserveHiddenState });
        return;
    }

    if (action === 'submit' && result.redirect?.url && result.redirect.target === 'new-tab') {
        const configuredSubmitAction = getResolvedSubmitAction(form, result);
        const preserveHiddenState = configuredSubmitAction === 'message' && shouldHideFormOnSuccess(form);
        clearPendingFinalSubmitReset(form);
        resetSubmissionState(form, { preserveHiddenState });
        return;
    }

    clearPendingFinalSubmitReset(form);
}
