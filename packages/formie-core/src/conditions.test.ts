import { describe, it, expect } from 'vitest';
import fixtures from '../../../tests/fixtures/conditions.json';
import { evaluateCondition, combineConditions } from './conditions';
import type { ConditionValueType } from './conditions';

describe('PHP/browser parity', () => {
    for (const fixture of fixtures) it(fixture.name, () => {
        const result = evaluateCondition(fixture.operator, fixture.actual, fixture.expected, fixture.type as ConditionValueType);
        expect(result.value).toBe(fixture.result);
        expect(result.diagnostics.length > 0).toBe(fixture.result === null);
    });
    it('rejects non-finite text operands', () => {
        for (const value of [Infinity, -Infinity, NaN]) expect(evaluateCondition('=', value, value).value).toBeNull();
    });
    it('retains invalid rule diagnostics in both boolean modes', () => {
        for (const mode of ['any', 'all']) expect(combineConditions(mode, [{ value: true, diagnostics: [] }, { value: null, diagnostics: [{ code: 'missingReference' }] }])).toEqual({ value: null, diagnostics: [{ code: 'missingReference', rule: 1 }] });
    });
});
