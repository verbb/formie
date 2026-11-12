import { describe, expect, it } from 'vitest';
import { defaultValueForField, fieldValueContract } from './schema';
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
