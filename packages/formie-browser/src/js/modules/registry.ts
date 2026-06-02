import type { FormieModuleDefinition, ModuleRegistrationOptions } from '#contracts/modules';

export class ModuleRegistry {
    private modules = new Map<string, FormieModuleDefinition>();

    register(moduleDefinition: FormieModuleDefinition, options: ModuleRegistrationOptions = {}): boolean {
        // The registry is intentionally lightweight: the loader handles lazy
        // resolution, while this class only tracks already-available definitions.
        const existing = this.modules.get(moduleDefinition.id);

        if (existing === moduleDefinition) {
            return true;
        }

        if (existing && !options.replace) {
            console.warn(
                `[formie] Module "${moduleDefinition.id}" is already registered. `
                + 'Pass { replace: true } to override the existing definition.',
            );
            return false;
        }

        this.modules.set(moduleDefinition.id, moduleDefinition);
        return true;
    }

    unregister(moduleId: string): void {
        this.modules.delete(moduleId);
    }

    get(moduleId: string): FormieModuleDefinition | null {
        return this.modules.get(moduleId) || null;
    }

    getAll(): FormieModuleDefinition[] {
        return Array.from(this.modules.values());
    }
}
