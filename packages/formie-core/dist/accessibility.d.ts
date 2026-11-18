import type { ClientFieldDefinition, ClientFormDefinition, ClientFormSession } from './types.js';
export type ClientErrorAriaLive = 'polite' | 'assertive' | 'off';
export type ClientFieldElementIds = {
    input: string;
    label: string;
    instructions: string;
    errors: string;
};
/**
 * How a field's label relates to its control: `control` labels one form control with `for`, `group` names a
 * set of controls (choices, or an Agree checkbox whose own label is its description), and `none` labels nothing.
 */
export type ClientFieldLabelTarget = 'control' | 'group' | 'none';
export declare function getClientErrorAriaLive(definition: ClientFormDefinition): ClientErrorAriaLive;
export declare function getClientFieldErrorId(session: ClientFormSession, errorKey: string): string;
export declare function getClientFieldIds(session: ClientFormSession, errorKey: string): ClientFieldElementIds;
/**
 * `rendererType` is the adapter's resolved renderer, which maps custom fields onto the built-in controls.
 */
export declare function getClientFieldLabelTarget(field: ClientFieldDefinition, rendererType: string): ClientFieldLabelTarget;
/**
 * Joins element IDs for `aria-describedby` and similar attributes, or returns undefined when there are none.
 */
export declare function joinIdReferences(...ids: Array<string | false | null | undefined>): string | undefined;
//# sourceMappingURL=accessibility.d.ts.map