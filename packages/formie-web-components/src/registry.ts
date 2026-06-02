import type { FormieRegionKey } from './types.js';

const TAG_RE = /^[a-z][a-z0-9]*(-[a-z0-9]+)+$/;

export function assertValidCustomElementName(name: string): void {
    if (!TAG_RE.test(name)) {
        throw new Error(
            `[Formie WC] Invalid custom element tag "${name}". Use lowercase hyphenated names (e.g. my-text-field).`,
        );
    }
}

/**
 * UI overrides for `<formie-core-form>`. Assign to `formie-core-form.registry` or mutate the
 * default singleton from {@link getFormieRegistry}.
 */
export class FormieRegistry {
    /**
     * Map `field.type` or renderer key (e.g. `single-line-text`) → custom element tag.
     * Controls receive `field`, `value`, `errorKey`, `disabled`, `hidden` and emit
     * `formie-control-value-change` with `event.detail` = next value.
     */
    fieldControls: Partial<Record<string, string>> = {};

    /**
     * When set, each **field** is rendered with this custom element (label, instructions,
     * control slot, errors). Distinct from {@link FormieRegistry.registerFieldControl}, which
     * replaces only the control.
     */
    fieldTag: string | null = null;

    /** Replace default layout regions with custom element tags (advanced). */
    regions: Partial<Record<FormieRegionKey, string>> = {};

    registerFieldControl(fieldKey: string, customElementTag: string): this {
        assertValidCustomElementName(customElementTag);
        this.fieldControls[fieldKey] = customElementTag;

        return this;
    }

    /** Register a custom element used as the **field** host for every question. */
    registerField(customElementTag: string): this {
        assertValidCustomElementName(customElementTag);
        this.fieldTag = customElementTag;

        return this;
    }

    registerRegion(key: FormieRegionKey, customElementTag: string): this {
        assertValidCustomElementName(customElementTag);
        this.regions[key] = customElementTag;

        return this;
    }

    clone(): FormieRegistry {
        const next = new FormieRegistry();
        next.fieldControls = { ...this.fieldControls };
        next.fieldTag = this.fieldTag;
        next.regions = { ...this.regions };

        return next;
    }
}

const defaultRegistry = new FormieRegistry();

export function getFormieRegistry(): FormieRegistry {
    return defaultRegistry;
}

export function createFormieRegistry(): FormieRegistry {
    return new FormieRegistry();
}
