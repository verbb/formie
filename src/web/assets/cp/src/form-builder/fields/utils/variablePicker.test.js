import { describe, expect, it } from 'vitest';
import { buildTransformOptions } from './variablePicker';

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
