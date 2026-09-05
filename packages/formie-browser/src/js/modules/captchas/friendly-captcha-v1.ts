import { defineCaptchaModule } from '#modules/captchas/api';

type FriendlyChallengeModule = typeof import('friendly-challenge');

// FriendlyCaptcha v1 ships a different package and callback shape from v2, so
// it keeps its own provider module even though the high-level flow is similar.
type FriendlyCaptchaProviderOptions = {
    siteKey?: string | null;
    language?: string;
    startMode?: string;
    theme?: string;
};

export const friendlyCaptchaV1Module = defineCaptchaModule<
    FriendlyCaptchaProviderOptions,
    FriendlyChallengeModule,
    import('friendly-challenge').WidgetInstance
>({
    id: 'friendly-captcha-v1',
    defaultPlaceholderSelector: '[data-friendly-captcha-placeholder]',
    defaultTokenFieldNames: ['frc-captcha-solution'],
    load: async() => {
        // The provider owns its package choice. Shared captcha services only
        // cares that `load()` returns some API object for later lifecycle work.
        return import('friendly-challenge');
    },
    mount: ({ api, container, provider, services }) => {
        // V1 uses a `dark` class on the widget element (no `auto` theme option).
        const theme = provider.theme || 'auto';
        const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches ?? false;
        const useDark = theme === 'dark' || (theme === 'auto' && prefersDark);

        if (useDark) {
            container.classList.add('dark');
        }

        return new api.WidgetInstance(container, {
            sitekey: provider.siteKey || '',
            startMode: (provider.startMode as 'auto' | 'focus' | 'none' | undefined) || 'none',
            language: (provider.language as 'en' | undefined) || 'en',
            solutionFieldName: 'frc-captcha-solution',
            doneCallback: (token?: string) => {
                // v1 writes to its provider-defined solution field name, while
                // the shared transport services own how that ends up in the form DOM.
                if (typeof token === 'string' && token.trim() !== '') {
                    services.tokens.write(token.trim());
                }

                services.errors.clear();
            },
            errorCallback: () => {
                services.tokens.clear();
            },
        });
    },
    screen: async({ widget, placeholder, services, stageCtx }) => {
        // FriendlyCaptcha is started programmatically on submit when needed.
        if (services.tokens.has()) {
            return;
        }

        await widget.start();
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
