import { defineAddressModule } from '#modules/address/api';
import { findAddressFieldInput, type AddressFieldInputKey } from '#modules/address/constants';
import { loadScriptAndEnsureGlobal } from '#utils/scripts';

type PcaFieldMode = {
    SEARCH: number;
    POPULATE: number;
    COUNTRY: number;
};

type PcaAddressInstance = {
    load?: () => void;
};

type PcaAddress = new (
    fields: Array<{ element: string; field: string; mode: number }>,
    options: Record<string, unknown>,
) => PcaAddressInstance;

type PcaGlobal = {
    fieldMode: PcaFieldMode;
    Address: PcaAddress;
};

type LoqateProviderOptions = {
    apiKey?: string;
    namespace?: string;
    reconfigurableOptions?: Record<string, unknown>;
};

const SCRIPT_ID = 'FORMIE_LOQATE_SCRIPT';

export const loqateModule = defineAddressModule<
    LoqateProviderOptions,
    PcaGlobal,
    PcaAddressInstance
>({
    id: 'loqate',
    load: async () => {
        await loadScriptAndEnsureGlobal<PcaGlobal>('pca', {
            id: SCRIPT_ID,
            src: 'https://services.pcapredict.com/js/address-3.91.min.js',
            async: true,
            defer: true,
        });

        const link = document.createElement('link');
        link.href = 'https://services.pcapredict.com/css/address-3.91.min.css';
        link.rel = 'stylesheet';
        link.type = 'text/css';

        if (!document.querySelector(`link[href="${link.href}"]`)) {
            document.body.appendChild(link);
        }

        return (window as unknown as { pca: PcaGlobal }).pca;
    },
    mount: ({ api, field, services, provider }) => {
        const namespace = provider.namespace || '';
        const apiKey = provider.apiKey || '';

        if (!apiKey) {
            throw new Error('Loqate API key is required');
        }

        // Loqate PCA expects element to be an id string, name string, or RegExp matching ids – not CSS selectors
        const getLoqateElementRef = (handle: AddressFieldInputKey): string => {
            if (namespace) {
                return `${namespace}[${handle}]`;
            }

            const el = findAddressFieldInput(field, handle);

            if (el?.name) {
                return el.name;
            }

            if (el?.id) {
                return el.id;
            }

            return '';
        };

        const autoCompleteRef = getLoqateElementRef('autoComplete');

        if (!autoCompleteRef) {
            throw new Error('Loqate: could not find autocomplete input within address field');
        }

        const fields = [
            { element: autoCompleteRef, field: '', mode: api.fieldMode.SEARCH },
            { element: getLoqateElementRef('address1'), field: 'Line1', mode: api.fieldMode.POPULATE },
            { element: getLoqateElementRef('address2'), field: 'Line2', mode: api.fieldMode.POPULATE },
            { element: getLoqateElementRef('address3'), field: 'Line3', mode: api.fieldMode.POPULATE },
            { element: getLoqateElementRef('city'), field: 'City', mode: api.fieldMode.POPULATE },
            { element: getLoqateElementRef('state'), field: 'Province', mode: api.fieldMode.POPULATE },
            { element: getLoqateElementRef('zip'), field: 'PostalCode', mode: api.fieldMode.POPULATE },
            { element: getLoqateElementRef('country'), field: 'CountryName', mode: api.fieldMode.COUNTRY },
        ].filter((f) => f.element);

        const control = new api.Address(fields, {
            key: apiKey,
            simulateReactEvents: true,
            ...(provider.reconfigurableOptions || {}),
        });

        if (typeof control.load === 'function') {
            control.load();
        }

        return control;
    },
});
