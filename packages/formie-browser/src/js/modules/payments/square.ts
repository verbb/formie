import { definePaymentModule } from '#modules/payments/api';
import { loadScriptAndEnsureGlobal } from '#utils/scripts';

type SquareCard = {
    tokenize: () => Promise<{ status: string; token?: string; errors?: Array<{ message: string }> }>;
    attach: (element: HTMLElement) => Promise<void>;
};

type SquarePayments = {
    card: () => Promise<SquareCard>;
};

type SquareGlobal = {
    payments: (applicationId: string, locationId: string) => SquarePayments;
};

type SquareProviderOptions = {
    applicationId?: string;
    locationId?: string;
    environment?: string;
};

const SCRIPT_ID = 'FORMIE_SQUARE_SCRIPT';

export const squareModule = definePaymentModule<SquareProviderOptions, SquareGlobal | null, SquareCard | null>({
    id: 'square',
    defaultRequiredInputSuffixes: ['squarePaymentId'],
    load: async(ctx) => {
        const { provider } = ctx.options;
        const applicationId = provider.applicationId as string | undefined;
        const locationId = provider.locationId as string | undefined;

        if (!applicationId?.trim() || !locationId?.trim()) {
            console.error('[formie] Missing applicationId or locationId for Square.');

            return null;
        }

        const environment = provider.environment as string;
        const src = environment === 'sandbox'
            ? 'https://sandbox.web.squarecdn.com/v1/square.js'
            : 'https://web.squarecdn.com/v1/square.js';

        await loadScriptAndEnsureGlobal<SquareGlobal>('Square', {
            id: SCRIPT_ID,
            src,
        });

        return (window as unknown as { Square: SquareGlobal }).Square;
    },
    mount: async(args) => {
        const { api, field, services, options } = args;
        const provider = options.provider as SquareProviderOptions;
        const placeholder = field.querySelector<HTMLElement>('[data-formie-square-button]');

        if (!placeholder || !api) {
            return null;
        }

        try {
            const payments = api.payments(provider.applicationId || '', provider.locationId || '');
            const card = await payments.card();

            await card.attach(placeholder);

            return card;
        } catch (error) {
            services.addError(error instanceof Error ? error.message : 'Unable to initialize payment.');

            return null;
        }
    },
    unmount: async() => {
        // Square card doesn't expose destroy, detach handled by removing element
    },
    onBeforeAuthorize: async(args) => {
        const { widget, services } = args;

        if (!widget) {
            services.addError('Square card is not ready.');

            return false;
        }

        try {
            const result = await widget.tokenize();

            if (result.status === 'OK' && result.token) {
                services.updateInputs('squarePaymentId', result.token);

                return true;
            }

            services.addError(result.errors?.[0]?.message || 'Tokenization failed.');

            return false;
        } catch {
            services.addError('Payment tokenization failed. Please try again.');

            return false;
        }
    },
    onAfterSubmit: async({ services }) => {
        services.updateInputs('squarePaymentId', '');
    },
});
