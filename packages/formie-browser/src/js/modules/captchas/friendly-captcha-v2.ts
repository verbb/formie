import { defineCaptchaModule } from '#modules/captchas/api';

type FriendlyCaptchaSdkModule = typeof import('@friendlycaptcha/sdk');
type FriendlyCaptchaWidget = import('@friendlycaptcha/sdk').WidgetHandle;

// FriendlyCaptcha v2 is similar in intent to v1, but its SDK, widget handle
// type and emitted event names are different enough that keeping a dedicated
// provider module is clearer than trying to abstract them together.
type FriendlyCaptchaProviderOptions = {
    siteKey?: string | null;
    language?: string;
    startMode?: string;
    theme?: string;
};

export const friendlyCaptchaV2Module = defineCaptchaModule<
    FriendlyCaptchaProviderOptions,
    FriendlyCaptchaSdkModule,
    FriendlyCaptchaWidget
>({
    id: 'friendly-captcha-v2',
    defaultPlaceholderSelector: '[data-friendly-captcha-placeholder]',
    defaultTokenFieldNames: ['frc-captcha-response'],
    load: async () => {
        return import('@friendlycaptcha/sdk');
    },
    mount: ({ api, container, provider, services }) => {
        // v2 uses an SDK instance that then creates a widget handle. This is a
        // good example of provider-specific lifecycle detail that should stay
        // outside the generic captcha services layer.
        const sdk = new api.FriendlyCaptchaSDK();
        const widget = sdk.createWidget({
            element: container,
            sitekey: provider.siteKey || '',
            formFieldName: 'frc-captcha-response',
            language: provider.language,
            startMode: (provider.startMode as 'auto' | 'focus' | 'none' | undefined) || 'none',
            theme: (provider.theme as 'auto' | 'light' | 'dark' | undefined) || 'auto',
        });

        widget.addEventListener('frc:widget.complete', (event) => {
            const detail = (event as CustomEvent<{ response?: string }>).detail;

            // v2 exposes the solved response in its custom event detail rather
            // than a direct callback argument.
            if (typeof detail?.response === 'string' && detail.response.trim() !== '') {
                services.tokens.write(detail.response.trim());
            }

            services.errors.clear();
        });

        widget.addEventListener('frc:widget.expire', () => {
            services.tokens.clear();
            services.errors.clear();
        });

        widget.addEventListener('frc:widget.error', (event) => {
            const detail = (event as CustomEvent<{ error?: unknown }>).detail;
            if (detail?.error) {
                services.tokens.clear();
            }
        });

        return widget;
    },
    screen: async ({ widget, placeholder, services, stageCtx }) => {
        // As with v1, start the challenge only if no token is currently present.
        if (services.tokens.has()) {
            return;
        }

        widget.start();
        const hasToken = await services.tokens.wait();

        if (!hasToken) {
            const message = services.errors.getDefaultMessage();
            services.errors.show(message, placeholder);
            stageCtx.abort(message);
        }
    },
    unmount: ({ widget, services }) => {
        widget.destroy();
        services.tokens.clear();
    },
});
