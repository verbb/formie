import type {
    FormieModuleDefinition,
    FormieModuleInstance,
    ModuleSetupContext,
} from '#contracts/modules';
import { DEFAULT_REQUIRED_INPUT_SUFFIXES } from '#modules/payments/constants';
import {
    createPaymentHostServices,
    type NormalizedPaymentModuleOptions,
    type PaymentHostServices,
    normalizePaymentModuleOptions,
} from '#modules/payments/host';
import { waitForRequiredPaymentInputs } from '#modules/payments/utils';
import { createDebug } from '#utils/debug';

type Cleanup = () => void;
type PaymentModuleRegistry = Record<string, { destroy: () => Promise<void> }>;
const debug = createDebug('payments');

function isTargetVisible(element: Element): boolean {
    const node = element as HTMLElement;

    return !node.closest('[data-formie-page-hidden]') && !node.closest('[hidden]');
}

export type PaymentModuleSetupContext<TProvider extends Record<string, unknown>> = Omit<ModuleSetupContext, 'options'> & {
    options: NormalizedPaymentModuleOptions<TProvider>;
    services: PaymentHostServices;
};

type PaymentSetupResult = {
    destroy?: () => void | Promise<void>;
    onBeforeStage?: (stageCtx: import('#contracts/modules').SubmitHookContext) => void | Promise<void>;
};

export type PaymentAfterSubmitResult = {
    /** Tear down the current widget and mount again (for example after a failed payment). */
    remount?: boolean;
};

export type ManagedPaymentModuleAdapter<
    TProvider extends Record<string, unknown>,
    TApi,
    TWidget,
> = {
    id: string;
    defaultRequiredInputSuffixes?: string[];
    load: (ctx: PaymentModuleSetupContext<TProvider>) => Promise<TApi>;
    /** Redirect-only providers (Mollie, GoCardless): attach listeners, return destroy. No mount needed. */
    setup?: (ctx: PaymentModuleSetupContext<TProvider> & { root: HTMLElement }) => Promise<PaymentSetupResult>;
    mount?: (args: {
        api: TApi;
        field: Element;
        services: PaymentHostServices;
        options: NormalizedPaymentModuleOptions<TProvider>;
        provider: TProvider;
    }) => Promise<TWidget> | TWidget;
    unmount?: (args: {
        api: TApi;
        widget: TWidget;
        field: Element;
        services: PaymentHostServices;
        options: NormalizedPaymentModuleOptions<TProvider>;
        provider: TProvider;
    }) => Promise<void> | void;
    onBeforeAuthorize?: (args: {
        api: TApi;
        widget: TWidget | null;
        field: Element;
        services: PaymentHostServices;
        options: NormalizedPaymentModuleOptions<TProvider>;
        provider: TProvider;
        stageCtx: import('#contracts/modules').SubmitHookContext;
    }) => Promise<boolean> | boolean;
    /** Called after dispatch (on any result) to reset hidden inputs, clear UI, etc. */
    onAfterSubmit?: (args: {
        field: Element;
        services: PaymentHostServices;
        options: NormalizedPaymentModuleOptions<TProvider>;
        provider: TProvider;
        result?: import('#contracts/schema').FormSubmitResult;
    }) => void | Promise<void> | PaymentAfterSubmitResult | Promise<PaymentAfterSubmitResult>;
};

export function createManagedPaymentModule<
    TProvider extends Record<string, unknown>,
    TApi,
    TWidget,
