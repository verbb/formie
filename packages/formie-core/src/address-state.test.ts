import { afterEach, describe, expect, it, vi } from 'vitest';

import { addressStatePart, cachedAddressSubdivisions, loadAddressSubdivisions } from './address-state.js';
import type { ClientFieldDefinition } from './types.js';

function statePart(input: Record<string, unknown> = {}): ClientFieldDefinition {
    return {
        id: 's', uid: 's', handle: 'state', type: 'address-state', label: 'State / Province', required: true, validation: [],
        input: { fieldKind: 'text', inputMode: 'dropdownWhenAvailable', hideWhenUnused: true, useSearchable: false, optionLabel: 'name', optionValue: 'name', subdivisionsUrl: 'https://example.test/actions/formie/address/subdivisions', ...input },
    } as unknown as ClientFieldDefinition;
}

function respond(data: Record<string, unknown>) {
    return vi.fn(async() => new Response(JSON.stringify(data), { status: 200, headers: { 'Content-Type': 'application/json' } }));
}

afterEach(() => vi.unstubAllGlobals());

describe('Address State / Province', () => {
    it('leaves a text State / Province as it is', () => {
        const part = statePart({ inputMode: 'text' });

        expect(addressStatePart(part, 'AU', 'Victoria')).toEqual({ field: part, hidden: false, value: 'Victoria', loading: false });
    });

    it('hides without a country, or waits disabled when it should stay', () => {
        expect(addressStatePart(statePart(), '', 'Victoria')).toMatchObject({ hidden: true, value: '' });
        expect(addressStatePart(statePart({ hideWhenUnused: false }), '', 'Victoria')).toMatchObject({ hidden: false, value: 'Victoria', field: { meta: { disabled: true } } });
    });

    it('shows the country subdivisions as a dropdown named after them once loaded', async() => {
        const fetch = respond({ administrativeAreaLabel: 'State', administrativeAreaUsed: true, subdivisions: [{ label: 'Victoria', value: 'Victoria', short: 'VIC' }, { label: 'Queensland', value: 'Queensland', short: 'QLD' }] });
        vi.stubGlobal('fetch', fetch);
        const part = statePart({ placeholder: 'Pick one' });

        expect(addressStatePart(part, 'AU', 'vic')).toMatchObject({ loading: true });
        await loadAddressSubdivisions(part, 'AU');
        await loadAddressSubdivisions(part, 'AU');

        expect(fetch).toHaveBeenCalledTimes(1);
        expect(String((fetch.mock.calls[0] as unknown[])[0])).toContain('country=AU');
        const shown = addressStatePart(part, 'AU', 'vic');
        expect(shown).toMatchObject({ hidden: false, loading: false, value: 'Victoria' });
        expect(shown.field.label).toBe('State');
        expect(shown.field.input).toMatchObject({ fieldKind: 'select', defaultValue: 'Victoria', options: [{ label: 'Pick one', value: '' }, { label: 'Victoria', value: 'Victoria' }, { label: 'Queensland', value: 'Queensland' }] });
        // An answer from another country can't be chosen from this one's dropdown.
        expect(addressStatePart(part, 'AU', 'Ontario').value).toBe('');
    });

    it('hides for countries that do not use it and keeps text when there is no list', async() => {
        vi.stubGlobal('fetch', respond({ administrativeAreaLabel: 'Province', administrativeAreaUsed: false, subdivisions: [] }));
        await loadAddressSubdivisions(statePart(), 'SG');
        expect(addressStatePart(statePart(), 'SG', 'Somewhere')).toMatchObject({ hidden: true, value: '' });
        expect(addressStatePart(statePart({ hideWhenUnused: false }), 'SG', 'Somewhere')).toMatchObject({ hidden: false, value: 'Somewhere', field: { label: 'Province' } });

        vi.stubGlobal('fetch', vi.fn(async() => new Response('', { status: 500 })));
        expect(await loadAddressSubdivisions(statePart(), 'XX')).toBeNull();
        expect(cachedAddressSubdivisions(statePart(), 'XX')).toBeNull();
        expect(addressStatePart(statePart(), 'XX', 'Somewhere')).toMatchObject({ hidden: false, value: 'Somewhere', loading: false });
    });
});
