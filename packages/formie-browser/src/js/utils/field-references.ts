export { normalizeFieldKey, fieldKeyToInputName, inputNameToFieldKey } from '#utils/field-references.keys';
export { parseFieldReference } from '#utils/field-references.parser';
export { buildFieldValueRegistry } from '#utils/field-references.registry';
export { resolveFieldReferenceLive, resolveFieldReferenceFromFormData } from '#utils/field-references.resolver';
export { getRowScopedWatchNames, parseRowsExpression, resolveRowScopedFieldReference } from '#utils/field-references.row-scope';
export type {
    FieldReferenceTransform,
    FieldValueRegistry,
    FieldValueRegistryEntry,
    ParsedFieldReference,
    ResolveFieldValueResult,
} from '#utils/field-references.types';
