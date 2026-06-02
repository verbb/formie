import type {
    FormieClient,
    FormieFormInstance,
    FormMountOptions,
} from '#contracts/client';
import { bindLegacyDomEventCompatibility } from '#compatibility/dom-adapter';
import { resolveLegacyCompatibilityOptions } from '#compatibility/event-map';
import { bindLegacyValidatorCompatibility } from '#compatibility/validator-adapter';
import type { FormAction, FormMode, FormTransport } from '#contracts/common';
import type { FormieModuleDefinition, FormieModuleInstance } from '#contracts/modules';
import type { FormEndpointPayload, FormModuleManifest, FormSubmitResult } from '#contracts/schema';
import type { ThemeClassMap } from '#contracts/theme';
import { dispatchFormieDomEvent } from '#core/dom-events';
import { dispatchPageClientEventForSubmit } from '#core/page-client-event';
import { syncPageTabErrors } from '#core/page-tab-errors';
import { clearSubmitFeedback, executeAjaxSubmitFlow, shouldKeepSubmitLoading } from '#core/submit-flow';
import { applySubmitResultUi } from '#core/submit-result-ui';
import { applyPageState, clearSubmitLoading, setSubmitLoading } from '#core/submit-result-state';
import { EventBus } from '#events/event-bus';
import { ModuleRegistry } from '#modules/registry';
import { loadModulesFromManifest } from '#modules/loader';
import { registerThemeClassMap } from '#theme/theme-classes';
import { runSubmitPipeline } from '#submit/pipeline';
import { clearSubmissionOnUnload, requestGraphqlRender, requestRefreshTokens, requestRender, requestSetPage } from '#transport/forms-api';
import { createDebug } from '#utils/debug';
import { createFormUnloadWarningGuard } from '#utils/unload-warning';
import { FormieValidator } from '#validation/validator';

type InternalInstanceState = {
    options: FormMountOptions;
    bus: EventBus;
    form: HTMLFormElement | null;
    validator: FormieValidator | null;
    modules: FormieModuleInstance[];
    unbinds: Array<() => void>;
    instance: FormieFormInstance;
};

type FormWithValidationApi = HTMLFormElement & {
    formieValidation?: FormieValidator;
};

const ROOT_SELECTORS = '[data-formie]:not([data-formie-init="false"]), [data-formie-form]:not([data-formie-init="false"])';
const DEFAULT_SUBMIT_DELAY_MS = 300;
const DEFAULT_HEADLESS_RENDER_ACTION = '/actions/formie/server/forms/render';
const DEFAULT_HEADLESS_GRAPHQL_ENDPOINT = '/api';
const DEFAULT_HEADLESS_REFRESH_TOKENS_ACTION = '/actions/formie/server/forms/refresh-tokens';
const DEFAULT_HEADLESS_SUBMIT_ACTION = '/actions/formie/server/submissions/submit';
const DEFAULT_HEADLESS_SET_PAGE_ACTION = '/actions/formie/server/submissions/set-page';
const DEFAULT_HEADLESS_CLEAR_SUBMISSION_ACTION = '/actions/formie/server/submissions/clear-submission';
const DEFAULT_FILE_UPLOAD_HYDRATE_ACTION = '/actions/formie/file-upload/hydrate';
const debug = createDebug('general', 'client');
const compatibilityWarnings = new Set<string>();

function parseBooleanOption(value: string | undefined, defaultValue: boolean): boolean {
    if (value == null || value === '') {
        return defaultValue;
    }

    const normalized = value.toLowerCase();

    return !(normalized === 'false' || normalized === '0' || normalized === 'off');
}

function inferStaticCacheOnLoadFromDataset(dataset: DOMStringMap): boolean {
    if (dataset.formieRefreshTokens != null && dataset.formieRefreshTokens !== '') {
        return parseBooleanOption(dataset.formieRefreshTokens, false);
    }

    if (dataset.formieStaticCache != null && dataset.formieStaticCache !== '') {
        return parseBooleanOption(dataset.formieStaticCache, false);
    }

    return false;
}

function inferOptionsFromElement(target: Element): FormMountOptions {
    const dataset = target instanceof HTMLElement ? target.dataset : ({} as DOMStringMap);

    return {
        mode: 'server-rendered',
        transport: (dataset.formieTransport as FormTransport) || 'rest',
        formHandle: dataset.formieHandle,
        endpoint: dataset.formieEndpoint,
        staticCache: inferStaticCacheOnLoadFromDataset(dataset),
        autoVisible: parseBooleanOption(dataset.formieAutoVisible, true),
        compatibility: parseBooleanOption(dataset.formieCompatibility, false),
    };
}

function normalizeMode(mode: FormMountOptions['mode'] | undefined): FormMode {
    return mode || 'server-rendered';
}

function normalizeTransport(transport: FormMountOptions['transport'] | undefined): FormTransport {
    return transport || 'rest';
}

function getFormFromTarget(target: Element): HTMLFormElement | null {
    if (target instanceof HTMLFormElement) {
        return target;
    }

    return target.querySelector('form');
}

function warnCompatibilityOnce(key: string, message: string): void {
    if (compatibilityWarnings.has(key)) {
        return;
    }

    compatibilityWarnings.add(key);
    debug.warn(message);
}

function resolveEndpointAgainstBase(endpoint: string, baseEndpoint?: string): string {
    if (!endpoint) {
        return endpoint;
    }

    // Absolute endpoint stays untouched.
    try {
        return new URL(endpoint).toString();
    } catch (_error) {
        // fall through
    }

    if (!baseEndpoint) {
        return endpoint;
    }

    try {
        return new URL(endpoint, baseEndpoint).toString();
    } catch (_error) {
        return endpoint;
    }
}

function resolveHeadlessEndpoint(baseOrEndpoint: string | undefined, actionPath: string): string {
    const candidate = (baseOrEndpoint || '').trim();

    if (!candidate) {
        return actionPath;
    }

    if (candidate.includes('/actions/')) {
        return candidate;
    }

    return resolveEndpointAgainstBase(actionPath, candidate);
}

