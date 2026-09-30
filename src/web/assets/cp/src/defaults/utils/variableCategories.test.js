import { describe, expect, it } from 'vitest';
import { resolveStaticVariableCategories } from './variableCategories';

describe('reference usage filtering', () => {
    it('filters restricted and disabled sources without hiding unrestricted inline sources', () => {
        const config = { staticGroups: { custom: [
            { label: 'Any', value: 'any', shape: 'inline' },
            { label: 'Mapping', value: 'mapping', shape: 'inline', usages: ['integration'] },
            { label: 'Disabled', value: 'disabled', shape: 'inline', usages: [] },
            { label: 'Body', value: 'body', shape: 'block', usages: ['richText'] },
        ] } };
        expect(resolveStaticVariableCategories(config, { groups: ['custom'], usage: 'integration', shapes: ['inline'] }).custom.map((item) => item.value)).toEqual(['any', 'mapping']);
        expect(resolveStaticVariableCategories(config, { groups: ['custom'], usage: 'emailHeader', shapes: ['inline'] }).custom.map((item) => item.value)).toEqual(['any']);
        expect(resolveStaticVariableCategories(config, { groups: ['custom'], usage: 'richText' }).custom.map((item) => item.value)).toEqual(['any', 'body']);
    });
});
