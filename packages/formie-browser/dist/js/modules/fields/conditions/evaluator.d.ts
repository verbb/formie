import type { ConditionDefinition, ParsedConditionSettings } from '#modules/fields/conditions/types';
export declare function evaluateConditionSettings(settings: ParsedConditionSettings, getConditionInputs: (condition: ConditionDefinition) => Array<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>): {
    finalResult: boolean;
    shouldHide: boolean;
};
//# sourceMappingURL=evaluator.d.ts.map