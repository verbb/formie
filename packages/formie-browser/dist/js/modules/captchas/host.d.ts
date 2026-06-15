import type { ModuleSetupContext } from '#contracts/modules';
type Cleanup = () => void;
type ObserveVisiblePlaceholdersResult = {
    cleanup: Cleanup;
    reconcile: () => void;
    reconcileImmediate: () => void;
    getVisible: () => HTMLElement[];
};
export type CaptchaRefreshEntry = {
    formId?: string;
    sessionKey?: string | null;
    value?: string | null;
};
export type CaptchaModuleOptions<TProvider extends Record<string, unknown> = Record<string, unknown>> = {
    handle?: string;
    placeholderSelector?: string;
    errorMessage?: string;
    sessionKey?: string | null;
    value?: string | null;
} & TProvider;
export type NormalizedCaptchaModuleOptions<TProvider extends Record<string, unknown>> = {
    handle: string;
    ui: {
        placeholderSelector: string;
        errorMessage: string;
    };
    transport: {
        tokenFieldNames: string[];
        waitForValueMs: number;
        sessionKey: string | null;
        value: string | null;
    };
    provider: TProvider;
};
type CaptchaModuleDefaults = {
    defaultPlaceholderSelector: string;
    defaultTokenFieldNames?: string[];
    defaultWaitForValueMs?: number;
};
export type CaptchaHostServices = {
    form: HTMLFormElement | null;
    root: Element;
    placeholder: {
        query: () => HTMLElement[];
        getPrimary: () => HTMLElement | null;
        observe: (onShow: (placeholder: HTMLElement) => void, onHide: (placeholder: HTMLElement) => void) => ObserveVisiblePlaceholdersResult;
        createContainer: (placeholder: HTMLElement) => HTMLElement;
        clear: (placeholder: HTMLElement | null) => void;
    };
    errors: {
        getDefaultMessage: () => string;
        show: (message?: string, placeholder?: HTMLElement | null) => void;
        clear: (placeholder?: HTMLElement | null) => void;
    };
    tokens: {
        names: string[];
        has: (names?: string[], root?: ParentNode) => boolean;
        read: (name?: string, root?: ParentNode) => string;
        write: (value: string, { names, root, container, }?: {
            names?: string[];
            root?: ParentNode;
            container?: HTMLElement | null;
        }) => void;
        clear: (names?: string[], root?: ParentNode) => void;
        wait: (timeoutMs?: number, names?: string[], root?: ParentNode) => Promise<boolean>;
    };
    refresh: {
        providerHandle: string;
        onTokensRefreshed: (callback: (entry: CaptchaRefreshEntry) => void) => Cleanup;
    };
    events: {
        onRoot: (eventName: string, callback: EventListener) => Cleanup;
        onForm: (eventName: string, callback: EventListener) => Cleanup;
    };
};
export declare function normalizeCaptchaModuleOptions<TProvider extends Record<string, unknown>>(id: string, rawOptions: Record<string, unknown> | undefined, { defaultPlaceholderSelector, defaultTokenFieldNames, defaultWaitForValueMs, }: CaptchaModuleDefaults): NormalizedCaptchaModuleOptions<TProvider>;
export declare function createCaptchaHostServices<TProvider extends Record<string, unknown>>(ctx: ModuleSetupContext, options: NormalizedCaptchaModuleOptions<TProvider>): CaptchaHostServices;
export {};
//# sourceMappingURL=host.d.ts.map