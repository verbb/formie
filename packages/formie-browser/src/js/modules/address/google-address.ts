import { defineAddressModule } from '#modules/address/api';
import type { AddressHostServices } from '#modules/address/host';
import { getAddressProviderEventName } from '#utils/event-names';
import { ensureGlobal, loadExternalScript } from '#utils/scripts';

type GoogleMaps = typeof google;

type GoogleAddressProviderOptions = {
    apiKey?: string;
    options?: Record<string, unknown>;
    countryDefaultValue?: string;
};

const SCRIPT_ID = 'FORMIE_GOOGLE_ADDRESS_SCRIPT';
const CALLBACK_NAME = 'formieGoogleMapsReady';

let loadPromise: Promise<GoogleMaps> | null = null;

/** Load Google Maps with callback; required when using loading=async. */
async function loadGoogleMapsScript(apiKey: string): Promise<GoogleMaps> {
    const w = window as unknown as Record<string, unknown>;
    const existing = w.google;

    if (typeof existing !== 'undefined' && existing !== null) {
        const maps = (existing as GoogleMaps).maps;
        if (maps?.places?.PlaceAutocompleteElement) {
            return existing as GoogleMaps;
        }
        const imp = maps as { importLibrary?: (name: string) => Promise<unknown> } | undefined;
        if (typeof imp?.importLibrary === 'function') {
            await imp.importLibrary('places');
        }
        return existing as GoogleMaps;
    }

    if (loadPromise) {
        return loadPromise;
    }

    const existingScript = document.getElementById(SCRIPT_ID);
    if (existingScript) {
        const google = await ensureGlobal<GoogleMaps>('google', 10000);
        const maps = google?.maps as { importLibrary?: (name: string) => Promise<unknown> } | undefined;
        if (typeof maps?.importLibrary === 'function') {
            await maps.importLibrary('places');
        }
        return google;
    }

    const url = new URL('https://maps.googleapis.com/maps/api/js');
    url.searchParams.set('key', apiKey);
    url.searchParams.set('loading', 'async');
    url.searchParams.set('libraries', 'places');
    url.searchParams.set('callback', CALLBACK_NAME);

    loadPromise = (async () => {
        const ready = new Promise<GoogleMaps>((resolve, reject) => {
            const t = setTimeout(() => {
                if (w[CALLBACK_NAME]) {
                    delete w[CALLBACK_NAME];
                    reject(new Error('Google Maps API load timeout'));
                }
            }, 15000);
            w[CALLBACK_NAME] = () => {
                clearTimeout(t);
                delete w[CALLBACK_NAME];
                resolve(w.google as GoogleMaps);
            };
        });

        await loadExternalScript({
            id: SCRIPT_ID,
            src: url.toString(),
            async: true,
            defer: true,
        });

        const google = await ready;
        const maps = google?.maps as { importLibrary?: (name: string) => Promise<unknown> } | undefined;
        if (typeof maps?.importLibrary === 'function') {
            await maps.importLibrary('places');
        }
        return google;
    })();

    try {
        return await loadPromise;
    } catch (e) {
        loadPromise = null;
        throw e;
    }
}

function setFieldValues(
    services: AddressHostServices,
    data: {
        address1?: string;
        city?: string;
        state?: string;
        zip?: string;
        country?: string;
        formattedAddress?: string;
    },
): void {
    if (data.formattedAddress) {
        services.input.setValue('autoComplete', data.formattedAddress);
    }

    if (data.address1) {
        services.input.setValue('address1', data.address1);
    }

    if (data.city !== undefined) {
        services.input.setValue('city', data.city);
    }

    if (data.state !== undefined) {
        services.input.setValue('state', data.state);
    }

    if (data.zip !== undefined) {
        services.input.setValue('zip', data.zip);
    }

    if (data.country !== undefined) {
        services.input.setValue('country', data.country);
    }
}

function componentMap(): Record<string, 'shortText' | 'longText'> {
    return {
        subpremise: 'shortText',
        street_number: 'shortText',
        route: 'longText',
        postal_town: 'longText',
        locality: 'longText',
        administrative_area_level_1: 'shortText',
        country: 'shortText',
        postal_code: 'shortText',
    };
}

type AddressComponent = {
    types: string[];
    shortText?: string;
    longText?: string;
    short_text?: string;
    long_text?: string;
};

function parseAddressComponents(components: AddressComponent[]): Record<string, string> {
    const map = componentMap();
    const out: Record<string, string> = {};

    for (const comp of components) {
        const type = comp.types?.[0];

        if (!type || !map[type as keyof typeof map]) continue;

        const fieldKey = map[type as keyof typeof map];
        const val = (comp as unknown as Record<string, string>)[fieldKey]
            ?? comp.short_text
            ?? comp.long_text
            ?? '';

        out[type] = val;
    }

    return out;
}

function buildAddressFromComponents(formData: Record<string, string>): {
    address1: string;
    city: string;
    state: string;
    zip: string;
    country: string;
} {
    let address1 = '';

    if (formData.street_number || formData.route) {
        address1 = [formData.street_number, formData.route].filter(Boolean).join(' ');

        if (formData.subpremise) {
            address1 = `${formData.subpremise}/${address1}`;
        }
    }

    return {
        address1,
        city: formData.locality || formData.postal_town || '',
        state: formData.administrative_area_level_1 || '',
        zip: formData.postal_code || '',
        country: formData.country || '',
    };
}

