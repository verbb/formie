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
    id: 'recaptcha-v3',
    defaultPlaceholderSelector: '[data-recaptcha-placeholder]',
    defaultTokenFieldNames: ['g-recaptcha-response'],
    load: ({ options }) => {
        // Score-based flows use a site-key `render=` mode instead of an
        // explicit widget render, so the loader needs the site key up front.
        return loadRecaptchaGlobal(options.provider, false, options.provider.siteKey || undefined);
    },
    mount: ({ api, provider }) => {
        return new Promise((resolve) => {
            api.ready(() => {
                // There is no real widget id for v3. We store the site key as
                // the "widget" value purely so the managed factory can treat
                // this provider like every other captcha module instance.
                resolve(provider.siteKey || 'recaptcha-v3');
            });
        });
    },
    screen: async({ api, provider, placeholder, services, stageCtx }) => {
        // If a token already exists we can skip execution, which matters for
        // re-renders or multi-step flows where the same challenge was already
        // completed very recently.
        if (services.tokens.has()) {
            return;
        }

        // v3 returns its token directly from `execute()`, unlike widget-based
        // captchas that signal completion later via callbacks.
        await whenRecaptchaReady(api, async() => {
            const token = await api.execute(provider.siteKey || '', { action: provider.action || 'submit' });

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
    unmount: ({ services }) => {
        services.tokens.clear();
    },
});
