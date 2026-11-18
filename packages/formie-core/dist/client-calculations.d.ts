import type { ClientFormState } from './types.js';
/** Row numbers like `1-3,5`, `even`, `odd` or `every:2`, as the server-rendered module reads them. */
export declare function parseRowsExpression(expression: string, rowCount: number): number[];
/**
 * Calculations fields are read-only answers worked out from other fields. Server-rendered forms work them out
 * from the page's inputs, so client-rendered forms work them out from the form values instead, including
 * Calculations fields in Groups and Repeater rows.
 */
export declare function deriveCalculationValues(state: ClientFormState): ClientFormState;
//# sourceMappingURL=client-calculations.d.ts.map