import type { ValidationRule, ValidationRuleDefinition } from '#validation/types';
type DatePartsField = {
    field: HTMLElement;
    rule: ValidationRule;
};
/**
 * Date fields put the rule on their own wrapper, while each part (year, month, day and time) is a subfield with its own
 * input. Returns the Date field an input belongs to when that field has date limits.
 */
export declare function getDatePartsField(input: Element | null): DatePartsField | null;
declare const dateParts: ValidationRuleDefinition;
export default dateParts;
//# sourceMappingURL=date-parts.d.ts.map