import stripeCss from '#theme-css/integrations/_stripe.css?inline';

import { definePaymentModule } from '#modules/payments/api';
import { ensureModuleStyles } from '#modules/styles';
import { addThemeClasses, removeThemeClasses } from '#theme/theme-classes';
import { getPaymentProviderActionEventName } from '#utils/event-names';
import { loadScriptAndEnsureGlobal } from '#utils/scripts';

ensureModuleStyles('stripe', [stripeCss]);

type StripeConstructor = (publishableKey: string) => StripeInstance;

type StripeInstance = {
    elements: (options: Record<string, unknown>) => StripeElements;
    confirmPayment: (opts: {
        elements: StripeElements;
        clientSecret: string;
        redirect?: string;
        confirmParams?: { return_url: string };
    }) => Promise<{ paymentIntent?: { id: string }; error?: { message: string } }>;
    confirmSetup: (opts: {
        elements: StripeElements;
        clientSecret: string;
        redirect?: string;
        confirmParams?: { return_url: string };
    }) => Promise<{ setupIntent?: { id: string }; error?: { message: string } }>;
};

type StripeElements = {
    create: (type: string, options?: Record<string, unknown>) => StripePaymentElement;
    update: (params: Record<string, unknown>) => void;
    submit: () => Promise<{ error?: { message: string } }>;
};

type StripePaymentElement = {
    mount: (el: HTMLElement) => void;
    clear: () => void;
    destroy: () => void;
    on?: (event: 'ready' | 'loaderror', callback: (event?: { error?: { message?: string } }) => void) => void;
};

type StripeProviderOptions = {
    publishableKey?: string;
    paymentType?: string;
    amountType?: string;
    currencyType?: string;
    billingDetails?: Record<string, string> | null;
    initialPaymentInformation?: Record<string, unknown>;
};

const SCRIPT_ID = 'FORMIE_STRIPE_SCRIPT';
const CONFIRM_EVENT = getPaymentProviderActionEventName('stripe', 'confirm');
const PLACEHOLDER_SELECTOR = '[data-formie-stripe-elements-placeholder]';

type StripeWidget = { elements: StripeElements; paymentElement: StripePaymentElement };
type StripeFieldState = Element & {
    __formieStripeElements?: StripeElements;
    __formieStripeInstance?: StripeInstance | null;
    __formieStripeWidget?: StripeWidget | null;
    __formieStripeDynamicUnbind?: (() => void) | null;
    __formieStripeConfirmUnbind?: (() => void) | null;
    __formieStripeConfirming?: boolean;
    __formieStripeLastClientSecret?: string;
    __formieStripeEvaluateAndRender?: (() => void) | null;
};

const ZERO_DECIMAL_CURRENCIES = new Set([
    'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG',
    'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
]);

function toStripeSubunitAmount(amount: number, currency: string): number {
    if (ZERO_DECIMAL_CURRENCIES.has(currency.toUpperCase())) {
        return Math.ceil(amount);
    }

    return Math.ceil(amount * 100);
}

function clearPlaceholderError(placeholder: HTMLElement | null, themeSource: Element | null): void {
    if (!placeholder) {
        return;
    }

    removeThemeClasses(placeholder, themeSource, 'fieldErrors', 'fieldError');
    placeholder.querySelectorAll('[data-payment-placeholder-error]').forEach((el) => {
        removeThemeClasses(el as Element, themeSource, 'fieldError');
    });
}

function renderPlaceholderLoading(placeholder: HTMLElement | null, themeSource: Element | null, message = 'Loading payment options...'): void {
    if (!placeholder) {
        return;
    }

    clearPlaceholderError(placeholder, themeSource);
    placeholder.removeAttribute('hidden');
    placeholder.innerHTML = '';
    const spinner = document.createElement('div');
    spinner.className = 'formie-loading';
    const text = document.createElement('div');
    text.textContent = message;
    placeholder.append(spinner, text);
}

function showPlaceholderMessage(placeholder: HTMLElement | null, themeSource: Element | null, message: string): void {
    if (!placeholder) {
        return;
    }

    clearPlaceholderError(placeholder, themeSource);
    placeholder.removeAttribute('hidden');
    addThemeClasses(placeholder, themeSource, 'fieldErrors');
    placeholder.innerHTML = '';
    const error = document.createElement('div');
    error.setAttribute('data-payment-placeholder-error', '');
    error.textContent = message;
    addThemeClasses(error, themeSource, 'fieldError');
    placeholder.appendChild(error);
}

