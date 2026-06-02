import type { ModuleSetupContext } from '#contracts/modules';
import type { FormRefreshTokensPayload } from '#contracts/schema';
import { addThemeClasses } from '#theme/theme-classes';
import { debounce } from '#utils/async';
import { t } from '#utils/i18n';
import { DEFAULT_WAIT_FOR_VALUE_MS } from '#modules/captchas/constants';
import {
    clearCaptchaValues,
    ensureCaptchaValueInput,
    getInputValue,
    hasCaptchaValue,
    waitForCaptchaValue,
} from '#modules/captchas/utils';

type Cleanup = () => void;

type ObserveVisiblePlaceholdersResult = {
    cleanup: Cleanup;
    reconcile: () => void;
    getVisible: () => HTMLElement[];
};

export type CaptchaRefreshEntry = {
    formId?: string;
    sessionKey?: string | null;
    value?: string | null;
};

export type CaptchaModuleOptions<TProvider extends Record<string, unknown> = Record<string, unknown>> = {
    handle?: string;
    placeholderSelector?: string;
    errorMessage?: string;
    sessionKey?: string | null;
    value?: string | null;
} & TProvider;

export type NormalizedCaptchaModuleOptions<TProvider extends Record<string, unknown>> = {
    handle: string;
    ui: {
        placeholderSelector: string;
        errorMessage: string;
    };
    transport: {
        tokenFieldNames: string[];
        waitForValueMs: number;
        sessionKey: string | null;
        value: string | null;
    };
    provider: TProvider;
};

type CaptchaModuleDefaults = {
    defaultPlaceholderSelector: string;
    defaultTokenFieldNames?: string[];
    defaultWaitForValueMs?: number;
};

export type CaptchaHostServices = {
    form: HTMLFormElement | null;
    root: Element;
    placeholder: {
        query: () => HTMLElement[];
        getPrimary: () => HTMLElement | null;
        observe: (
            onShow: (placeholder: HTMLElement) => void,
            onHide: (placeholder: HTMLElement) => void,
        ) => ObserveVisiblePlaceholdersResult;
        createContainer: (placeholder: HTMLElement) => HTMLElement;
        clear: (placeholder: HTMLElement | null) => void;
    };
    errors: {
        getDefaultMessage: () => string;
        show: (message?: string, placeholder?: HTMLElement | null) => void;
        clear: (placeholder?: HTMLElement | null) => void;
    };
    tokens: {
        names: string[];
        has: (names?: string[], root?: ParentNode) => boolean;
        read: (name?: string, root?: ParentNode) => string;
        write: (
            value: string,
            {
                names,
                root,
                container,
            }?: {
                names?: string[];
                root?: ParentNode;
                container?: HTMLElement | null;
            },
        ) => void;
        clear: (names?: string[], root?: ParentNode) => void;
        wait: (timeoutMs?: number, names?: string[], root?: ParentNode) => Promise<boolean>;
    };
    refresh: {
        providerHandle: string;
        onTokensRefreshed: (callback: (entry: CaptchaRefreshEntry) => void) => Cleanup;
    };
    events: {
        onRoot: (eventName: string, callback: EventListener) => Cleanup;
        onForm: (eventName: string, callback: EventListener) => Cleanup;
    };
};

const CAPTCHA_OPTION_KEYS = new Set([
    'handle',
    'placeholderSelector',
    'errorMessage',
    'sessionKey',
    'value',
]);

const CAPTCHA_ERROR_SELECTOR = '[data-formie-captcha-error-container]';
const CAPTCHA_VISIBILITY_EVENTS = [
    'formie:page:navigate',
    'formie:page:navigate:after',
    'formie:submit:result',
];

function bindDomEvent(target: EventTarget, eventName: string, callback: EventListener): Cleanup {
    target.addEventListener(eventName, callback);

    return () => {
        target.removeEventListener(eventName, callback);
    };
}

function queryCaptchaPlaceholders(root: Element, selector: string): HTMLElement[] {
    if (root instanceof HTMLElement && root.matches(selector)) {
        return [root, ...Array.from(root.querySelectorAll(selector)) as HTMLElement[]];
    }

    return Array.from(root.querySelectorAll(selector)) as HTMLElement[];
}

function isElementVisible(target: Element): target is HTMLElement {
    if (!(target instanceof HTMLElement)) {
        return false;
    }

    if (!target.isConnected) {
        return false;
    }

    if (target.hidden || target.closest('[hidden]')) {
        return false;
    }

    if (target.closest('[data-formie-page-hidden]') || target.closest('[aria-hidden="true"]')) {
        return false;
    }

    const style = window.getComputedStyle(target);

    return style.display !== 'none' && style.visibility !== 'hidden';
}

