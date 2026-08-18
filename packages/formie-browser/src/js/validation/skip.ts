/**
 * Controls marked with this attribute are not value carriers.
 * The validator, live handlers, and match lookups all skip them.
 */
export const VALIDATION_SKIP_ATTR = 'data-formie-validation-skip';

export function isValidationSkipped(element: Element | null): boolean {
    return !!element && element.hasAttribute(VALIDATION_SKIP_ATTR);
}
