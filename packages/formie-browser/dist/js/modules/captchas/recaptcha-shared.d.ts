export type RecaptchaGlobal = {
    ready: (callback: () => void) => void;
    render: (container: HTMLElement, options: Record<string, unknown>) => number | string;
    execute: (widgetIdOrSiteKey?: number | string, options?: Record<string, unknown>) => Promise<string> | void;
    getResponse?: (widgetId?: number | string) => string;
    reset: (widgetId?: number | string) => void;
    enterprise?: {
        ready: (callback: () => void) => void;
        render: (container: HTMLElement, options: Record<string, unknown>) => number | string;
        execute: (widgetIdOrSiteKey?: number | string, options?: Record<string, unknown>) => Promise<string> | void;
        getResponse?: (widgetId?: number | string) => string;
        reset: (widgetId?: number | string) => void;
    };
};
export type RecaptchaProviderOptions = {
    siteKey?: string | null;
    badge?: string;
    theme?: string;
    size?: string;
    language?: string;
    loadingMethod?: string;
    action?: string;
    enterpriseType?: string | null;
};
export declare function loadRecaptchaGlobal(options: RecaptchaProviderOptions, enterprise?: boolean, renderValue?: string): Promise<RecaptchaGlobal>;
export declare function whenRecaptchaReady(api: RecaptchaGlobal, callback: () => void | Promise<void>): Promise<void>;
//# sourceMappingURL=recaptcha-shared.d.ts.map