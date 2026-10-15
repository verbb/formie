import type {
    BrowserModuleDefinition,
    BrowserModuleInstance,
    ModuleSetupContext,
    BeforeSubmitContext,
} from '#contracts/modules';
import type { FormSubmitResult } from '#contracts/schema';
import { DEFAULT_WAIT_FOR_VALUE_MS } from '#modules/captchas/constants';
import { ensureCaptchaValueInput, waitForCaptchaValue } from '#modules/captchas/utils';
import {
    createCaptchaHostServices,
    type CaptchaHostServices,
    type CaptchaModuleOptions,
    type NormalizedCaptchaModuleOptions,
    normalizeCaptchaModuleOptions,
} from '#modules/captchas/host';
import { createDebug } from '#utils/debug';
import { getFormStateEventName } from '#utils/event-names';
import type { FormUnloadWarningExclusionsEventDetail } from '#utils/unload-warning';

type Cleanup = () => void;
const debug = createDebug('captchas');

type CaptchaModuleFactory<TProvider extends Record<string, unknown>> = {
    moduleId: string;
    defaultPlaceholderSelector: string;
    defaultTokenFieldNames?: string[];
    defaultWaitForValueMs?: number;
    setup: (ctx: CaptchaModuleSetupContext<TProvider>) => Promise<BrowserModuleInstance | void>;
};

export type ManagedCaptchaModuleAdapter<
    TProvider extends Record<string, unknown>,
    TApi,
    TWidget,
> = {
    moduleId: string;
    defaultPlaceholderSelector: string;
    defaultTokenFieldNames?: string[];
    load: (ctx: CaptchaModuleSetupContext<TProvider>) => Promise<TApi>;
    mount: (args: {
        api: TApi;
        placeholder: HTMLElement;
        container: HTMLElement;
        services: CaptchaHostServices;
        options: NormalizedCaptchaModuleOptions<TProvider>;
        provider: TProvider;
    }) => Promise<TWidget> | TWidget;
    challenge: (args: {
        api: TApi;
        widget: TWidget;
        placeholder: HTMLElement;
        services: CaptchaHostServices;
        options: NormalizedCaptchaModuleOptions<TProvider>;
        provider: TProvider;
        stageCtx: BeforeSubmitContext;
    }) => Promise<void> | void;
    unmount?: (args: {
        api: TApi;
        widget: TWidget;
        placeholder: HTMLElement;
        services: CaptchaHostServices;
        options: NormalizedCaptchaModuleOptions<TProvider>;
        provider: TProvider;
    }) => Promise<void> | void;
    reset?: (args: {
        api: TApi;
        widget: TWidget;
        placeholder: HTMLElement;
        services: CaptchaHostServices;
        options: NormalizedCaptchaModuleOptions<TProvider>;
        provider: TProvider;
        reason: 'submit-result' | 'reset-state';
    }) => Promise<void> | void;
};

export type CaptchaModuleSetupContext<TProvider extends Record<string, unknown>> = Omit<ModuleSetupContext, 'options'> & {
    options: NormalizedCaptchaModuleOptions<TProvider>;
    services: CaptchaHostServices;
};

