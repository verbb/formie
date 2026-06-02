import type { FormieFormInstance } from '#contracts/client';
import { type ResolvedLegacyCompatibilityOptions } from '#compatibility/event-map';
type BindLegacyDomEventCompatibilityOptions = {
    target: Element;
    form: HTMLFormElement;
    instance: FormieFormInstance;
    options: ResolvedLegacyCompatibilityOptions;
    unbinds: Array<() => void>;
};
export declare function bindLegacyDomEventCompatibility({ target, form, instance, options, unbinds, }: BindLegacyDomEventCompatibilityOptions): void;
export {};
//# sourceMappingURL=dom-adapter.d.ts.map