function resolveHtmlRenderEndpoint(options: FormMountOptions, target: Element): string {
    return resolveHeadlessEndpoint(options.endpoint || (target as HTMLElement).dataset.formieEndpoint, DEFAULT_HEADLESS_RENDER_ACTION);
}

function resolveGraphqlEndpoint(options: FormMountOptions, target: Element): string {
    const candidate = (options.endpoint || (target as HTMLElement).dataset.formieEndpoint || '').trim();

    if (!candidate) {
        return DEFAULT_HEADLESS_GRAPHQL_ENDPOINT;
    }

    if (candidate.includes('/graphql') || candidate.endsWith('/api') || candidate.includes('/actions/graphql/')) {
        return candidate;
    }

    return resolveEndpointAgainstBase(DEFAULT_HEADLESS_GRAPHQL_ENDPOINT, candidate);
}

function resolveRefreshTokensEndpoint(options: FormMountOptions, target: Element): string {
    return resolveHeadlessEndpoint(
        (target as HTMLElement).dataset.formieRefreshTokensEndpoint || options.endpoint || (target as HTMLElement).dataset.formieEndpoint,
        DEFAULT_HEADLESS_REFRESH_TOKENS_ACTION,
    );
}

function mergeSearchParams(sourceUrl: string | null, destinationUrl: string): string {
    if (!sourceUrl) {
        return destinationUrl;
    }

    try {
        const source = new URL(sourceUrl, window.location.origin);
        const destination = new URL(destinationUrl, window.location.origin);

        source.searchParams.forEach((value, key) => {
            if (!destination.searchParams.has(key)) {
                destination.searchParams.set(key, value);
            }
        });

        return destination.toString();
    } catch (_error) {
        return destinationUrl;
    }
}

function normalizeHeadlessManagedUrls(target: Element, form: HTMLFormElement, options: FormMountOptions): void {
    const baseEndpoint = options.endpoint || (target as HTMLElement).dataset.formieEndpoint;
    const submitAction = resolveHeadlessEndpoint(baseEndpoint, DEFAULT_HEADLESS_SUBMIT_ACTION);
    const existingAction = form.getAttribute('action');

    form.setAttribute('action', mergeSearchParams(existingAction, submitAction));

    form.querySelectorAll<HTMLAnchorElement>('[data-formie-tab-link]').forEach((link) => {
        const existingHref = link.getAttribute('href');
        const setPageEndpoint = resolveHeadlessEndpoint(baseEndpoint, DEFAULT_HEADLESS_SET_PAGE_ACTION);

        link.setAttribute('href', mergeSearchParams(existingHref, setPageEndpoint));
    });

    form.querySelectorAll<HTMLElement>('[data-formie-file-upload-hydrate-endpoint]').forEach((input) => {
        input.setAttribute(
            'data-formie-file-upload-hydrate-endpoint',
            resolveHeadlessEndpoint(baseEndpoint, DEFAULT_FILE_UPLOAD_HYDRATE_ACTION),
        );
    });
}

function ensureSupportedHeadlessTransport(transport: FormTransport, mode: FormMode): void {
    if (transport === 'graphql' && mode !== 'server-rendered') {
        throw new Error(`Formie ${mode} mode does not support GraphQL transport yet.`);
    }
}

function parseBooleanDatasetValue(value: string | undefined): boolean {
    if (value == null) {
        return false;
    }

    const normalized = value.trim().toLowerCase();
    return normalized === 'true' || normalized === '1' || normalized === '';
}

function hasAutomaticSubmissionState(form: HTMLFormElement): boolean {
    return parseBooleanOption(form.dataset.formieAutomaticSubmissionState, true);
}

function resolveClearSubmissionEndpoint(options: FormMountOptions, target: Element, form: HTMLFormElement): string {
    return resolveHeadlessEndpoint(
        form.dataset.formieClearSubmissionEndpoint || options.endpoint || (target as HTMLElement).dataset.formieEndpoint,
        DEFAULT_HEADLESS_CLEAR_SUBMISSION_ACTION,
    );
}

function shouldEnableUnloadWarning(form: HTMLFormElement): boolean {
    return parseBooleanDatasetValue(form.dataset.formieUnloadWarning);
}

function markInternalNavigation(form: HTMLFormElement, reason: string): void {
    form.setAttribute('data-formie-internal-navigation', reason);
}

function clearInternalNavigation(form: HTMLFormElement): void {
    form.removeAttribute('data-formie-internal-navigation');
}

function hasInternalNavigation(form: HTMLFormElement): boolean {
    return form.getAttribute('data-formie-internal-navigation') !== null;
}

function urlHasSearchParam(sourceUrl: string | null, param: string): boolean {
    if (!sourceUrl) {
        return false;
    }

    try {
        return new URL(sourceUrl, window.location.origin).searchParams.has(param);
    } catch (_error) {
        return false;
    }
}

function formHasResumeTokenState(form: HTMLFormElement): boolean {
    return urlHasSearchParam(window.location.href, 'resumeToken')
        || urlHasSearchParam(form.getAttribute('action'), 'resumeToken');
}

function isSameTabClickEvent(event: Event): boolean {
    if (!(event instanceof MouseEvent)) {
        return true;
    }

    return event.button === 0 && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey;
}

function parseIntegerDatasetValue(value: string | undefined, fallback = 0): number {
    if (!value) {
        return fallback;
    }

    const parsed = Number.parseInt(value, 10);

    if (!Number.isFinite(parsed)) {
        return fallback;
    }

    return parsed;
}

function getSubmitDelayMs(form: HTMLFormElement): number {
    return Math.max(0, parseIntegerDatasetValue(form.dataset.formieSubmitDelay, DEFAULT_SUBMIT_DELAY_MS));
}

function shouldValidateOnSubmit(form: HTMLFormElement): boolean {
    return parseBooleanDatasetValue(form.dataset.formieValidationOnSubmit);
}

async function waitForSubmitDelay(form: HTMLFormElement): Promise<void> {
    const delay = getSubmitDelayMs(form);

    if (delay < 1) {
        return;
    }

    await new Promise((resolve) => {
        window.setTimeout(resolve, delay);
    });
}

