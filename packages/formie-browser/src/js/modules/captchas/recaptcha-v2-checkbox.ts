import {
    defineCaptchaModule,
} from '#modules/captchas/api';
import {
    loadRecaptchaGlobal,
    type RecaptchaGlobal,
    type RecaptchaProviderOptions,
} from '#modules/captchas/recaptcha-shared';

// This is the classic checkbox widget. The important distinction from the
// invisible/score flows is that we never trigger execution ourselves; the user
// must solve the already-rendered widget before submit succeeds.
export const recaptchaV2CheckboxModule = defineCaptchaModule<RecaptchaProviderOptions, RecaptchaGlobal, number | string>({
    id: 'recaptcha-v2-checkbox',
    defaultPlaceholderSelector: '[data-recaptcha-placeholder]',
    defaultTokenFieldNames: ['g-recaptcha-response'],
    load: ({ options }) => {
        return loadRecaptchaGlobal(options.provider);
    },
    mount: ({ api, container, provider, services }) => {
        return new Promise((resolve) => {
            api.ready(() => {
                // For checkbox mode the Google SDK gives us a widget id, which
                // later becomes important for reset/unmount.
                resolve(api.render(container, {
                    sitekey: provider.siteKey || '',
                    theme: provider.theme || 'light',
                    size: provider.size || 'normal',
                    callback: (token?: string) => {
                        // Checkbox mode solves interactively before submit, so
                        // the callback is the point where the transport layer
                        // finally becomes "screen-stage ready".
                        if (typeof token === 'string' && token.trim() !== '') {
                            services.tokens.write(token.trim());
                        }

                        services.errors.clear();
                    },
                    'expired-callback': () => {
                        services.tokens.clear();
                        services.errors.clear();
                    },
                    'error-callback': () => {
                        services.tokens.clear();
                    },
                }));
            });
        });
    },
    screen: ({ placeholder, services, stageCtx }) => {
        // Checkbox mode does not execute at submit time. We simply check if the
        // user already solved it; if not, abort immediately with a field-style
        // error and let the user interact with the widget.
        if (services.tokens.has()) {
            return;
        }

        const message = services.errors.getDefaultMessage();
        services.errors.show(message, placeholder);
        stageCtx.abort(message);
    },
    reset: ({ api, widget, services }) => {
        // A successful AJAX submit or form reset should leave the checkbox
        // rendered but unsolved, so `reset()` is the right provider behavior.
        api.reset(widget);
        services.tokens.clear();
    },
    unmount: ({ api, widget, services }) => {
        api.reset(widget);
        services.tokens.clear();
    },
});
