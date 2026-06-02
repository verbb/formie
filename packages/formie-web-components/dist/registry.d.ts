import type { FormieRegionKey } from './types.js';
export declare function assertValidCustomElementName(name: string): void;
/**
 * UI overrides for `<formie-core-form>`. Assign to `formie-core-form.registry` or mutate the
 * default singleton from {@link getFormieRegistry}.
 */
export declare class FormieRegistry {
    /**
     * Map `field.type` or renderer key (e.g. `single-line-text`) → custom element tag.
     * Controls receive `field`, `value`, `errorKey`, `disabled`, `hidden` and emit
     * `formie-control-value-change` with `event.detail` = next value.
     */
    fieldControls: Partial<Record<string, string>>;
    /**
     * When set, each **field** is rendered with this custom element (label, instructions,
     * control slot, errors). Distinct from {@link FormieRegistry.registerFieldControl}, which
     * replaces only the control.
     */
    fieldTag: string | null;
    /** Replace default layout regions with custom element tags (advanced). */
    regions: Partial<Record<FormieRegionKey, string>>;
    registerFieldControl(fieldKey: string, customElementTag: string): this;
    /** Register a custom element used as the **field** host for every question. */
    registerField(customElementTag: string): this;
    registerRegion(key: FormieRegionKey, customElementTag: string): this;
    clone(): FormieRegistry;
}
export declare function getFormieRegistry(): FormieRegistry;
export declare function createFormieRegistry(): FormieRegistry;
//# sourceMappingURL=registry.d.ts.map