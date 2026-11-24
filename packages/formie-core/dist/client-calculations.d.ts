import type { ClientFormState } from './types.js';
/** Row numbers like `1-3,5`, `even`, `odd` or `every:2`, as the server-rendered module reads them. */
export declare function parseRowsExpression(expression: string, rowCount: number): number[];
/**
 * Reads a field's answer by key, such as `quantity` or `group.part`, from form values keyed by field ID. Browser modules
 * use it where a client-rendered form has no named inputs to read.
 */
export declare function readFieldValueByKey(state: ClientFormState, key: string): {
    found: boolean;
    value: string | string[];
};
/**
 * Calculations fields are read-only answers worked out from other fields. Server-rendered forms work them out
 * from the page's inputs, so client-rendered forms work them out from the form values instead, including
 * Calculations fields in Groups and Repeater rows.
 */
export declare function deriveCalculationValues(state: ClientFormState): ClientFormState;
//# sourceMappingURL=client-calculations.d.ts.map