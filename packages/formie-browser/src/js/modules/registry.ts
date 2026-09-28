import type { BrowserModuleDefinition, ModuleRegistrationOptions } from '#contracts/modules';

export class ModuleRegistry {
    private modules = new Map<string, BrowserModuleDefinition>();

    register(moduleDefinition: BrowserModuleDefinition, options: ModuleRegistrationOptions = {}): boolean {
        if (!/^[a-z][a-z0-9.-]*:[a-z][a-z0-9.-]*$/.test(moduleDefinition.moduleId) ||
            moduleDefinition.version !== 2 || !Array.isArray(moduleDefinition.surfaces) || moduleDefinition.surfaces.length === 0 ||
            moduleDefinition.surfaces.some((surface) => !['server-rendered', 'client-rendered', 'cp-edit'].includes(surface)) ||
            !['field', 'captcha', 'payment', 'address', 'core'].includes(moduleDefinition.kind) ||
            typeof moduleDefinition.match !== 'function' || typeof moduleDefinition.setup !== 'function') {
            throw new Error('Unsupported browser module definition. Register a namespaced moduleId compatible with version 2.');
        }
        const existing = this.modules.get(moduleDefinition.moduleId);

        if (existing === moduleDefinition) {
            return true;
        }

        if (existing && !options.replace) {
            console.warn(
                `[formie] Module "${moduleDefinition.moduleId}" is already registered. `
                + 'Pass { replace: true } to override the existing definition.',
            );
            return false;
        }

        this.modules.set(moduleDefinition.moduleId, moduleDefinition);
        return true;
    }

    unregister(moduleId: string): void {
        this.modules.delete(moduleId);
    }

    get(moduleId: string): BrowserModuleDefinition | null {
        return this.modules.get(moduleId) || null;
    }

    getAll(): BrowserModuleDefinition[] {
        return Array.from(this.modules.values());
    }
}

/** Trusted application registrations shared by client-rendered framework hosts. */
export const clientRenderedModuleRegistry = new ModuleRegistry();