function parseJsonAttribute<T>(element: Element | null, attributeName: string): T | null {
    const rawValue = element?.getAttribute(attributeName)?.trim();

    if (!rawValue) {
        return null;
    }

    try {
        return JSON.parse(rawValue) as T;
    } catch (error) {
        console.error(`[formie] Failed to parse ${attributeName}.`, error);
        return null;
    }
}

function getEmbeddedPayload(target: Element, form: HTMLFormElement | null): FormEndpointPayload | null {
    const payloadRoot = form || (target instanceof HTMLFormElement ? target : null);

    if (!payloadRoot) {
        return null;
    }

    // Server-rendered forms carry the same minimal payload shape that endpoint-
    // rendered forms receive, which keeps mount behavior identical in both flows.
    const modules = parseJsonAttribute<FormEndpointPayload['modules']>(payloadRoot, 'data-formie-modules');
    const theme = parseJsonAttribute<FormEndpointPayload['theme']>(payloadRoot, 'data-formie-theme');

    if (!modules && !theme) {
        return null;
    }

    return {
        modules: modules || undefined,
        theme: theme || undefined,
    };
}

function isElementVisible(target: Element): boolean {
    if (!(target instanceof HTMLElement)) {
        return true;
    }

    if (!target.isConnected) {
        return false;
    }

    if (target.hidden || target.closest('[hidden]')) {
        return false;
    }

    const style = window.getComputedStyle(target);
    if (style.display === 'none' || style.visibility === 'hidden') {
        return false;
    }

    return target.getClientRects().length > 0;
}

function isWithinScope(target: Element, scope: ParentNode): boolean {
    if (scope === document) {
        return true;
    }

    if (scope instanceof Element) {
        return scope === target || scope.contains(target);
    }

    return true;
}

function getTargetDebugLabel(target: Element): string {
    const element = target as HTMLElement;
    const id = element.id ? `#${element.id}` : '';
    const handle = element.dataset?.formieHandle ? `[handle="${element.dataset.formieHandle}"]` : '';
    const tag = element.tagName ? element.tagName.toLowerCase() : 'element';
    return `${tag}${id}${handle}`;
}

function applyRefreshTokensToForm(form: HTMLFormElement, refreshTokens: FormEndpointPayload['refreshTokens'] | null | undefined): void {
    if (!refreshTokens) {
        return;
    }

    // Refresh-tokens mutates continuity inputs in place so cached or long-lived
    // forms keep submitting with fresh CSRF/request/captcha state.
    if (refreshTokens.csrf?.param && refreshTokens.csrf?.token) {
        const csrfInput = form.querySelector(`input[name="${refreshTokens.csrf.param}"]`) as HTMLInputElement | null;
        if (csrfInput) {
            csrfInput.value = refreshTokens.csrf.token;
        }
    }

    if (refreshTokens.requestToken) {
        const requestTokenInput = form.querySelector('input[name="requestToken"]') as HTMLInputElement | null;
        if (requestTokenInput) {
            requestTokenInput.value = refreshTokens.requestToken;
        }
    }

    if (refreshTokens.renderId) {
        const renderIdInput = form.querySelector('input[name="renderId"]') as HTMLInputElement | null;
        if (renderIdInput) {
            renderIdInput.value = refreshTokens.renderId;
        }
    }

    if (refreshTokens.captchas && typeof refreshTokens.captchas === 'object') {
        Object.values(refreshTokens.captchas).forEach((captchaEntry) => {
            if (!captchaEntry || typeof captchaEntry !== 'object') {
                return;
            }

            const entry = captchaEntry as { sessionKey?: string; value?: string };
            if (!entry.sessionKey) {
                return;
            }

            const captchaInput = form.querySelector(`input[name="${entry.sessionKey}"]`) as HTMLInputElement | null;
            if (captchaInput && typeof entry.value === 'string') {
                captchaInput.value = entry.value;
            }
        });
    }
}

async function ensureHtmlRender(target: Element, options: FormMountOptions): Promise<FormEndpointPayload | null> {
    const mode = normalizeMode(options.mode);
    const transport = normalizeTransport(options.transport);

    if (mode !== 'server-rendered') {
        return null;
    }

    if (options.payload) {
        if (options.payload.html) {
            (target as HTMLElement).innerHTML = options.payload.html;
        }

        return options.payload;
    }

    ensureSupportedHeadlessTransport(transport, mode);

    const hasForm = !!getFormFromTarget(target);
    const formHandle = options.formHandle || (target as HTMLElement).dataset.formieHandle;

    // Server-rendered forms only render here when the host surface is just a mount shell.
    // Pre-rendered forms skip this and continue through the normal mount path.
    if (hasForm || !formHandle) {
        return null;
    }

    const renderOptions = {
        mode,
        endpoint: options.endpoint,
        locale: options.locale,
        siteId: options.siteId,
        theme: options.theme,
        themeConfig: options.themeConfig,
    };
    const endpoint = transport === 'graphql'
        ? resolveGraphqlEndpoint(options, target)
        : resolveHtmlRenderEndpoint(options, target);
    const payload = transport === 'graphql'
        ? await requestGraphqlRender(endpoint, formHandle, renderOptions)
        : await requestRender(endpoint, formHandle, {
            ...renderOptions,
            endpoint,
        });

    if (payload?.html) {
        (target as HTMLElement).innerHTML = payload.html;
    }

    return payload;
}

async function refreshTokensAfterSubmitIfNeeded(target: Element, options: FormMountOptions, form: HTMLFormElement): Promise<void> {
    if (options.refreshTokens === false) {
        return;
    }

    ensureSupportedHeadlessTransport(normalizeTransport(options.transport), normalizeMode(options.mode));

    const formHandle = options.formHandle || (target as HTMLElement).dataset.formieHandle;
    if (!formHandle) {
        return;
    }

    // Refresh-tokens happens after most submit outcomes so subsequent page moves,
    // retries, or repeated submits reuse fresh request/captcha tokens.
    const endpoint = resolveRefreshTokensEndpoint(options, target);
    const renderIdInput = form.querySelector('input[name="renderId"]') as HTMLInputElement | null;
    const renderId = renderIdInput?.value || undefined;
    const refreshTokens = await requestRefreshTokens(endpoint, formHandle, renderId);
    applyRefreshTokensToForm(form, refreshTokens);
    dispatchFormieDomEvent(target, 'formie:refresh-tokens:refreshed', refreshTokens);
}

