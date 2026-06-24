import type { FieldValueRegistry, ResolveFieldValueResult } from '#utils/field-references.types';
export type RowScopeParams = {
    scope?: string;
    index?: string | number;
    rows?: string;
    fieldKind?: string;
};
export declare function parseRowsExpression(expression: string, rowCount: number): number[];
export declare function getRowScopedWatchNames(sourceKey: string, params: RowScopeParams, registry: FieldValueRegistry): Set<string>;
export declare function resolveRowScopedFieldReference(sourceKey: string, params: RowScopeParams, registry: FieldValueRegistry): ResolveFieldValueResult;
//# sourceMappingURL=field-references.row-scope.d.ts.map