function getPrimaryPlaceholder(root: Element, selector: string): HTMLElement | null {
    const placeholders = queryCaptchaPlaceholders(root, selector);

    return placeholders.find((placeholder) => isElementVisible(placeholder)) || placeholders[0] || null;
}

function createCaptchaContainer(placeholder: HTMLElement): HTMLElement {
    placeholder.innerHTML = '';

    const container = document.createElement('div');
    placeholder.appendChild(container);

    return container;
}

function clearCaptchaError(placeholder: HTMLElement | null): void {
    placeholder?.querySelector(CAPTCHA_ERROR_SELECTOR)?.remove();
}

function showCaptchaError(placeholder: HTMLElement | null, message: string, themeSource?: Element | null): void {
    if (!placeholder) {
        return;
    }

    clearCaptchaError(placeholder);

    const container = document.createElement('div');
    container.setAttribute('data-formie-captcha-error-container', '');
    container.setAttribute('aria-live', 'polite');
    container.setAttribute('aria-atomic', 'true');
    addThemeClasses(container, themeSource || placeholder, 'fieldErrors');

    const error = document.createElement('div');
    error.setAttribute('data-formie-captcha-error', '');
    error.setAttribute('role', 'alert');
    addThemeClasses(error, themeSource || placeholder, 'fieldError');
    error.textContent = message;

    container.appendChild(error);
    placeholder.appendChild(container);
}

function parseRefreshTokensEvent(event: Event): FormRefreshTokensPayload | null {
    const detail = event instanceof CustomEvent ? event.detail : null;

    if (!detail || typeof detail !== 'object') {
        return null;
    }

    return detail as FormRefreshTokensPayload;
}

function getCaptchaRefreshEntry(
    payload: FormRefreshTokensPayload | null,
    providerHandle: string,
): CaptchaRefreshEntry | null {
    if (!payload?.captchas || typeof payload.captchas !== 'object') {
        return null;
    }

    const entry = payload.captchas[providerHandle];

    if (!entry || typeof entry !== 'object') {
        return null;
    }

    return entry as CaptchaRefreshEntry;
}

function observeVisiblePlaceholders(
    root: Element,
    selector: string,
    onShow: (placeholder: HTMLElement) => void,
    onHide: (placeholder: HTMLElement) => void,
): ObserveVisiblePlaceholdersResult {
    const visible = new Set<HTMLElement>();

    const reconcileNow = () => {
        const placeholders = queryCaptchaPlaceholders(root, selector);
        const nextVisible = new Set(placeholders.filter((placeholder) => isElementVisible(placeholder)));

        placeholders.forEach((placeholder) => {
            if (nextVisible.has(placeholder) && !visible.has(placeholder)) {
                visible.add(placeholder);
                onShow(placeholder);
            }
        });

        Array.from(visible).forEach((placeholder) => {
            if (nextVisible.has(placeholder)) {
                return;
            }

            visible.delete(placeholder);
            onHide(placeholder);
        });
    };

    const reconcile = debounce(reconcileNow, 20);
    const mutationObserver = new MutationObserver(() => {
        reconcile();
    });

    mutationObserver.observe(root, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['class', 'style', 'hidden', 'aria-hidden', 'data-formie-page-hidden'],
    });

    const cleanups = [
        bindDomEvent(window, 'resize', () => {
            reconcile();
        }),
        ...CAPTCHA_VISIBILITY_EVENTS.map((eventName) => {
            return bindDomEvent(root, eventName, () => {
                reconcile();
            });
        }),
    ];

    reconcileNow();

    return {
        cleanup: () => {
            mutationObserver.disconnect();
            cleanups.forEach((cleanup) => {
                cleanup();
            });

            Array.from(visible).forEach((placeholder) => {
                onHide(placeholder);
            });

            visible.clear();
        },
        reconcile,
        getVisible: () => {
            return queryCaptchaPlaceholders(root, selector).filter((placeholder) => isElementVisible(placeholder));
        },
    };
}

function getCaptchaProviderHandle(id: string, options: Record<string, unknown>): string {
    const handle = typeof options.handle === 'string' && options.handle.trim() !== ''
        ? options.handle.trim()
        : '';

    return handle || id;
}

