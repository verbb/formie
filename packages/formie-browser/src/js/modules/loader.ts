import { assertBrowserModuleManifest, type BrowserModuleManifest, type BrowserModuleEntry } from '@verbb/formie-core';
import type { BrowserModuleDefinition, BrowserModuleInstance, BrowserModuleFailure, BrowserModuleHydrationReport, ModuleMatchContext, ModuleSetupContext } from '#contracts/modules';
import { builtinAddressModuleLoaders } from '#modules/address';
import { builtinCaptchaModuleLoaders } from '#modules/captchas';
import { builtinFieldModuleLoaders } from '#modules/fields';
import { builtinPaymentModuleLoaders } from '#modules/payments';
import { ModuleRegistry } from '#modules/registry';

export type BrowserModuleRuntime = BrowserModuleHydrationReport & {
    assertReady: () => void;
    destroy: () => Promise<void>;
    updateManifest: (manifest: BrowserModuleManifest) => Promise<void>;
};

type ModuleLoadContext = {
    registry: ModuleRegistry;
    setupContext: ModuleSetupContext;
    matchContext: Pick<ModuleMatchContext, 'root' | 'form' | 'surface'>;
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
    return [...new Set(entry.targets.flatMap((target) => {
        if (target.type === 'form') return [form || root];
        const selector = target.type === 'selector'
            ? target.selector
            : target.type === 'field'
                ? `[data-formie-field-uid="${CSS.escape(target.uid)}"]`
                : target.type === 'page'
                    ? `[data-formie-page-id="${CSS.escape(target.id)}"]`
                    : `[data-formie-action="${CSS.escape(target.action)}"]`;
        return [...(root.matches(selector) ? [root] : []), ...root.querySelectorAll(selector)];
    }))].filter((target) => !target.closest('[hidden], [data-formie-hidden="true"], [data-formie-conditionally-hidden], [data-formie-page-hidden]'));
}

/** Reconcile declaration key + DOM occurrence, including targets added by repeaters. */
export async function loadModulesFromManifest(manifest: BrowserModuleManifest, ctx: ModuleLoadContext): Promise<BrowserModuleRuntime> {
    assertBrowserModuleManifest(manifest);
    const surface = manifest.surface;
    if (ctx.matchContext.surface !== surface || ctx.setupContext.surface !== surface) {
        throw new Error(`Browser module manifest surface ${surface} cannot mount as ${ctx.matchContext.surface}.`);
    }
    const { root, form } = ctx.setupContext;
    const mounted = new Map<string, Map<Element, { instance: BrowserModuleInstance; config: string; moduleId: string; required: boolean }>>();
    const failures = new Map<string, BrowserModuleFailure>();
    const instances: BrowserModuleInstance[] = [];
    let disposed = false;
    let running: Promise<void> = Promise.resolve();
    let queued = false;
    const diagnose = async(entry: BrowserModuleEntry, error: unknown) => {
        const diagnostic: BrowserModuleFailure = { key: entry.key, moduleId: entry.moduleId, required: entry.required, surface, code: 'MODULE_UNAVAILABLE', message: 'A form feature could not start. Reload the page or contact the site administrator.' };
        failures.set(entry.key, diagnostic);
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
        const activeKeys = new Set(manifest.entries.map((entry) => entry.key));
        for (const key of failures.keys()) if (!activeKeys.has(key)) failures.delete(key);
        const inactiveInstances: BrowserModuleInstance[] = [];
        for (const [key, records] of mounted) {
            if (activeKeys.has(key)) continue;
            inactiveInstances.push(...Array.from(records.values(), ({ instance }) => instance));
            mounted.delete(key);
            failures.delete(key);
        }
        for (const instance of inactiveInstances.reverse()) {
            await dispose(instance);
            instances.splice(instances.indexOf(instance), 1);
        }
        for (const entry of manifest.entries) {
            if (disposed) continue;
            const failure = failures.get(entry.key);
            if (failure) failures.set(entry.key, { ...failure, required: entry.required });
            let targets: Element[];
            try { targets = resolveTargets(entry, root, form); }
            catch (error) { if (!failures.has(entry.key)) await diagnose(entry, error); continue; }
            const records = mounted.get(entry.key) ?? new Map();
            mounted.set(entry.key, records);
            for (const [target, record] of Array.from(records.entries()).reverse()) {
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
                const setup = { ...ctx.setupContext, target, entryKey: entry.key, surface, scope: entry.targets[0]?.type ?? 'form', options: entry.config };
                try {
                    if (existing) {
                        if (existing.instance.update && existing.moduleId === entry.moduleId && existing.required === entry.required) { await existing.instance.update(setup); existing.config = config; continue; }
                        await dispose(existing.instance); records.delete(target);
                        instances.splice(instances.indexOf(existing.instance), 1);
                    }
                    if (definition.surfaces && !definition.surfaces.includes(surface)) throw new Error(`Module ${entry.moduleId} does not support ${surface}.`);
                    if (definition.kind !== entry.kind) throw new Error(`Module ${entry.moduleId} is registered as ${definition.kind}, not ${entry.kind}.`);
                    if (!definition.match({ ...ctx.matchContext, target, scope: setup.scope, manifestItem: entry })) {
                        throw new Error(`Module ${entry.moduleId} does not support the rendered target.`);
                    }
                    const instance = await definition.setup(setup);
                    if (!instance) throw new Error(`Module ${entry.moduleId} did not initialize.`);
                    if (disposed || !root.contains(target) && target !== root) { await dispose(instance); continue; }
                    instance.key = entry.key; instance.moduleId = entry.moduleId; instance.kind = entry.kind; instance.target = target;
                    const ready = instance.assertReady;
                    instance.assertReady = () => {
                        try { ready?.(); }
                        catch (error) { void diagnose(entry, error); if (entry.required) throw new Error('A required form feature could not start.'); }
                    };
                    const before = instance.beforeSubmit;
                    const after = instance.afterSubmit;
                    instance.beforeSubmit = async(context) => {
                        try { await before?.(context); }
                        catch (error) { await diagnose(entry, error); if (entry.required) context.abort('A required form feature could not complete. Reload the page or contact the site administrator.'); }
                    };
                    instance.afterSubmit = async(context, result) => {
                        try { await after?.(context, result); }
                        catch (error) { await diagnose(entry, error); }
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
    const runtime: BrowserModuleRuntime = {
        get instances() { return instances; },
        get failures() { return [...failures.values()]; },
        assertReady: () => { if (blocked()) throw new Error('A required form feature could not start. Reload the page or contact the site administrator.'); },
        destroy: async() => {
            disposed = true; observer.disconnect(); form?.removeEventListener('submit', guard, true);
            await running;
            for (const records of Array.from(mounted.values()).reverse()) {
                for (const { instance } of Array.from(records.values()).reverse()) await dispose(instance);
            }
            mounted.clear(); instances.splice(0); failures.clear();
        },
        updateManifest: async(next) => {
            assertBrowserModuleManifest(next);
            if (next.surface !== surface) throw new Error('A mounted browser module runtime cannot change surfaces.');
            manifest = next;
            running = running.then(reconcile);
            await running;
        },
    };
    form?.addEventListener('submit', guard, true);
    await reconcile();
    observer.observe(root, { childList: true, subtree: true, attributes: true, attributeFilter: ['hidden', 'data-formie-hidden', 'data-formie-conditionally-hidden', 'data-formie-page-hidden', 'data-formie-field-uid', 'data-formie-page-id', 'data-formie-action'] });
    return runtime;
}
