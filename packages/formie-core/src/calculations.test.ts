import { describe, expect, it } from 'vitest';
import { coerceCalculationVariables, evaluateCalculationExpression, readCalculationVariableValue } from './calculations';

describe('formatted calculation dependencies', () => {
    it.each([
        ['$', '', '$59.85', 59.85],
        ['', ' kg', '-2.50 kg', -2.5],
        ['AUD ', ' inc GST', 'AUD 0.00 inc GST', 0],
        ['2026: ', ' units', '2026: 4.25 units', 4.25],
    ])('reads the numeric result with prefix %s and suffix %s', (prefix, suffix, value, expected) => {
        const variable = { sourceKey: 'subtotal', numberFormat: { prefix, suffix } };
        expect(readCalculationVariableValue(variable, value)).toBe(expected);
    });

    it('calculates tax from a formatted subtotal and rounds the displayed result', () => {
        const variable = { sourceKey: 'subtotal', numberFormat: { prefix: '$', suffix: '' } };
        const variables = coerceCalculationVariables({ subtotal: readCalculationVariableValue(variable, '$59.85') }, 'number');
        expect(evaluateCalculationExpression('subtotal * 1.1', variables, { formatting: 'number', prefix: '$', decimals: 2 })).toBe('$65.84');
    });

    it('sums repeated formatted results without changing ordinary text values', () => {
        const variable = { sourceKey: 'subtotal', numberFormat: { prefix: '$', suffix: '' } };
        expect(coerceCalculationVariables({ subtotal: readCalculationVariableValue(variable, ['$10.50', '$2.25']) }, 'number').subtotal).toBe(12.75);
        expect(readCalculationVariableValue({ sourceKey: 'description' }, '$10.50')).toBe('$10.50');
    });
});
