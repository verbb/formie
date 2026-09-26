import { describe, expect, it } from 'vitest';
import { assertClientFormBootstrap } from './contract';
import { assertBrowserModuleManifest } from './browser-modules';

const entry = { key: 'one', moduleId: 'example:field', type: 'field', capability: 'field', surfaces: ['server-rendered', 'client-rendered'], targets: [{ targetType: 'field', targetId: 'instance-uid' }], config: {}, required: true };

describe('versioned rendering contracts', () => {
    it('preserves repeated declarations with independent occurrence keys', () => {
        const manifest = { contractVersion: 1, entries: [entry, { ...entry, key: 'two' }] };
        expect(() => assertBrowserModuleManifest(manifest)).not.toThrow();
        expect(manifest.entries).toHaveLength(2);
    });
    it.each([undefined, 0, 2, '1'])('rejects unsupported version %s before rendering', (contractVersion) => {
        expect(() => assertClientFormBootstrap({ contractVersion })).toThrow(/contractVersion/);
        expect(() => assertBrowserModuleManifest({ contractVersion, entries: [] })).toThrow(/contractVersion/);
    });
    it('rejects duplicate keys, arbitrary src and non-registry module IDs', () => {
        for (const entries of [[entry, entry], [{ ...entry, src: 'https://evil.test/module.js' }], [{ ...entry, moduleId: 'https://evil.test/module.js' }]]) {
            expect(() => assertBrowserModuleManifest({ contractVersion: 1, entries })).toThrow(/entry/);
        }
    });
});
