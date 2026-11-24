import { describe, expect, it } from 'vitest';
import {
    buildTransformOptions,
    findOptionLabelByValue,
    findVariableOptionByValue,
    getComparableTokenValue,
    resolveTransformLabel,
} from './variablePicker';

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

describe('selected variables with a transform', () => {
    const categories = {
        form: [{ label: 'Form Name', value: '{form:name}', types: ['text'] }],
        time: [{ label: 'Current Date/Time', value: '{timestamp:}', types: ['text', 'date'] }],
        fields: [
            { label: 'Notes', value: '{field:notes}', types: ['text'] },
            { label: 'Items: Qty', value: '{field:items:qty;scope=first}', repeaterSubField: true, types: ['text'] },
        ],
    };

    // Formie 3 date aliases such as {dateUs} become Current Date/Time with a format transform.
    it.each([
        ['{form:name;transform=lower}', '{form:name}', 'Form Name'],
        ['{form:name;transform=truncate;length=5|Fallback}', '{form:name}', 'Form Name'],
        ['{timestamp:;transform=format;preset=custom;pattern=m%2Fd%2FY}', '{timestamp:}', 'Current Date/Time'],
        ['{field:notes;transform=upper}', '{field:notes}', 'Notes'],
    ])('finds %s as %s', (token, base, label) => {
        expect(getComparableTokenValue(token)).toBe(getComparableTokenValue(base));
        expect(findOptionLabelByValue(categories, token)).toBe(label);
        expect(findVariableOptionByValue(categories, token)?.label).toBe(label);
    });

    it('keeps the rows a repeater column reads', () => {
        expect(getComparableTokenValue('{field:items:qty;scope=first;transform=upper}')).toBe('{field:items:qty;scope=first}');
    });

    it('leaves tokens without a transform and plain text as they were', () => {
        expect(getComparableTokenValue('{form:name}')).toBe('{form:name}');
        expect(getComparableTokenValue('{form:name|Fallback}')).toBe('{form:name}');
        expect(getComparableTokenValue('{field:items:qty;scope=index;index=2}')).toBe('{field:items:qty;scope=index;index=2}');
        expect(getComparableTokenValue('Plain text')).toBe('Plain text');
        expect(getComparableTokenValue('')).toBe('');
    });
});
