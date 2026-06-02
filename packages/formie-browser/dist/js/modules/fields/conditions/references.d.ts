import type { ConditionDefinition, ConditionInput, ConditionSource } from '#modules/fields/conditions/types';
export declare const CONDITION_INPUT_SELECTOR = "input, select, textarea";
export declare function resolveConditionSource(condition: ConditionDefinition): ConditionSource | null;
export declare function queryConditionInputs(root: Element, targetNode: Element, condition: ConditionDefinition): ConditionInput[];
//# sourceMappingURL=references.d.ts.map