function hasCountryRestriction(options: Record<string, unknown>): boolean {
    const componentRestrictions = options.componentRestrictions;

    if (componentRestrictions && typeof componentRestrictions === 'object') {
        const country = (componentRestrictions as { country?: unknown }).country;

        if (Array.isArray(country) ? country.length > 0 : Boolean(country)) {
            return true;
        }
    }

    const includedRegionCodes = options.includedRegionCodes;

    return Array.isArray(includedRegionCodes) && includedRegionCodes.length > 0;
}

export function buildGoogleAutocompleteOptions(provider: GoogleAddressProviderOptions): Record<string, unknown> {
    const options = { types: ['geocode'], ...(provider.options || {}) };
    const country = provider.countryDefaultValue?.trim().toLowerCase();

    if (!country || hasCountryRestriction(options)) {
        return options;
    }

    return {
        ...options,
        componentRestrictions: { country },
        includedRegionCodes: [country.toUpperCase()],
    };
}

export const googleAddressModule = defineAddressModule<
    GoogleAddressProviderOptions,
    GoogleMaps,
    google.maps.places.PlaceAutocompleteElement
>({
    id: 'google-address',
    load: async ({ options }) => {
        const apiKey = options.provider.apiKey;

        if (!apiKey) {
            throw new Error('Google Places API key is required');
        }

        return loadGoogleMapsScript(apiKey);
    },
    mount: async ({ api, field, services, provider }) => {
        const input = services.input.getAutocomplete();
        const PlaceAutocompleteElement = api?.maps?.places?.PlaceAutocompleteElement;

        if (!input || typeof PlaceAutocompleteElement !== 'function') {
            console.warn('[formie] Google Places API not ready; address autocomplete skipped.');
            return null;
        }

        const options = buildGoogleAutocompleteOptions(provider);
        const autocomplete = new PlaceAutocompleteElement(options);
        const inputHeight = window.getComputedStyle(input).height;

        autocomplete.style.height = inputHeight;
        autocomplete.style.boxSizing = 'border-box';

        let wrapper = input.parentElement;

        if (!wrapper?.classList.contains('formie-autocomplete-wrapper')) {
            wrapper = document.createElement('div');
            wrapper.classList.add('formie-autocomplete-wrapper');
            input.parentNode?.insertBefore(wrapper, input);
            wrapper.appendChild(input);
        }

        const savedValue = input.value;

        if (savedValue) {
            const overlay = document.createElement('div');
            overlay.classList.add('formie-autocomplete-placeholder');
            overlay.textContent = savedValue;

            (wrapper as HTMLElement).style.position = 'relative';
            overlay.style.cssText = `
                position: absolute; left: 0; top: 0; height: ${inputHeight};
                line-height: ${inputHeight}; width: 100%; padding: 0 2.5rem;
                pointer-events: none; color: #6B7280; font-size: 14px; z-index: 1;
            `;

            wrapper.appendChild(overlay);

            autocomplete.addEventListener('focusin', () => {
                overlay.style.display = 'none';
            });

            autocomplete.addEventListener('focusout', () => {
                if ((input as HTMLInputElement).value) {
                    overlay.style.display = '';
                }
            });
        }

        wrapper.replaceChild(autocomplete, input);
        input.type = 'hidden';
        input.name = (input as HTMLInputElement).getAttribute('name') || '';
        wrapper.appendChild(input);

        const onSelect = async (ev: Event): Promise<void> => {
            const e = ev as unknown as { placePrediction?: { toPlace: () => Promise<{ addressComponents?: unknown[]; formattedAddress?: string; fetchFields: (opts: { fields: string[] }) => Promise<void> }> } };
            const pred = e.placePrediction;

            if (!pred) return;

            const place = await pred.toPlace();
            await place.fetchFields({ fields: ['addressComponents', 'formattedAddress'] });

            if (!place.addressComponents) return;

            const formData = parseAddressComponents(
                place.addressComponents as AddressComponent[],
            );
            const address = buildAddressFromComponents(formData);

            setFieldValues(services, {
                ...address,
                formattedAddress: place.formattedAddress,
            });

            field.dispatchEvent(
                new CustomEvent(getAddressProviderEventName('google', 'populate'), {
                    bubbles: true,
                    detail: {
                        addressProvider: 'google',
                        place,
                        formattedAddress: place.formattedAddress,
                        addressComponents: place.addressComponents,
                    },
                }),
            );
        };
        autocomplete.addEventListener('gmp-select', onSelect as EventListener);

        return autocomplete;
    },
    onCurrentLocation: async (position, { field, services }) => {
        const { latitude, longitude } = position.coords;
        const form = services.form;
        const actionUrl = form?.action || window.location.href;
        const fieldHandle = field.getAttribute('data-formie-field-handle')?.trim();
        const formHandle = (form?.querySelector('[name="handle"]') as HTMLInputElement | null)?.value?.trim();

        if (!formHandle || !fieldHandle) return;

        try {
            const formData = new FormData();
            formData.append('action', 'formie/address/google-places-geocode');
            formData.append('latlng', `${latitude},${longitude}`);
            formData.append('handle', formHandle);
            formData.append('fieldHandle', fieldHandle);

            const response = await fetch(actionUrl, {
                method: 'POST',
                body: formData,
                credentials: 'include',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });

            const data = await response.json();

            if (data?.results?.[0]?.address_components) {
                const formDataParsed = parseAddressComponents(
                    data.results[0].address_components as AddressComponent[],
                );
                const address = buildAddressFromComponents(formDataParsed);
                setFieldValues(services, address);
            }
        } catch {
            // Silently fail geocode
        }
    },
});
