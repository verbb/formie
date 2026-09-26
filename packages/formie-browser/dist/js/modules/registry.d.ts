import type { BrowserModuleDefinition, ModuleRegistrationOptions } from '#contracts/modules';
export declare class ModuleRegistry {
    private modules;
    register(moduleDefinition: BrowserModuleDefinition, options?: ModuleRegistrationOptions): boolean;
    unregister(moduleId: string): void;
    get(moduleId: string): BrowserModuleDefinition | null;
    getAll(): BrowserModuleDefinition[];
}
/** Trusted application registrations shared by client-rendered framework hosts. */
export declare const clientRenderedModuleRegistry: ModuleRegistry;
//# sourceMappingURL=registry.d.ts.map