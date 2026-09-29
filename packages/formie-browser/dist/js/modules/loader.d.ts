import { type BrowserModuleManifest } from '@verbb/formie-core';
import type { BrowserModuleInstance, ModuleMatchContext, ModuleSetupContext } from '#contracts/modules';
import { ModuleRegistry } from '#modules/registry';
export type BrowserModuleRuntime = BrowserModuleInstance[] & {
    updateManifest: (manifest: BrowserModuleManifest) => Promise<void>;
};
type ModuleLoadContext = {
    registry: ModuleRegistry;
    setupContext: ModuleSetupContext;
    matchContext: Pick<ModuleMatchContext, 'root' | 'form' | 'surface'>;
};
/** Reconcile declaration key + DOM occurrence, including targets added by repeaters. */
export declare function loadModulesFromManifest(manifest: BrowserModuleManifest, ctx: ModuleLoadContext): Promise<BrowserModuleRuntime>;
export {};
//# sourceMappingURL=loader.d.ts.map