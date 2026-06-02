import { definePaymentModule } from '#modules/payments/api';
import type { PaymentHostServices } from '#modules/payments/host';
import { getPaymentProviderActionEventName } from '#utils/event-names';

type MollieProviderOptions = {
    handle?: string;
};

/** Event name from PHP addSubmitData – dispatched on form when backend returns Mollie checkout URL */
const REDIRECT_EVENT = getPaymentProviderActionEventName('mollie', 'redirect');

export const mollieModule = definePaymentModule<MollieProviderOptions, null, null>({
    id: 'mollie',
    defaultRequiredInputSuffixes: [],
    load: async() => null,
    setup: async({ services, root }) => {
        const handler = (event: Event) => {
            const e = event as CustomEvent<{ data?: { checkoutUrl?: string } }>;
            const data = e.detail?.data;

            if (!data?.checkoutUrl) {
                services.addError('Missing Mollie checkout URL.');

                return;
            }

            window.location.href = data.checkoutUrl;
        };

        root.addEventListener(REDIRECT_EVENT, handler as EventListener);

        return {
            destroy: () => {
                root.removeEventListener(REDIRECT_EVENT, handler as EventListener);
            },
        };
    },
});
