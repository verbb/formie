export type FrontendConditionRule = {
    condition: string;
    value?: unknown;
};

export type FrontendConditionSettings = {
    showRule: 'show' | 'hide';
    conditionRule: 'all' | 'any';
    conditions: FrontendConditionRule[];
};

function toExpectedValues(value: unknown): string[] {
    if (Array.isArray(value)) {
        return value.map((item) => {
            return String(item ?? '');
        });
    }

    return [String(value ?? '')];
}

function matchesEquality(expectedValues: string[], actualValues: string[]): boolean {
    return expectedValues.some((expectedValue) => {
        return actualValues.includes(expectedValue);
    });
}

function matchesContains(expectedValues: string[], actualValues: string[]): boolean {
    return expectedValues.some((expectedValue) => {
        return actualValues.some((actualValue) => {
            return actualValue === expectedValue || actualValue.includes(expectedValue);
        });
    });
}

function matchesStringOperator(
    actualValues: string[],
    expectedValues: string[],
    comparator: (actualValue: string, expectedValue: string) => boolean,
): boolean {
    return expectedValues.some((expectedValue) => {
        return actualValues.some((actualValue) => {
            return comparator(actualValue, expectedValue);
        });
    });
}

function matchesNumericOperator(
    actualValues: string[],
    expectedValues: string[],
    comparator: (actualValue: number, expectedValue: number) => boolean,
): boolean {
    return expectedValues.some((expectedValue) => {
        const parsedExpectedValue = Number.parseFloat(expectedValue);

        if (!Number.isFinite(parsedExpectedValue)) {
            return false;
        }

        return actualValues.some((actualValue) => {
            const parsedActualValue = Number.parseFloat(actualValue);

            if (!Number.isFinite(parsedActualValue)) {
                return false;
            }

            return comparator(parsedActualValue, parsedExpectedValue);
        });
    });
}

function isConditionValueEmpty(actualValues: string[]): boolean {
    return actualValues.length === 0 || actualValues.every((value) => value.trim() === '');
}

export function evaluateConditionDefinition(
    condition: FrontendConditionRule,
    actualValues: string[],
    options: {
        visibility?: boolean | null;
    } = {},
): boolean {
    const operator = String(condition.condition || '');
    const expectedValues = toExpectedValues(condition.value);
    const visibility = options.visibility ?? null;

    switch (operator) {
        case '=':
            return matchesEquality(expectedValues, actualValues);
        case '!=':
            return !matchesEquality(expectedValues, actualValues);
        case '>':
            return matchesNumericOperator(actualValues, expectedValues, (actualValue, expectedValue) => {
                return actualValue > expectedValue;
            });
        case '<':
            return matchesNumericOperator(actualValues, expectedValues, (actualValue, expectedValue) => {
                return actualValue < expectedValue;
            });
        case 'contains':
            return matchesContains(expectedValues, actualValues);
        case 'notContains':
            return !matchesContains(expectedValues, actualValues);
        case 'startsWith':
            return matchesStringOperator(actualValues, expectedValues, (actualValue, expectedValue) => {
                return actualValue.startsWith(expectedValue);
            });
        case 'endsWith':
            return matchesStringOperator(actualValues, expectedValues, (actualValue, expectedValue) => {
                return actualValue.endsWith(expectedValue);
            });
        case 'empty':
            return isConditionValueEmpty(actualValues);
        case 'notEmpty':
            return !isConditionValueEmpty(actualValues);
        case 'visible':
            return visibility === true;
        case 'hidden':
            return visibility === false;
        default:
            return false;
    }
}

export function finalizeConditionEvaluation(settings: Pick<FrontendConditionSettings, 'conditionRule' | 'showRule'>, results: boolean[]): {
    finalResult: boolean;
    shouldHide: boolean;
} {
    const finalResult = settings.conditionRule === 'any'
        ? results.includes(true)
        : results.every((result) => {
            return result === true;
        });

    const shouldHide = (finalResult && settings.showRule !== 'show') || (!finalResult && settings.showRule === 'show');

    return {
        finalResult,
        shouldHide,
    };
}

