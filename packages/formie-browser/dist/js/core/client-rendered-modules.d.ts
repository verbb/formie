import type { ClientFormInstance } from '@verbb/formie-core';
import { ModuleRegistry } from '#modules/registry';
export declare function mountClientRenderedModules(root: Element, instance: ClientFormInstance, registry?: ModuleRegistry): Promise<{
    instances: readonly import("../contracts/modules.js").BrowserModuleInstance[];
    failures: readonly import("../contracts/modules.js").BrowserModuleFailure[];
    destroy: () => Promise<void>;
    assertReady: () => void;
    prepare: (action: import("@verbb/formie-core").ClientSubmitAction) => Promise<Record<string, unknown>>;
    result: (result: import("../contracts/schema.js").FormSubmitResult) => Promise<void>;
    update: (manifest: import("@verbb/formie-core").BrowserModuleManifest) => Promise<void>;
    on: (eventName: string, callback: (payload: unknown) => void | Promise<void>) => import("../contracts/client.js").FormEventUnsubscribe;
    emit: (eventName: string, payload?: unknown) => Promise<void>;
    registerModule: (moduleDefinition: import("../contracts/modules.js").BrowserModuleDefinition, options?: import("../contracts/modules.js").ModuleRegistrationOptions) => boolean;
    unregisterModule: (moduleId: string) => void;
    getRegisteredModules: () => import("../contracts/modules.js").BrowserModuleDefinition[];
}>;
//# sourceMappingURL=client-rendered-modules.d.ts.map