export function normalizeCaptchaModuleOptions<TProvider extends Record<string, unknown>>(
    id: string,
    rawOptions: Record<string, unknown> | undefined,
    {
        defaultPlaceholderSelector,
        defaultTokenFieldNames = [],
        defaultWaitForValueMs = DEFAULT_WAIT_FOR_VALUE_MS,
    }: CaptchaModuleDefaults,
): NormalizedCaptchaModuleOptions<TProvider> {
    const options = rawOptions || {};
    const provider = Object.entries(options).reduce((carry, [key, value]) => {
        if (CAPTCHA_OPTION_KEYS.has(key)) {
            return carry;
        }

        carry[key] = value;

        return carry;
    }, {} as Record<string, unknown>) as TProvider;
    const tokenFieldNames = defaultTokenFieldNames.map(String).filter(Boolean);
    const waitForValueMs = Number(defaultWaitForValueMs);
    const placeholderSelector = typeof options.placeholderSelector === 'string' && options.placeholderSelector.trim() !== ''
        ? options.placeholderSelector.trim()
        : defaultPlaceholderSelector;
    const errorMessage = typeof options.errorMessage === 'string' && options.errorMessage.trim() !== ''
        ? options.errorMessage.trim()
        : t('Captcha challenge must be completed.');
    const sessionKey = typeof options.sessionKey === 'string' && options.sessionKey.trim() !== ''
        ? options.sessionKey.trim()
        : null;
    const value = typeof options.value === 'string'
        ? options.value
        : null;

    return {
        handle: getCaptchaProviderHandle(id, options),
        ui: {
            placeholderSelector,
            errorMessage,
        },
        transport: {
            tokenFieldNames,
            waitForValueMs: Number.isFinite(waitForValueMs) ? waitForValueMs : defaultWaitForValueMs,
            sessionKey,
            value,
        },
        provider,
    };
}

export function createCaptchaHostServices<TProvider extends Record<string, unknown>>(
    ctx: ModuleSetupContext,
    options: NormalizedCaptchaModuleOptions<TProvider>,
): CaptchaHostServices {
    const tokenRoot = ctx.form || ctx.root;
    const placeholderSelector = options.ui.placeholderSelector;
    const providerHandle = options.handle;

    return {
        form: ctx.form,
        root: ctx.root,
        placeholder: {
            query: () => queryCaptchaPlaceholders(ctx.root, placeholderSelector),
            getPrimary: () => getPrimaryPlaceholder(ctx.root, placeholderSelector),
            observe: (onShow, onHide) => observeVisiblePlaceholders(ctx.root, placeholderSelector, onShow, onHide),
            createContainer: (placeholder) => createCaptchaContainer(placeholder),
            clear: (placeholder) => {
                if (!placeholder) {
                    return;
                }

                clearCaptchaError(placeholder);
                placeholder.innerHTML = '';
            },
        },
        errors: {
            getDefaultMessage: () => options.ui.errorMessage,
            show: (message, placeholder) => {
                showCaptchaError(placeholder || getPrimaryPlaceholder(ctx.root, placeholderSelector), message || options.ui.errorMessage, ctx.form || ctx.root);
            },
            clear: (placeholder) => {
                clearCaptchaError(placeholder || getPrimaryPlaceholder(ctx.root, placeholderSelector));
            },
        },
        tokens: {
            names: options.transport.tokenFieldNames,
            has: (names = options.transport.tokenFieldNames, root = tokenRoot) => hasCaptchaValue(root, names),
            read: (name = options.transport.tokenFieldNames[0], root = tokenRoot) => (name ? getInputValue(root, name) : ''),
            write: (value, {
                names = options.transport.tokenFieldNames,
                root = tokenRoot,
                container = ctx.form,
            } = {}) => {
                names.forEach((name) => {
                    ensureCaptchaValueInput(root, name, {
                        value,
                        container,
                    });
                });
            },
            clear: (names = options.transport.tokenFieldNames, root = tokenRoot) => {
                clearCaptchaValues(root, names);
            },
            wait: (timeoutMs = options.transport.waitForValueMs, names = options.transport.tokenFieldNames, root = tokenRoot) => {
                return waitForCaptchaValue(root, names, timeoutMs);
            },
        },
        refresh: {
            providerHandle,
            onTokensRefreshed: (callback) => {
                const handlers = ['formie:refresh-tokens:after', 'formie:refresh-tokens:refreshed'].map((eventName) => {
                    return bindDomEvent(ctx.root, eventName, (event) => {
                        const payload = parseRefreshTokensEvent(event);
                        const entry = getCaptchaRefreshEntry(payload, providerHandle);

                        if (entry) {
                            callback(entry);
                        }
                    });
                });

                return () => {
                    handlers.forEach((cleanup) => {
                        cleanup();
                    });
                };
            },
        },
        events: {
            onRoot: (eventName, callback) => bindDomEvent(ctx.root, eventName, callback),
            onForm: (eventName, callback) => {
                if (!ctx.form) {
                    return () => {};
                }

                return bindDomEvent(ctx.form, eventName, callback);
            },
        },
    };
}
