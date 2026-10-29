import { describe, expect, it } from 'vitest';
import { applyOptionSourceParamDefaults } from './optionSourceParams';

describe('option source parameter defaults', () => {
    const fields = [{ handle: 'group', required: false }, { handle: 'source' }];
    const defaults = { group: 'all', source: 'contacts', unknown: 'ignored' };

    it.each(['', null, 0, '0', false])('preserves an explicit cleared or falsy value on reload: %j', (value) => {
        const saved = JSON.parse(JSON.stringify({ group: value }));
        const next = applyOptionSourceParamDefaults(saved, fields, defaults);
        expect(next).toEqual({ group: value, source: 'contacts' });
        expect(applyOptionSourceParamDefaults(next, fields, defaults)).toEqual(next);
        expect(saved).toEqual({ group: value });
    });

    it('initializes missing declared parameters without injecting unrelated defaults', () => {
        expect(applyOptionSourceParamDefaults({}, fields, defaults)).toEqual({ group: 'all', source: 'contacts' });
        expect(applyOptionSourceParamDefaults({}, fields, { group: '', source: null })).toEqual({ group: '' });
    });
});
