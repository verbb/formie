# Captchas
Captchas are one of the provided integrations with Formie, and are primarily used to prevent spam submissions.

Captchas have settings at the plugin level, as well as per-form, allowing you to customise their behaviour for particular forms, or globally for all forms.

We highly recommend using reCAPTCHA v3 for the most effective way to prevent spam submissions on your site.

Formie comes with 4 core captchas.

## reCAPTCHA
The recommended captcha to use, reCAPTCHA is a free service that protects your forms from spam and abuse. To get started, head to the [Google reCAPTCHA](https://www.google.com/recaptcha) page to register for API keys to use their service. Once you have these keys, go to Formie → Settings → Integrations → reCAPTCHA and enter your Site & Secret keys.

You can also configure which type of reCAPTCHA captcha to use. We highly recommend using reCAPTCHA v3.

- reCAPTCHA v2 (Checkbox)
- reCAPTCHA v2 (Invisible)
- reCAPTCHA v3
- reCAPTCHA v3 (Enterprise)

## hCaptcha
[hCaptcha](https://www.hcaptcha.com/) is an anti-bot solution that protects user privacy and rewards websites. It is the most popular reCAPTCHA alternative.

## Snaptcha Plugin
The [Snaptcha Plugin](https://plugins.craftcms.com/snaptcha) is an invisible CAPTCHA that automatically validates forms and prevents spam-bots from submitting to your Craft CMS site.

## Duplicate
This captcha checks for duplicate submissions, where bots might be submitting multiple times. This mechanism assigns a unique value to a submission, so if the same content is submitted again without refreshing or changing the form content, it will be marked as a duplicate. 

## Honeypot
This captcha checks for bots that auto-fill forms, by providing an additional hidden field that should be left blank. This is completely hidden to normal users filling out the form.

## Javascript
This captcha checks if the user has Javascript enabled, and flags as spam if they do not. This may be beneficial in some cases, but due to the reliance on the presence of Javascript being enabled (which can sometimes be valid for users), this should be enabled with caution.

## Multi-page Forms
For multi-page forms, you have the open to enable captchas to be shown and validate on each step of the form submission process, or at the very end. This is through the "Show on All Pages" setting.

## Custom Captchas
If you are a developer and looking to create your own captchas, head to the [Custom Integration](docs:developers/custom-integration) docs.

## Disabling Captchas
You might also like to force captcha verification to be disabled for testing purposes, or other means. To prevent security issues, you could also combine this disabling with a query parameter in the URL, that will only disable captchas when present.

```twig
{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}

{% do form.setSettings({
    {# Disable captchas immediately, for everyone #}
    disableCaptchas: true,

    {# Or, disable captchas for a URL that looks like `my-form?disable-captchas=true` #}
    disableCaptchas: craft.app.request.getParam('disable-captchas') == 'true',
}) %}

{{ craft.formie.renderForm(form) }}
```