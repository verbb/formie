import { loadExternalScript } from '#utils/scripts';
import { waitFor } from '#utils/async';
import { CAPTCHA_PROVIDER_LOAD_TIMEOUT_MS } from '#modules/captchas/constants';
import { getScriptAttributes } from '#modules/captchas/utils';

// reCAPTCHA is the one place where a provider-local "shared" file still earns
// its keep: v2 checkbox, v2 invisible, v3, and Enterprise all rely on the same
// Google global and script-loading rules, but use different module lifecycles.
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

export async function loadRecaptchaGlobal(
    options: RecaptchaProviderOptions,
    enterprise = false,
    renderValue?: string,
): Promise<RecaptchaGlobal> {
    // Google reCAPTCHA has two loading styles:
    // - `render=explicit` for widget-based modes where the module later calls
    //   `grecaptcha.render(container, ...)`
    // - `render=<siteKey>` for execute-by-site-key modes such as v3 and some
    //   Enterprise flows
    //
    // That distinction matters across multiple reCAPTCHA modules, which is why
    // this helper is worth sharing even though singular providers were inlined.
    const language = typeof options.language === 'string' && options.language.trim() !== '' ? options.language.trim() : 'en';
    const { async, defer } = getScriptAttributes(options.loadingMethod);
    const host = enterprise ? 'https://www.google.com/recaptcha/enterprise.js' : 'https://www.recaptcha.net/recaptcha/api.js';
    const render = typeof renderValue === 'string' && renderValue.trim() !== '' ? renderValue.trim() : 'explicit';
    const src = new URL(host);

    src.searchParams.set('render', render);
    src.searchParams.set('hl', language);

    // Badge placement is configured on the script URL for the execute-by-site-
    // key flows because there is no later widget render call to carry it.
    if (render !== 'explicit' && typeof options.badge === 'string' && options.badge.trim() !== '') {
        src.searchParams.set('badge', options.badge.trim());
    }

    // Both SDKs share `grecaptcha`, but loading one does not load the other.
    // A ready() stub can also exist before either SDK has attached its methods.
    const getLoadedApi = (): RecaptchaGlobal | null => {
        const api = (window as Window & { grecaptcha?: RecaptchaGlobal }).grecaptcha;
        const target = enterprise ? api?.enterprise : api;

        return api && target && typeof target.ready === 'function' && typeof target.execute === 'function'
            && typeof target.render === 'function' ? api : null;
    };
    const existing = getLoadedApi();

    if (existing) {
        return existing;
    }

    await loadExternalScript({
        id: enterprise ? 'FORMIE_RECAPTCHA_ENTERPRISE_SCRIPT' : 'FORMIE_RECAPTCHA_SCRIPT',
        src: src.toString(),
        async,
        defer,
    });

    return waitFor(getLoadedApi, { timeoutMs: CAPTCHA_PROVIDER_LOAD_TIMEOUT_MS });
}

export function whenRecaptchaReady(api: RecaptchaGlobal, callback: () => void | Promise<void>, enterprise = false): Promise<void> {
    const readyApi = enterprise ? api.enterprise : api;

    return new Promise((resolve, reject) => {
        try {
            if (!readyApi) throw new Error('The reCAPTCHA API is unavailable.');
            readyApi.ready(() => {
                Promise.resolve(callback()).then(resolve).catch(reject);
            });
        } catch (error) {
            reject(error);
        }
    });
}
