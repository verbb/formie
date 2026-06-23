import type { ValidationRuleDefinition } from '#validation/types';
import { getComparableInput, getLabelText } from '#validation/rules/shared';

const match: ValidationRuleDefinition = {
    rule: (ctx) => {
        const sourceInput = getComparableInput(ctx);

        if (!sourceInput) {
            return true;
        }

        return sourceInput.value === ctx.input.value;
    },
    message: (ctx) => {
        const sourceInput = getComparableInput(ctx);
        const sourceField = sourceInput?.closest('[data-formie-field-handle]') as HTMLElement | null;
        const sourceLabel = getLabelText(sourceField);

        return ctx.input.getAttribute('data-formie-validation-match-message')
            ?? ctx.t('{label} must match {value}.', {
                label: ctx.label,
                value: sourceLabel,
            });
    },
};

export default match;
