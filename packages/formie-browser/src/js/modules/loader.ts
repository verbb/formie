import type {
    FormieModuleDefinition,
    FormieModuleInstance,
    ModuleMatchContext,
    ModuleSetupContext,
} from '#contracts/modules';
import type { FormModuleManifest, FormModuleTarget, FormModuleTargetType } from '#contracts/schema';
import { builtinAddressModuleLoaders } from '#modules/address';
import { builtinCaptchaModuleLoaders } from '#modules/captchas';
import { builtinFieldModuleLoaders } from '#modules/fields';
import { builtinPaymentModuleLoaders } from '#modules/payments';
import { ModuleRegistry } from '#modules/registry';
import { createDebug } from '#utils/debug';
import {
    getGlobalModuleLifecycleEventName,
    getScopedModuleLifecycleEventName,
    type ModuleLifecycleEventPhase,
} from '#utils/event-names';

type ModuleLoadContext = {
    registry: ModuleRegistry;
    setupContext: ModuleSetupContext;
    matchContext: Omit<ModuleMatchContext, 'target' | 'scope' | 'manifestItem'>;
};

type ModuleDefinitionLoader = () => Promise<FormieModuleDefinition>;

const builtinModuleLoaders: Record<string, ModuleDefinitionLoader> = {
    ...builtinFieldModuleLoaders,
    ...builtinAddressModuleLoaders,
    ...builtinCaptchaModuleLoaders,
    ...builtinPaymentModuleLoaders,
};

const builtinModuleLoadCache = new Map<string, Promise<FormieModuleDefinition | null>>();
const debug = createDebug('general', 'loader');
const importModuleFromSrc = new Function('src', 'return import(src);') as (src: string) => Promise<unknown>;

async function emitModuleLifecycleEvent(
    emit: ModuleSetupContext['emit'],
    moduleId: string,
    phase: ModuleLifecycleEventPhase,
    detail: Record<string, unknown>,
): Promise<void> {
    // Emit one stable global lifecycle event plus the scoped module event.
    // Consumers can subscribe globally and filter by `detail.moduleId`, or bind
    // directly to the scoped module id when they need a narrow hook.
    await emit(getGlobalModuleLifecycleEventName(phase), detail);
    await emit(getScopedModuleLifecycleEventName(moduleId, phase), detail);
}

function isModuleDefinition(definition: unknown): definition is FormieModuleDefinition {
    return !!definition && typeof definition === 'object' &&
        typeof (definition as FormieModuleDefinition).id === 'string' &&
        typeof (definition as FormieModuleDefinition).setup === 'function' &&
        typeof (definition as FormieModuleDefinition).match === 'function';
}

async function resolveBuiltinDefinition(moduleId: string, ctx: ModuleLoadContext): Promise<FormieModuleDefinition | null> {
    const loader = builtinModuleLoaders[moduleId];

    if (!loader) {
        return null;
    }

    // Cache the in-flight import promise so multiple forms needing the same builtin
    // module share one chunk fetch and one registration path.
    if (!builtinModuleLoadCache.has(moduleId)) {
        builtinModuleLoadCache.set(moduleId, (async() => {
            try {
                const definition = await loader();

                if (!isModuleDefinition(definition)) {
                    return null;
                }

                ctx.registry.register(definition);

                return definition;
            } catch (error) {
                console.error('[formie] Failed to load builtin module:', moduleId, error);
                debug.warn('Failed loading builtin module.', { moduleId, error });
                return null;
            }
        })());
    }

    return builtinModuleLoadCache.get(moduleId) || null;
}

async function resolveDefinitionFromSrc(src: string): Promise<FormieModuleDefinition | null> {
    try {
        const imported = await importModuleFromSrc(src) as {
            default?: unknown;
            formieModule?: unknown;
        };
        const definition = imported?.default || imported?.formieModule || null;

        if (!isModuleDefinition(definition)) {
            return null;
        }

        return definition;
    } catch (error) {
        console.error('[formie] Failed to load module from src:', src, error);
        debug.warn('Failed loading module from src.', { src, error });
        return null;
    }
}

async function resolveDefinition(manifestItem: FormModuleManifest, ctx: ModuleLoadContext): Promise<FormieModuleDefinition | null> {
    const registered = ctx.registry.get(manifestItem.id);

    // Resolution order is deliberate:
    // 1) already-registered custom/client-side modules
    // 2) first-party lazy builtin modules
    // 3) explicit manifest src imports
    if (registered) {
        return registered;
    }

    const builtin = await resolveBuiltinDefinition(manifestItem.id, ctx);

    if (builtin) {
        return builtin;
    }

    if (manifestItem.src) {
        const fromSrc = await resolveDefinitionFromSrc(manifestItem.src);

        if (fromSrc) {
            ctx.registry.register(fromSrc);
            return fromSrc;
        }
    }

    return null;
}

