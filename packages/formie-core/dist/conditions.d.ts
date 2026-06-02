export type FrontendConditionRule = {
    condition: string;
    value?: unknown;
};
export type FrontendConditionSettings = {
    showRule: 'show' | 'hide';
    conditionRule: 'all' | 'any';
    conditions: FrontendConditionRule[];
};
export declare function evaluateConditionDefinition(condition: FrontendConditionRule, actualValues: string[], options?: {
    visibility?: boolean | null;
}): boolean;
export declare function finalizeConditionEvaluation(settings: Pick<FrontendConditionSettings, 'conditionRule' | 'showRule'>, results: boolean[]): {
    finalResult: boolean;
    shouldHide: boolean;
};
//# sourceMappingURL=conditions.d.ts.map