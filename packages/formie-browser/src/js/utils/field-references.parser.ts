import { parseReference } from '@verbb/formie-core';
import type { ParsedFieldReference } from '#utils/field-references.types';
import { normalizeFieldKey } from '#utils/field-references.keys';

/** Browser field slots adapt the shared grammar; plain keys are legacy field operands. */
export function parseFieldReference(rawValue: string): ParsedFieldReference {
    const raw = String(rawValue || '').trim();
    const expression = parseReference(raw);
    const isToken = raw.startsWith('{');
    const field = expression.isValid && expression.target === 'field';
    return {
        raw,
        target: field ? 'field' : '',
        key: field ? normalizeFieldKey(expression.identifier) : (isToken ? '' : normalizeFieldKey(raw)),
        selector: expression.selector,
        defaultValue: expression.default,
        transforms: expression.transformerId ? [{ id: expression.transformerId, params: expression.transformerParams }] : [],
        isToken,
        isValid: isToken ? field : raw !== '',
    };
}
