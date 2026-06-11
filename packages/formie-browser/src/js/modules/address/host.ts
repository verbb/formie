import type { ModuleSetupContext } from '#contracts/modules';
import {
    ADDRESS_LOCATION_SELECTOR,
    DEFAULT_AUTOCOMPLETE_SELECTOR,
    findAddressFieldInput,
    type AddressFieldInputKey,
} from '#modules/address/constants';

type Cleanup = () => void;

export type AddressModuleOptions<TProvider extends Record<string, unknown> = Record<string, unknown>> = {
    handle?: string;
} & TProvider;

export type NormalizedAddressModuleOptions<TProvider extends Record<string, unknown>> = {
    handle: string;
    provider: TProvider;
};

const ADDRESS_OPTION_KEYS = new Set(['handle']);

export type AddressHostServices = {
    root: Element;
    field: Element;
    form: HTMLFormElement | null;
    input: {
        getAutocomplete: () => HTMLInputElement | null;
        setValue: (selector: AddressFieldInputKey, value: string, fallback?: string) => void;
    };
    location: {
        getButton: () => HTMLElement | null;
        onUseLocation: (callback: (position: GeolocationPosition) => void) => Cleanup;
    };
    events: {
        onField: (eventName: string, callback: EventListener) => Cleanup;
    };
};

function getAddressProviderHandle(id: string, options: Record<string, unknown>): string {
    const handle = typeof options.handle === 'string' && options.handle.trim() !== ''
        ? options.handle.trim()
        : '';

    return handle || id;
}

export function normalizeAddressModuleOptions<TProvider extends Record<string, unknown>>(
    id: string,
    rawOptions: Record<string, unknown> | undefined,
): NormalizedAddressModuleOptions<TProvider> {
    const options = rawOptions || {};
    const provider = Object.entries(options).reduce((carry, [key, value]) => {
        if (ADDRESS_OPTION_KEYS.has(key)) {
            return carry;
        }

        carry[key] = value;

        return carry;
    }, {} as Record<string, unknown>) as TProvider;

    return {
        handle: getAddressProviderHandle(id, options),
        provider,
    };
}

function bindDomEvent(target: EventTarget, eventName: string, callback: EventListener): Cleanup {
    target.addEventListener(eventName, callback);

    return () => {
        target.removeEventListener(eventName, callback);
    };
}

export function createAddressHostServices(
    ctx: ModuleSetupContext,
): AddressHostServices {
    const field = ctx.target;
    const form = ctx.form;
    const root = ctx.root;

    const autocompleteSelector = DEFAULT_AUTOCOMPLETE_SELECTOR;

    return {
        root,
        field,
        form,
        input: {
            getAutocomplete: () => {
                return field.querySelector(autocompleteSelector) as HTMLInputElement | null;
            },
            setValue: (selectorKey, value, fallback) => {
                const el = findAddressFieldInput(field, selectorKey);

                if (!el) {
                    return;
                }

                const nextValue = value || fallback || '';

                if (el.value === nextValue) {
                    return;
                }

                el.value = nextValue;
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            },
        },
        location: {
            getButton: () => {
                return field.querySelector(ADDRESS_LOCATION_SELECTOR) as HTMLElement | null;
            },
            onUseLocation: (callback) => {
                const btn = field.querySelector(ADDRESS_LOCATION_SELECTOR);

                if (!btn) {
                    return () => {};
                }

                const handler = (e: Event) => {
                    e.preventDefault();

                    if (!navigator.geolocation) {
                        return;
                    }

                    navigator.geolocation.getCurrentPosition(
                        callback,
                        () => {},
                        { enableHighAccuracy: true },
                    );
                };

                btn.addEventListener('click', handler);

                return () => {
                    btn.removeEventListener('click', handler);
                };
            },
        },
        events: {
            onField: (eventName, callback) => bindDomEvent(field, eventName, callback),
        },
    };
}
