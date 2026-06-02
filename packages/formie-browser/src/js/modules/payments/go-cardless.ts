import { definePaymentModule } from '#modules/payments/api';
import { getPaymentProviderActionEventName } from '#utils/event-names';

type GoCardlessProviderOptions = {
    handle?: string;
};

/** Event name from PHP addSubmitData – dispatched on form when backend returns GoCardless redirect URL */
const REDIRECT_EVENT = getPaymentProviderActionEventName('go-cardless', 'redirect');

export const goCardlessModule = definePaymentModule<GoCardlessProviderOptions, null, null>({
    id: 'go-cardless',
    defaultRequiredInputSuffixes: [],
    load: async() => null,
    setup: async({ services, root }) => {
        const handler = (event: Event) => {
            const e = event as CustomEvent<{ data?: { redirectUrl?: string } }>;
            const data = e.detail?.data;

            if (!data?.redirectUrl) {
                services.addError('Missing GoCardless redirect URL.');

                return;
            }

            window.location.href = data.redirectUrl;
        };

        root.addEventListener(REDIRECT_EVENT, handler as EventListener);

        return {
            destroy: () => {
                root.removeEventListener(REDIRECT_EVENT, handler as EventListener);
            },
        };
    },
});
