import * as ExpressionLanguageModule from 'expression-language';

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

type ExpressionLanguageInstance = {
    evaluate(expression: string, values?: Record<string, unknown>): unknown;
};

type ExpressionLanguageConstructor = new () => ExpressionLanguageInstance;

let expressionLanguage: ExpressionLanguageInstance | null = null;

function resolveExpressionLanguageConstructor(): ExpressionLanguageConstructor {
    const expressionLanguageModule = ExpressionLanguageModule as {
        ExpressionLanguage?: unknown;
        default?: unknown;
    };

    const ExpressionLanguage = (
        expressionLanguageModule.ExpressionLanguage ||
        expressionLanguageModule.default ||
        ExpressionLanguageModule
    );

    if (typeof ExpressionLanguage !== 'function') {
        throw new TypeError('Unable to resolve expression-language constructor.');
    }

    return ExpressionLanguage as ExpressionLanguageConstructor;
}

function getExpressionLanguage(): ExpressionLanguageInstance {
    expressionLanguage ??= new (resolveExpressionLanguageConstructor())();

    return expressionLanguage;
}

export function getCalculationFormula(options: CalculationOptions): string {
    return (options.formula?.expression || options.formula?.formula || '').trim();
}

export function getCalculationVariableEntries(options: CalculationOptions): CalculationVariableEntry[] {
    return Object.entries(options.formula?.variables || {}).filter((entry): entry is CalculationVariableEntry => {
        return !!entry[1]?.sourceKey;
    });
}

export function coerceCalculationVariables(
    variables: Record<string, unknown>,
    formatting: string | undefined,
): Record<string, unknown> {
    void formatting;

    // Calculations should treat numeric-looking field values as numbers even when the
    // result is displayed without number formatting, otherwise `1 + 2` becomes `12`.
    Object.entries(variables).forEach(([key, value]) => {
        if (Array.isArray(value)) {
            const coerced = value.map((item) => {
                return typeof item === 'string' && item.trim() !== '' && !Number.isNaN(Number(item)) ? Number(item) : item;
            });
            const numericValues = coerced.filter((item) => typeof item === 'number');

            variables[key] = numericValues.length === coerced.length && coerced.length > 0
                ? numericValues.reduce((total, item) => total + Number(item || 0), 0)
                : coerced;

            return;
        }

        if (typeof value === 'string' && value.trim() !== '' && !Number.isNaN(Number(value))) {
            variables[key] = Number(value);
        }
    });

    return variables;
}

export function formatCalculationValue(value: unknown, options: CalculationOptions): string | number {
    if (options.formatting !== 'number') {
        return typeof value === 'number' || typeof value === 'string' ? value : '';
    }

    let normalized = value;
    if (Array.isArray(normalized)) {
        normalized = normalized.reduce((total, item) => {
            return total + Number(item || 0);
        }, 0);
    }

    const decimals = typeof options.decimals === 'number' ? options.decimals : 0;
    const numericValue = Number(normalized || 0).toFixed(decimals);

    return `${options.prefix || ''}${numericValue}${options.suffix || ''}`;
}

export function readCalculationVariableValue(variable: CalculationVariable, value: string | string[]): unknown {
    const isNumberVariable = variable.type?.endsWith('\\Number');
    const isCheckboxVariable = variable.type?.endsWith('\\Checkboxes');

    if (isCheckboxVariable) {
        if (Array.isArray(value)) {
            return value.length ? value : '';
        }

        return value ? [value] : '';
    }

    if (Array.isArray(value)) {
        return value.length ? (isNumberVariable ? value.map((item) => Number(item || 0)) : value) : '';
    }

    return isNumberVariable ? Number(value || 0) : value;
}

export function evaluateCalculationExpression(
    formula: string,
    variables: Record<string, unknown>,
    options: CalculationOptions,
): string | number {
    return formatCalculationValue(getExpressionLanguage().evaluate(formula, variables), options);
}

