import { describe, expect, it } from 'vitest';
import { buildTransformOptions, resolveTransformLabel } from './variablePicker';

const registry = {
    text: [{ id: 'upper', label: 'Uppercase' }],
    number: [{ id: 'round', label: 'Round' }],
    array: [{ id: 'join', label: 'Join' }, { id: 'count', label: 'Count' }],
};

describe('row-scoped variable transforms', () => {
    it.each(['tableColumnSubField', 'repeaterSubField', 'selector'])('uses the selected rows for %s transform choices', (marker) => {
        const option = { [marker]: true, types: ['text'], value: '{field:items:col1;scope=first}' };
        const ids = (scope) => buildTransformOptions(option, registry, `{field:items:col1;scope=${scope}}`).map(({ value }) => value);
        expect(ids('first')).toEqual(['upper']);
        expect(ids('all')).toEqual(['join', 'count']);
        expect(ids('count')).toEqual(['round']);
        expect(ids('last')).toEqual(['upper']);
        expect(option.types).toEqual(['text']);
    });
});

describe('variable tag transform labels', () => {
    // Number and Date Format share the `format` ID, as BuiltinReferenceTransforms registers them.
    const sharedRegistry = {
        text: [{ id: 'upper', label: 'Uppercase' }],
        number: [{ id: 'format', label: 'Number Format' }],
        date: [{ id: 'format', label: 'Date Format' }],
        array: [{ id: 'count', label: 'Count' }],
    };
    const categories = {
        general: [{ label: 'Current Date/Time', value: '{timestamp:}', types: ['text', 'date'] }],
        fields: [
            { label: 'Quantity', value: '{field:quantity}', types: ['number'] },
            { label: 'Items', value: '{field:items:qty}', repeaterSubField: true, types: ['date'] },
        ],
    };
    const label = (value, transformerId = 'format') => {
        return resolveTransformLabel({ value, transformerId }, categories, sharedRegistry);
    };

    it('names a shared transform ID by the variable\'s value type', () => {
        expect(label('{timestamp:}')).toBe('Date Format');
        expect(label('{field:quantity}')).toBe('Number Format');
    });

    it('follows the value type that row targeting gives a repeater column', () => {
        expect(label('{field:items:qty;scope=first}')).toBe('Date Format');
        expect(label('{field:items:qty;scope=count}')).toBe('Number Format');
    });

    it('leaves unknown variables and transforms to the registry', () => {
        expect(label('{field:deleted}')).toBeUndefined();
        expect(label('{timestamp:}', 'missing')).toBeUndefined();
        expect(label('{timestamp:}', '')).toBeUndefined();
    });
});
