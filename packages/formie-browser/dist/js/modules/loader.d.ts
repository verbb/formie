import type { FormieModuleInstance, ModuleMatchContext, ModuleSetupContext } from '#contracts/modules';
import type { FormModuleManifest } from '#contracts/schema';
import { ModuleRegistry } from '#modules/registry';
type ModuleLoadContext = {
    registry: ModuleRegistry;
    setupContext: ModuleSetupContext;
    matchContext: Omit<ModuleMatchContext, 'target' | 'scope' | 'manifestItem'>;
};
export declare function loadModulesFromManifest(manifest: FormModuleManifest[], ctx: ModuleLoadContext): Promise<FormieModuleInstance[]>;
export {};
//# sourceMappingURL=loader.d.ts.map