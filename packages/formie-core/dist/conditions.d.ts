export type ConditionValueType = 'text' | 'number' | 'boolean' | 'date' | 'time' | 'datetime' | 'collection';
export type ConditionEvaluation = {
    value: boolean | null;
    diagnostics: Array<{
        code: string;
        rule?: number;
    }>;
};
export type ClientConditionRule = {
    condition: string;
    value?: unknown;
    valueType?: ConditionValueType;
    browserSafe?: boolean;
};
export type ClientConditionSettings = {
    showRule: 'show' | 'hide' | 'enable' | 'disable';
    conditionRule: 'all' | 'any';
    conditions: ClientConditionRule[];
};
export declare function conditionNumber(value: unknown): number | null;
export declare function evaluateCondition(operator: string, actual: unknown, expected: unknown, type?: ConditionValueType): ConditionEvaluation;
export declare function combineConditions(mode: string, results: ConditionEvaluation[]): ConditionEvaluation;
export declare function evaluateConditionDefinition(condition: ClientConditionRule, actualValues: string[], options?: {
    visibility?: boolean | null;
}): ConditionEvaluation;
export declare function finalizeConditionEvaluation(settings: Pick<ClientConditionSettings, 'conditionRule' | 'showRule'>, results: Array<ConditionEvaluation | boolean>): {
    finalResult: boolean;
    shouldHide: boolean;
    evaluation: ConditionEvaluation;
};
//# sourceMappingURL=conditions.d.ts.map