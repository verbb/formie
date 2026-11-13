import { describe, expect, it } from 'vitest';
import { defaultValueForField, displayFieldContent, fieldValueContract, isFieldLabelHidden } from './schema';
import type { ClientFieldDefinition } from './types';

describe('client-rendered value metadata', () => {
    it('uses the declared children and value type', () => {
        const field = {
            handle: 'items',
            client: { children: { model: 'repeatable-parent', mode: 'rows' }, valueType: { kind: 'array' } },
        } as ClientFieldDefinition;

        expect(fieldValueContract(field)).toEqual({ structure: 'repeatable-parent', valueType: { kind: 'array' } });
    });

    it('rejects obsolete runtime metadata with an actionable compatibility error', () => {
        const field = { handle: 'items', runtime: { structure: 'repeatable-parent' } } as unknown as ClientFieldDefinition;

        expect(() => fieldValueContract(field)).toThrow('Update Formie and its packages together.');
    });
});

describe('repeater initial values', () => {
    const textField = (handle: string, defaultValue: unknown = ''): ClientFieldDefinition => ({
        id: handle,
        handle,
        type: 'single-line-text',
        input: { fieldKind: 'text', defaultValue },
        client: { children: { model: 'scalar', mode: 'none' }, valueType: { kind: 'string' } },
    } as unknown as ClientFieldDefinition);
    const repeater = (input: Record<string, unknown>): ClientFieldDefinition => ({
        id: 'repeater',
        handle: 'repeater',
        type: 'repeater',
        input: {
            fieldKind: 'repeater',
            rowSchema: { rows: [{ fields: [textField('number'), textField('note', 'Default note')] }] },
            ...input,
        },
        client: { children: { model: 'repeatable-parent', mode: 'rows' }, valueType: { kind: 'array' } },
    } as unknown as ClientFieldDefinition);

    // Prefilled rows arrive as the Repeater's default value.
    it('starts with the rows the server sends', () => {
        const field = repeater({ defaultValue: [{ number: '4', note: 'first' }, { number: '7', note: 'second' }] });

        expect(defaultValueForField(field)).toEqual([{ number: '4', note: 'first' }, { number: '7', note: 'second' }]);
    });

    it('fills fields missing from a sent row with their defaults', () => {
        expect(defaultValueForField(repeater({ defaultValue: [{ number: '4' }] }))).toEqual([{ number: '4', note: 'Default note' }]);
    });

    it('adds empty rows up to the minimum', () => {
        expect(defaultValueForField(repeater({ minRows: 2, defaultValue: [{ number: '4', note: 'first' }] }))).toEqual([
            { number: '4', note: 'first' },
            { number: '', note: 'Default note' },
        ]);
    });

    it('keeps the existing empty and minimum-row behaviour without sent rows', () => {
        expect(defaultValueForField(repeater({ defaultValue: [] }))).toEqual([]);
        expect(defaultValueForField(repeater({ defaultValue: null, minRows: 1 }))).toEqual([{ number: '', note: 'Default note' }]);
        expect(defaultValueForField(repeater({ defaultValue: 'unexpected' }))).toEqual([]);
    });
});

describe('display fields', () => {
    const field = (type: string, input: Record<string, unknown>, label: string | null = 'Label'): ClientFieldDefinition => ({
        id: type,
        handle: type,
        label,
        type,
        input: { fieldKind: 'custom', fieldType: type, ...input },
        client: { children: { model: 'scalar', mode: 'none' }, valueType: { kind: 'none' } },
    } as unknown as ClientFieldDefinition);

    it('returns the HTML of Rich Text and HTML fields', () => {
        expect(displayFieldContent(field('content', { contentHtml: '<p>Rich</p>' }))).toEqual({ kind: 'html', html: '<p>Rich</p>' });
        expect(displayFieldContent(field('html', { htmlContent: '<p>Raw</p>' }))).toEqual({ kind: 'html', html: '<p>Raw</p>' });
        expect(displayFieldContent(field('content', {}))).toEqual({ kind: 'html', html: '' });
    });

    it('returns the heading level and text, defaulting to h2', () => {
        expect(displayFieldContent(field('heading', { headingSize: 'h4' }, 'Details'))).toEqual({ kind: 'heading', level: 'h4', text: 'Details' });
        expect(displayFieldContent(field('heading', { headingSize: 'script' }, 'Details'))).toEqual({ kind: 'heading', level: 'h2', text: 'Details' });
        expect(displayFieldContent(field('heading', {}, null))).toEqual({ kind: 'heading', level: 'h2', text: '' });
    });

    it('ignores fields that collect a value', () => {
        expect(displayFieldContent(field('single-line-text', { contentHtml: '<p>x</p>' }))).toBeNull();
    });

    it('hides the wrapper label for headings and hidden display labels', () => {
        expect(isFieldLabelHidden(field('heading', {}))).toBe(true);
        expect(isFieldLabelHidden(field('content', { labelHidden: true }))).toBe(true);
        expect(isFieldLabelHidden(field('content', { labelHidden: false }))).toBe(false);
        expect(isFieldLabelHidden(field('single-line-text', {}))).toBe(false);
    });
});
