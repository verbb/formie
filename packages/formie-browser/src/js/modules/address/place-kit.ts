import placeKitCss from '@placekit/autocomplete-js/dist/placekit-autocomplete.css?inline';
import { defineAddressModule } from '#modules/address/api';
import { ensureModuleStyles } from '#modules/styles';
import { getAddressProviderEventName } from '#utils/event-names';

ensureModuleStyles('place-kit', [placeKitCss]);

type PlaceKitPicker = {
    on: (event: string, callback: (value: unknown, item: PlaceKitItem) => void) => void;
};

type PlaceKitItem = {
    street?: { number?: string; name?: string; suffix?: string };
    city?: string;
    administrative?: string;
    zipcode?: string | string[];
    country?: string;
    countrycode?: string;
};

type PlaceKitProviderOptions = {
    apiKey?: string;
    options?: Record<string, unknown>;
};

export const placeKitModule = defineAddressModule<
    PlaceKitProviderOptions,
    (apiKey: string, options: Record<string, unknown>) => PlaceKitPicker,
    PlaceKitPicker
>({
    id: 'place-kit',
    load: async () => {
        const module = await import('@placekit/autocomplete-js');

        const placekitAutocomplete = module.default;

        return (apiKey: string, options: Record<string, unknown>) => {
            return placekitAutocomplete(apiKey, options as Parameters<typeof placekitAutocomplete>[1]) as PlaceKitPicker;
        };
    },
    mount: async ({ api, field, services, provider }) => {
        const input = services.input.getAutocomplete();

        if (!input || !provider.apiKey) {
            throw new Error('PlaceKit API key is required');
        }

        const options = { target: input as HTMLInputElement, ...(provider.options || {}) };

        field.dispatchEvent(
            new CustomEvent(getAddressProviderEventName('place-kit', 'before-init'), {
                bubbles: true,
                detail: { addressProvider: 'place-kit', options },
            }),
        );

        // PlaceKit's PKAOptions requires target; we provide it.
        const picker = api(provider.apiKey, options as { target: HTMLInputElement });

        picker.on('pick', (value, item: PlaceKitItem) => {
            field.dispatchEvent(
                new CustomEvent(getAddressProviderEventName('place-kit', 'populate'), {
                    bubbles: true,
                    detail: { addressProvider: 'place-kit', addressComponents: item },
                }),
            );

            let address1 = '';

            if (item.street) {
                const num = item.street.number ?? '';
                const name = item.street.name ?? '';
                const suffix = item.street.suffix ?? '';

                address1 = [num, name, suffix].filter(Boolean).join(' ');
            }

            const zip = Array.isArray(item.zipcode) ? item.zipcode[0] : item.zipcode;

            // Country: prefer countrycode (uppercase) for select values, else full name
            const countryValue = item.countrycode
                ? (item.countrycode as string).toUpperCase()
                : (item.country || '');

            services.input.setValue('address1', address1);
            services.input.setValue('city', item.city || '');
            services.input.setValue('state', item.administrative || '');
            services.input.setValue('zip', typeof zip === 'string' ? zip : '');
            services.input.setValue('country', countryValue || item.country || '');
        });

        return picker;
    },
});
