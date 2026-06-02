import type { ModuleSetupContext } from '#contracts/modules';
import { clearSubmitLoading } from '#core/submit-result-state';
import { addThemeClasses } from '#theme/theme-classes';
import { debounce } from '#utils/async';
import { getFieldLabel, currencyToFloat } from '#utils/fields';
import { buildFieldValueRegistry, fieldKeyToInputName, normalizeFieldKey, resolveFieldReferenceLive } from '#utils/field-references';
import { DEFAULT_WAIT_FOR_VALUE_MS } from '#modules/payments/constants';
import {
    findPaymentInputBySuffix,
    hasRequiredPaymentInputs,
    waitForRequiredPaymentInputs,
} from '#modules/payments/utils';
import { t } from '#utils/i18n';

type Cleanup = () => void;
type ResolvedValueResult<T> = { ok: true; value: T } | { ok: false; error: string };

export type PaymentModuleOptions<TProvider extends Record<string, unknown> = Record<string, unknown>> = {
    handle?: string;
    requiredInputSuffixes?: string[];
    waitForValueMs?: number;
    errorMessage?: string;
} & TProvider;

export type NormalizedPaymentModuleOptions<TProvider extends Record<string, unknown>> = {
    handle: string;
    transport: {
        requiredInputSuffixes: string[];
        waitForValueMs: number;
        errorMessage: string;
    };
    provider: TProvider;
};

const PAYMENT_OPTION_KEYS = new Set([
    'handle',
    'requiredInputSuffixes',
    'waitForValueMs',
    'errorMessage',
]);

const PAYMENT_SUCCESS_SELECTOR = '[data-payment-success]';
const PAYMENT_ERROR_SELECTOR = '[data-payment-error]';

function getPaymentProviderHandle(id: string, options: Record<string, unknown>): string {
    const handle = typeof options.handle === 'string' && options.handle.trim() !== ''
        ? options.handle.trim()
        : '';

    return handle || id;
}

export function normalizePaymentModuleOptions<TProvider extends Record<string, unknown>>(
    id: string,
    rawOptions: Record<string, unknown> | undefined,
    defaults: { defaultRequiredInputSuffixes?: string[]; defaultWaitForValueMs?: number },
): NormalizedPaymentModuleOptions<TProvider> {
    const options = rawOptions || {};
    const provider = Object.entries(options).reduce((carry, [key, value]) => {
        if (PAYMENT_OPTION_KEYS.has(key)) {
            return carry;
        }

        carry[key] = value;

        return carry;
    }, {} as Record<string, unknown>) as TProvider;

    const requiredInputSuffixes = Array.isArray(options.requiredInputSuffixes)
        ? options.requiredInputSuffixes.map(String).filter(Boolean)
        : (defaults.defaultRequiredInputSuffixes || []);
    const waitForValueMs = Number(options.waitForValueMs ?? defaults.defaultWaitForValueMs ?? DEFAULT_WAIT_FOR_VALUE_MS);
    const errorMessage = typeof options.errorMessage === 'string' && options.errorMessage.trim() !== ''
        ? options.errorMessage.trim()
        : 'Payment authorization is incomplete.';

    return {
        handle: getPaymentProviderHandle(id, options),
        transport: {
            requiredInputSuffixes,
            waitForValueMs: Number.isFinite(waitForValueMs) ? waitForValueMs : DEFAULT_WAIT_FOR_VALUE_MS,
            errorMessage,
        },
        provider,
    };
}

export type PaymentHostServices = {
    root: Element;
    form: HTMLFormElement | null;
    field: Element;
    updateInputs: (name: string | string[], value: string) => void;
    addError: (message: string) => void;
    removeError: () => void;
    addSuccess: (message: string) => void;
    removeSuccess: () => void;
    hasToken: () => boolean;
    waitForToken: (timeoutMs?: number) => Promise<boolean>;
    getFieldValue: (handle: string, type?: 'string' | 'float' | 'int' | 'number') => string | number;
    resolveAmount: (options: {
        type?: string | null;
        fixed?: unknown;
        variable?: string | null;
        value?: unknown;
    }) => ResolvedValueResult<number>;
    resolveCurrency: (options: {
        type?: string | null;
        fixed?: unknown;
        variable?: string | null;
        value?: unknown;
        defaultCurrency?: string;
    }) => ResolvedValueResult<string>;
    watchFieldValueChanges: (
        handles: string[],
        callback: () => void,
        debounceMs?: number,
    ) => Cleanup;
    getBillingData: (billingDetails: {
        billingName?: string;
        billingEmail?: string;
        billingAddress?: string;
    } | null) => Record<string, unknown>;
    /** Trigger form submit (e.g. after 3DS confirmation). */
    triggerSubmit: () => void;
    releaseSubmitLoading: () => void;
    events: {
        onForm: (eventName: string, callback: EventListener) => Cleanup;
        onRoot: (eventName: string, callback: EventListener) => Cleanup;
    };
};

