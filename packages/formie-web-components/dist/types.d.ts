import type { FrontendFieldDefinition } from '@verbb/formie-core';
/** Dispatched as `formie-control-value-change` from custom field controls (`bubbles` + `composed`). */
export declare const FORMIE_CONTROL_VALUE_EVENT = "formie-control-value-change";
/**
 * Contract for custom elements registered via {@link FormieRegistry.registerFieldControl}.
 * Set as properties on the host; listen for {@link FORMIE_CONTROL_VALUE_EVENT}.
 */
export type FormieFieldControlElement = HTMLElement & {
    field: FrontendFieldDefinition;
    value: unknown;
    errorKey: string;
    disabled: boolean;
    hidden: boolean;
};
/**
 * Optional **field** host element: wraps label, instructions, the control, and errors.
 * Distinct from {@link FormieFieldControlElement}, which is only the input widget.
 * Use a **default slot** (or light DOM projection) where the control is rendered.
 */
export type FormieFieldElement = HTMLElement & {
    field: FrontendFieldDefinition;
    errors: string[];
};
/** Keys for optional custom elements that replace default layout regions on `<formie-core-form>`. */
export type FormieRegionKey = 'form' | 'page' | 'errorSummary' | 'loading' | 'pageActions';
//# sourceMappingURL=types.d.ts.map