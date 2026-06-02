import type { FormieModuleDefinition } from '#contracts/modules';

export const builtinCaptchaModuleLoaders: Record<string, () => Promise<FormieModuleDefinition>> = {
    // Module ids map directly to importer functions so the loader can fetch only
    // the captcha chunks required by the current form manifest.
    'captcha-eu': () => import('#modules/captchas/captcha-eu').then((module) => module.captchaEuModule),
    'friendly-captcha-v1': () => import('#modules/captchas/friendly-captcha-v1').then((module) => module.friendlyCaptchaV1Module),
    'friendly-captcha-v2': () => import('#modules/captchas/friendly-captcha-v2').then((module) => module.friendlyCaptchaV2Module),
    'hcaptcha': () => import('#modules/captchas/hcaptcha').then((module) => module.hcaptchaModule),
    'recaptcha-enterprise': () => import('#modules/captchas/recaptcha-enterprise').then((module) => module.recaptchaEnterpriseModule),
    'recaptcha-v2-checkbox': () => import('#modules/captchas/recaptcha-v2-checkbox').then((module) => module.recaptchaV2CheckboxModule),
    'recaptcha-v2-invisible': () => import('#modules/captchas/recaptcha-v2-invisible').then((module) => module.recaptchaV2InvisibleModule),
    'recaptcha-v3': () => import('#modules/captchas/recaptcha-v3').then((module) => module.recaptchaV3Module),
    'snaptcha': () => import('#modules/captchas/snaptcha').then((module) => module.snaptchaModule),
    'turnstile': () => import('#modules/captchas/turnstile').then((module) => module.turnstileModule),
};