export function createCaptchaModule<TProvider extends Record<string, unknown> = Record<string, unknown>>({
    moduleId,
    defaultPlaceholderSelector,
    defaultTokenFieldNames = [],
    defaultWaitForValueMs = DEFAULT_WAIT_FOR_VALUE_MS,
    setup,
}: CaptchaModuleFactory<TProvider>): BrowserModuleDefinition {
    return {
        moduleId,
        version: 2,
        surfaces: ['server-rendered', 'client-rendered'],
        kind: 'captcha',
        match: () => true,
        setup: async (ctx) => {
            // Every captcha module, passive or widget-backed, starts from the
            // same two building blocks:
            // 1. normalized options from the backend manifest
            // 2. shared host services for placeholders, errors, tokens, and
            //    refresh-token events
            const options = normalizeCaptchaModuleOptions<TProvider>(
                moduleId.split(':')[1],
                ctx.options || {},
                {
                    defaultPlaceholderSelector,
                    defaultTokenFieldNames,
                    defaultWaitForValueMs,
                },
            );
            debug.log('Setup module.', {
                moduleId: moduleId,
                placeholderSelector: options.ui.placeholderSelector,
                tokenFieldNames: options.transport.tokenFieldNames,
            });
            let placeholder: HTMLElement | undefined;
            if (
                ctx.surface === 'client-rendered' &&
                !ctx.root.querySelector(options.ui.placeholderSelector)
            ) {
                const attribute = /^\[(data-[a-z0-9-]+)\]$/.exec(
                    options.ui.placeholderSelector,
                )?.[1];
                if (!attribute)
                    throw new Error(
                        'The CAPTCHA needs a supported placeholder in the client-rendered form.',
                    );
                placeholder = document.createElement('div');
                placeholder.setAttribute(attribute, '');
                (ctx.form ?? ctx.root).append(placeholder);
            }
            // Providers own their transport names. Keep refreshed names until
            // teardown so removing or rotating a token cannot dirty the baseline.
            const excludedFieldNames = new Set(
                options.transport.tokenFieldNames,
            );
            let active = true;
            const trackTokenNames = (names: string[]) => {
                names
                    .filter(Boolean)
                    .forEach((name) => excludedFieldNames.add(name));
            };
            trackTokenNames(
                options.transport.sessionKey
                    ? [options.transport.sessionKey]
                    : [],
            );
            const services = createCaptchaHostServices(ctx, options, {
                onTokenFieldNames: trackTokenNames,
                isActive: () => active,
            });
            const stopCollecting = services.events.onForm(
                'formie:unload-warning:collect-exclusions',
                (event) => {
                    const detail = (
                        event as CustomEvent<FormUnloadWarningExclusionsEventDetail>
                    ).detail;
                    if (detail?.form === ctx.form) {
                        detail.exclude(...excludedFieldNames);
                    }
                },
            );
            const stopTrackingRefreshes = services.refresh.onTokensRefreshed(
                (entry) => {
                    if (
                        typeof entry.sessionKey === 'string' &&
                        entry.sessionKey.trim() !== ''
                    ) {
                        trackTokenNames([entry.sessionKey.trim()]);
                    }
                },
            );
            const cleanup = () => {
                active = false;
                stopCollecting();
                stopTrackingRefreshes();
                placeholder?.remove();
            };

            try {
                const instance = await setup({ ...ctx, options, services });
                if (!instance) {
                    cleanup();
                    return;
                }

                const destroy = instance.destroy;
                return {
                    ...instance,
                    destroy: async () => {
                        try {
                            await destroy();
                        } finally {
                            cleanup();
                        }
                    },
                };
            } catch (error) {
                cleanup();
                throw error;
            }
        },
    };
}