export const stripeModule = definePaymentModule<StripeProviderOptions, StripeInstance | null, StripeWidget | null>({
    id: 'stripe',
    defaultRequiredInputSuffixes: ['stripePaymentIntentId'],
    load: async (ctx) => {
        const { provider } = ctx.options;
        const publishableKey = provider.publishableKey as string | undefined;

        if (!publishableKey?.trim()) {
            console.error('[formie] Missing publishableKey for Stripe.');

            return null;
        }

        const StripeGlobal = await loadScriptAndEnsureGlobal<StripeConstructor>('Stripe', {
            id: SCRIPT_ID,
            src: 'https://js.stripe.com/v3',
        });

        const stripe = (StripeGlobal as (key: string) => StripeInstance)(publishableKey);

        return stripe;
    },
    mount: async (args) => {
        const { api, field, services, provider } = args;
        const placeholder = field.querySelector<HTMLElement>('[data-formie-stripe-elements]');
        const loadingPlaceholder = field.querySelector<HTMLElement>(PLACEHOLDER_SELECTOR);
        const stripeField = field as StripeFieldState;
        const themeSource = services.form || services.root;

        if (!placeholder || !api) {
            return null;
        }

        const initial = (provider.initialPaymentInformation as Record<string, unknown>) || {};
        const dynamicHandles = [initial.amount, initial.currency]
            .map((value) => String(value ?? '').trim())
            .filter((value, index) => {
                const isAmount = index === 0;
                const type = isAmount ? provider.amountType : provider.currencyType;

                return type === 'dynamic' && value !== '';
            });

        const getResolvedPaymentInfo = (): { ok: true; value: Record<string, unknown> } | { ok: false; error: string } => {
            const amountResult = services.resolveAmount({ value: initial.amount });
            if (!amountResult.ok) {
                return {
                    ok: false,
                    error: 'error' in amountResult ? amountResult.error : 'Provide a payment amount to proceed.',
                };
            }

            const currencyResult = services.resolveCurrency({ value: initial.currency });
            if (!currencyResult.ok) {
                return {
                    ok: false,
                    error: 'error' in currencyResult ? currencyResult.error : 'Provide a payment currency to proceed.',
                };
            }

            const currencyCode = currencyResult.value.toLowerCase();
            const amountValue = provider.amountType === 'dynamic'
                ? toStripeSubunitAmount(amountResult.value, currencyCode)
                : amountResult.value;

            return {
                ok: true,
                value: {
                    ...initial,
                    capture_method: 'automatic',
                    mode: provider.paymentType === 'subscription' ? 'subscription' : 'payment',
                    appearance: {},
                    amount: amountValue,
                    currency: currencyCode,
                },
            };
        };

        const destroyWidget = () => {
            try {
                stripeField.__formieStripeWidget?.paymentElement?.destroy?.();
            } catch (error) {
                // Best-effort cleanup only. We'll still reset local state below.
            }

            stripeField.__formieStripeWidget = null;
            stripeField.__formieStripeElements = undefined;

            // Ensure the container is empty after teardown to avoid stale Stripe UI.
            placeholder.innerHTML = '';
        };

        const evaluateAndRender = () => {
            const resolved = getResolvedPaymentInfo();
            if (!resolved.ok) {
                destroyWidget();
                showPlaceholderMessage(
                    loadingPlaceholder,
                    themeSource,
                    'error' in resolved ? resolved.error : 'Unable to resolve payment details.',
                );
                return;
            }

            try {
                if (stripeField.__formieStripeWidget && stripeField.__formieStripeElements) {
                    stripeField.__formieStripeElements.update(resolved.value);
                    clearPlaceholderError(loadingPlaceholder, themeSource);
                    loadingPlaceholder?.setAttribute('hidden', 'hidden');
                    return;
                }

                renderPlaceholderLoading(loadingPlaceholder, themeSource);
                const elements = api.elements(resolved.value);
                const paymentElement = elements.create('payment', {});
                paymentElement.mount(placeholder);

                paymentElement.on?.('loaderror', (event) => {
                    const message = event?.error?.message || 'Unable to load payment options.';
                    destroyWidget();
                    showPlaceholderMessage(loadingPlaceholder, themeSource, message);
                });

                paymentElement.on?.('ready', () => {
                    clearPlaceholderError(loadingPlaceholder, themeSource);
                    loadingPlaceholder?.setAttribute('hidden', 'hidden');
                });

                stripeField.__formieStripeElements = elements;
                stripeField.__formieStripeInstance = api;
                stripeField.__formieStripeWidget = { elements, paymentElement };
                if (!paymentElement.on) {
                    clearPlaceholderError(loadingPlaceholder, themeSource);
                    loadingPlaceholder?.setAttribute('hidden', 'hidden');
                }
            } catch (error) {
                destroyWidget();
                showPlaceholderMessage(
                    loadingPlaceholder,
                    themeSource,
                    error instanceof Error ? error.message : 'Unable to initialize Stripe payment element.',
                );
            }
        };

        stripeField.__formieStripeEvaluateAndRender = evaluateAndRender;

        stripeField.__formieStripeDynamicUnbind?.();
        if (dynamicHandles.length > 0) {
            stripeField.__formieStripeDynamicUnbind = services.watchFieldValueChanges(dynamicHandles, () => {
                evaluateAndRender();
            }, 600);
        }

        evaluateAndRender();

        return stripeField.__formieStripeWidget || null;
    },
    unmount: async (args) => {
        args.widget?.paymentElement?.destroy();
        const stripeField = args.field as StripeFieldState;
        stripeField.__formieStripeWidget = null;
        stripeField.__formieStripeElements = undefined;
        stripeField.__formieStripeInstance = null;
        stripeField.__formieStripeLastClientSecret = undefined;
        stripeField.__formieStripeEvaluateAndRender = null;
        stripeField.__formieStripeDynamicUnbind?.();
        stripeField.__formieStripeDynamicUnbind = null;
    },
    onBeforeAuthorize: async (args) => {
        const { widget, services, field } = args;
        const stripeField = field as StripeFieldState;
        let activeWidget = widget;

        // Multi-page dynamic flows can temporarily destroy the payment element when
        // amount/currency becomes invalid. Re-evaluate right before authorize so a
        // now-valid value can remount without requiring another input/change event.
        if (!activeWidget?.elements) {
            stripeField.__formieStripeEvaluateAndRender?.();
            activeWidget = stripeField.__formieStripeWidget || null;
        }

        if (!activeWidget?.elements) {
            return false;
        }

        const result = await activeWidget.elements.submit();

        if (result?.error) {
            services.addError(result.error.message);

            return false;
        }

        return true;
    },
    setup: async (ctx) => {
        const { services, options } = ctx;
        const provider = options.provider as StripeProviderOptions;
        const field = ctx.target as StripeFieldState;

        const handler = async (event: Event) => {
            try {
                const e = event as CustomEvent<{ data?: { clientSecret?: string; returnUrl?: string; type?: string; subscriptionId?: string } }>;
                const data = e.detail?.data;

                if (!data?.clientSecret) {
                    return;
                }

                // Prevent duplicate/looped confirms for the same server response.
                if (field.__formieStripeConfirming) {
                    return;
                }

                if (field.__formieStripeLastClientSecret === data.clientSecret) {
                    return;
                }

                const elements = field.__formieStripeElements;
                if (!elements) {
                    services.addError('Stripe elements not ready for 3DS.');

                    return;
                }

                const instance = field.__formieStripeInstance;
                const publishableKey = provider.publishableKey as string;

                if (!instance || !publishableKey) {
                    services.addError('Stripe is not initialized.');

                    return;
                }

                field.__formieStripeConfirming = true;
                const returnUrl = new URL(data.returnUrl || window.location.href);
                returnUrl.searchParams.set('origin', window.location.href);

                const confirmFn = data.type === 'setup' ? instance.confirmSetup : instance.confirmPayment;
                const result = await confirmFn({
                    elements,
                    clientSecret: data.clientSecret,
                    redirect: 'if_required',
                    confirmParams: { return_url: returnUrl.toString() },
                });

                if (result?.error) {
                    services.releaseSubmitLoading();
                    services.addError(result.error.message);
                    return;
                }

                if (data.subscriptionId) {
                    services.updateInputs('stripeSubscriptionId', data.subscriptionId);
                }

                const pi = result && 'paymentIntent' in result ? result.paymentIntent : null;
                const si = result && 'setupIntent' in result ? result.setupIntent : null;

                if (pi?.id) {
                    services.updateInputs('stripePaymentIntentId', pi.id);
                } else if (si?.id) {
                    services.updateInputs('stripePaymentIntentId', si.id);
                } else {
                    services.releaseSubmitLoading();
                    services.addError('Stripe confirmation did not return an intent ID.');
                    return;
                }

                field.__formieStripeLastClientSecret = data.clientSecret;
                services.triggerSubmit();
            } catch (error) {
                services.releaseSubmitLoading();
                services.addError(error instanceof Error ? error.message : 'Unable to confirm Stripe payment.');
            } finally {
                field.__formieStripeConfirming = false;
            }
        };

        field.__formieStripeConfirmUnbind?.();
        const unbind = services.events.onForm(CONFIRM_EVENT, handler as EventListener);
        field.__formieStripeConfirmUnbind = unbind;

        return {
            destroy: () => {
                field.__formieStripeConfirmUnbind?.();
                field.__formieStripeConfirmUnbind = null;
            },
        };
    },
    onAfterSubmit: async (args) => {
        const stripeField = args.field as StripeFieldState;

        // After a completed final submit, clear the Stripe element + hidden IDs
        // so stale intents cannot be replayed.
        if (args.result?.ok && !args.result?.nextPage) {
            stripeField.__formieStripeWidget?.paymentElement?.destroy?.();
            stripeField.__formieStripeWidget = null;
            stripeField.__formieStripeElements = undefined;
            args.services.updateInputs(['stripePaymentIntentId', 'stripeSubscriptionId'], '');
            stripeField.__formieStripeLastClientSecret = undefined;
        }
    },
});
