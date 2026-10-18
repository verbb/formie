import {
    defineCaptchaModule,
} from '#modules/captchas/api';
import { CAPTCHA_EXECUTE_WAIT_FOR_VALUE_MS } from '#modules/captchas/constants';
import {
    loadRecaptchaGlobal,
    whenRecaptchaReady,
    type RecaptchaGlobal,
    type RecaptchaProviderOptions,
} from '#modules/captchas/recaptcha-shared';

// reCAPTCHA v3 is the "no visible widget" score flow. We still mount a module
// instance so it can participate in the same submit lifecycle as other
// providers, but most of the work happens at screen time.
export const recaptchaV3Module = defineCaptchaModule<RecaptchaProviderOptions, RecaptchaGlobal, number | string>({
    moduleId: 'formie:recaptcha-v3',
    defaultPlaceholderSelector: '[data-recaptcha-placeholder]',
    defaultTokenFieldNames: ['g-recaptcha-response'],
    load: ({ options }) => {
        return loadRecaptchaGlobal(options.provider);
    },
    mount: ({ api, container, provider, services }) => {
        return new Promise((resolve, reject) => {
            // An existing api.js may have loaded without render=<our site key>.
            // Register this form explicitly, then execute its returned client ID.
            void whenRecaptchaReady(api, async() => {
                resolve(api.render(container, {
                    sitekey: provider.siteKey || '',
                    size: 'invisible',
                    badge: provider.badge || 'bottomright',
                    theme: provider.theme || 'light',
                    'expired-callback': () => services.tokens.clear(),
                    'error-callback': () => services.tokens.clear(),
                }));
            }).catch(reject);
        });
    },
    challenge: async({ api, widget, provider, placeholder, services, stageCtx }) => {
        // If a token already exists we can skip execution, which matters for
        // re-renders or multi-step flows where the same challenge was already
        // completed very recently.
        if (services.tokens.has()) {
            return;
        }

        // v3 returns its token directly from `execute()`, unlike widget-based
        // captchas that signal completion later via callbacks.
        await whenRecaptchaReady(api, async() => {
            const token = await api.execute(widget, { action: provider.action || 'submit' });

            if (typeof token === 'string' && token.trim() !== '') {
                services.tokens.write(token.trim());
            }
        });

        // We still wait on the shared token layer so the module follows the
        // same transport contract as every other captcha provider.
        const hasToken = await services.tokens.wait(CAPTCHA_EXECUTE_WAIT_FOR_VALUE_MS);

        if (!hasToken) {
            const message = services.errors.getDefaultMessage();
            services.errors.show(message, placeholder);
            stageCtx.abort(message);
        }
    },
    reset: ({ api, widget, services }) => {
        api.reset(widget);
        services.tokens.clear();
    },
    unmount: ({ api, widget, services }) => {
        api.reset(widget);
        services.tokens.clear();
    },
});