function bindDomEvent(target: EventTarget, eventName: string, callback: EventListener): Cleanup {
    target.addEventListener(eventName, callback);

    return () => {
        target.removeEventListener(eventName, callback);
    };
}

export function createPaymentHostServices(
    ctx: ModuleSetupContext,
    options: NormalizedPaymentModuleOptions<Record<string, unknown>>,
): PaymentHostServices {
    const field = ctx.target;
    const form = ctx.form;
    const root = ctx.root;
    const tokenRoot = form || root;
    const suffixes = options.transport.requiredInputSuffixes;
    const getRegistry = () => {
        return buildFieldValueRegistry(form || root);
    };
    const getReferenceValue = (reference: string): string => {
        const resolved = resolveFieldReferenceLive(reference, getRegistry());
        const value = resolved.value;

        if (Array.isArray(value)) {
            return value[0] || '';
        }

        return String(value || '');
    };

    const updateInputs = (name: string | string[], value: string) => {
        const names = Array.isArray(name) ? name : [name];

        for (const n of names) {
            const input = findPaymentInputBySuffix(tokenRoot, n)
                ?? field.querySelector(`input[name*="${n}"]`) as HTMLInputElement | null;

            if (input) {
                input.value = value;
            }
        }
    };

    const addError = (message: string) => {
        const container = field.querySelector('[data-formie-field-type] > div, [data-field-type] > div') || field;
        const existing = container.querySelector(PAYMENT_ERROR_SELECTOR);

        if (existing) {
            existing.remove();
        }

        const el = document.createElement('div');
        el.setAttribute('data-payment-error', '');
        el.textContent = message;
        addThemeClasses(el, form || root, 'fieldError');
        container.appendChild(el);
    };

    const removeError = () => {
        field.querySelector(PAYMENT_ERROR_SELECTOR)?.remove();
    };

    const addSuccess = (message: string) => {
        const container = field.querySelector('[data-formie-field-type] > div, [data-field-type] > div') || field;
        const existing = container.querySelector(PAYMENT_SUCCESS_SELECTOR);

        if (existing) {
            existing.remove();
        }

        const el = document.createElement('div');
        el.setAttribute('data-payment-success', '');
        el.textContent = message;
        addThemeClasses(el, form || root, 'successMessage');
        container.appendChild(el);
    };

    const removeSuccess = () => {
        field.querySelector(PAYMENT_SUCCESS_SELECTOR)?.remove();
    };

    const triggerSubmit = () => {
        if (form) {
            // Allow one internal follow-up submit (e.g. Stripe confirm replay)
            // even when the form is intentionally kept in loading state.
            form.setAttribute('data-formie-internal-resubmit', 'true');
        }

        if (form && typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else if (form) {
            form.submit();
        }
    };

    const releaseSubmitLoading = () => {
        if (!form) {
            return;
        }

        form.removeAttribute('data-formie-internal-resubmit');
        clearSubmitLoading(form);
    };

    const resolveAmount = (opts: {
        type?: string | null;
        fixed?: unknown;
        variable?: string | null;
        value?: unknown;
    }): ResolvedValueResult<number> => {
        const searchRoot = form || root;
        const mode = String(opts.type || '').toLowerCase();
        const isDynamic = mode === 'dynamic' && typeof opts.variable === 'string' && opts.variable.trim() !== '';
        const source = opts.value ?? (isDynamic ? opts.variable : opts.fixed);
        const sourceString = String(source ?? '').trim();
        const numericDirect = typeof source === 'number' ? source : currencyToFloat(sourceString);

        if (Number.isFinite(numericDirect) && numericDirect > 0) {
            return { ok: true, value: numericDirect };
        }

        if (sourceString !== '') {
            const raw = getReferenceValue(sourceString);
            const numeric = currencyToFloat(raw);

            if (Number.isFinite(numeric) && numeric > 0) {
                return { ok: true, value: numeric };
            }

            const label = getFieldLabel(searchRoot, sourceString);
            if (!raw) {
                return {
                    ok: false,
                    error: label
                        ? t('Provide a value for "{label}" to proceed.', { label })
                        : t('Provide a payment amount to proceed.'),
                };
            }
        }

        return {
            ok: false,
            error: t('Payment amount must be greater than 0.'),
        };
    };

    const resolveCurrency = (opts: {
        type?: string | null;
        fixed?: unknown;
        variable?: string | null;
        value?: unknown;
        defaultCurrency?: string;
    }): ResolvedValueResult<string> => {
        const searchRoot = form || root;
        const mode = String(opts.type || '').toLowerCase();
        const isDynamic = mode === 'dynamic' && typeof opts.variable === 'string' && opts.variable.trim() !== '';
        const source = opts.value ?? (isDynamic ? opts.variable : opts.fixed ?? opts.defaultCurrency ?? '');
        const sourceString = String(source ?? '').trim();

        const direct = sourceString.toUpperCase();
        if (/^[A-Z]{3}$/.test(direct) && !isDynamic) {
            return { ok: true, value: direct };
        }

        if (sourceString !== '') {
            const raw = String(getReferenceValue(sourceString) || '').trim();
            const normalized = raw.toUpperCase();

            if (/^[A-Z]{3}$/.test(normalized)) {
                return { ok: true, value: normalized };
            }

            const label = getFieldLabel(searchRoot, sourceString);
            if (!raw) {
                return {
                    ok: false,
                    error: label
                        ? t('Provide a value for "{label}" to proceed.', { label })
                        : t('Provide a payment currency to proceed.'),
                };
            }
        }

        return {
            ok: false,
            error: t('Payment currency must be a valid 3-letter code.'),
        };
    };

    const watchFieldValueChanges = (
        handles: string[],
        callback: () => void,
        debounceMs = 600,
    ): Cleanup => {
        const searchRoot = form || root;
        const normalizedHandles = handles
            .map((handle) => String(handle || '').trim())
            .filter(Boolean);

        if (normalizedHandles.length === 0) {
            return () => { };
        }

        const registry = getRegistry();
        const watchedNames = new Set<string>();
        normalizedHandles.forEach((handle) => {
            const key = normalizeFieldKey(handle);
            const entry = registry.get(key);

            if (entry?.names?.length) {
                entry.names.forEach((name) => {
                    watchedNames.add(name);
                });
                return;
            }

            const fallback = fieldKeyToInputName(key);
            if (fallback) {
                watchedNames.add(fallback);
                watchedNames.add(`${fallback}[]`);
            }
        });

        const debounced = debounce(() => {
            callback();
        }, debounceMs);

        const onInput = (event: Event) => {
            const target = event.target as HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement | null;
            const inputName = target?.name || '';

            if (!inputName || !watchedNames.has(inputName)) {
                return;
            }

            debounced();
        };

        searchRoot.addEventListener('input', onInput as EventListener);
        searchRoot.addEventListener('change', onInput as EventListener);

        return () => {
            searchRoot.removeEventListener('input', onInput as EventListener);
            searchRoot.removeEventListener('change', onInput as EventListener);
        };
    };

    return {
        root,
        form,
        field,
        updateInputs,
        addError,
        removeError,
        addSuccess,
        removeSuccess,
        hasToken: () => hasRequiredPaymentInputs(tokenRoot, suffixes).ok,
        waitForToken: (timeoutMs = options.transport.waitForValueMs) => {
            return waitForRequiredPaymentInputs(tokenRoot, suffixes, timeoutMs).then((r) => r.ok);
        },
        getFieldValue: (handle: string, type: 'string' | 'float' | 'int' | 'number' = 'string') => {
            const raw = getReferenceValue(handle);

            if (type === 'float' || type === 'int' || type === 'number') {
                return currencyToFloat(raw);
            }

            return raw;
        },
        resolveAmount,
        resolveCurrency,
        watchFieldValueChanges,
        triggerSubmit,
        releaseSubmitLoading,
        getBillingData: (billingDetails) => {
            const billing: Record<string, unknown> = {};

            if (!billingDetails || typeof billingDetails !== 'object') {
                return { billing_details: billing };
            }

            if (billingDetails.billingName) {
                const name = getReferenceValue(billingDetails.billingName);

                if (name) billing.name = name;
            }

            if (billingDetails.billingEmail) {
                const email = getReferenceValue(billingDetails.billingEmail);

                if (email) billing.email = email;
            }

            if (billingDetails.billingAddress) {
                const addr = billingDetails.billingAddress as string;
                const address: Record<string, string> = {};
                const address1 = getReferenceValue(`${addr}.address1`);
                const address2 = getReferenceValue(`${addr}.address2`);
                const address3 = getReferenceValue(`${addr}.address3`);
                const city = getReferenceValue(`${addr}.city`);
                const zip = getReferenceValue(`${addr}.zip`);
                const state = getReferenceValue(`${addr}.state`);
                const country = getReferenceValue(`${addr}.country`);

                if (address1) address.line1 = address1;
                if (address2) address.line2 = address2;
                if (address3) address.line3 = address3;
                if (city) address.city = city;
                if (zip) address.postal_code = zip;
                if (state) address.state = state;
                if (country) address.country = country;

                if (Object.keys(address).length) billing.address = address;
            }

            return { billing_details: billing };
        },
        events: {
            onForm: (eventName, callback) => {
                if (!form) return () => { };

                return bindDomEvent(form, eventName, callback);
            },
            onRoot: (eventName, callback) => bindDomEvent(root, eventName, callback),
        },
    };
}
