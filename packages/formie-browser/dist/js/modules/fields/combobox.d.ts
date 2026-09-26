import TomSelect from 'tom-select/dist/esm/tom-select.complete.js';
import type { BrowserModuleDefinition } from '#contracts/modules';
export type FormieComboboxOptions = {
    multiple?: boolean;
    placeholder?: string | null;
};
type TomSelectInstance = InstanceType<typeof TomSelect> & {
    wrapper: HTMLElement;
    dropdown?: HTMLElement;
};
type SelectElement = HTMLSelectElement & {
    _formieTomSelect?: TomSelectInstance;
};
export declare function initFormieCombobox(select: SelectElement, options?: FormieComboboxOptions): () => void;
export declare const comboboxModule: BrowserModuleDefinition;
export {};
//# sourceMappingURL=combobox.d.ts.map