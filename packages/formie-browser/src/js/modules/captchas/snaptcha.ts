import { definePassiveCaptchaModule } from '#modules/captchas/api';

// Snaptcha is the remaining "passive" captcha style: there is no browser SDK
// to load and no user-facing widget to render. The shared passive factory is a
// good fit here because the provider is really just about keeping a hidden
// transport value in sync across renders and token refreshes.
export const snaptchaModule = definePassiveCaptchaModule({
    id: 'snaptcha',
    defaultPlaceholderSelector: '[data-snaptcha-captcha-placeholder]',
});
