import { beforeEach, describe, expect, it, vi } from 'vitest';

const registerExtension = vi.fn(() => { return vi.fn(); });
const registerControl = vi.fn(() => { return vi.fn(); });
const registerTextStyle = vi.fn(() => { return vi.fn(); });

vi.mock('@verbb/plugin-kit-tiptap-core', () => {
    return {
        registerTiptapExtension: registerExtension,
        registerTiptapToolbarControl: registerControl,
        registerTiptapTextStyleDefinition: registerTextStyle,
    };
});
vi.mock('@tiptap/core', () => { return { Extension: class Extension {} }; });
vi.mock('@tiptap/pm/model', () => { return { Schema: class Schema {} }; });
vi.mock('@tiptap/pm/state', () => { return { Plugin: class Plugin {} }; });
vi.mock('@tiptap/pm/view', () => { return { EditorView: class EditorView {} }; });

beforeEach(() => {
    vi.resetModules();
    registerExtension.mockClear();
    registerControl.mockClear();
    registerTextStyle.mockClear();
    globalThis.Craft = { Formie: {} };
    globalThis.document = new EventTarget();
});

describe('Formie TipTap bridge', () => {
    it('exposes bundled APIs and verifies matching server and client registrations', async () => {
        const module = await import('./formie-tiptap.js');

        expect(Craft.Formie.tiptap.core.Extension).toBeTypeOf('function');
        expect(Craft.Formie.tiptap.prosemirror.state.Plugin).toBeTypeOf('function');

        Craft.Formie.registerTiptapExtension('acme/abbr', {});
        Craft.Formie.registerTiptapControl('abbr', { label: 'Abbreviation', run: vi.fn() });
        module.configureFormieTiptap({
            extensionIds: ['acme/abbr'],
            textStyles: [{
                id: 'acme-uppercase',
                label: 'Uppercase',
                attribute: 'textTransform',
                cssProperty: 'text-transform',
                allowedValues: ['uppercase'],
                toolbarValue: 'uppercase',
            }],
        });

        expect(registerExtension).toHaveBeenCalledWith({
            id: 'acme/abbr',
            extension: {},
            surfaces: undefined,
        });
        expect(registerControl).toHaveBeenCalledWith(expect.objectContaining({ id: 'abbr' }));
        expect(registerTextStyle).toHaveBeenCalledOnce();
    });

    it('fails clearly when the server extension has no client half', async () => {
        const module = await import('./formie-tiptap.js');

        expect(() => {
            module.configureFormieTiptap({
                extensionIds: ['acme/missing'],
            });
        }).toThrow('missing from the client: acme/missing');
    });

    it('preserves registration state when the bridge module is evaluated again', async () => {
        const firstModule = await import('./formie-tiptap.js');
        Craft.Formie.registerTiptapExtension('acme/abbr', {});
        firstModule.configureFormieTiptap({
            extensionIds: ['acme/abbr'],
            textStyles: [{
                id: 'acme-uppercase',
                label: 'Uppercase',
                attribute: 'textTransform',
                cssProperty: 'text-transform',
                allowedValues: ['uppercase'],
                toolbarValue: 'uppercase',
            }],
        });

        vi.resetModules();
        const secondModule = await import('./formie-tiptap.js');

        expect(() => {
            secondModule.configureFormieTiptap({
                extensionIds: ['acme/abbr'],
                textStyles: [{
                    id: 'acme-uppercase',
                    label: 'Uppercase',
                    attribute: 'textTransform',
                    cssProperty: 'text-transform',
                    allowedValues: ['uppercase'],
                    toolbarValue: 'uppercase',
                }],
            });
        }).not.toThrow();
        expect(registerTextStyle).toHaveBeenCalledOnce();
    });
});
