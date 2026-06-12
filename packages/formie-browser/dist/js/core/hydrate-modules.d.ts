import type { FormEventUnsubscribe } from '#contracts/client';
import type { FormMode } from '#contracts/common';
import type { FormieModuleDefinition, ModuleRegistrationOptions } from '#contracts/modules';
import type { FormModuleManifest } from '#contracts/schema';
import { ModuleRegistry } from '#modules/registry';
export type FormieModuleHydratorOptions = {
    root: Element;
    form?: HTMLFormElement | null;
    modules?: FormModuleManifest[];
    mode?: FormMode;
    registry?: ModuleRegistry;
};
export type FormieModuleHydrator = {
    destroy: () => Promise<void>;
    on: (eventName: string, callback: (payload: unknown) => void | Promise<void>) => FormEventUnsubscribe;
    emit: (eventName: string, payload?: unknown) => Promise<void>;
    registerModule: (moduleDefinition: FormieModuleDefinition, options?: ModuleRegistrationOptions) => boolean;
    unregisterModule: (moduleId: string) => void;
    getRegisteredModules: () => FormieModuleDefinition[];
};
export declare function hydrateFormieModules(options: FormieModuleHydratorOptions): Promise<FormieModuleHydrator>;
//# sourceMappingURL=hydrate-modules.d.ts.map