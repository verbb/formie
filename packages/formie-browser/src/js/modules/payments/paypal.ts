import paypalCss from '#theme-css/integrations/_paypal.css?inline';

import { definePaymentModule } from '#modules/payments/api';
import { ensureModuleStyles } from '#modules/styles';
import { loadScriptAndEnsureGlobal } from '#utils/scripts';

ensureModuleStyles('paypal', [paypalCss]);

type PayPalButtonsInstance = {
    close: () => void;
};

type PayPalActions = {
    order: {
        create: (config: Record<string, unknown>) => Promise<string>;
        authorize: () => Promise<Record<string, unknown>>;
    };
};

type PayPalButtonsOptions = {
    env: string;
    style: Record<string, unknown>;
    createOrder: (data: unknown, actions: PayPalActions) => Promise<string>;
    onCancel?: () => void;
    onError: (err: Error) => void;
    onApprove: (data: { orderID: string }, actions: PayPalActions) => Promise<void>;
};

type PayPalGlobal = {
    Buttons: (options: PayPalButtonsOptions) => { render: (el: HTMLElement) => PayPalButtonsInstance };
};

type PayPalProviderOptions = {
    clientId?: string;
    useSandbox?: boolean;
    currency?: string;
    amountType?: string;
    amountFixed?: number;
    amountVariable?: string;
    buttonLayout?: string;
    buttonColor?: string;
    buttonShape?: string;
    buttonLabel?: string;
    buttonTagline?: boolean;
    buttonWidth?: number;
    buttonHeight?: number;
};

const SCRIPT_ID = 'FORMIE_PAYPAL_SCRIPT';

function extractAuthIdFromAuthorization(result: Record<string, unknown> | null): string {
    if (!result) {
        return '';
    }

    const purchaseUnits = (result.purchase_units || []) as Array<Record<string, unknown>>;
    const payments = (purchaseUnits[0]?.payments || {}) as Record<string, unknown>;

    const authorizations = (payments.authorizations || []) as Array<Record<string, unknown>>;
    const captures = (payments.captures || []) as Array<Record<string, unknown>>;

    const authId = String(authorizations[0]?.id || captures[0]?.id || '');

    return authId.trim();
}

function hidePayPalButtons(widget: PayPalButtonsInstance | null, placeholder: HTMLElement): void {
    if (widget?.close) {
        widget.close();
    }

    placeholder.removeAttribute('data-formie-paypal-rendered');
    placeholder.innerHTML = '';
}

function getScriptUrl(clientId: string, currency: string): string {
    const params = [
        'intent=authorize',
        `currency=${encodeURIComponent(currency)}`,
        `client-id=${encodeURIComponent(clientId)}`,
    ];

    return `https://www.paypal.com/sdk/js?${params.join('&')}`;
}

export const paypalModule = definePaymentModule<PayPalProviderOptions, PayPalGlobal | null, PayPalButtonsInstance | null>({
    id: 'paypal',
    defaultRequiredInputSuffixes: ['paypalOrderId', 'paypalAuthId'],
    load: async (ctx) => {
        const { provider } = ctx.options;
        const clientId = provider.clientId as string | undefined;

        if (!clientId?.trim()) {
            console.error('[formie] Missing clientId for PayPal.');

            return null;
        }

        const currency = (provider.currency as string) || 'AUD';
        const scriptUrl = getScriptUrl(clientId, currency);

        await loadScriptAndEnsureGlobal<PayPalGlobal>('paypal', {
            id: SCRIPT_ID,
            src: scriptUrl,
        });

        return (window as unknown as { paypal: PayPalGlobal }).paypal;
    },
    mount: async (args) => {
        const { api, field, services, options, provider } = args;
        const placeholder = field.querySelector<HTMLElement>('[data-formie-paypal-button]');

        if (!placeholder || !api) {
            return null;
        }

        // Guard against duplicate mounts on the same placeholder.
        if (placeholder.getAttribute('data-formie-paypal-rendered') === 'true') {
            return null;
        }

        // Ensure a clean mount surface before rendering PayPal buttons.
        placeholder.innerHTML = '';

        const useSandbox = Boolean(provider.useSandbox);
        const currencyResult = services.resolveCurrency({ value: provider.currency, defaultCurrency: 'AUD' });

        if (!currencyResult.ok) {
            services.addError(('error' in currencyResult) ? currencyResult.error : 'Invalid PayPal currency.');

            return null;
        }

        const currency = currencyResult.value;

        const style: Record<string, unknown> = {
            layout: provider.buttonLayout || 'vertical',
            color: provider.buttonColor || 'gold',
            shape: provider.buttonShape || 'rect',
            label: provider.buttonLabel || 'paypal',
            width: provider.buttonWidth || 250,
            height: provider.buttonHeight || 35,
        };

        if (style.layout === 'horizontal') {
            style.tagline = provider.buttonTagline ?? true;
        }

        let widget: PayPalButtonsInstance | null = null;

        const paypalOptions: PayPalButtonsOptions = {
            env: useSandbox ? 'sandbox' : 'production',
            style,
            createOrder: (_data, actions) => {
                services.removeError();
                const amountResult = services.resolveAmount({
                    type: provider.amountType as string,
                    fixed: provider.amountFixed,
                    variable: provider.amountVariable as string,
                });

                if (!amountResult.ok) {
                    const errorMessage = ('error' in amountResult) ? amountResult.error : 'Invalid PayPal amount.';
                    services.addError(errorMessage);

                    throw new Error(errorMessage);
                }

                return actions.order.create({
                    intent: 'AUTHORIZE',
                    application_context: { user_action: 'CONTINUE' },
                    purchase_units: [{
                        amount: {
                            currency_code: currency,
                            value: String(amountResult.value),
                        },
                    }],
                });
            },
            onError: (err) => {
                services.addError(err?.message || 'PayPal error.');
            },
            onApprove: async (data, actions) => {
                try {
                    const authorization = await actions.order.authorize();
                    const authId = extractAuthIdFromAuthorization(authorization);

                    services.updateInputs('paypalOrderId', data.orderID);
                    services.updateInputs('paypalAuthId', authId || '');

                    if (authId) {
                        services.addSuccess('Payment authorized. Finalize the form to complete payment.');
                    } else {
                        // Allow submit to continue using order ID fallback on the backend.
                        services.addSuccess('PayPal approval received. Finalizing payment on submit.');
                    }

                    hidePayPalButtons(widget, placeholder);
                } catch {
                    services.addError('Unable to authorize payment. Please try again.');
                }
            },
        };

        const buttons = api.Buttons(paypalOptions);
        placeholder.setAttribute('data-formie-paypal-rendered', 'true');
        widget = buttons.render(placeholder);

        return widget;
    },
    unmount: async (args) => {
        if (args.widget?.close) {
            args.widget.close();
        }

        const placeholder = args.field.querySelector<HTMLElement>('[data-formie-paypal-button]');
        if (placeholder) {
            placeholder.removeAttribute('data-formie-paypal-rendered');
            placeholder.innerHTML = '';
        }
    },
    onAfterSubmit: async ({ services, result }) => {
        services.updateInputs(['paypalOrderId', 'paypalAuthId'], '');
        services.removeSuccess();
        services.removeError();

        // Restore buttons when auth was cleared but payment did not fully complete.
        if (!result?.ok || result?.nextPage) {
            return { remount: true };
        }

        return {};
    },
});
