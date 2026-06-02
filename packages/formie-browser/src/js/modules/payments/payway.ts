import paywayCss from '#theme/integrations/_payway.css?inline';

import { definePaymentModule } from '#modules/payments/api';
import { ensureModuleStyles } from '#modules/styles';
import { loadScriptAndEnsureGlobal } from '#utils/scripts';

ensureModuleStyles('payway', [paywayCss]);

type PayWayFrame = {
    getToken: (callback: (err: Error | null, data?: { singleUseTokenId: string }) => void) => void;
    destroy: () => void;
};

type PayWayGlobal = {
    createCreditCardFrame: (
        opts: { layout: string; publishableApiKey: string; tokenMode: string },
        callback: (err: Error | null, frame?: PayWayFrame) => void
    ) => void;
};

type PayWayProviderOptions = {
    publishableKey?: string;
};

const SCRIPT_ID = 'FORMIE_PAYWAY_SCRIPT';

export const paywayModule = definePaymentModule<PayWayProviderOptions, null, PayWayFrame | null>({
    id: 'payway',
    defaultRequiredInputSuffixes: ['paywayTokenId'],
    load: async (ctx) => {
        const { provider } = ctx.options;
        const publishableKey = provider.publishableKey as string | undefined;

        if (!publishableKey?.trim()) {
            console.error('[formie] Missing publishableKey for PayWay.');

            return null;
        }

        await loadScriptAndEnsureGlobal<PayWayGlobal>('payway', {
            id: SCRIPT_ID,
            src: 'https://api.payway.com.au/rest/v1/payway.js',
        });

        return null;
    },
    mount: async (args) => {
        const { field, services, options } = args;
        const provider = options.provider as PayWayProviderOptions;
        const placeholder = field.querySelector<HTMLElement>('[data-formie-payway-button]');

        if (!placeholder) {
            return null;
        }

        const payway = (window as unknown as { payway: PayWayGlobal }).payway;

        if (!payway) {
            return null;
        }

        return new Promise<PayWayFrame | null>((resolve) => {
            payway.createCreditCardFrame({
                layout: 'wide',
                publishableApiKey: provider.publishableKey || '',
                tokenMode: 'callback',
            }, (err, frame) => {
                if (err || !frame) {
                    services.addError(err?.message || 'PayWay frame failed to load.');
                    resolve(null);

                    return;
                }

                resolve(frame);
            });
        });
    },
    unmount: async (args) => {
        args.widget?.destroy();
    },
    onBeforeAuthorize: async (args) => {
        const { widget, services } = args;

        if (!widget) {
            services.addError('PayWay card frame is not ready.');

            return false;
        }

        return new Promise<boolean>((resolve) => {
            widget.getToken((err, data) => {
                if (err) {
                    services.addError(err.message);
                    resolve(false);

                    return;
                }

                if (data?.singleUseTokenId) {
                    services.updateInputs('paywayTokenId', data.singleUseTokenId);

                    resolve(true);
                } else {
                    services.addError('Tokenization failed.');
                    resolve(false);
                }
            });
        });
    },
    onAfterSubmit: async ({ services }) => {
        services.updateInputs('paywayTokenId', '');
    },
});
