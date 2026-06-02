import { definePaymentModule } from '#modules/payments/api';
import { waitFor } from '#utils/async';
import { loadScriptAndEnsureGlobal } from '#utils/scripts';

declare global {
    interface Window {
        eCrypt?: {
            encryptValue(value: string, key: string): string;
        };
    }
}

type EwayProviderOptions = {
    cseKey?: string;
};

const SCRIPT_SOURCES = [
    {
        id: 'FORMIE_EWAY_SCRIPT_MIN',
        src: 'https://secure.ewaypayments.com/scripts/eCrypt.min.js',
    },
    {
        id: 'FORMIE_EWAY_SCRIPT',
        src: 'https://secure.ewaypayments.com/scripts/eCrypt.js',
    },
];

type EwayEncryptApi = {
    encryptValue: (value: string, key: string) => string;
};

async function ensureEwayEncryptApi(): Promise<EwayEncryptApi> {
    let lastError: Error | null = null;

    for (const source of SCRIPT_SOURCES) {
        try {
            await loadScriptAndEnsureGlobal<unknown>('eCrypt', {
                id: source.id,
                src: source.src,
                timeoutMs: 10000,
            });

            const api = await waitFor<EwayEncryptApi>(() => {
                const candidate = (window as Window).eCrypt as EwayEncryptApi | undefined;

                if (candidate && typeof candidate.encryptValue === 'function') {
                    return candidate;
                }

                return null;
            }, {
                timeoutMs: 10000,
                intervalMs: 50,
            });

            return api;
        } catch (error) {
            lastError = error instanceof Error ? error : new Error('Unknown eWay script load error.');
        }

        // If a prior load inserted a stale/failed script element, remove all
        // candidate tags so the next source can retry cleanly.
        SCRIPT_SOURCES.forEach(({ id }) => {
            document.getElementById(id)?.remove();
        });
    }

    throw lastError || new Error('Eway encryption script failed to load.');
}

export const ewayModule = definePaymentModule<EwayProviderOptions, EwayEncryptApi | null, null>({
    id: 'eway',
    defaultRequiredInputSuffixes: ['ewayTokenData'],
    load: async(ctx) => {
        const { provider } = ctx.options;
        const cseKey = provider.cseKey as string | undefined;

        if (!cseKey?.trim()) {
            console.error('[formie] Missing cseKey for Eway.');
            return null;
        }

        return ensureEwayEncryptApi();
    },
    onBeforeAuthorize: async(args) => {
        const { field, services, provider, api } = args;
        const cseKey = provider.cseKey as string | undefined;

        if (!cseKey?.trim()) {
            services.addError('Missing cseKey for Eway.');

            return false;
        }

        let eCrypt = api;

        if (!eCrypt?.encryptValue) {
            try {
                eCrypt = await ensureEwayEncryptApi();
            } catch (error) {
                services.addError(error instanceof Error ? error.message : 'Eway encryption script failed to load.');

                return false;
            }
        }

        const cardholderName = field.querySelector<HTMLInputElement>('[data-eway-card="cardholder-name"]')?.value ?? '';
        const cardNumber = field.querySelector<HTMLInputElement>('[data-eway-card="card-number"]')?.value ?? '';
        const expiryDate = field.querySelector<HTMLInputElement>('[data-eway-card="expiry-date"]')?.value ?? '';
        const securityCode = field.querySelector<HTMLInputElement>('[data-eway-card="security-code"]')?.value ?? '';

        try {
            const cardDetails = {
                cardholderName,
                cardNumber: eCrypt.encryptValue(cardNumber, cseKey),
                expiryDate,
                securityCode: eCrypt.encryptValue(securityCode, cseKey),
            };

            services.updateInputs('ewayTokenData', JSON.stringify(cardDetails));

            return true;
        } catch (e) {
            services.addError(e instanceof Error ? e.message : 'Failed to encrypt card details.');

            return false;
        }
    },
    onAfterSubmit: async({ services }) => {
        services.updateInputs('ewayTokenData', '');
    },
});
