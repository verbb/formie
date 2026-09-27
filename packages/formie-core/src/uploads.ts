import { browserRequest, type BrowserRequestOptions } from './request-profile';
import { allFields } from './schema';
import type { ClientFormDefinition, ClientFormSession } from './types';

const stagedFiles = new WeakMap<Blob, Map<string, Promise<{ uploadUid: string; attachToken: string }>>>();

/** Files are staged before submission; final submits carry scoped attachment capabilities. */
export async function stageTransportFiles(definition: ClientFormDefinition, session: ClientFormSession, values: Record<string, unknown>, options: BrowserRequestOptions): Promise<Record<string, unknown>> {
    const walk = async(value: unknown, path: string): Promise<unknown> => {
        if (typeof Blob !== 'undefined' && value instanceof Blob) {
            const key = JSON.stringify([definition.id, session.tokens.render, session.continuation?.draftContextToken, path]);
            const staged = stagedFiles.get(value) ?? new Map();
            stagedFiles.set(value, staged);
            if (staged.has(key)) return staged.get(key);
            const pending = (async() => {
                const data = new FormData();
                data.set('file', value, value instanceof File ? value.name : 'upload');
                data.set('handle', definition.handle);
                if (definition.siteId) data.set('siteId', String(definition.siteId));
                data.set('fieldHandle', path.replace(/\.\d+$/, ''));
                data.set('renderId', session.tokens.render ?? '');
                data.set('draftContext', String(session.continuation?.draftContext ?? ''));
                data.set('draftContextToken', String(session.continuation?.draftContextToken ?? ''));
                data.set('uploadCreateToken', session.tokens.uploadCreate ?? '');
                if (session.continuation?.submissionId) data.set('submissionId', String(session.continuation.submissionId));
                if (session.tokens.csrf) data.set(session.tokens.csrf.name, session.tokens.csrf.value);
                const endpoint = definition.submission.uploadEndpoint;
                if (!endpoint) throw new Error('The form bootstrap does not provide a staged upload endpoint.');
                const response = await browserRequest(endpoint, { method: 'POST', headers: { Accept: 'application/json' }, body: data }, options);
                const uploaded = await response.json();
                if (!response.ok || !uploaded.success || !uploaded.uploadUid || !uploaded.attachToken) throw new Error('The file could not be staged. Please select it again.');
                return { uploadUid: uploaded.uploadUid, attachToken: uploaded.attachToken };
            })();
            staged.set(key, pending);
            try { return await pending; }
            catch (error) { staged.delete(key); throw error; }
        }
        if (Array.isArray(value)) return Promise.all(value.map((item, index) => walk(item, `${path}.${index}`)));
        if (value && typeof value === 'object') return Object.fromEntries(await Promise.all(Object.entries(value).map(async([key, item]) => [key, await walk(item, `${path}.${key}`)])));
        return value;
    };
    const fields = allFields(definition);
    return Object.fromEntries(await Promise.all(Object.entries(values).map(async([key, value]) => [key, await walk(value, fields.find((field) => field.id === key)?.handle ?? key)])));
}