function bindFormEvents(
    target: Element,
    form: HTMLFormElement,
    options: FormMountOptions,
    bus: EventBus,
    validator: FormieValidator | null,
    unbinds: Array<() => void>,
): void {
    const submitMethod = String(
        form.dataset.formieSubmitMethod || '',
    ).trim().toLowerCase();
    const clearSubmissionEndpoint = resolveClearSubmissionEndpoint(options, target, form);
    let allowNativeSubmit = false;
    const submitButtons = form.querySelectorAll('[data-formie-action]');
    const setPendingAction = (action: FormAction | null) => {
        if (action) {
            // Store the last explicit button action so keyboard submits and synthetic
            // submits still preserve back/save intent when the submitter is absent.
            form.setAttribute('data-formie-pending-action', action);
            return;
        }

        form.removeAttribute('data-formie-pending-action');
    };

    if (shouldEnableUnloadWarning(form)) {
        const unloadWarning = createFormUnloadWarningGuard(form, {
            shouldWarn: () => {
                return !hasInternalNavigation(form);
            },
        });
        const handleSubmitResult = (event: Event): void => {
            if (!(event instanceof CustomEvent)) {
                return;
            }

            const result = event.detail as FormSubmitResult | null;

            if (!result?.ok) {
                return;
            }

            if (result.action === 'save') {
                unloadWarning.scheduleBaselineCapture();
            }
        };
        const handleStateReset = (): void => {
            unloadWarning.scheduleBaselineCapture();
        };

        target.addEventListener('formie:submit:result', handleSubmitResult as EventListener);
        form.addEventListener('formie:state:reset', handleStateReset as EventListener);
        unbinds.push(() => {
            target.removeEventListener('formie:submit:result', handleSubmitResult as EventListener);
            form.removeEventListener('formie:state:reset', handleStateReset as EventListener);
            unloadWarning.destroy();
        });
    }

    submitButtons.forEach((button) => {
        const handler = (event: Event) => {
            const action = (event.currentTarget as HTMLElement).getAttribute('data-formie-action') as FormAction | null;
            const submitAction = form.querySelector('input[name="submitAction"]') as HTMLInputElement | null;

            setPendingAction(action);

            if (action && submitAction) {
                submitAction.value = action;
            }
        };

        button.addEventListener('click', handler);
        unbinds.push(() => {
            button.removeEventListener('click', handler);
        });
    });

    const pageTabLinks = form.querySelectorAll('[data-formie-tab-link]');

    pageTabLinks.forEach((link) => {
        const handler = async(event: Event) => {
            if (submitMethod !== 'ajax') {
                if (isSameTabClickEvent(event)) {
                    markInternalNavigation(form, 'set-page');
                }

                return;
            }

            // Ajax multipage navigation updates UI immediately, then persists page
            // continuity in the background so refresh/recovery stays in sync.
            event.preventDefault();

            const currentTarget = event.currentTarget as HTMLAnchorElement | null;
            const nextPageId = currentTarget?.getAttribute('data-formie-page-id');
            const href = currentTarget?.getAttribute('href');

            if (!nextPageId || !href) {
                return;
            }

            applyPageState(form, nextPageId);
            dispatchFormieDomEvent(target, 'formie:page:navigate', {
                pageId: nextPageId,
                href,
            });

            try {
                const response = await requestSetPage(href, form, nextPageId);

                dispatchFormieDomEvent(target, 'formie:page:navigate:after', {
                    pageId: nextPageId,
                    href,
                    response,
                });
            } catch (error) {
                console.error('[formie] Failed to persist page navigation state.', error);
                dispatchFormieDomEvent(target, 'formie:page:navigate:error', {
                    pageId: nextPageId,
                    href,
                    error,
                });
            }
        };

        link.addEventListener('click', handler);
        unbinds.push(() => {
            link.removeEventListener('click', handler);
        });
    });

    if (!hasAutomaticSubmissionState(form)) {
        let requestedClearOnLeave = false;
        const leaveHandler = () => {
            if (requestedClearOnLeave || hasInternalNavigation(form) || formHasResumeTokenState(form)) {
                return;
            }

            requestedClearOnLeave = true;
            clearSubmissionOnUnload(clearSubmissionEndpoint, form);
        };

        window.addEventListener('pagehide', leaveHandler);
        window.addEventListener('beforeunload', leaveHandler);
        unbinds.push(() => {
            window.removeEventListener('pagehide', leaveHandler);
            window.removeEventListener('beforeunload', leaveHandler);
        });
    }

    const submitHandler = async(event: Event) => {
        if (allowNativeSubmit) {
            return;
        }

        const isAjaxSubmit = submitMethod === 'ajax';
        if (!isAjaxSubmit) {
            event.preventDefault();
        } else {
            event.preventDefault();
        }

        // Loading state guards the whole submit pipeline, including async module
        // hooks, so duplicate native submits cannot race each other.
        if (form.getAttribute('data-formie-loading') === 'true') {
            const isInternalResubmit = form.getAttribute('data-formie-internal-resubmit') === 'true';

            if (!isInternalResubmit) {
                return;
            }

            form.removeAttribute('data-formie-internal-resubmit');
        } else {
            form.removeAttribute('data-formie-internal-resubmit');
        }

        const submitEvent = event as SubmitEvent;
        const submitter = submitEvent.submitter as HTMLElement | null;
        const actionFromSubmitter = submitter?.getAttribute('data-formie-action') as FormAction | null;
        const pendingAction = form.getAttribute('data-formie-pending-action') as FormAction | null;

        const submitAction = form.querySelector('input[name="submitAction"]') as HTMLInputElement | null;
        const action = actionFromSubmitter || pendingAction || (submitAction?.value as FormAction) || 'submit';
        let result: FormSubmitResult | null = null;
        let nativeSubmitStarted = false;

        try {
            if (isAjaxSubmit) {
                result = await executeAjaxSubmitFlow({
                    target,
                    form,
                    bus,
                    validator,
                    validateOnSubmit: shouldValidateOnSubmit(form),
                    action,
                    submitter,
                    waitForSubmitDelay,
                    onRefreshTokensAfterSubmit: async() => {
                        await refreshTokensAfterSubmitIfNeeded(target, options, form);
                    },
                    dispatchSubmitResult: (submitResult) => {
                        dispatchFormieDomEvent(target, 'formie:submit:result', submitResult);
                    },
                });
            } else {
                clearSubmitFeedback(form);
                setSubmitLoading(form, submitter);
                await waitForSubmitDelay(form);
                result = await runSubmitPipeline(form, action, bus, {
                    validator,
                    validateOnSubmit: shouldValidateOnSubmit(form),
                    preflightOnly: true,
                });

                if (result.ok) {
                    dispatchPageClientEventForSubmit(form, action);
                    allowNativeSubmit = true;
                    markInternalNavigation(form, 'submit');
                    setPendingAction(null);

                    let nativeValidationFailed = false;
                    const nativeInvalidHandler = () => {
                        nativeValidationFailed = true;
                        allowNativeSubmit = false;
                        clearInternalNavigation(form);
                        clearSubmitLoading(form);
                    };

                    if (typeof form.requestSubmit === 'function') {
                        // Keep the existing loading/disabled state intact while the
                        // browser performs the final native validation and navigation.
                        // The hidden submitAction already carries the clicked action.
                        form.addEventListener('invalid', nativeInvalidHandler, true);
                        try {
                            form.requestSubmit();
                        } finally {
                            form.removeEventListener('invalid', nativeInvalidHandler, true);
                        }
                    } else {
                        form.submit();
                    }

                    if (nativeValidationFailed) {
                        return;
                    }

                    nativeSubmitStarted = true;
                    return;
                }

                applySubmitResultUi(form, result);
                dispatchFormieDomEvent(target, 'formie:submit:result', result);
                clearInternalNavigation(form);
            }
        } catch (error) {
            allowNativeSubmit = false;
            result = {
                ok: false,
                code: 'SUBMIT_ERROR',
                message: error instanceof Error ? error.message : 'Submission failed.',
                formErrors: [error instanceof Error ? error.message : 'Submission failed.'],
            };
            applySubmitResultUi(form, result);
            dispatchFormieDomEvent(target, 'formie:submit:result', result);
            clearInternalNavigation(form);
        } finally {
            setPendingAction(null);
            if (!isAjaxSubmit && !nativeSubmitStarted && !shouldKeepSubmitLoading(result)) {
                clearSubmitLoading(form);
            }
        }
    };

    form.addEventListener('submit', submitHandler);
    unbinds.push(() => {
        form.removeEventListener('submit', submitHandler);
    });
}