function escapeSelectorValue(value: string): string {
    if (typeof window.CSS?.escape === 'function') {
        return window.CSS.escape(value);
    }

    return value.replace(/["\\]/g, '\\$&');
}

function queryTargets(root: Element, selector: string): Element[] {
    if (root.matches(selector)) {
        return [root, ...Array.from(root.querySelectorAll(selector))];
    }

    return Array.from(root.querySelectorAll(selector));
}

function resolveTarget(target: FormModuleTarget, ctx: ModuleLoadContext): Array<{ scope: FormModuleTargetType; element: Element }> {
    const root = ctx.setupContext.root;
    const form = ctx.setupContext.form;
    const scope = target.targetType;
    const targetId = target.targetId;

    // Manifest targeting is resolved centrally so module implementations receive
    // a concrete DOM surface instead of re-querying ownership from options.
    if (scope === 'selector') {
        return queryTargets(root, targetId).map((element) => {
            return { scope, element };
        });
    }

    if (scope === 'field') {
        return queryTargets(root, `[data-formie-field-handle="${escapeSelectorValue(targetId)}"]`).map((element) => {
            return { scope, element };
        });
    }

    if (scope === 'page') {
        return queryTargets(root, `[data-formie-page-id="${escapeSelectorValue(targetId)}"]`).map((element) => {
            return { scope, element };
        });
    }

    if (scope === 'button') {
        return queryTargets(root, `[data-formie-action="${escapeSelectorValue(targetId)}"]`).map((element) => {
            return { scope, element };
        });
    }

    return [{
        scope: 'form',
        element: form || root,
    }];
}

function resolveTargets(item: FormModuleManifest, ctx: ModuleLoadContext): Array<{ scope: FormModuleTargetType; element: Element }> {
    const targets: FormModuleTarget[] = item.targets && item.targets.length > 0 ? item.targets : [{
        targetType: 'form',
        targetId: 'form',
    }];

    return targets.flatMap((target) => {
        return resolveTarget(target, ctx);
    });
}

export async function loadModulesFromManifest(
    manifest: FormModuleManifest[],
    ctx: ModuleLoadContext,
): Promise<FormieModuleInstance[]> {
    const instances: FormieModuleInstance[] = [];
    debug.log('Loading module manifest.', {
        manifestCount: manifest.length,
    });

    for (const item of manifest) {
        const definition = await resolveDefinition(item, ctx);

        if (!definition) {
            debug.warn('Skipping manifest item (definition not resolved).', {
                moduleId: item.id,
                src: item.src,
            });
            continue;
        }

        const targets = resolveTargets(item, ctx);
        debug.log('Resolved module targets.', {
            moduleId: definition.id,
            targets: item.targets || [],
            targetCount: targets.length,
        });

        if (targets.length === 0 && definition.kind === 'address') {
            console.warn(
                `[formie] Address module "${item.id}" skipped: no target element found for ` +
                `fieldHandle="${item.targets?.find((target) => target.targetType === 'field')?.targetId ?? '?'}". ` +
                'Check that the Address field exists in the rendered form.',
            );
        }

        for (const target of targets) {
            const matchContext: ModuleMatchContext = {
                ...ctx.matchContext,
                target: target.element,
                scope: target.scope,
                manifestItem: item,
            };

            if (!definition.match(matchContext)) {
                if (definition.kind === 'address') {
                    console.warn(
                        `[formie] Address module "${definition.id}" skipped: target element does not contain ` +
                        '[data-formie-address-autocomplete-input]. Enable the Auto-Complete subfield.',
                    );
                }
                debug.log('Module target did not match predicate.', {
                    moduleId: definition.id,
                    scope: target.scope,
                });
                continue;
            }

            const options = item.config || ctx.setupContext.options;
            const moduleEventName = definition.id;
            const lifecycleDetail = {
                moduleId: definition.id,
                moduleKind: definition.kind,
                target: target.element,
                scope: target.scope,
                options,
                manifestItem: item,
            };

            await emitModuleLifecycleEvent(ctx.setupContext.emit, moduleEventName, 'before-setup', lifecycleDetail);

            let instance: FormieModuleInstance | null = null;

            try {
                const setupResult = await definition.setup({
                    ...ctx.setupContext,
                    target: target.element,
                    scope: target.scope,
                    options,
                });

                if (setupResult) {
                    instance = setupResult;
                }
            } catch (err) {
                console.error(`[formie] Module "${definition.id}" setup failed:`, err);
                debug.warn('Module setup failed.', {
                    moduleId: definition.id,
                    scope: target.scope,
                    error: err,
                });
            }

            await emitModuleLifecycleEvent(ctx.setupContext.emit, moduleEventName, 'after-setup', {
                ...lifecycleDetail,
                instanceCreated: !!instance,
            });

            if (instance) {
                debug.log('Module instance created.', {
                    moduleId: definition.id,
                    scope: target.scope,
                });
                instances.push({
                    ...instance,
                    destroy: async() => {
                        debug.log('Destroying module instance.', {
                            moduleId: definition.id,
                            scope: target.scope,
                        });
                        await emitModuleLifecycleEvent(ctx.setupContext.emit, moduleEventName, 'before-destroy', lifecycleDetail);
                        await instance.destroy();
                        await emitModuleLifecycleEvent(ctx.setupContext.emit, moduleEventName, 'after-destroy', lifecycleDetail);
                        debug.log('Module instance destroyed.', {
                            moduleId: definition.id,
                            scope: target.scope,
                        });
                    },
                });
            }
        }
    }

    debug.log('Module manifest processing complete.', {
        instanceCount: instances.length,
    });
    return instances;
}
