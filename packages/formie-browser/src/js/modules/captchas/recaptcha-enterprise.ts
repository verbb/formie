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

type RecaptchaEnterpriseProviderOptions = RecaptchaProviderOptions & {
    enterpriseType?: string | null;
    action?: string;
};

// Enterprise is intentionally still one module because Google ships these as
// one SDK with three web-facing modes:
// - checkbox: rendered widget, user solves before submit
// - score: no visible widget, execute() returns a score token
// - policy: execute() triggers the challenge/assessment flow for policy keys
//
// Shared captcha services should not know about any of those distinctions, so
// this module owns the branching itself.
export const recaptchaEnterpriseModule = defineCaptchaModule<RecaptchaEnterpriseProviderOptions, RecaptchaGlobal, number | string>({
    id: 'recaptcha-enterprise',
    defaultPlaceholderSelector: '[data-recaptcha-placeholder]',
    defaultTokenFieldNames: ['g-recaptcha-response'],
    load: ({ options }) => {
        // Score/policy keys use the site-key `render=` script mode, while
        // checkbox still behaves like an explicit widget render.
        return loadRecaptchaGlobal(
            options.provider,
            true,
            options.provider.enterpriseType === 'score' || options.provider.enterpriseType === 'policy' ? (options.provider.siteKey || undefined) : undefined,
        );
    },
    mount: ({ api, container, provider, services }) => {
        const enterpriseApi = api.enterprise || api;

        return new Promise((resolve) => {
            enterpriseApi.ready(() => {
                if (provider.enterpriseType !== 'checkbox') {
                    // Score and policy modes do not produce a widget id. As
                    // with reCAPTCHA v3, we keep the site key as the managed
                    // instance value so the provider still fits the generic
                    // managed-captcha lifecycle.
                    resolve(provider.siteKey || `recaptcha-enterprise-${provider.enterpriseType || 'score'}`);
                    return;
                }

                // Checkbox mode is a true rendered widget and therefore follows
                // the more traditional render/callback/reset lifecycle.
                resolve(enterpriseApi.render(container, {
                    sitekey: provider.siteKey || '',
                    theme: provider.theme || 'light',
                    badge: provider.badge || 'bottomright',
                    size: provider.size || 'normal',
                    action: provider.action || 'submit',
                    callback: (token?: string) => {
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
    screen: async({ api, widget, provider, placeholder, services, stageCtx }) => {
        const enterpriseApi = api.enterprise || api;

        if (provider.enterpriseType === 'checkbox') {
            // Checkbox mode behaves like reCAPTCHA v2 checkbox: by submit time
            // we only verify that the user has already solved the widget.
            if (services.tokens.has()) {
                return;
            }

            const message = services.errors.getDefaultMessage();
            services.errors.show(message, placeholder);
            stageCtx.abort(message);
            return;
        }

        if (services.tokens.has()) {
            return;
        }

        await whenRecaptchaReady(api, async() => {
            if (provider.enterpriseType === 'score' || provider.enterpriseType === 'policy') {
                // Score and policy-based keys execute by site key and can resolve
                // directly to a token. We write that token into the shared transport
                // layer so the backend can validate it in the usual way.
                const token = await enterpriseApi.execute(provider.siteKey || '', { action: provider.action || 'submit' });

                if (typeof token === 'string' && token.trim() !== '') {
                    services.tokens.write(token.trim());
                }
            } else {
                // Fallback branch for any future Enterprise widget-like mode.
                enterpriseApi.execute(widget);
            }
        });

        const hasToken = await services.tokens.wait(CAPTCHA_EXECUTE_WAIT_FOR_VALUE_MS);

        if (!hasToken) {
            const message = services.errors.getDefaultMessage();
            services.errors.show(message, placeholder);
            stageCtx.abort(message);
        }
    },
    reset: ({ api, widget, provider, services }) => {
        const enterpriseApi = api.enterprise || api;

        if (provider.enterpriseType === 'checkbox') {
            // Only checkbox mode has a long-lived rendered widget to reset.
            enterpriseApi.reset(widget);
        }

        services.tokens.clear();
    },
    unmount: ({ api, widget, provider, services }) => {
        const enterpriseApi = api.enterprise || api;

        if (provider.enterpriseType === 'checkbox') {
            enterpriseApi.reset(widget);
        }

        services.tokens.clear();
    },
});
