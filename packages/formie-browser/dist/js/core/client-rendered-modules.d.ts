import type { ClientFormInstance } from '@verbb/formie-core';
import { ModuleRegistry } from '#modules/registry';
export declare function mountClientRenderedModules(root: Element, instance: ClientFormInstance, registry?: ModuleRegistry): Promise<{
    instances: readonly import("../..").BrowserModuleInstance[];
    failures: readonly import("../..").BrowserModuleFailure[];
    destroy: () => Promise<void>;
    assertReady: () => void;
    prepare: (action: import("@verbb/formie-core").ClientSubmitAction) => Promise<Record<string, unknown>>;
    result: (result: import("../..").FormSubmitResult) => Promise<void>;
    update: (manifest: import("@verbb/formie-core").BrowserModuleManifest) => Promise<void>;
    on: (eventName: string, callback: (payload: unknown) => void | Promise<void>) => import("../..").FormEventUnsubscribe;
    emit: (eventName: string, payload?: unknown) => Promise<void>;
    registerModule: (moduleDefinition: import("../..").BrowserModuleDefinition, options?: import("../..").ModuleRegistrationOptions) => boolean;
    unregisterModule: (moduleId: string) => void;
    getRegisteredModules: () => import("../..").BrowserModuleDefinition[];
}>;
//# sourceMappingURL=client-rendered-modules.d.ts.map