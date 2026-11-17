/** Row scopes consume normalized rows; selectors and row identity remain explicit. */
export declare function selectConditionRows(rows: unknown[], params: Record<string, string>, current?: number): {
    value?: unknown;
    diagnostic?: string;
};
/**
 * Element inputs hold the selected element IDs, while title and URL rules compare the values the server sends by ID.
 * Like the server, blank values are left out, one value compares as text and several as a collection.
 */
export declare function projectElementConditionValues(selected: unknown, elementValues: Record<string, string>): string | string[] | null;
//# sourceMappingURL=condition-projections.d.ts.map