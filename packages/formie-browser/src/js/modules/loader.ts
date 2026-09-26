import { assertBrowserModuleManifest, type BrowserModuleManifest, type BrowserModuleEntry, type BrowserSurface } from '@verbb/formie-core';
import type { BrowserModuleDefinition, BrowserModuleInstance, ModuleSetupContext } from '#contracts/modules';
import { builtinAddressModuleLoaders } from '#modules/address';
import { builtinCaptchaModuleLoaders } from '#modules/captchas';
import { builtinFieldModuleLoaders } from '#modules/fields';
import { builtinPaymentModuleLoaders } from '#modules/payments';
import { ModuleRegistry } from '#modules/registry';

export type BrowserModuleRuntime = BrowserModuleInstance[] & { updateManifest: (manifest: BrowserModuleManifest) => Promise<void> };

type ModuleLoadContext = {
    registry: ModuleRegistry;
    setupContext: ModuleSetupContext;
    matchContext: { root: Element; form: HTMLFormElement | null; mode?: string; surface?: BrowserSurface };
};
const builtinLoaders = { ...builtinFieldModuleLoaders, ...builtinAddressModuleLoaders, ...builtinCaptchaModuleLoaders, ...builtinPaymentModuleLoaders };
const pendingDefinitions = new Map<string, Promise<BrowserModuleDefinition>>();

async function resolveDefinition(moduleId: string, registry: ModuleRegistry): Promise<BrowserModuleDefinition> {
    const registered = registry.get(moduleId);
    if (registered) return registered;
    const loader = moduleId.startsWith('formie:') && Object.prototype.hasOwnProperty.call(builtinLoaders, moduleId.slice(7)) ? builtinLoaders[moduleId.slice(7)] : undefined;
    if (!loader) throw new Error(`Browser module ${moduleId} is not registered.`);
    if (!pendingDefinitions.has(moduleId)) {
        pendingDefinitions.set(moduleId, loader().catch((error) => { pendingDefinitions.delete(moduleId); throw error; }));
    }
    const definition = await pendingDefinitions.get(moduleId)!;
    if (definition.moduleId !== moduleId) throw new Error(`Module definition does not match ${moduleId}.`);
    registry.register(definition);
    return definition;
}

function resolveTargets(entry: BrowserModuleEntry, root: Element, form: HTMLFormElement | null): Element[] {
    const targets = entry.targets.length ? entry.targets : [{ targetType: 'form', targetId: 'form' }];
    return [...new Set(targets.flatMap((target) => {
        if (target.targetType === 'form' || target.targetType === 'global') return [form || root];
        const attribute = { field: 'data-formie-field-uid', page: 'data-formie-page-id', button: 'data-formie-action' }[target.targetType];
        const selector = `[${attribute}="${CSS.escape(target.targetId)}"]`;
        return [...(root.matches(selector) ? [root] : []), ...root.querySelectorAll(selector)];
    }))].filter((target) => !target.closest('[hidden], [data-formie-hidden="true"], [data-formie-conditionally-hidden], [data-formie-page-hidden]'));
}