>(adapter: ManagedPaymentModuleAdapter<TProvider, TApi, TWidget>): FormieModuleDefinition {
    const defaultSuffixes = adapter.defaultRequiredInputSuffixes
        ?? DEFAULT_REQUIRED_INPUT_SUFFIXES[adapter.id]
        ?? [];

    return {
        id: adapter.id,
        kind: 'payment',
        match: (ctx) => {
            return !!(ctx.target.querySelector('[data-formie-field-type="payment"]')
                || ctx.target.closest('[data-formie-field-type="payment"]')
                || (ctx.target as HTMLElement).getAttribute?.('data-formie-field-type') === 'payment');
        },
        setup: async(ctx) => {
            const fieldWithRegistry = ctx.target as Element & { __formiePaymentModuleRegistry?: PaymentModuleRegistry };
            const registry = fieldWithRegistry.__formiePaymentModuleRegistry || {};
            fieldWithRegistry.__formiePaymentModuleRegistry = registry;

            // Defensive singleton guard per field/provider: if this module somehow
            // gets initialized again without teardown, clean the previous instance first.
            const previous = registry[adapter.id];
            if (previous?.destroy) {
                debug.warn('Found stale payment module instance; destroying previous.', {
                    moduleId: adapter.id,
                });
                try {
                    await previous.destroy();
                } catch {
                    // Ignore teardown errors from stale instances.
                }
            }

            const options = normalizePaymentModuleOptions<TProvider>(
                adapter.id,
                ctx.options || {},
                {
                    defaultRequiredInputSuffixes: defaultSuffixes,
                },
            );
            const services = createPaymentHostServices(ctx, options);

            const setupCtx: PaymentModuleSetupContext<TProvider> = {
                ...ctx,
                options,
                services,
            };

            const cleanups: Cleanup[] = [];
            let apiPromise: Promise<TApi> | null = null;
            let widget: TWidget | null = null;
            let customSetupResult: PaymentSetupResult | null = null;
            let authorizeInFlight: Promise<boolean> | null = null;

            const getApi = async(): Promise<TApi> => {
                if (!apiPromise) {
                    debug.log('Loading payment provider API.', { moduleId: adapter.id });
                    apiPromise = adapter.load(setupCtx);
                }

                return apiPromise;
            };

            const ensureMounted = async() => {
                if (!adapter.mount || widget || !isTargetVisible(ctx.target)) {
                    return;
                }

                const api = await getApi();

                try {
                    widget = await adapter.mount({
                        api,
                        field: ctx.target,
                        services,
                        options,
                        provider: options.provider,
                    });
                    debug.log('Payment widget mounted.', {
                        moduleId: adapter.id,
                        handle: options.handle,
                    });
                } catch {
                    // Mount failed; widget stays null
                    debug.warn('Payment widget mount failed.', {
                        moduleId: adapter.id,
                        handle: options.handle,
                    });
                }
            };

            // Clear stale payment feedback at the start of every submit attempt.
            cleanups.push(ctx.on('formie:submit:before', () => {
                services.removeError();
                services.removeSuccess();
            }));

            if (adapter.setup) {
                const root = (ctx.root || ctx.form || ctx.target) as HTMLElement;
                customSetupResult = await adapter.setup({ ...setupCtx, root });
                if (customSetupResult.destroy) {
                    cleanups.push(customSetupResult.destroy);
                }
            }

            if (adapter.mount && isTargetVisible(ctx.target)) {
                await ensureMounted();
            }

            const visibilityEvents = ['formie:page:navigate:after', 'formie:submit:result'];
            visibilityEvents.forEach((eventName) => {
                const handleVisibility = () => {
                    void ensureMounted();
                };

                ctx.root.addEventListener(eventName, handleVisibility as EventListener);
                cleanups.push(() => {
                    ctx.root.removeEventListener(eventName, handleVisibility as EventListener);
                });
            });

            const destroy = async() => {
                debug.log('Destroying payment module.', {
                    moduleId: adapter.id,
                    handle: options.handle,
                });
                cleanups.forEach((c) => c());

                if (widget && adapter.unmount) {
                    const api = await getApi();

                    await adapter.unmount({
                        api,
                        widget,
                        field: ctx.target,
                        services,
                        options,
                        provider: options.provider,
                    });
                    debug.log('Payment widget unmounted.', {
                        moduleId: adapter.id,
                        handle: options.handle,
                    });
                }

                if (registry[adapter.id]?.destroy === destroy) {
                    delete registry[adapter.id];
                }
                debug.log('Payment module destroy complete.', {
                    moduleId: adapter.id,
                    handle: options.handle,
                });
            };

            registry[adapter.id] = { destroy };

            return {
                destroy,
                onBeforeStage: async(stageCtx) => {
                    if (customSetupResult?.onBeforeStage) {
                        await customSetupResult.onBeforeStage(stageCtx);
                        return;
                    }

                    if (stageCtx.stage !== 'authorize' || stageCtx.action !== 'submit') {
                        return;
                    }

                    // Multi-page forms can mount payment modules for fields on
                    // hidden pages. Only enforce authorize checks for payment
                    // fields on the active/visible page.
                    const fieldElement = ctx.target as HTMLElement;
                    const page = fieldElement.closest('[data-formie-page]') as HTMLElement | null;
                    if (page?.hasAttribute('data-formie-page-hidden')) {
                        return;
                    }

                    await ensureMounted();
                    const api = await getApi();

                    if (adapter.onBeforeAuthorize) {
                        if (!authorizeInFlight) {
                            authorizeInFlight = (async() => {
                                return adapter.onBeforeAuthorize!({
                                    api,
                                    widget,
                                    field: ctx.target,
                                    services,
                                    options,
                                    provider: options.provider,
                                    stageCtx,
                                });
                            })().finally(() => {
                                authorizeInFlight = null;
                            });
                        }

                        const ok = await authorizeInFlight;
                        debug.log('onBeforeAuthorize resolved.', {
                            moduleId: adapter.id,
                            handle: options.handle,
                            ok,
                        });

                        if (!ok) {
                            stageCtx.abort(options.transport.errorMessage);

                            return;
                        }

                        return;
                    }

                    if (options.transport.requiredInputSuffixes.length === 0) {
                        return;
                    }

                    const tokenRoot = ctx.form || ctx.root;
                    const result = await waitForRequiredPaymentInputs(
                        tokenRoot,
                        options.transport.requiredInputSuffixes,
                        options.transport.waitForValueMs,
                    );

                    if (!result.ok) {
                        debug.warn('Required payment input(s) missing.', {
                            moduleId: adapter.id,
                            handle: options.handle,
                            missingSuffix: result.missingSuffix,
                        });
                        stageCtx.abort(options.transport.errorMessage);
                    }
                },
                onAfterStage: async(stagePayload, result) => {
                    if (stagePayload.stage !== 'dispatch' || !adapter.onAfterSubmit) {
                        return;
                    }

                    const afterSubmitResult = await adapter.onAfterSubmit({
                        field: ctx.target,
                        services,
                        options,
                        provider: options.provider,
                        result,
                    });

                    if (!afterSubmitResult?.remount || !adapter.mount) {
                        return;
                    }

                    if (widget && adapter.unmount) {
                        const api = await getApi();

                        await adapter.unmount({
                            api,
                            widget,
                            field: ctx.target,
                            services,
                            options,
                            provider: options.provider,
                        });
                    }

                    widget = null;
                    await ensureMounted();
                },
            };
        },
    };
}
