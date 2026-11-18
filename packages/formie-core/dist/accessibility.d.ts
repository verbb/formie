import type { ClientFieldDefinition, ClientFormDefinition, ClientFormSession, ClientStringKey } from './types.js';
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
/**
 * Button text for client-rendered controls, translated by the server for the form's site. Older definitions
 * fall back to English.
 */
export declare function getClientString(definition: Pick<ClientFormDefinition, 'settings'> | null | undefined, key: ClientStringKey): string;
/**
 * A label in the Hidden position still names its control, so it stays in the page for assistive technology.
 */
export declare function isFieldLabelVisuallyHidden(field: ClientFieldDefinition): boolean;
/** Hides content visually while keeping it available to assistive technology. */
export declare const VISUALLY_HIDDEN_STYLE: {
    readonly position: "absolute";
    readonly width: "1px";
    readonly height: "1px";
    readonly padding: "0";
    readonly margin: "-1px";
    readonly overflow: "hidden";
    readonly clip: "rect(0, 0, 0, 0)";
    readonly whiteSpace: "nowrap";
    readonly border: "0";
};
export declare const VISUALLY_HIDDEN_CSS = "position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0";
/**
 * Moves focus after a submit, page change or Back: to the first invalid control, then to a form-level
 * message (an error summary or completion notice), then to the new page when the page changed.
 */
export declare function focusClientFormResult(form: Element | null | undefined, previousPageId?: string | null): void;
//# sourceMappingURL=accessibility.d.ts.map