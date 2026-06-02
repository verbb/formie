import type { LegacyCompatibilityOptions } from '#compatibility/event-map';
import type { FormAction, FormMode, FormTransport } from '#contracts/common';
import type { FormieModuleDefinition, ModuleRegistrationOptions } from '#contracts/modules';
import type { FormEndpointPayload, FormSubmitResult } from '#contracts/schema';
export type FormMountOptions = {
    mode: FormMode;
    transport?: FormTransport;
    formHandle?: string;
    endpoint?: string;
    payload?: FormEndpointPayload;
    staticCache?: boolean;
    refreshTokens?: boolean;
    locale?: string;
    siteId?: number;
    autoVisible?: boolean;
    compatibility?: LegacyCompatibilityOptions;
    theme?: 'formie' | 'none';
    themeConfig?: Record<string, unknown>;
};
export type FormEventUnsubscribe = () => void;
export type FormieFormInstance = {
    id: string;
    root: Element;
    submit: (action?: FormAction) => Promise<FormSubmitResult>;
    destroy: () => Promise<void>;
    on: (eventName: string, callback: (payload: unknown) => void) => FormEventUnsubscribe;
};
export type FormieClient = {
    mount: (target: Element, options: FormMountOptions) => Promise<FormieFormInstance>;
    unmount: (target: Element) => Promise<void>;
    update: (target: Element, options: Partial<FormMountOptions>) => Promise<FormieFormInstance>;
    getInstance: (target: Element) => FormieFormInstance | null;
    refreshForCache: (targetOrId: Element | string) => Promise<void>;
    registerModule: (moduleDefinition: FormieModuleDefinition, options?: ModuleRegistrationOptions) => boolean;
    unregisterModule: (moduleId: string) => void;
    getRegisteredModules: () => FormieModuleDefinition[];
    scan: (root?: ParentNode) => Promise<FormieFormInstance[]>;
    observe: (root?: ParentNode) => () => void;
};
//# sourceMappingURL=client.d.ts.map