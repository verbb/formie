import type { ModuleSetupContext } from '#contracts/modules';
import type { ValidationContext } from '#validation/types';
type ElementCleanup = void | (() => void);
type ValidatorApi = {
    addValidator: (name: string, validatorFunction: (ctx: ValidationContext) => boolean, errorMessage?: (ctx: ValidationContext) => string) => void;
    removeValidator: (name: string) => void;
};
export declare function escapeSelectorValue(value: string): string;
export declare function getFieldContainers(root: Element, fieldHandle?: string): HTMLElement[];
export declare function getModuleFieldContainers(ctx: ModuleSetupContext): HTMLElement[];
export declare function getModuleFieldTarget(ctx: ModuleSetupContext): HTMLElement | null;
export declare function getFormValidator(form: HTMLFormElement | null): ValidatorApi | null;
export declare function retainFormValidators(form: HTMLFormElement | null, key: string, register: (validator: ValidatorApi) => void): void;
export declare function releaseFormValidators(form: HTMLFormElement | null, key: string, validatorNames: readonly string[]): void;
export declare function observeMatchingElements<TElement extends Element>(root: Element, selector: string, isMatch: (element: Element) => element is TElement, init: (element: TElement) => ElementCleanup): () => void;
export declare function getTemplateSource(root: Element, templateId: string | null | undefined): HTMLTemplateElement | HTMLScriptElement | HTMLElement | null;
export declare function getTemplateSourceHtml(source: HTMLTemplateElement | HTMLScriptElement | HTMLElement): string;
export declare function dispatchFieldEvent(target: Element, moduleId: string, name: string, detail: Record<string, unknown>): void;
export {};
//# sourceMappingURL=shared.d.ts.map