import type { FormEventUnsubscribe } from '#contracts/client';
import type { FormMode } from '#contracts/common';
import type { BrowserModuleDefinition, BrowserModuleInstance, ModuleRegistrationOptions } from '#contracts/modules';
import type { BrowserModuleEntry } from '#contracts/schema';
import { EventBus } from '#events/event-bus';
import { loadModulesFromManifest } from '#modules/loader';
import { ModuleRegistry } from '#modules/registry';
import { createDebug } from '#utils/debug';

export type FormieModuleHydratorOptions = {
    root: Element;
    form?: HTMLFormElement | null;
    modules?: import('@verbb/formie-core').BrowserModuleManifest;
    surface?: import('@verbb/formie-core').BrowserSurface;
    mode?: FormMode;
    registry?: ModuleRegistry;
};

export type FormieModuleHydrator = {
    assertReady: () => void;
    prepare: (action: import('@verbb/formie-core').ClientSubmitAction) => Promise<Record<string, unknown>>;
    result: (result: import('#contracts/schema').FormSubmitResult) => Promise<void>;
    update: (manifest: import('@verbb/formie-core').BrowserModuleManifest) => Promise<void>;
    destroy: () => Promise<void>;
    on: (eventName: string, callback: (payload: unknown) => void | Promise<void>) => FormEventUnsubscribe;
    emit: (eventName: string, payload?: unknown) => Promise<void>;
    registerModule: (moduleDefinition: BrowserModuleDefinition, options?: ModuleRegistrationOptions) => boolean;
    unregisterModule: (moduleId: string) => void;
    getRegisteredModules: () => BrowserModuleDefinition[];
};

const debug = createDebug('general', 'module-hydrator');

export async function hydrateFormieModules(options: FormieModuleHydratorOptions): Promise<FormieModuleHydrator> {
    const root = options.root;
    const form = options.form ?? (root instanceof HTMLFormElement ? root : root.closest('form') ?? root.querySelector('form'));
    const modules = options.modules ?? { contractVersion: 1, entries: [] };
    const mode = options.mode ?? 'server-rendered';
    const registry = options.registry ?? new ModuleRegistry();
    const bus = new EventBus();

    // This helper reuses the canonical module manifest loader without mounting the
    // full form client, which lets CP edit hosts opt into shared
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
                root.dispatchEvent(new CustomEvent(eventName, { detail: payload, bubbles: true }));
                await bus.emit(eventName, payload);
            },
        },
        matchContext: {
            root,
            form,
            mode,
            surface: options.surface ?? 'cp-edit',
        },
    });

    debug.log('Hydrated module manifest.', {
        moduleCount: modules.entries.length,
        instanceCount: instances.length,
        mode,
    });

    return {
        prepare: async(action) => {
            if (!form) throw new Error('Browser modules require a mounted form element.');
            instances.forEach((instance) => instance.assertReady?.());
            let reason: string | undefined;
            for (const stage of ['prepare', 'validate', 'challenge', 'payment', 'send'] as const) {
                const context = {
                    form, stage, action: action === 'back' || action === 'save' ? action : 'submit' as const,
                    formData: new FormData(form),
                    abort: (message?: string) => { reason = message || 'A form feature could not complete.'; },
                    isAborted: () => Boolean(reason), abortReason: () => reason,
                };
                await bus.emit(`formie:browser:${stage}`, context);
                for (const instance of instances) await instance.onBeforeStage?.(context);
                if (reason) throw new Error(reason);
                if (stage !== 'send') {
                    for (const instance of instances) await instance.onAfterStage?.(context);
                    if (reason) throw new Error(reason);
                }
            }
            // Only transient module inputs cross this seam. PHP reads CAPTCHA data
            // and payment fields without allowing arbitrary request/authority overrides.
            return Object.fromEntries(Array.from(form.querySelectorAll<HTMLInputElement>('input[type="hidden"][name]')).map((input) => [input.name, input.value]));
        },
        result: async(result) => {
            if (!form) return;
            const context = { form, stage: 'send' as const, action: 'submit' as const, formData: new FormData(form), abort: () => {}, isAborted: () => false, abortReason: () => undefined };
            for (const instance of instances) await instance.onAfterStage?.(context, result);
            const resultContext = { ...context, stage: 'result' as const };
            await bus.emit('formie:browser:result', resultContext);
            for (const instance of instances) await instance.onBeforeStage?.(resultContext);
            await bus.emit('formie:submit:result', result);
            for (const instance of instances) await instance.onAfterStage?.(resultContext, result);
            form.dispatchEvent(new CustomEvent('formie:submit:result', { detail: result, bubbles: true }));
        },
        update: (manifest) => instances.updateManifest(manifest),
        assertReady: () => instances.forEach((instance) => instance.assertReady?.()),
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

async function destroyModuleInstances(instances: BrowserModuleInstance[]): Promise<void> {
    for (const instance of instances) {
        try {
            await instance.destroy();
        } catch (error) {
            console.error('[formie] Failed to destroy module instance.', error);
            debug.warn('Failed destroying module instance.', { error });
        }
    }
}
