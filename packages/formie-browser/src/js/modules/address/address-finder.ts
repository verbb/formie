import { defineAddressModule } from '#modules/address/api';
import { getAddressProviderEventName } from '#utils/event-names';
import { loadScriptAndEnsureGlobal } from '#utils/scripts';

type AddressFinderWidget = {
    on: (event: string, callback: (fullAddress: string, metaData: Record<string, string>) => void) => void;
};

type AddressFinderGlobal = {
    Widget: new (
        element: HTMLInputElement,
        apiKey: string,
        countryCode: string,
        widgetOptions?: Record<string, unknown>,
    ) => AddressFinderWidget;
};

type AddressFinderProviderOptions = {
    apiKey?: string;
    countryCode?: string;
    widgetOptions?: Record<string, unknown>;
};

const SCRIPT_ID = 'FORMIE_ADDRESS_FINDER_SCRIPT';

export const addressFinderModule = defineAddressModule<
    AddressFinderProviderOptions,
    AddressFinderGlobal,
    AddressFinderWidget
>({
    id: 'address-finder',
    load: async () => {
        return loadScriptAndEnsureGlobal<AddressFinderGlobal>('AddressFinder', {
            id: SCRIPT_ID,
            src: 'https://api.addressfinder.io/assets/v3/widget.js',
            async: true,
            defer: true,
        });
    },
    mount: ({ api, field, services, provider }) => {
        const input = services.input.getAutocomplete();

        if (!input || typeof api === 'undefined' || !api.Widget) {
            throw new Error('AddressFinder API not ready');
        }

        const apiKey = provider.apiKey || '';
        const countryCode = provider.countryCode || 'au';

        const widget = new api.Widget(
            input as HTMLInputElement,
            apiKey,
            countryCode,
            provider.widgetOptions,
        );

        widget.on('result:select', (fullAddress, metaData: Record<string, string>) => {
            if (metaData.address_line_2) {
                services.input.setValue('address1', metaData.address_line_2);
                services.input.setValue('address2', metaData.address_line_1);
            } else {
                services.input.setValue('address1', metaData.address_line_1 || '');
                services.input.setValue('address2', '');
            }

            services.input.setValue('city', metaData.locality_name || '');
            services.input.setValue('zip', metaData.postcode || '');
            services.input.setValue('state', metaData.state_territory || '');
            services.input.setValue('country', countryCode);

            field.dispatchEvent(
                new CustomEvent(getAddressProviderEventName('address-finder', 'populate'), {
                    bubbles: true,
                    detail: {
                        addressProvider: 'address-finder',
                        fullAddress,
                        metaData,
                    },
                }),
            );
        });

        return widget;
    },
});