export function createPassiveCaptchaModule({
    moduleId,
    defaultPlaceholderSelector,
    defaultTokenFieldNames = [],
    defaultWaitForValueMs = DEFAULT_WAIT_FOR_VALUE_MS,
}: {
    moduleId: string;
    defaultPlaceholderSelector: string;
    defaultTokenFieldNames?: string[];
    defaultWaitForValueMs?: number;
}): BrowserModuleDefinition {
    return createCaptchaModule<Record<string, never>>({
        moduleId,
        defaultPlaceholderSelector,
        defaultTokenFieldNames,
        defaultWaitForValueMs,
        setup: async({ services, options, root }) => {
            // Passive captchas do not have a browser SDK or visible widget.
            // Their lifecycle is basically "keep the hidden transport input in
            // sync, then verify it still exists at challenge-stage submit time".
            const cleanups: Cleanup[] = [];
            let activePlaceholder = services.placeholder.getPrimary();
            let sessionKey = options.transport.sessionKey;
            let value = options.transport.value || '';

            const renderPlaceholder = (placeholder: HTMLElement | null) => {
                if (!placeholder || !sessionKey) {
                    return;
                }

                // For passive providers, rendering simply means writing the
                // hidden input into the current placeholder.
                placeholder.innerHTML = '';
                ensureCaptchaValueInput(placeholder, sessionKey, {
                    value,
                    container: placeholder,
                });
            };

            const visibility = services.placeholder.observe(
                (placeholder) => {
                    activePlaceholder = placeholder;
                    debug.log('Passive placeholder visible.', {
                        moduleId: moduleId,
                    });
                    renderPlaceholder(placeholder);
                },
                (placeholder) => {
                    if (activePlaceholder === placeholder) {
                        activePlaceholder = services.placeholder.getPrimary();
                    }

                    placeholder.innerHTML = '';
                },
            );

            cleanups.push(visibility.cleanup);
            renderPlaceholder(activePlaceholder);

            cleanups.push(services.refresh.onTokensRefreshed((entry) => {
                sessionKey = typeof entry.sessionKey === 'string' && entry.sessionKey.trim() !== ''
                    ? entry.sessionKey.trim()
                    : sessionKey;
                value = typeof entry.value === 'string' ? entry.value : '';

                const placeholder = services.placeholder.getPrimary() || activePlaceholder;
                activePlaceholder = placeholder;
                renderPlaceholder(placeholder);
            }));

            return {
                destroy: () => {
                    cleanups.forEach((cleanup) => {
                        cleanup();
                    });
                },
                beforeSubmit: async(stageCtx) => {
                    if (stageCtx.action !== 'submit') {
                        return;
                    }

                    const tokenFieldNames = sessionKey ? [sessionKey] : options.transport.tokenFieldNames;

                    if (tokenFieldNames.length === 0) {
                        return;
                    }

                    const hasToken = await waitForCaptchaValue(root, tokenFieldNames, options.transport.waitForValueMs);

                    if (!hasToken) {
                        const message = services.errors.getDefaultMessage();
                        services.errors.show(message, activePlaceholder);
                        debug.warn('Passive captcha missing token.', {
                            moduleId: moduleId,
                            tokenFieldNames,
                        });
                        stageCtx.abort(message);
                    }
                },
            };
        },
    });
}

export function createManagedCaptchaModule<
    TProvider extends Record<string, unknown>,
    TApi,
    TWidget,
