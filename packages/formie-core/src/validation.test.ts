import { expect, it } from 'vitest';
import { registerBrowserValidationRule, validateBrowserValue } from './validation';
import { selectConditionRows } from './condition-projections';
it('shares localized validation without partial numeric coercion', () => {
    const rule = { type: 'number', min: 1, max: 5, messages: { number: 'Nombre invalide.', numberMin: 'Trop petit.', numberMax: 'Trop grand.' } };
    expect(validateBrowserValue('3px', rule)).toBe('Nombre invalide.');
    expect(validateBrowserValue('0', rule)).toBe('Trop petit.');
    expect(validateBrowserValue('6', rule)).toBe('Trop grand.');
    expect(validateBrowserValue('3', rule)).toBeNull();
    expect(validateBrowserValue('', rule)).toBeNull();
});
it('defers unknown rules and invalid browser regex configuration to server authority', () => {
    expect(validateBrowserValue('anything', { type: 'not-loaded' })).toBeNull();
    expect(validateBrowserValue('anything', { type: 'pattern', pattern: '[' })).toBeNull();
    const off = registerBrowserValidationRule('specialist', () => 'Specialist message.');
    expect(validateBrowserValue('anything', { type: 'specialist' })).toBe('Specialist message.');
    off();
    expect(validateBrowserValue('anything', { type: 'specialist' })).toBeNull();
});
it('keeps explicit row scopes and diagnoses missing or invalid scopes', () => {
    const rows = ['a', 'b', 'c'];
    expect(selectConditionRows(rows, { scope: 'all' }).value).toEqual(rows);
    expect(selectConditionRows(rows, { scope: 'current' }, 1).value).toBe('b');
    expect(selectConditionRows(rows, { scope: 'rows', rows: 'odd' }).value).toEqual(['a', 'c']);
    expect(selectConditionRows(rows, { scope: 'rows', rows: '2' }).value).toBe('b');
    expect(selectConditionRows(rows, {}).diagnostic).toBe('missingRowScope');
    expect(selectConditionRows(rows, { scope: 'index', index: '-1' }).diagnostic).toBe('invalidRowScope');
});