/** Reconcile declaration key + DOM occurrence, including targets added by repeaters. */
export async function loadModulesFromManifest(manifest: BrowserModuleManifest, ctx: ModuleLoadContext): Promise<BrowserModuleRuntime> {
    assertBrowserModuleManifest(manifest);
    const surface = ctx.matchContext.surface ?? 'server-rendered';
    const { root, form } = ctx.setupContext;
    const mounted = new Map<string, Map<Element, { instance: BrowserModuleInstance; config: string; moduleId: string; required: boolean }>>();
    const failures = new Map<string, BrowserModuleEntry>();
    const instances = [] as unknown as BrowserModuleRuntime;
    let disposed = false;
    let running: Promise<void> = Promise.resolve();
    let queued = false;
    const diagnose = async(entry: BrowserModuleEntry, error: unknown) => {
        failures.set(entry.key, entry);
        const diagnostic = { key: entry.key, moduleId: entry.moduleId, required: entry.required, surface, code: 'MODULE_UNAVAILABLE', message: 'A form feature could not start. Reload the page or contact the site administrator.' };
        console.error('[formie] Browser module failure', diagnostic, error);
        await ctx.setupContext.emit('formie:browser:module:error', diagnostic);
    };
    const dispose = async(instance: BrowserModuleInstance) => {
        try { await instance.destroy(); }
        catch (error) {
            console.error('[formie] Browser module disposal failed', error);
            await ctx.setupContext.emit('formie:browser:module:error', { key: instance.key, moduleId: instance.moduleId, surface, code: 'MODULE_DISPOSE_FAILED', message: 'A form feature could not clean up. Reload the page before continuing.' });
        }
    };
    const blocked = () => [...failures.values()].some((entry) => entry.required);
    const showFailure = () => {
        if (!form || form.querySelector('[data-formie-module-error]')) return;
        const message = document.createElement('div');
        message.dataset.formieModuleError = 'true';
        message.setAttribute('role', 'alert');
        message.textContent = 'A required form feature could not start. Reload the page or contact the site administrator.';
        form.prepend(message);
    };
    const guard = (event: Event) => {
        if (!blocked()) return;
        event.preventDefault(); event.stopImmediatePropagation(); showFailure();
    };
    const reconcile = async() => {
        const activeKeys = new Set(manifest.entries.filter((entry) => entry.surfaces.includes(surface)).map((entry) => entry.key));
        for (const key of failures.keys()) if (!activeKeys.has(key)) failures.delete(key);
        for (const [key, records] of mounted) {
            if (activeKeys.has(key)) continue;
            for (const { instance } of records.values()) {
                await dispose(instance);
                instances.splice(instances.indexOf(instance), 1);
            }
            mounted.delete(key);
            failures.delete(key);
        }
        for (const entry of manifest.entries) {
            if (disposed || !entry.surfaces.includes(surface)) continue;
            if (failures.has(entry.key)) failures.set(entry.key, entry);
            const targets = resolveTargets(entry, root, form);
            const records = mounted.get(entry.key) ?? new Map();
            mounted.set(entry.key, records);
            for (const [target, record] of records) {
                if (!targets.includes(target)) {
                    await dispose(record.instance); records.delete(target);
                    instances.splice(instances.indexOf(record.instance), 1);
                }
            }
            // Resolve even absent targets: unknown required declarations cannot disappear silently.
            let definition: BrowserModuleDefinition;
            try { definition = await resolveDefinition(entry.moduleId, ctx.registry); }
            catch (error) { if (!failures.has(entry.key)) await diagnose(entry, error); continue; }
            let entryFailed = false;
            let recovered = false;
            for (const target of targets) {
                if (disposed) return;
                const config = JSON.stringify([entry.moduleId, entry.config, entry.required]);
                const existing = records.get(target);
                if (existing?.config === config) continue;
                const setup = { ...ctx.setupContext, target, entryKey: entry.key, surface, scope: entry.targets[0]?.targetType ?? 'form', options: entry.config };
                try {
                    if (existing) {
                        if (existing.instance.update && existing.moduleId === entry.moduleId && existing.required === entry.required) { await existing.instance.update(setup); existing.config = config; continue; }
                        await dispose(existing.instance); records.delete(target);
                        instances.splice(instances.indexOf(existing.instance), 1);
                    }
                    if (definition.surfaces && !definition.surfaces.includes(surface)) throw new Error(`Module ${entry.moduleId} does not support ${surface}.`);
                    if (!definition.match({ ...ctx.matchContext, mode: 'server-rendered', target, scope: setup.scope, manifestItem: entry })) {
                        throw new Error(`Module ${entry.moduleId} does not support the rendered target.`);
                    }
                    const instance = await definition.setup(setup);
                    if (!instance) throw new Error(`Module ${entry.moduleId} did not initialize.`);
                    if (disposed || !root.contains(target) && target !== root) { await dispose(instance); continue; }
                    instance.key = entry.key; instance.moduleId = entry.moduleId; instance.target = target;
                    const ready = instance.assertReady;
                    instance.assertReady = () => {
                        try { ready?.(); }
                        catch (error) { void diagnose(entry, error); if (entry.required) throw new Error('A required form feature could not start.'); }
                    };
                    const before = instance.onBeforeStage;
                    const after = instance.onAfterStage;
                    instance.onBeforeStage = async(context) => {
                        try { await before?.(context); }
                        catch (error) { await diagnose(entry, error); if (entry.required) context.abort('A required form feature could not complete. Reload the page or contact the site administrator.'); }
                    };
                    instance.onAfterStage = async(context, result) => {
                        try { await after?.(context, result); }
                        catch (error) { await diagnose(entry, error); if (entry.required) context.abort('A required form feature could not complete.'); }
                    };
                    records.set(target, { instance, config, moduleId: entry.moduleId, required: entry.required }); instances.push(instance); recovered = true;
                    await ctx.setupContext.emit('formie:browser:module:mount', { key: entry.key, moduleId: entry.moduleId, target });
                } catch (error) { entryFailed = true; if (!failures.has(entry.key)) await diagnose(entry, error); }
            }
            if (!entryFailed && (recovered || targets.length === 0)) failures.delete(entry.key);
        }
        if (blocked()) showFailure(); else form?.querySelector('[data-formie-module-error]')?.remove();
    };
    const schedule = () => {
        if (queued || disposed) return;
        queued = true;
        running = running.then(async() => { queued = false; if (!disposed) await reconcile(); });
        void running.catch((error) => console.error('[formie] Module reconciliation failed', error));
    };
    const observer = new MutationObserver(schedule);
    // The controller is also a submit hook, so API-driven sends obey the same failure policy.
    instances.push({
        assertReady: () => { if (blocked()) throw new Error('A required form feature could not start. Reload the page or contact the site administrator.'); },
        destroy: async() => {
            disposed = true; observer.disconnect(); form?.removeEventListener('submit', guard, true);
            await running;
            for (const records of mounted.values()) for (const { instance } of records.values()) await dispose(instance);
            mounted.clear(); instances.splice(1);
        },
        onBeforeStage: (context) => { if (blocked()) context.abort('A required form feature could not start. Reload the page or contact the site administrator.'); },
    });
    instances.updateManifest = async(next) => {
        assertBrowserModuleManifest(next);
        manifest = next;
        running = running.then(reconcile);
        await running;
    };
    form?.addEventListener('submit', guard, true);
    await reconcile();
    observer.observe(root, { childList: true, subtree: true, attributes: true, attributeFilter: ['hidden', 'data-formie-hidden', 'data-formie-conditionally-hidden', 'data-formie-page-hidden', 'data-formie-field-uid', 'data-formie-page-id', 'data-formie-action'] });
    return instances;
}