>(
    adapter: ManagedCaptchaModuleAdapter<TProvider, TApi, TWidget>,
): BrowserModuleDefinition {
    return createCaptchaModule<TProvider>({
        moduleId: adapter.moduleId,
        defaultPlaceholderSelector: adapter.defaultPlaceholderSelector,
        defaultTokenFieldNames: adapter.defaultTokenFieldNames,
        setup: async (ctx) => {
            // Managed providers are the widget/SDK-backed family. The factory
            // owns only the generic plumbing:
            // - load the provider API once
            // - mount/unmount when placeholders appear/disappear
            // - call the provider's `challenge()` at submit time
            //
            // It intentionally does not know provider policy such as "execute
            // now" vs "must already be solved" - that stays in each module.
            const cleanups: Cleanup[] = [];
            const mountedWidgets = new Map<HTMLElement, TWidget>();
            const mountPromises = new Map<HTMLElement, Promise<void>>();
            const unmountPromises = new Map<HTMLElement, Promise<void>>();
            let destructionPromise: Promise<void> | null = null;
            let activePlaceholder = ctx.services.placeholder.getPrimary();
            let destroyed = false;
            let initializationError: unknown;

            let apiPromise: Promise<TApi> | null = null;

            const getApi = async (): Promise<TApi> => {
                if (!apiPromise) {
                    debug.log('Loading captcha provider API.', {
                        moduleId: adapter.moduleId,
                    });
                    apiPromise = adapter.load(ctx);
                }

                return apiPromise;
            };

            const unmountPlaceholder = (
                placeholder: HTMLElement,
            ): Promise<void> => {
                const pending = unmountPromises.get(placeholder);
                if (pending) return pending;

                const promise = (async () => {
                    // A widget can finish mounting after its placeholder hides or
                    // teardown starts. Wait so its provider resources are released too.
                    await mountPromises.get(placeholder)?.catch(() => {});
                    ctx.services.errors.clear(placeholder);
                    const hasWidget = mountedWidgets.has(placeholder);
                    const widget = mountedWidgets.get(placeholder) as TWidget;
                    mountedWidgets.delete(placeholder);

                    try {
                        if (hasWidget && adapter.unmount) {
                            const api = await getApi();
                            await adapter.unmount({
                                api,
                                widget,
                                placeholder,
                                services: ctx.services,
                                options: ctx.options,
                                provider: ctx.options.provider,
                            });
                        }
                    } finally {
                        // Provider failures must not leave transport values or DOM
                        // behind, or prevent another widget from being cleaned up.
                        placeholder.innerHTML = '';
                        ctx.services.tokens.clear();
                        if (activePlaceholder === placeholder) {
                            activePlaceholder =
                                ctx.services.placeholder.getPrimary();
                        }
                    }
                })().finally(() => unmountPromises.delete(placeholder));

                unmountPromises.set(placeholder, promise);
                return promise;
            };

            const mountPlaceholder = async (placeholder: HTMLElement) => {
                const unmounting = unmountPromises.get(placeholder);
                if (unmounting) await unmounting;
                if (destroyed || mountedWidgets.has(placeholder)) return;
                if (mountPromises.has(placeholder))
                    return mountPromises.get(placeholder);

                // Guard against repeated visibility events or rapid DOM churn
                // by tracking an in-flight mount promise per placeholder.
                const promise = (async () => {
                    const api = await getApi();

                    if (destroyed || mountedWidgets.has(placeholder)) {
                        return;
                    }

                    const container =
                        ctx.services.placeholder.createContainer(placeholder);
                    // Providers always render into a fresh container so the
                    // shared services can safely tear down and rebuild
                    // placeholder DOM.
                    const widget = await adapter.mount({
                        api,
                        placeholder,
                        container,
                        services: ctx.services,
                        options: ctx.options,
                        provider: ctx.options.provider,
                    });

                    mountedWidgets.set(placeholder, widget);
                    activePlaceholder = placeholder;
                    debug.log('Mounted captcha placeholder widget.', {
                        moduleId: adapter.moduleId,
                    });
                })().finally(() => {
                    mountPromises.delete(placeholder);
                });

                mountPromises.set(placeholder, promise);

                await promise;
            };

            const visibility = ctx.services.placeholder.observe(
                (placeholder) => {
                    activePlaceholder = placeholder;
                    void mountPlaceholder(placeholder).catch((error) => {
                        initializationError = error;
                    });
                },
                (placeholder) => {
                    if (!destroyed) {
                        void unmountPlaceholder(placeholder).catch((error) => {
                            initializationError = error;
                        });
                    }
                },
            );

            cleanups.push(visibility.cleanup);

            const rerenderVisiblePlaceholders = async (
                reason: 'submit-result' | 'reset-state',
            ) => {
                if (destroyed) return;
                // Some providers can cheaply reset in-place, while others are
                // safer to unmount and mount again. The provider advertises
                // that through the optional `reset()` hook.
                const visiblePlaceholders = visibility.getVisible();
                const placeholdersToMount = visiblePlaceholders;

                if (adapter.reset) {
                    const api = await getApi();

                    for (const placeholder of placeholdersToMount) {
                        const widget = mountedWidgets.get(placeholder) as TWidget;

                        if (!mountedWidgets.has(placeholder)) {
                            await mountPlaceholder(placeholder);
                            continue;
                        }

                        await adapter.reset({
                            api,
                            widget,
                            placeholder,
                            services: ctx.services,
                            options: ctx.options,
                            provider: ctx.options.provider,
                            reason,
                        });

                        ctx.services.tokens.clear();
                        ctx.services.errors.clear(placeholder);
                    }

                    visibility.reconcile();
                    return;
                }

                for (const placeholder of Array.from(mountedWidgets.keys())) {
                    await unmountPlaceholder(placeholder);
                }

                for (const placeholder of placeholdersToMount) {
                    await mountPlaceholder(placeholder);
                }

                visibility.reconcile();
            };

            cleanups.push(
                ctx.services.events.onRoot('formie:submit:result', (event) => {
                    const detail =
                        event instanceof CustomEvent
                            ? (event.detail as FormSubmitResult)
                            : null;

                    if (detail?.stage === 'validate') {
                        return;
                    }

                    if (detail?.ok === false && detail?.stage === 'challenge') {
                        return;
                    }

                    if (detail?.ok === true) {
                        return;
                    }

                    void rerenderVisiblePlaceholders('submit-result');
                }),
            );

            if (ctx.form) {
                cleanups.push(
                    ctx.services.events.onForm(
                        getFormStateEventName('reset'),
                        () => {
                            activePlaceholder =
                                ctx.services.placeholder.getPrimary() ||
                                activePlaceholder;

                            window.setTimeout(() => {
                                void rerenderVisiblePlaceholders('reset-state');
                            }, 0);
                        },
                    ),
                );
            }

            const destroy = (): Promise<void> => {
                if (destructionPromise) return destructionPromise;
                destroyed = true;
                destructionPromise = (async () => {
                    cleanups.forEach((cleanup) => cleanup());
                    await Promise.allSettled([...mountPromises.values()]);
                    const results = await Promise.allSettled(
                        new Set([
                            ...unmountPromises.values(),
                            ...Array.from(
                                mountedWidgets.keys(),
                                unmountPlaceholder,
                            ),
                        ]),
                    );
                    const errors = results.flatMap((result) =>
                        result.status === 'rejected' ? [result.reason] : [],
                    );
                    if (errors.length)
                        throw new AggregateError(
                            errors,
                            'CAPTCHA widgets could not clean up.',
                        );
                })();
                return destructionPromise;
            };

            visibility.reconcileImmediate();
            try {
                await Promise.all([...mountPromises.values()]);
                if (initializationError) throw initializationError;
            } catch (error) {
                // Setup can fail after another placeholder has already mounted.
                // Preserve the setup error while still releasing every widget.
                await destroy().catch((cleanupError) => {
                    debug.warn('Captcha cleanup after failed setup.', {
                        error: cleanupError,
                    });
                });
                throw error;
            }

            return {
                assertReady: () => {
                    if (initializationError) throw initializationError;
                },
                destroy,
                beforeSubmit: async (stageCtx) => {
                    if (stageCtx.action !== 'submit') {
                        return;
                    }

                    // Reconcile immediately so captcha placeholders on the newly
                    // visible page are mounted before challenge-stage policy runs.
                    visibility.reconcileImmediate();

                    // By the time provider `challenge()` runs, the widget is
                    // guaranteed to be mounted and the active placeholder is
                    // resolved. That lets provider code focus on challenge
                    // policy rather than generic lifecycle setup.
                    const visiblePlaceholders = visibility.getVisible();

                    // Captcha fields on hidden pages should not run in challenge stage.
                    if (visiblePlaceholders.length === 0) {
                        return;
                    }

                    let placeholder =
                        visiblePlaceholders.find(
                            (candidate) => candidate === activePlaceholder,
                        ) || visiblePlaceholders[0];

                    await mountPlaceholder(placeholder);
                    placeholder = activePlaceholder || placeholder;

                    ctx.services.errors.clear(placeholder);

                    const widget = mountedWidgets.get(placeholder) as TWidget;

                    if (!mountedWidgets.has(placeholder)) {
                        const message = ctx.services.errors.getDefaultMessage();
                        ctx.services.errors.show(message, placeholder);
                        debug.warn(
                            'Captcha widget unavailable at challenge stage.',
                            {
                                moduleId: adapter.moduleId,
                            },
                        );
                        stageCtx.abort(message);
                        return;
                    }

                    const api = await getApi();

                    await adapter.challenge({
                        api,
                        widget,
                        placeholder,
                        services: ctx.services,
                        options: ctx.options,
                        provider: ctx.options.provider,
                        stageCtx,
                    });
                },
            };
        },
    });
}
