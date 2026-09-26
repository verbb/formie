export type ClientConditionRule = {
    condition: string;
    value?: unknown;
};
export type ClientConditionSettings = {
    showRule: 'show' | 'hide';
    conditionRule: 'all' | 'any';
    conditions: ClientConditionRule[];
};
export declare function evaluateConditionDefinition(condition: ClientConditionRule, actualValues: string[], options?: {
    visibility?: boolean | null;
}): boolean;
export declare function finalizeConditionEvaluation(settings: Pick<ClientConditionSettings, 'conditionRule' | 'showRule'>, results: boolean[]): {
    finalResult: boolean;
    shouldHide: boolean;
};
//# sourceMappingURL=conditions.d.ts.map