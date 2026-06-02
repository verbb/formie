import type { FormieModuleDefinition, ModuleRegistrationOptions } from '#contracts/modules';
export declare class ModuleRegistry {
    private modules;
    register(moduleDefinition: FormieModuleDefinition, options?: ModuleRegistrationOptions): boolean;
    unregister(moduleId: string): void;
    get(moduleId: string): FormieModuleDefinition | null;
    getAll(): FormieModuleDefinition[];
}
//# sourceMappingURL=registry.d.ts.map