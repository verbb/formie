import {
    defineCaptchaModule,
} from '#modules/captchas/api';
import {
    CAPTCHA_EXECUTE_WAIT_FOR_VALUE_MS,
    CAPTCHA_PROVIDER_LOAD_TIMEOUT_MS,
    CAPTCHA_SUBMIT_WAIT_FOR_VALUE_MS,
} from '#modules/captchas/constants';
import { loadScriptAndEnsureGlobal } from '#utils/scripts';
import { getScriptAttributes } from '#modules/captchas/utils';

// Narrow local contract for the Cloudflare Turnstile browser SDK.
type TurnstileGlobal = {
    render: (container: HTMLElement, options: Record<string, unknown>) => string;
    execute: (widgetId: string) => void;
    reset: (widgetId: string) => void;
    remove: (widgetId: string) => void;
};

// Provider-specific options only. Generic captcha concerns are handled by the
// shared captcha services/factory layer.
type TurnstileProviderOptions = {
    siteKey?: string | null;
    theme?: string;
    size?: string;
    appearance?: string;
    execution?: string;
    loadingMethod?: string;
};

function getTurnstileWaitForValueMs(provider: TurnstileProviderOptions): number {
    const appearance = provider.appearance || 'always';
    const execution = provider.execution || (appearance === 'execute' ? 'execute' : 'render');

    return execution === 'execute' ? CAPTCHA_EXECUTE_WAIT_FOR_VALUE_MS : CAPTCHA_SUBMIT_WAIT_FOR_VALUE_MS;
}

async function loadTurnstileGlobal(options: TurnstileProviderOptions): Promise<TurnstileGlobal> {
    // Turnstile is simpler than hCaptcha/reCAPTCHA: once the script has loaded,
    // the global is ready to use immediately. This helper stays local because
    // the URL and readiness assumptions are specific to Turnstile.
    const { async, defer } = getScriptAttributes(options.loadingMethod);

    return loadScriptAndEnsureGlobal<TurnstileGlobal>('turnstile', {
        id: 'FORMIE_TURNSTILE_SCRIPT',
        src: 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit',
        async,
        defer,
        timeoutMs: CAPTCHA_PROVIDER_LOAD_TIMEOUT_MS,
    });
}

export const turnstileModule = defineCaptchaModule<TurnstileProviderOptions, TurnstileGlobal, string>({
    id: 'turnstile',
    defaultPlaceholderSelector: '[data-turnstile-placeholder]',
    defaultTokenFieldNames: ['cf-turnstile-response'],
    load: ({ options }) => {
        // Load once, then let the managed captcha factory share the resolved
        // SDK across any remount/reset work for this form.
        return loadTurnstileGlobal(options.provider);
    },
    mount: ({ api, container, provider, services }) => {
        // Turnstile has two related concepts:
        // - `appearance`: when/if the widget should visibly appear
        // - `execution`: whether the challenge runs automatically or only when
        //   we explicitly call `turnstile.execute(widget)` during submit
        //
        // We normalize that relationship here because it is a provider concern,
        // not something the shared captcha module should need to understand.
        const appearance = provider.appearance || 'always';
        const execution = provider.execution || (appearance === 'execute' ? 'execute' : 'render');

        return api.render(container, {
            sitekey: provider.siteKey || '',
            theme: provider.theme || 'auto',
            size: provider.size || 'normal',
            appearance,
            execution,
            callback: (token?: string) => {
                // Turnstile verification completed successfully, so copy its
                // token into the shared transport inputs for backend submit.
                if (typeof token === 'string' && token.trim() !== '') {
                    services.tokens.write(token.trim());
                }

                services.errors.clear();
            },
            'expired-callback': () => {
                // Expired tokens must be removed immediately so a later submit
                // cannot reuse something Turnstile no longer accepts.
                services.tokens.clear();
                services.errors.clear();
            },
            'timeout-callback': () => {
                // Treat timeouts like expiry: the challenge must be solved again.
                services.tokens.clear();
                services.errors.clear();
            },
            'error-callback': () => {
                // Provider-side failures should not leave stale transport state.
                services.tokens.clear();
            },
        });
    },
    screen: ({ api, widget, placeholder, services, provider, stageCtx }) => {
        // Submit-time rule: if a valid token already exists, do nothing. This
        // covers cases where Turnstile solved itself on load or before submit.
        if (services.tokens.has()) {
            return;
        }

        // Otherwise explicitly ask Turnstile to run now. This is what supports
        // "run on submit" style configurations without the shared module
        // needing any Turnstile-specific policy logic.
        api.execute(widget);
        return services.tokens.wait(getTurnstileWaitForValueMs(provider)).then((hasToken) => {
            if (!hasToken) {
                // No token arrived in time, so block the screen stage and show
                // the shared themed inline error next to the active placeholder.
                const message = services.errors.getDefaultMessage();
                services.errors.show(message, placeholder);
                stageCtx.abort(message);
            }
        });
    },
    unmount: ({ api, widget, services }) => {
        // Remove the widget before clearing placeholder DOM so Turnstile does not
        // log errors about orphaned challenge iframes during remounts.
        if (typeof api.remove === 'function') {
            api.remove(widget);
        } else {
            api.reset(widget);
        }

        services.tokens.clear();
    },
});
