import { describe, expect, it } from 'vitest';
import { fieldValueContract } from './schema';
import type { ClientFieldDefinition } from './types';

describe('client-rendered value metadata', () => {
    it('uses the declared children and value type', () => {
        const field = {
            handle: 'items',
            client: { children: { model: 'repeatable-parent', mode: 'rows' }, valueType: { kind: 'array' } },
        } as ClientFieldDefinition;

        expect(fieldValueContract(field)).toEqual({ structure: 'repeatable-parent', valueType: { kind: 'array' } });
    });

    it('rejects obsolete runtime metadata with an actionable compatibility error', () => {
        const field = { handle: 'items', runtime: { structure: 'repeatable-parent' } } as unknown as ClientFieldDefinition;

        expect(() => fieldValueContract(field)).toThrow('Update Formie and its packages together.');
    });
});
