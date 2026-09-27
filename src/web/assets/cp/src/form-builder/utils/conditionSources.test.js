import { describe, expect, it } from 'vitest';
import { precedingConditionValues } from './conditionSources';

describe('visibility condition sources', () => {
    it('keeps earlier fields and nested siblings but excludes later content', () => {
        const values = { pages: [{ rows: [{ fields: [{ id: 'before' }, { id: 'group', rows: [{ fields: [{ id: 'child-before' }, { id: 'target' }, { id: 'child-after' }] }] }, { id: 'after' }] }] }] };
        const result = precedingConditionValues(values, 'target');
        expect(result.pages[0].rows[0].fields.map((field) => field.id)).toEqual(['before', 'group']);
        expect(result.pages[0].rows[0].fields[1].rows[0].fields.map((field) => field.id)).toEqual(['child-before']);
        expect(values.pages[0].rows[0].fields[1].rows[0].fields).toHaveLength(3);
    });
    it('does not infer sources when the edited instance cannot be located', () => {
        expect(precedingConditionValues({ pages: [{ rows: [{ fields: [{ id: 'other' }] }] }] }, 'missing').pages).toEqual([]);
    });
});
