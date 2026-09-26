import { afterEach, describe, expect, it, vi } from 'vitest';
import { buildActionUrl, createRestClientTransport } from './rest';
import type { ClientFormDefinition, ClientFormSession } from './types';

afterEach(() => vi.unstubAllGlobals());

describe('buildActionUrl', () => {
    it('preserves Craft subdirectory install paths for absolute bases', () => {
        expect(buildActionUrl('https://example.test/craft/', '/actions/formie/client/forms/load'))
            .toBe('https://example.test/craft/actions/formie/client/forms/load');
    });

    it('joins relative subdirectory bases', () => {
        expect(buildActionUrl('/craft', '/actions/formie/client/forms/load'))
            .toBe('/craft/actions/formie/client/forms/load');
    });

    it('uses root-relative actions when the base is the site root', () => {
        expect(buildActionUrl('https://example.test/', '/actions/formie/client/forms/load'))
            .toBe('https://example.test/actions/formie/client/forms/load');
    });
});

describe('submission outcomes', () => {
    it.each([[422, 'validationFailed'], [409, 'stateConflict'], [403, 'rejected']])('preserves a domain outcome with HTTP %s', async (status, outcome) => {
        const payload = { success: false, outcome, version: 2, errors: { fields: { name: ['Required'] } } };
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(JSON.stringify(payload), { status: Number(status) })));
        const transport = createRestClientTransport({ endpoint: '/', formHandle: 'contact' });
        const result = await transport.submit({
            definition: { pages: [] } as unknown as ClientFormDefinition,
            session: { version: 1, tokens: {} } as ClientFormSession,
            values: {}, action: 'submit',
        });
        expect(result).toEqual(payload);
    });

    it('keeps unexpected server errors as transport failures', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(JSON.stringify({ message: 'Internal failure' }), { status: 500 })));
        const transport = createRestClientTransport({ endpoint: '/', formHandle: 'contact' });
        await expect(transport.submit({
            definition: { pages: [] } as unknown as ClientFormDefinition,
            session: { version: 1, tokens: {} } as ClientFormSession,
            values: {}, action: 'submit',
        })).rejects.toThrow('Request failed with status 500.');
    });
});
