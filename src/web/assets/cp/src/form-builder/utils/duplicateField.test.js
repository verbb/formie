import { expect, it, vi } from 'vitest';
import { buildDuplicatedFieldData } from './duplicateField';

it('clears nested instance and definition identities when duplicating a saved field', () => {
    vi.stubGlobal('Craft', { t: (_category, text) => text });
    const child = { id: 13, definitionId: 14, reference: 'child', handle: 'email', isSynced: true, definitionToken: 'grant' };
    const field = { id: 1, definitionId: 2, handle: 'items', label: 'Items', reference: 'parent', isSynced: true, instructions: '{field:child:value}', conditions: { field: 'child' },
        rows: [{ id: 10, fields: [child] }], layouts: { alternative: [{ id: 11, fields: [child] }] } };
    const copy = buildDuplicatedFieldData(field, ['items']);
    expect(copy.instructions).toBe(`{field:${copy.rows[0].fields[0].reference}:value}`);
    expect(copy.conditions.field).toBe(copy.rows[0].fields[0].reference);
    expect(copy.id).toBeUndefined();
    expect(copy.definitionId).toBeUndefined();
    expect(copy.isSynced).toBe(false);
    for (const row of [copy.rows[0], copy.layouts.alternative[0]]) {
        expect(row.id).toBeUndefined();
        expect(row.fields[0].id).toBeUndefined();
        expect(row.fields[0].definitionId).toBeUndefined();
        expect(row.fields[0].definitionToken).toBeUndefined();
        expect(row.fields[0].isSynced).toBe(false);
        expect(row.fields[0].reference).not.toBe('child');
    }
    expect(field.rows[0].fields[0].id).toBe(13);
    vi.unstubAllGlobals();
});
