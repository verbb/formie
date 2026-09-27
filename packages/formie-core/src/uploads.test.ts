import { afterEach, expect, it, vi } from 'vitest';
import { stageTransportFiles } from './uploads';
import { browserRequest } from './request-profile';
import type { ClientFormDefinition, ClientFormSession } from './types';

afterEach(() => vi.unstubAllGlobals());

it('stages nested files with scoped multipart context and carries public session credentials', async() => {
    const fetch = vi.fn()
        .mockResolvedValueOnce(new Response('{}', { headers: { 'X-Formie-Session': 'opaque-test-session' } }))
        .mockResolvedValueOnce(new Response(JSON.stringify({ success: true, uploadUid: 'pending-uid', attachToken: 'attachment-capability' })));
    vi.stubGlobal('fetch', fetch);
    await browserRequest('https://upload.test/bootstrap', {}, { profile: 'cross-origin-public' });
    const definition = { handle: 'contact', siteId: 2, pages: [{ rows: [{ fields: [{ id: 'items-id', handle: 'items', input: {}, client: { children: { mode: 'rows' } } }] }] }], submission: { uploadEndpoint: 'https://upload.test/upload' } } as unknown as ClientFormDefinition;
    const session = {
        tokens: {
            render: 'render-id',
            uploadCreate: 'upload-create-capability',
            csrf: { name: 'csrf', value: 'csrf-value' },
        },
        continuation: {
            submissionId: 42,
            draftContext: 'draft',
            draftContextToken: 'draft-token',
        },
    } as unknown as ClientFormSession;
    const file = new File(['hello'], 'hello.txt', { type: 'text/plain' });
    const result = await stageTransportFiles(definition, session, { 'items-id': [{ document: [file] }] }, { profile: 'cross-origin-public' });
    expect(result).toEqual({ 'items-id': [{ document: [{ uploadUid: 'pending-uid', attachToken: 'attachment-capability' }] }] });
    await stageTransportFiles(definition, session, { 'items-id': [{ document: [file] }] }, { profile: 'cross-origin-public' });
    expect(fetch).toHaveBeenCalledTimes(2);
    const init = fetch.mock.calls[1][1];
    expect(init.credentials).toBe('omit');
    expect(init.headers.get('X-Formie-Session')).toBe('opaque-test-session');
    expect(init.body).toBeInstanceOf(FormData);
    expect(init.body.get('fieldHandle')).toBe('items.0.document');
    expect(init.body.get('renderId')).toBe('render-id');
    expect(init.body.get('draftContextToken')).toBe('draft-token');
    expect(init.body.get('submissionId')).toBe('42');
    expect(init.body.get('uploadCreateToken')).toBe('upload-create-capability');
    expect(await init.body.get('file').text()).toBe('hello');
});

it('cannot elevate a public browser transport into trusted administration', async() => {
    const fetch = vi.fn(); vi.stubGlobal('fetch', fetch);
    await expect(browserRequest('https://example.test/submit', {}, { profile: 'trusted-administrative' as any })).rejects.toThrow(/administrative/);
    expect(fetch).not.toHaveBeenCalled();
});
