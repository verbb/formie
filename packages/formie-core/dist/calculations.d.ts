export type CalculationVariable = {
    sourceKey?: string;
    type?: string;
    scope?: string;
    index?: string;
    rows?: string;
    fieldKind?: string;
};
export type CalculationFormula = {
    formula?: string;
    expression?: string;
    variables?: Record<string, CalculationVariable | null>;
};
export type CalculationOptions = {
    formula?: CalculationFormula;
    formatting?: string;
    prefix?: string;
    suffix?: string;
    decimals?: number;
};
export type CalculationVariableEntry = [string, CalculationVariable];
export declare function getCalculationFormula(options: CalculationOptions): string;
export declare function getCalculationVariableEntries(options: CalculationOptions): CalculationVariableEntry[];
export declare function coerceCalculationVariables(variables: Record<string, unknown>, formatting: string | undefined): Record<string, unknown>;
export declare function formatCalculationValue(value: unknown, options: CalculationOptions): string | number;
export declare function readCalculationVariableValue(variable: CalculationVariable, value: string | string[]): unknown;
export declare function evaluateCalculationExpression(formula: string, variables: Record<string, unknown>, options: CalculationOptions): string | number;
//# sourceMappingURL=calculations.d.ts.map