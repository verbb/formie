import { validateBrowserValue } from '@verbb/formie-core';
import type { ValidationRuleDefinition } from '#validation/types';
const bounds = (input: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement) => ({ type: 'number', min: input.hasAttribute('min') ? Number(input.getAttribute('min')) : null, max: input.hasAttribute('max') ? Number(input.getAttribute('max')) : null });
const minmax: ValidationRuleDefinition = {
    rule: ({ input }) => input.type !== 'number' || validateBrowserValue(input.value, bounds(input)) === null,
    message: ({ input, label, t }) => validateBrowserValue(input.value, bounds(input), { label }) ?? t('{label} is not a valid number.', { label }),
};
export default minmax;
