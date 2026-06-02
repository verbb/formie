import {
    defineCaptchaModule,
} from '#modules/captchas/api';
import {
    CAPTCHA_PROVIDER_LOAD_TIMEOUT_MS,
} from '#modules/captchas/constants';
import { ensureGlobal, loadExternalScript } from '#utils/scripts';
import { getScriptAttributes } from '#modules/captchas/utils';

// Minimal subset of the hCaptcha browser API that this provider needs.
// Keeping this local makes it obvious which parts of the SDK contract this
// module relies on, without teaching shared captcha services about hCaptcha.
type HcaptchaGlobal = {
    render: (container: HTMLElement, options: Record<string, unknown>) => string | number;
    execute: (widgetId: string | number) => void;
    reset: (widgetId: string | number) => void;
};

// These are hCaptcha-specific settings only. Anything generic such as token
// field names or error messaging now comes from shared captcha services config.
type HcaptchaProviderOptions = {
    siteKey?: string | null;
    theme?: string;
    size?: string;
    language?: string;
    loadingMethod?: string;
};

async function loadHcaptchaGlobal(options: HcaptchaProviderOptions): Promise<HcaptchaGlobal> {
    // hCaptcha exposes a single global (`window.hcaptcha`) after its script
    // has loaded and fired the configured onload callback. This helper stays
    // local to the provider because the exact script URL, callback wiring and
    // readiness contract are all hCaptcha-specific.
    const language = typeof options.language === 'string' && options.language.trim() !== '' ? options.language.trim() : 'en';
    const { async, defer } = getScriptAttributes(options.loadingMethod);
    const callbackName = 'FORMIE_HCAPTCHA_ONLOAD';
    const globalWindow = window as unknown as Record<string, unknown>;
    const existing = globalWindow.hcaptcha;

    // Re-use the already-loaded SDK when multiple forms/providers exist on
    // the page. We want one shared browser global, not duplicate script loads.
    if (existing) {
        return existing as HcaptchaGlobal;
    }

    // The script calls this named function when its internals are ready. We
    // bridge that callback to a promise so the rest of the module can stay
    // async/await based and deterministic.
    const callbackPromise = new Promise<void>((resolve) => {
        globalWindow[callbackName] = () => {
            delete globalWindow[callbackName];
            resolve();
        };
    });

    await loadExternalScript({
        id: 'FORMIE_HCAPTCHA_SCRIPT',
        src: `https://js.hcaptcha.com/1/api.js?recaptchacompat=off&render=explicit&onload=${encodeURIComponent(callbackName)}&hl=${encodeURIComponent(language)}`,
        async,
        defer,
    });

    await callbackPromise;

    return ensureGlobal<HcaptchaGlobal>('hcaptcha', CAPTCHA_PROVIDER_LOAD_TIMEOUT_MS);
}

export const hcaptchaModule = defineCaptchaModule<HcaptchaProviderOptions, HcaptchaGlobal, number | string>({
    id: 'hcaptcha',
    defaultPlaceholderSelector: '[data-hcaptcha-placeholder]',
    defaultTokenFieldNames: ['h-captcha-response'],
    load: ({ options }) => {
        // `load()` is deliberately separate from `mount()`: the factory can
        // cache and share this provider API promise across placeholder mounts,
        // resets and page transitions without reloading the SDK each time.
        return loadHcaptchaGlobal(options.provider);
    },
    mount: ({ api, container, provider, services }) => {
        // Mount only renders/configures the widget. It does not decide whether
        // a submit should be blocked; that responsibility lives in `screen()`.
        return api.render(container, {
            sitekey: provider.siteKey || '',
            theme: provider.theme || 'light',
            size: provider.size || 'normal',
            callback: (token?: string) => {
                // hCaptcha gives us the token in the verify callback. We push
                // that into the shared transport inputs so the backend sees the
                // same payload shape regardless of provider implementation.
                if (typeof token === 'string' && token.trim() !== '') {
                    services.tokens.write(token.trim());
                }

                // If the user previously tried to submit without a token, the
                // shared services may have rendered an inline error. Clear it as
                // soon as the challenge completes successfully.
                services.errors.clear();
            },
            'expired-callback': () => {
                // Expiry means the old token is no longer valid, so keep the
                // DOM transport layer honest by clearing it immediately.
                services.tokens.clear();
                services.errors.clear();
            },
            'chalexpired-callback': () => {
                // hCaptcha has a second expiry-style callback for challenge
                // expiration; treat it the same as normal token expiration.
                services.tokens.clear();
                services.errors.clear();
            },
            'error-callback': () => {
                // SDK/network errors should not leave a stale token behind.
                services.tokens.clear();
            },
        });
    },
    screen: ({ api, widget, placeholder, services, stageCtx }) => {
        // `screen()` runs at submit time. By the time we get here the widget is
        // already mounted, so this method only answers "do we have a valid
        // token yet, and if not, can we get one before the screen stage ends?"
        if (services.tokens.has()) {
            // Users can complete hCaptcha before clicking submit. In that case
            // there is nothing to do; we already have the token the backend
            // expects, so let the submit pipeline continue immediately.
            return;
        }

        // No token yet, so tell hCaptcha to start/continue the challenge flow.
        // For invisible or programmatic widget modes this is the moment the
        // provider gets a chance to prompt the user.
        api.execute(widget);

        // After execute(), the token will arrive asynchronously via the widget
        // callback configured in `mount()`. We therefore wait on the shared
        // token transport layer, not on a provider-specific promise chain.
        return services.tokens.wait().then((hasToken) => {
            if (!hasToken) {
                // If the callback never produced a token in time, surface a
                // normal themed error and abort the submit pipeline's `screen`
                // stage so the form is not posted without verification.
                const message = services.errors.getDefaultMessage();
                services.errors.show(message, placeholder);
                stageCtx.abort(message);
            }
        });
    },
    unmount: ({ api, widget, services }) => {
        // Reset tears down provider state and clears any token the widget had
        // produced. This keeps later page visits/submits from accidentally
        // reusing an old solved challenge.
        api.reset(widget);
        services.tokens.clear();
    },
});
