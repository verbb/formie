import { describe, expect, it } from 'vitest';
import { assertClientFormBootstrap } from './contract';
import { assertBrowserModuleManifest } from './browser-modules';

const entry = { key: 'one', moduleId: 'example:field', kind: 'field', targets: [{ type: 'field' as const, uid: 'instance-uid' }], config: {}, required: true };

describe('versioned rendering contracts', () => {
    it('preserves repeated declarations with independent occurrence keys', () => {
        const manifest = { contractVersion: 2 as const, surface: 'client-rendered' as const, entries: [entry, { ...entry, key: 'two' }] };
        expect(() => assertBrowserModuleManifest(manifest)).not.toThrow();
        expect(manifest.entries).toHaveLength(2);
    });
    it.each([undefined, 0, 2, '1'])('rejects unsupported version %s before rendering', (contractVersion) => {
        expect(() => assertClientFormBootstrap({ contractVersion })).toThrow(/contractVersion/);
        const moduleContractVersion = contractVersion === 2 ? 1 : contractVersion;
        expect(() => assertBrowserModuleManifest({ contractVersion: moduleContractVersion, surface: 'client-rendered', entries: [] })).toThrow(/contractVersion/);
    });
    it('rejects duplicate keys, arbitrary src and non-registry module IDs', () => {
        for (const entries of [[entry, entry], [{ ...entry, src: 'https://evil.test/module.js' }], [{ ...entry, moduleId: 'https://evil.test/module.js' }]]) {
            expect(() => assertBrowserModuleManifest({ contractVersion: 2, surface: 'client-rendered', entries })).toThrow(/entry/);
        }
    });

    it('rejects malformed discriminated targets and missing surfaces', () => {
        expect(() => assertBrowserModuleManifest({ contractVersion: 2, entries: [entry] })).toThrow(/contractVersion/);
        expect(() => assertBrowserModuleManifest({ contractVersion: 2, surface: 'client-rendered', entries: [{ ...entry, targets: [{ type: 'field' }] }] })).toThrow(/target/);
        expect(() => assertBrowserModuleManifest({ contractVersion: 2, surface: 'client-rendered', entries: [{ ...entry, kind: 'decorative-capability' }] })).toThrow(/entry/);
    });
});
