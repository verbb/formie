import type {
    BrowserModuleDefinition,
    BrowserModuleInstance,
    ModuleSetupContext,
} from '#contracts/modules';
import {
    createAddressHostServices,
    type AddressHostServices,
    type NormalizedAddressModuleOptions,
    normalizeAddressModuleOptions,
} from '#modules/address/host';
import { createDebug } from '#utils/debug';

type Cleanup = () => void;
const debug = createDebug('address');

function isTargetVisible(element: Element): boolean {
    const node = element as HTMLElement;

    return !node.closest('[data-formie-page-hidden]') && !node.closest('[hidden]');
}

export type AddressModuleSetupContext<TProvider extends Record<string, unknown>> = Omit<ModuleSetupContext, 'options'> & {
    options: NormalizedAddressModuleOptions<TProvider>;
    services: AddressHostServices;
};

export type ManagedAddressModuleAdapter<
    TProvider extends Record<string, unknown>,
    TApi,
    TWidget,
> = {
    moduleId: string;
    load: (ctx: AddressModuleSetupContext<TProvider>) => Promise<TApi>;
    mount: (args: {
        api: TApi;
        field: Element;
        services: AddressHostServices;
        options: NormalizedAddressModuleOptions<TProvider>;
        provider: TProvider;
    }) => Promise<TWidget | null> | TWidget | null;
    unmount?: (args: {
        api: TApi;
        widget: TWidget;
        field: Element;
        services: AddressHostServices;
        options: NormalizedAddressModuleOptions<TProvider>;
        provider: TProvider;
    }) => Promise<void> | void;
    onCurrentLocation?: (position: GeolocationPosition, args: {
        api: TApi;
        widget: TWidget;
        field: Element;
        services: AddressHostServices;
        options: NormalizedAddressModuleOptions<TProvider>;
        provider: TProvider;
    }) => void | Promise<void>;
};

export function createManagedAddressModule<
    TProvider extends Record<string, unknown>,
    TApi,
    TWidget,
>(adapter: ManagedAddressModuleAdapter<TProvider, TApi, TWidget>): BrowserModuleDefinition {
    return {
        moduleId: adapter.moduleId,
        version: 2,
        surfaces: ['server-rendered', 'client-rendered', 'cp-edit'],
        kind: 'address',
        match: (ctx) => {
            const input = ctx.target.querySelector('[data-formie-address-autocomplete-input]');

            return !!input;
        },
        setup: async (ctx) => {
            const options = normalizeAddressModuleOptions<TProvider>(adapter.moduleId.split(':')[1], ctx.options || {});
            const services = createAddressHostServices(ctx);
            debug.log('Setup module.', {
                moduleId: adapter.moduleId,
            });

            const setupCtx: AddressModuleSetupContext<TProvider> = {
                ...ctx,
                options,
                services,
            };

            const cleanups: Cleanup[] = [];
            let apiPromise: Promise<TApi> | null = null;
            let widget: TWidget | null = null;
            const input = services.input.getAutocomplete();

            if (!input) {
                console.warn(
                    `[formie] Address module "${adapter.moduleId}" skipped: no autocomplete input found in target. ` +
                    'Ensure the Address field has the Auto-Complete subfield enabled.',
                );
                debug.warn('Autocomplete input missing; skipping module.', {
                    moduleId: adapter.moduleId,
                });
                return {
                    destroy: () => { },
                };
            }

            const getApi = async(): Promise<TApi> => {
                if (!apiPromise) {
                    debug.log('Loading provider API.', {
                        moduleId: adapter.moduleId,
                    });
                    apiPromise = adapter.load(setupCtx);
                }

                return apiPromise;
            };

            const ensureMounted = async() => {
                if (widget || !isTargetVisible(ctx.target)) {
                    return;
                }

                const api = await getApi();
                widget = await adapter.mount({
                    api,
                    field: ctx.target,
                    services,
                    options,
                    provider: options.provider,
                });
                debug.log('Widget mounted.', {
                    moduleId: adapter.moduleId,
                });
            };

            if (isTargetVisible(ctx.target)) {
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

            const locationCleanup = services.location.onUseLocation((position) => {
                if (!adapter.onCurrentLocation) {
                    return;
                }

                void (async() => {
                    await ensureMounted();

                    if (!widget) {
                        return;
                    }

                    const api = await getApi();

                    await adapter.onCurrentLocation?.(position, {
                        api,
                        widget,
                        field: ctx.target,
                        services,
                        options,
                        provider: options.provider,
                    });
                })();
            });

            if (locationCleanup) {
                cleanups.push(locationCleanup);
            }

            return {
                destroy: async () => {
                    debug.log('Destroying module.', {
                        moduleId: adapter.moduleId,
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
                        debug.log('Widget unmounted.', {
                            moduleId: adapter.moduleId,
                        });
                    }
                },
            };
        },
    };
}
