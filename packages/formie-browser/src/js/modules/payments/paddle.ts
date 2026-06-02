import { definePaymentModule } from '#modules/payments/api';
import { getPaymentProviderActionEventName } from '#utils/event-names';
import { loadScriptAndEnsureGlobal } from '#utils/scripts';

type PaddleGlobal = {
    Environment: { set: (env: string) => void };
    Initialize: (opts: {
        token: string;
        checkout?: { settings?: { displayMode?: string; variant?: string } };
        eventCallback?: (e: { name: string; data?: unknown }) => void;
    }) => void;
    Checkout: {
        open: (data: { items?: unknown[] }) => void;
        close: () => void;
    };
};

type PaddleProviderOptions = {
    clientSideToken?: string;
    environment?: string;
};

const SCRIPT_ID = 'FORMIE_PADDLE_SCRIPT';

const CHECKOUT_EVENT = getPaymentProviderActionEventName('paddle', 'initialize');

export const paddleModule = definePaymentModule<PaddleProviderOptions, null, null>({
    id: 'paddle',
    defaultRequiredInputSuffixes: ['paddleCheckoutData'],
    load: async() => null,
    setup: async(ctx) => {
        const { services } = ctx;
        const field = ctx.target;
        const provider = ctx.options.provider as PaddleProviderOptions;

        const clientSideToken = provider.clientSideToken as string | undefined;
        if (!clientSideToken?.trim()) {
            services.addError('Missing clientSideToken for Paddle.');
            return {};
        }

        let paddle: PaddleGlobal;
        try {
            paddle = await loadScriptAndEnsureGlobal<PaddleGlobal>('Paddle', {
                id: SCRIPT_ID,
                src: 'https://cdn.paddle.com/paddle/v2/paddle.js',
            });
            paddle.Environment.set((provider.environment as string) || 'production');
        } catch (error) {
            services.addError(error instanceof Error ? error.message : 'Failed to load Paddle SDK.');

            return {};
        }

        paddle.Initialize({
            token: clientSideToken,
            checkout: {
                settings: {
                    displayMode: 'overlay',
                    variant: 'multi-page',
                },
            },
            eventCallback: (e) => {
                if (e.name === 'checkout.completed') {
                    services.updateInputs('paddleCheckoutInit', '');
                    services.updateInputs('paddleCheckoutData', JSON.stringify(e.data || {}));

                    setTimeout(() => {
                        paddle.Checkout.close();

                        services.triggerSubmit();
                    }, 500);
                }
            },
        });

        const openCheckout = (data?: { items?: unknown[] }) => {
            if (!data?.items) {
                services.addError('Missing Paddle checkout items.');

                return false;
            }

            try {
                // Action-required flows should release submit loading while checkout is open.
                services.releaseSubmitLoading();
                paddle.Checkout.open(data);
            } catch (error) {
                services.addError(error instanceof Error ? error.message : 'Unable to open Paddle checkout.');

                return false;
            }

            return true;
        };

        const unbindCheckout = services.events.onForm(CHECKOUT_EVENT, ((event: CustomEvent<{ data?: { items?: unknown[] } }>) => {
            openCheckout(event.detail?.data);
        }) as EventListener);

        return {
            destroy: () => {
                unbindCheckout();
            },
        };
    },
    onAfterSubmit: async({ services }) => {
        services.updateInputs('paddleCheckoutInit', 'true');
        services.updateInputs('paddleCheckoutData', '');
    },
});
