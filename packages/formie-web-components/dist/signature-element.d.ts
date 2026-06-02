import { LitElement } from 'lit';
import type { FrontendFieldDefinition, FrontendFormDefinition } from '@verbb/formie-core';
/** Internal `<formie-internal-signature>` used by `<formie-core-form>` for draw-signature fields. */
export declare class FormieInternalSignature extends LitElement {
    static styles: import("lit").CSSResult;
    field: FrontendFieldDefinition;
    modules: FrontendFormDefinition['modules'];
    value: string;
    disabled: boolean;
    private loadError;
    private pad;
    private strokeListener;
    firstUpdated(): Promise<void>;
    disconnectedCallback(): void;
    private onWinResize;
    private resolveDrawModuleConfig;
    private resizeCanvas;
    updated(changed: Map<string, unknown>): void;
    private applySerializedValue;
    private emitValue;
    render(): import("lit-html").TemplateResult<1>;
}
//# sourceMappingURL=signature-element.d.ts.map