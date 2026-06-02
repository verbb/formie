import type {
    FormieModuleDefinition,
    FormieModuleInstance,
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
    id: string;
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
>(adapter: ManagedAddressModuleAdapter<TProvider, TApi, TWidget>): FormieModuleDefinition {
    return {
        id: adapter.id,
        kind: 'address',
        match: (ctx) => {
            const input = ctx.target.querySelector('[data-formie-address-autocomplete-input]');

            return !!input;
        },
        setup: async (ctx) => {
            const options = normalizeAddressModuleOptions<TProvider>(adapter.id, ctx.options || {});
            const services = createAddressHostServices(ctx);
            debug.log('Setup module.', {
                moduleId: adapter.id,
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
                    `[formie] Address module "${adapter.id}" skipped: no autocomplete input found in target. ` +
                    'Ensure the Address field has the Auto-Complete subfield enabled.',
                );
                debug.warn('Autocomplete input missing; skipping module.', {
                    moduleId: adapter.id,
                });
                return {
                    destroy: () => { },
                };
            }

            const getApi = async(): Promise<TApi> => {
                if (!apiPromise) {
                    debug.log('Loading provider API.', {
                        moduleId: adapter.id,
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
                    moduleId: adapter.id,
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
                        moduleId: adapter.id,
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
                            moduleId: adapter.id,
                        });
                    }
                },
            };
        },
    };
}
