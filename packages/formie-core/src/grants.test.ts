import { afterEach, expect, it, vi } from 'vitest';
import { loadFrontendEnvelope } from './rest';
import { loadGraphqlFrontendEnvelope } from './graphql';

afterEach(() => vi.unstubAllGlobals());

it.each(['rest', 'graphql'])('exchanges a grant and strips only the matching bearer from browser history through %s', async (transport) => {
    const replaceState = vi.fn();
    vi.stubGlobal('window', { location: { href: 'https://example.test/form?resumeToken=secret&campaign=summer' }, history: { state: {}, replaceState } });
    const envelope = { schemaVersion: 1, definition: {}, session: { version: 2, continuation: { progressId: '17' } } };
    const fetch = vi.fn().mockResolvedValue(new Response(JSON.stringify(transport === 'rest' ? envelope : { data: { formieClientForm: envelope } })));
    vi.stubGlobal('fetch', fetch);
    const options = { endpoint: '/api', formHandle: 'contact', grantToken: 'secret', grantPurpose: 'continue-incomplete' as const };
    const result = await (transport === 'rest' ? loadFrontendEnvelope(options) : loadGraphqlFrontendEnvelope(options));
    const body = JSON.parse(fetch.mock.calls[0][1].body);
    expect((transport === 'rest' ? body : body.variables).grantToken).toBe('secret');
    expect(result).toEqual(envelope);
    expect(String(replaceState.mock.calls[0][2])).toBe('https://example.test/form?campaign=summer');
});
