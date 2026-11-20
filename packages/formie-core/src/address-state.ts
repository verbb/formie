import { browserRequest, type BrowserRequestOptions } from './request-profile.js';
import type { ClientFieldDefinition } from './types.js';

/**
 * An Address field's State / Province part with **Input Mode** set to **Dropdown when available** shows the selected
 * country's subdivisions as a dropdown, names itself after them (such as "County" or "Prefecture"), and hides for
 * countries whose addresses don't use them, as the `formie:address-state` module does in server-rendered forms.
 * Adapters render it from the part definition `addressStatePart()` returns, so they keep their own controls.
 */
export type AddressSubdivisions = {
    label: string;
    used: boolean;
    options: Array<{ label: string; value: string; name?: string; short?: string }>;
};

export type AddressStatePart = {
    /** The part to render, with the country's label and, when it has subdivisions, a dropdown of them. */
    field: ClientFieldDefinition;
    hidden: boolean;
    /** The answer the part should hold for this country, which adapters store when it differs. */
    value: string;
    /** True while the country's subdivisions are loading. */
    loading: boolean;
};

const cache = new Map<string, AddressSubdivisions | null>();
const inflight = new Map<string, Promise<AddressSubdivisions | null>>();

export function isDynamicAddressStatePart(field: ClientFieldDefinition): boolean {
    return field.type === 'address-state' && field.input.inputMode === 'dropdownWhenAvailable' && typeof field.input.subdivisionsUrl === 'string';
}

function cacheKey(field: ClientFieldDefinition, country: string): string {
    return [field.input.subdivisionsUrl, country, field.input.optionLabel, field.input.optionValue].join('|');
}

/**
 * The country's subdivisions once loaded: null when they couldn't be loaded, and undefined until they have been.
 */
export function cachedAddressSubdivisions(field: ClientFieldDefinition, country: string): AddressSubdivisions | null | undefined {
    return cache.get(cacheKey(field, country));
}

export function loadAddressSubdivisions(field: ClientFieldDefinition, country: string, requestOptions: BrowserRequestOptions = {}): Promise<AddressSubdivisions | null> {
    const key = cacheKey(field, country);

    if (cache.has(key)) {
        return Promise.resolve(cache.get(key) ?? null);
    }

    let request = inflight.get(key);

    if (!request) {
        const url = new URL(String(field.input.subdivisionsUrl), typeof location === 'undefined' ? 'http://localhost' : location.href);
        url.searchParams.set('country', country);
        url.searchParams.set('optionLabel', field.input.optionLabel === 'short' ? 'short' : 'name');
        url.searchParams.set('optionValue', field.input.optionValue === 'short' ? 'short' : 'name');

        request = browserRequest(url.toString(), { headers: { Accept: 'application/json' } }, requestOptions)
            .then(async(response) => {
                if (!response.ok) return null;
                const data = await response.json() as Record<string, unknown>;
                const options = Array.isArray(data.subdivisions) ? data.subdivisions as AddressSubdivisions['options'] : [];

                return {
                    label: typeof data.administrativeAreaLabel === 'string' && data.administrativeAreaLabel ? data.administrativeAreaLabel : '',
                    used: data.administrativeAreaUsed !== false,
                    options: options.filter((option) => !!option && typeof option.value === 'string'),
                };
            })
            .catch(() => null)
            .then((result) => {
                cache.set(key, result);
                inflight.delete(key);

                return result;
            });
        inflight.set(key, request);
    }

    return request;
}

// A stored answer matches a subdivision by its value, name, short name or label, ignoring case.
function matchSubdivision(value: string, options: AddressSubdivisions['options']): string | null {
    const wanted = value.trim().toLowerCase();

    if (!wanted) return null;

    return options.find((option) => [option.value, option.name, option.short, option.label].some((text) => (text ?? '').toLowerCase() === wanted))?.value ?? null;
}

/**
 * The State / Province part for the chosen country. Without a country it's hidden when **Hide When Not Used** is on,
 * or a disabled text input otherwise. While the country's subdivisions load, the part stays as it is.
 */
export function addressStatePart(part: ClientFieldDefinition, country: unknown, value: unknown): AddressStatePart {
    const current = typeof value === 'string' ? value : '';

    if (!isDynamicAddressStatePart(part)) {
        return { field: part, hidden: false, value: current, loading: false };
    }

    const hideWhenUnused = part.input.hideWhenUnused !== false;
    const countryValue = typeof country === 'string' ? country.trim() : '';

    if (!countryValue) {
        // Without a country there's nothing to choose from, so the part waits for one.
        return hideWhenUnused
            ? { field: part, hidden: true, value: '', loading: false }
            : { field: { ...part, meta: { ...part.meta, disabled: true } }, hidden: false, value: current, loading: false };
    }

    const data = cachedAddressSubdivisions(part, countryValue);

    if (data === undefined) {
        return { field: part, hidden: false, value: current, loading: true };
    }

    if (data && !data.used && hideWhenUnused) {
        return { field: part, hidden: true, value: '', loading: false };
    }

    const label = data?.label || part.label;
    const options = data?.options ?? [];

    if (options.length === 0) {
        return { field: { ...part, label }, hidden: false, value: current, loading: false };
    }

    // A dropdown can only hold one of its options, so an answer from another country is cleared.
    const matched = matchSubdivision(current, options) ?? '';
    const placeholder = typeof part.input.placeholder === 'string' && part.input.placeholder ? part.input.placeholder : '';

    return {
        field: {
            ...part,
            label,
            input: {
                ...part.input,
                fieldKind: 'select',
                placeholder,
                options: [{ label: placeholder, value: '' }, ...options.map((option) => ({ label: option.label, value: option.value }))],
                defaultValue: matched,
            },
        },
        hidden: false,
        value: matched,
        loading: false,
    };
}
