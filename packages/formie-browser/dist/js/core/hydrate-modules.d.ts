import type { FormEventUnsubscribe } from '#contracts/client';
import type { BrowserModuleDefinition, BrowserModuleHydrationReport, ModuleRegistrationOptions } from '#contracts/modules';
import { ModuleRegistry } from '#modules/registry';
export type FormieModuleHydratorOptions = {
    root: Element;
    form?: HTMLFormElement | null;
    modules?: import('@verbb/formie-core').BrowserModuleManifest;
    surface?: import('@verbb/formie-core').BrowserSurface;
    registry?: ModuleRegistry;
};
export type FormieModuleHydrator = BrowserModuleHydrationReport & {
    assertReady: () => void;
    prepare: (action: import('@verbb/formie-core').ClientSubmitAction) => Promise<Record<string, unknown>>;
    result: (result: import('#contracts/schema').FormSubmitResult) => Promise<void>;
    update: (manifest: import('@verbb/formie-core').BrowserModuleManifest) => Promise<void>;
    destroy: () => Promise<void>;
    on: (eventName: string, callback: (payload: unknown) => void | Promise<void>) => FormEventUnsubscribe;
    emit: (eventName: string, payload?: unknown) => Promise<void>;
    registerModule: (moduleDefinition: BrowserModuleDefinition, options?: ModuleRegistrationOptions) => boolean;
    unregisterModule: (moduleId: string) => void;
    getRegisteredModules: () => BrowserModuleDefinition[];
};
export declare function hydrateFormieModules(options: FormieModuleHydratorOptions): Promise<FormieModuleHydrator>;
//# sourceMappingURL=hydrate-modules.d.ts.map