import type { ConditionInput, ConditionSource } from '#modules/fields/conditions/types';
export declare function getConditionInputEventNames(_input: ConditionInput): string[];
export declare function readConditionValues(inputs: ConditionInput[], source?: ConditionSource | null): string[];
export declare function readConditionProjection(inputs: ConditionInput[], source: ConditionSource | null, type: import('@verbb/formie-core').ConditionValueType, currentRow?: number): unknown;
//# sourceMappingURL=values.d.ts.map