async function refreshTokensIfNeeded(target: Element, options: FormMountOptions, form: HTMLFormElement | null): Promise<void> {
    if (options.refreshTokens === false) {
        return;
    }

    if (!options.staticCache) {
        return;
    }

    ensureSupportedHeadlessTransport(normalizeTransport(options.transport), normalizeMode(options.mode));

    const formHandle = options.formHandle || (target as HTMLElement).dataset.formieHandle;
    const endpoint = resolveRefreshTokensEndpoint(options, target);
    const renderIdInput = form?.querySelector('input[name="renderId"]') as HTMLInputElement | null;
    const renderId = renderIdInput?.value || undefined;

    if (!formHandle) {
        return;
    }

    // Refresh-tokens-before-ready is the cache-safe path: forms can render from SSR/cache
    // and still receive fresh transport tokens before the user submits anything.
    const refreshTokens = await requestRefreshTokens(endpoint, formHandle, renderId);

    if (!refreshTokens || !form) {
        return;
    }

    applyRefreshTokensToForm(form, refreshTokens);

    dispatchFormieDomEvent(target, 'formie:refresh-tokens:after', refreshTokens);
}

export function createFormieClient(): FormieClient {
    const instances = new Map<Element, InternalInstanceState>();
    const moduleRegistry = new ModuleRegistry();
    const pendingVisibilityMounts = new Map<Element, () => void>();
    const pendingUnmounts = new Map<Element, Promise<void>>();
    const stageNames: Array<'prepare' | 'normalize' | 'validate' | 'screen' | 'authorize' | 'dispatch' | 'finalize'> = [
        'prepare',
        'normalize',
        'validate',
        'screen',
        'authorize',
        'dispatch',
        'finalize',
    ];

    const unmount = async(target: Element): Promise<void> => {
        const inFlightUnmount = pendingUnmounts.get(target);
        if (inFlightUnmount) {
            await inFlightUnmount;
            return;
        }

        const unmountPromise = (async() => {
        debug.log('Unmount requested.', { target: getTargetDebugLabel(target) });
        const pendingUnmount = pendingVisibilityMounts.get(target);
        if (pendingUnmount) {
            pendingUnmount();
            pendingVisibilityMounts.delete(target);
        }

        const state = instances.get(target);

        if (!state) {
            debug.log('Unmount skipped (no mounted state).', { target: getTargetDebugLabel(target) });
            return;
        }

        dispatchFormieDomEvent(target, 'formie:unmount:before', {
            id: state.instance.id,
        });

        state.unbinds.forEach((unbind) => {
            unbind();
        });
        state.unbinds = [];

        state.validator?.destroy();
        state.validator = null;

        for (const moduleInstance of state.modules) {
            await moduleInstance.destroy();
        }
        state.modules = [];

        state.bus.clear();
        instances.delete(target);

        dispatchFormieDomEvent(target, 'formie:unmount:after', {
            id: state.instance.id,
        });
        debug.log('Unmount complete.', { id: state.instance.id, target: getTargetDebugLabel(target) });
        })().finally(() => {
            pendingUnmounts.delete(target);
        });

        pendingUnmounts.set(target, unmountPromise);
        await unmountPromise;
    };

    const mount = async(target: Element, options: FormMountOptions): Promise<FormieFormInstance> => {
        debug.log('Mount requested.', {
            target: getTargetDebugLabel(target),
            mode: options.mode,
            autoVisible: options.autoVisible,
        });
        const pendingMount = pendingVisibilityMounts.get(target);
        if (pendingMount) {
            pendingMount();
            pendingVisibilityMounts.delete(target);
        }

        const existing = instances.get(target);

        if (existing) {
            debug.log('Mount skipped (already mounted).', {
                id: existing.instance.id,
                target: getTargetDebugLabel(target),
            });
            return existing.instance;
        }

        const bus = new EventBus();
        const unbinds: Array<() => void> = [];
        const id = (target as HTMLElement)?.id || `formie-${instances.size + 1}`;
        const mergedFromDom = inferOptionsFromElement(target);
        const normalizedOptions: FormMountOptions = {
            ...mergedFromDom,
            ...options,
            mode: normalizeMode(options.mode ?? mergedFromDom.mode),
            transport: normalizeTransport(options.transport ?? mergedFromDom.transport),
        };
        const compatibilityOptions = resolveLegacyCompatibilityOptions(normalizedOptions.compatibility);

        if (normalizedOptions.mode !== 'server-rendered' && !getFormFromTarget(target)) {
            throw new Error(`Formie ${normalizedOptions.mode} mode is not implemented yet in the browser client.`);
        }

        const renderPayload = await ensureHtmlRender(target, normalizedOptions);
        const form = getFormFromTarget(target);
        normalizedOptions.staticCache =
            options.staticCache ??
            (form
                ? inferStaticCacheOnLoadFromDataset(form.dataset)
                : inferStaticCacheOnLoadFromDataset((target as HTMLElement).dataset));
        const embeddedPayload = getEmbeddedPayload(target, form);
        const payload = renderPayload || embeddedPayload
            ? {
                ...(renderPayload || {}),
                ...(embeddedPayload || {}),
            }
            : null;
        const themeClassMap = payload?.theme as ThemeClassMap | undefined;
        const stateStore: Record<string, unknown> = {};
        const moduleManifest = ((payload?.modules || []) as FormModuleManifest[]).filter((item) => {
            return !!item?.id && !!item?.type;
        });
        debug.log('Resolved mount payload.', {
            target: getTargetDebugLabel(target),
            hasRenderPayload: !!renderPayload,
            hasEmbeddedPayload: !!embeddedPayload,
            moduleCount: moduleManifest.length,
        });
        const resolvedThemeClassMap = registerThemeClassMap(target, themeClassMap, form);

        const validator = form ? new FormieValidator(form, {
            live: parseBooleanDatasetValue(form.dataset.formieValidationOnFocus),
            errorMessage: form.dataset.formieErrorMessage || '',
            fieldContainerErrorClass: resolvedThemeClassMap.fieldLayoutError || [],
            inputErrorClass: resolvedThemeClassMap.fieldControlError || [],
            messagesClass: resolvedThemeClassMap.fieldErrors || [],
            messageClass: resolvedThemeClassMap.fieldError || [],
        }) : null;

        if (form && validator) {
            const formWithValidationApi = form as FormWithValidationApi;
            formWithValidationApi.formieValidation = validator;
            // Preserve the validator on the form element for browser helpers that
            // only receive DOM references during later submit/result transitions.
            stateStore.validation = validator;

            const validatorDetail = {
                validator,
                addValidator: validator.addValidator.bind(validator),
                removeValidator: validator.removeValidator.bind(validator),
            };

            dispatchFormieDomEvent(form, 'formie:validator:ready', validatorDetail);
            dispatchFormieDomEvent(target, 'formie:validator:ready', validatorDetail);
        }

        if (form) {
            if (renderPayload || normalizedOptions.endpoint || (target as HTMLElement).dataset.formieEndpoint) {
                normalizeHeadlessManagedUrls(target, form, normalizedOptions);
            }

            syncPageTabErrors(form);
        }

        if (Object.keys(resolvedThemeClassMap).length) {
            dispatchFormieDomEvent(target, 'formie:theme:applied', {
                hasClasses: true,
            });
        }

        // Modules run against the mounted DOM surface, not against server payload
        // objects, so the loader resolves real targets before calling setup().
        const modules = await loadModulesFromManifest(moduleManifest, {
            registry: moduleRegistry,
            matchContext: {
                root: target,
                form,
                mode: normalizedOptions.mode,
            },
            setupContext: {
                formId: id,
                root: target,
                form,
                target,
                scope: 'form',
                state: stateStore,
                on: (eventName, callback) => {
                    return bus.on(eventName, callback);
                },
                emit: (eventName, payload) => {
                    dispatchFormieDomEvent(target, eventName, payload);
                    return bus.emitSafe(eventName, payload).then((emitReport) => {
                        if (emitReport.failed.length > 0) {
                            debug.warn('Lifecycle listeners failed.', {
                                eventName,
                                failed: emitReport.failed.length,
                            });
                        }
                    });
                },
            },
        });
        debug.log('Module setup complete.', {
            target: getTargetDebugLabel(target),
            moduleInstances: modules.length,
        });

        const instance: FormieFormInstance = {
            id,
            root: target,
            submit: async(action = 'submit') => {
                debug.log('Submit requested.', {
                    id,
                    target: getTargetDebugLabel(target),
                    action,
                });
                if (!form) {
                    return {
                        ok: false,
                        code: 'FORM_NOT_FOUND',
                        message: 'No form element found for mount target.',
                        formErrors: ['No form element found for mount target.'],
                    };
                }

                const submitAction = form.querySelector('input[name="submitAction"]') as HTMLInputElement | null;

                if (submitAction) {
                    submitAction.value = action;
                }

                if (form.getAttribute('data-formie-loading') === 'true') {
                    return {
                        ok: false,
                        code: 'SUBMIT_IN_PROGRESS',
                        message: 'Submission already in progress.',
                        formErrors: [],
                    };
                }

                const fallbackSubmitter = form.querySelector(`[data-formie-action="${action}"]`) as HTMLElement | null;
                const result = await executeAjaxSubmitFlow({
                    id,
                    target,
                    form,
                    bus,
                    validator,
                    validateOnSubmit: shouldValidateOnSubmit(form),
                    action,
                    submitter: fallbackSubmitter,
                    waitForSubmitDelay,
                    onRefreshTokensAfterSubmit: async() => {
                        await refreshTokensAfterSubmitIfNeeded(target, normalizedOptions, form);
                    },
                    dispatchSubmitResult: (submitResult) => {
                        dispatchFormieDomEvent(target, 'formie:submit:result', submitResult);
                    },
                });

                debug.log('Submit completed.', {
                    id,
                    action,
                    ok: result.ok,
                    code: result.code,
                    message: result.message,
                });

                return result;
            },
            destroy: async() => {
                await unmount(target);
            },
            on: (eventName, callback) => {
                return bus.on(eventName, callback);
            },
        };

        if (form) {
            bindLegacyValidatorCompatibility({
                target,
                form,
                validatorDetail: validator ? {
                    validator,
                    addValidator: validator.addValidator.bind(validator),
                    removeValidator: validator.removeValidator.bind(validator),
                } : null,
                options: compatibilityOptions,
                unbinds,
            });

            bindLegacyDomEventCompatibility({
                target,
                form,
                instance,
                options: compatibilityOptions,
                unbinds,
            });
        }

        if (form) {
            bindFormEvents(target, form, normalizedOptions, bus, validator, unbinds);
            await refreshTokensIfNeeded(target, normalizedOptions, form);
        }

        stageNames.forEach((stageName) => {
            // Stage fan-out keeps submit pipeline ownership centralized while still
            // letting modules participate before and after each stage.
            const beforeDomUnbind = bus.on(`formie:stage:${stageName}:before`, async(payload) => {
                dispatchFormieDomEvent(target, `formie:stage:${stageName}:before`, payload);
            });

            const beforeUnbind = bus.on(`formie:stage:${stageName}:before`, async(payload) => {
                for (const moduleInstance of modules) {
                    if (moduleInstance.onBeforeStage) {
                        await moduleInstance.onBeforeStage(payload as Parameters<NonNullable<FormieModuleInstance['onBeforeStage']>>[0]);
                    }
                }
            });

            const afterDomUnbind = bus.on(`formie:stage:${stageName}:after`, async(payload) => {
                dispatchFormieDomEvent(target, `formie:stage:${stageName}:after`, payload);
            });

            const afterUnbind = bus.on(`formie:stage:${stageName}:after`, async(payload) => {
                const stagePayload = payload as {
                    result?: FormSubmitResult;
                } & Parameters<NonNullable<FormieModuleInstance['onAfterStage']>>[0];

                for (const moduleInstance of modules) {
                    if (moduleInstance.onAfterStage) {
                        await moduleInstance.onAfterStage(stagePayload, stagePayload.result);
                    }
                }
            });

            unbinds.push(beforeDomUnbind, beforeUnbind, afterDomUnbind, afterUnbind);
        });

        const submitBeforeUnbind = bus.on('formie:submit:before', async(payload) => {
            dispatchFormieDomEvent(target, 'formie:submit:before', payload);
        });

        const submitAfterUnbind = bus.on('formie:submit:after', async(payload) => {
            dispatchFormieDomEvent(target, 'formie:submit:after', payload);
        });

        const submitFinalBeforeUnbind = bus.on('formie:submit:final:before', async(payload) => {
            dispatchFormieDomEvent(target, 'formie:submit:final:before', payload);
        });

        const submitFinalAfterUnbind = bus.on('formie:submit:final:after', async(payload) => {
            dispatchFormieDomEvent(target, 'formie:submit:final:after', payload);
        });

        unbinds.push(
            submitBeforeUnbind,
            submitAfterUnbind,
            submitFinalBeforeUnbind,
            submitFinalAfterUnbind,
        );

        instances.set(target, {
            options: normalizedOptions,
            bus,
            form,
            validator,
            modules,
            unbinds,
            instance,
        });

        dispatchFormieDomEvent(target, 'formie:mount:after', {
            id,
            mode: normalizedOptions.mode as FormMode,
        });
        debug.log('Mount complete.', {
            id,
            target: getTargetDebugLabel(target),
            mode: normalizedOptions.mode,
        });

        return instance;
    };

    const mountWhenVisible = (target: Element, options: FormMountOptions): Promise<FormieFormInstance | null> => {
        if (!options.autoVisible || isElementVisible(target) || typeof IntersectionObserver === 'undefined') {
            return mount(target, options);
        }

        if (instances.has(target)) {
            return Promise.resolve(instances.get(target)?.instance || null);
        }

        if (pendingVisibilityMounts.has(target)) {
            debug.log('Mount deferred (already waiting visibility).', {
                target: getTargetDebugLabel(target),
            });
            return Promise.resolve(null);
        }

        // Hidden/modal forms defer all setup until visible so heavy providers and
        // DOM bindings do not initialize against detached or zero-size surfaces.
        const observer = new IntersectionObserver((entries) => {
            const hasVisibleEntry = entries.some((entry) => {
                return entry.target === target && entry.isIntersecting;
            });

            if (!hasVisibleEntry) {
                return;
            }

            observer.disconnect();
            pendingVisibilityMounts.delete(target);
            debug.log('Visibility reached, proceeding mount.', {
                target: getTargetDebugLabel(target),
            });

            void mount(target, {
                ...options,
                autoVisible: false,
            });
        }, {
            threshold: 0.01,
        });

        observer.observe(target);
        pendingVisibilityMounts.set(target, () => {
            observer.disconnect();
        });
        debug.log('Mount deferred until visible.', {
            target: getTargetDebugLabel(target),
        });

        return Promise.resolve(null);
    };

    const update = async(target: Element, options: Partial<FormMountOptions>): Promise<FormieFormInstance> => {
        const state = instances.get(target);

        if (!state) {
            return mount(target, {
                ...inferOptionsFromElement(target),
                ...options,
                mode: options.mode || 'server-rendered',
            });
        }

        state.options = {
            ...state.options,
            ...options,
        };

        const themeClassMap = (
            options.payload?.theme
            || state.options.payload?.theme
            || getEmbeddedPayload(target, state.form)?.theme
        ) as ThemeClassMap | undefined;
        const resolvedThemeClassMap = registerThemeClassMap(target, themeClassMap, state.form);

        if (state.validator) {
            state.validator.config.fieldContainerErrorClass = resolvedThemeClassMap.fieldLayoutError || [];
            state.validator.config.inputErrorClass = resolvedThemeClassMap.fieldControlError || [];
            state.validator.config.messagesClass = resolvedThemeClassMap.fieldErrors || [];
            state.validator.config.messageClass = resolvedThemeClassMap.fieldError || [];
        }

        if (Object.keys(resolvedThemeClassMap).length) {
            dispatchFormieDomEvent(target, 'formie:theme:applied', {
                hasClasses: true,
                reason: 'update',
            });
        }

        return state.instance;
    };

    const getInstance = (target: Element): FormieFormInstance | null => {
        return instances.get(target)?.instance || null;
    };

    const refreshForCache = async(targetOrId: Element | string): Promise<void> => {
        warnCompatibilityOnce(
            'refreshForCache',
            'Global `Formie.refreshForCache()` has been deprecated. Use built-in static-cache token refresh handling instead.',
        );

        let target: Element | null = null;

        if (typeof targetOrId === 'string') {
            const byId = document.getElementById(targetOrId);

            if (byId) {
                target = byId;
            } else {
                target = document.querySelector(`[data-formie-form-id="${targetOrId}"]`);
            }
        } else {
            target = targetOrId;
        }

        if (!target) {
            debug.warn('refreshForCache target not found.', {
                targetOrId,
            });

            return;
        }

        const state = instances.get(target);
        const form = getFormFromTarget(target);
        const options = state?.options || inferOptionsFromElement(target);

        if (!form) {
            debug.warn('refreshForCache found no form element for target.', {
                target: getTargetDebugLabel(target),
            });

            return;
        }

        const formHandle = options.formHandle || (target as HTMLElement).dataset.formieHandle || form.dataset.formieHandle;
        const endpoint = resolveRefreshTokensEndpoint(options, target);
        const renderIdInput = form.querySelector('input[name="renderId"]') as HTMLInputElement | null;
        const renderId = renderIdInput?.value || undefined;

        if (!formHandle) {
            debug.warn('refreshForCache found no form handle for target.', {
                target: getTargetDebugLabel(target),
            });

            return;
        }

        const refreshTokens = await requestRefreshTokens(endpoint, formHandle, renderId);

        if (!refreshTokens) {
            return;
        }

        applyRefreshTokensToForm(form, refreshTokens);
        dispatchFormieDomEvent(target, 'formie:refresh-tokens:after', refreshTokens);
    };

    const registerModule = (
        moduleDefinition: FormieModuleDefinition,
        options?: Parameters<ModuleRegistry['register']>[1],
    ): boolean => {
        return moduleRegistry.register(moduleDefinition, options);
    };

    const unregisterModule = (moduleId: string): void => {
        moduleRegistry.unregister(moduleId);
    };

    const getRegisteredModules = (): FormieModuleDefinition[] => {
        return moduleRegistry.getAll();
    };

    const scan = async(root?: ParentNode): Promise<FormieFormInstance[]> => {
        const scope = root || document;
        const targets = Array.from(scope.querySelectorAll(ROOT_SELECTORS));
        debug.log('Scan started.', {
            scope: scope === document ? 'document' : scope,
            targetCount: targets.length,
        });
        const results = await Promise.all(targets.map((target) => {
            const options = inferOptionsFromElement(target);
            return mountWhenVisible(target, options);
        }));
        const instances = results.filter((item): item is FormieFormInstance => !!item);
        debug.log('Scan finished.', {
            mountedCount: instances.length,
            deferredCount: targets.length - instances.length,
        });
        return instances;
    };

    const observe = (root?: ParentNode): (() => void) => {
        if (typeof MutationObserver === 'undefined') {
            return () => {};
        }

        const scope = root || document;
        debug.log('Observer started.', {
            scope: scope === document ? 'document' : scope,
        });

        // Observe is the convenience layer for classic DOM-driven pages. It mounts
        // new roots and tears down removed ones so cache swaps and modal inserts do
        // not leak validators, event listeners, or module instances.
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (!(node instanceof Element)) {
                        return;
                    }

                    if (node.matches(ROOT_SELECTORS)) {
                        debug.log('Observer detected new root.', {
                            target: getTargetDebugLabel(node),
                        });
                        void mountWhenVisible(node, inferOptionsFromElement(node));
                    }

                    node.querySelectorAll(ROOT_SELECTORS).forEach((child) => {
                        debug.log('Observer detected new nested root.', {
                            target: getTargetDebugLabel(child),
                        });
                        void mountWhenVisible(child, inferOptionsFromElement(child));
                    });
                });

                mutation.removedNodes.forEach((node) => {
                    if (!(node instanceof Element)) {
                        return;
                    }

                    if (instances.has(node)) {
                        debug.log('Observer detected removed root.', {
                            target: getTargetDebugLabel(node),
                        });
                        void unmount(node);
                    }

                    node.querySelectorAll(ROOT_SELECTORS).forEach((child) => {
                        if (instances.has(child)) {
                            debug.log('Observer detected removed nested root.', {
                                target: getTargetDebugLabel(child),
                            });
                            void unmount(child);
                        }
                    });
                });
            });
        });

        observer.observe(scope, {
            childList: true,
            subtree: true,
        });

        return () => {
            observer.disconnect();
            debug.log('Observer stopped.');

            pendingVisibilityMounts.forEach((cleanup, target) => {
                if (isWithinScope(target, scope)) {
                    cleanup();
                    pendingVisibilityMounts.delete(target);
                }
            });

            const roots: Element[] = [];

            if (scope instanceof Element && scope.matches(ROOT_SELECTORS)) {
                roots.push(scope);
            }

            scope.querySelectorAll(ROOT_SELECTORS).forEach((target) => {
                roots.push(target);
            });

            roots.forEach((target) => {
                if (instances.has(target)) {
                    void unmount(target);
                }
            });
        };
    };

    return {
        mount,
        unmount,
        update,
        getInstance,
        refreshForCache,
        registerModule,
        unregisterModule,
        getRegisteredModules,
        scan,
        observe,
    };
}
