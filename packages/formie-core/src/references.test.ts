import { describe, expect, it } from 'vitest';
import { parseReference, serializeReference } from './references';

describe('shared reference grammar', () => {
    it('round trips metadata and reserved defaults', () => {
        const expression = parseReference('{field:instance:child;transform=acme%2Fcase;scope=all;replace=%2B%7B%7D%25|A%7CB%7D}');
        expect(expression.isValid).toBe(true);
        expect(expression.default).toBe('A|B}');
        expect(expression.transformerParams.replace).toBe('+{}%');
        expect(parseReference(serializeReference(expression))).toEqual({ ...expression, raw: serializeReference(expression) });
    });
    it('supports stable aliases and rejects invalid versions, duplicate metadata and executable templates', () => {
        expect(parseReference('{formName}').target).toBe('form');
        expect(parseReference('{field.group.name}').identifier).toBe('group.name');
        for (const token of ['{field:x;v=2}', '{field:x;scope=all;scope=first}', '{{ craft.app }}', '{field:x;search=%zz}']) expect(parseReference(token).isValid).toBe(false);
    });
    it('does not interpret prototype properties as aliases', () => {
        expect(parseReference('{constructor}').isValid).toBe(false);
        expect(parseReference('{field:x;__proto__=a}').isValid).toBe(false);
    });
});

import { resolveReference } from './references';

it('isolates server-only sources and diagnoses missing fields without applying defaults', () => {
    const context = { definitions: { 'custom:acme/secret': { id: 'custom:acme/secret', availability: { server: true, browser: false } } }, values: {} };
    expect(resolveReference('{custom:acme/secret}', context).diagnostic).toBe('forbiddenSource');
    expect(resolveReference('{field:deleted|fallback}', context).diagnostic).toBe('missingField');
    expect(resolveReference('{env:SECRET}', context).diagnostic).toBe('unknownSource');
    expect(JSON.stringify(context)).not.toContain('private-value');
});


it('resolves explicit native browser values and validates registered transforms', () => {
    const context = {
        definitions: { 'field:answer': { id: 'field:answer', availability: { server: true, browser: true }, transforms: ['acme/double'] } },
        values: { 'field:answer': 0 },
        transforms: { 'acme/double': { browser: true, parameters: [], accepts: (value: unknown) => typeof value === 'number', acceptsOutput: (value: unknown) => typeof value === 'number', resolve: (value: unknown) => Number(value) * 2 } },
    };
    expect(resolveReference('{field:answer|fallback}', context).value).toBe(0);
    expect(resolveReference('{field:answer;transform=acme%2Fdouble}', context).value).toBe(0);
    expect(resolveReference('{field:answer;transform=acme%2Fdouble;unknown=x}', context).diagnostic).toBe('invalidType');
    expect(parseReference('{field:x;search=%FF}').isValid).toBe(false);
});
