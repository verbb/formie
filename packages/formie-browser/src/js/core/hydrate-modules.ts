import type { FormEventUnsubscribe } from '#contracts/client';
import type { FormMode } from '#contracts/common';
import type { FormieModuleDefinition, FormieModuleInstance, ModuleRegistrationOptions } from '#contracts/modules';
import type { FormModuleManifest } from '#contracts/schema';
import { EventBus } from '#events/event-bus';
import { loadModulesFromManifest } from '#modules/loader';
import { ModuleRegistry } from '#modules/registry';
import { createDebug } from '#utils/debug';

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

const debug = createDebug('general', 'module-hydrator');

export async function hydrateFormieModules(options: FormieModuleHydratorOptions): Promise<FormieModuleHydrator> {
    const root = options.root;
    const form = options.form ?? (root instanceof HTMLFormElement ? root : root.closest('form'));
    const modules = options.modules ?? [];
    const mode = options.mode ?? 'server-rendered';
    const registry = options.registry ?? new ModuleRegistry();
    const bus = new EventBus();

    // This helper reuses the canonical module manifest loader without mounting the
    // full form client, which lets non-frontend hosts like the CP opt into shared
    // field modules without inheriting submit or pagination ownership.
    const instances = await loadModulesFromManifest(modules, {
        registry,
        setupContext: {
            formId: form?.id || (root as HTMLElement).id || 'formie-modules',
            root,
            form,
            target: root,
            scope: 'form',
            state: {},
            options: {},
            on: (eventName, callback) => {
                return bus.on(eventName, callback);
            },
            emit: async(eventName, payload) => {
                await bus.emit(eventName, payload);
            },
        },
        matchContext: {
            root,
            form,
            mode,
        },
    });

    debug.log('Hydrated module manifest.', {
        moduleCount: modules.length,
        instanceCount: instances.length,
        mode,
    });

    return {
        destroy: async() => {
            await destroyModuleInstances(instances);
            bus.clear();
        },
        on: (eventName, callback) => {
            return bus.on(eventName, callback);
        },
        emit: async(eventName, payload) => {
            await bus.emit(eventName, payload);
        },
        registerModule: (moduleDefinition, registrationOptions = {}) => {
            return registry.register(moduleDefinition, registrationOptions);
        },
        unregisterModule: (moduleId) => {
            registry.unregister(moduleId);
        },
        getRegisteredModules: () => {
            return registry.getAll();
        },
    };
}

async function destroyModuleInstances(instances: FormieModuleInstance[]): Promise<void> {
    for (const instance of instances) {
        try {
            await instance.destroy();
        } catch (error) {
            console.error('[formie] Failed to destroy module instance.', error);
            debug.warn('Failed destroying module instance.', { error });
        }
    }
}
