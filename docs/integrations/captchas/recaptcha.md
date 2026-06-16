# reCAPTCHA

Follow the below steps to connect Formie to Google reCAPTCHA.

### Step 1. Open the Captcha Settings
1. Navigate to **Formie** → **Settings** → **Spam Protection** → **Captchas**.
1. Select **reCAPTCHA** in the left-hand sidebar.
1. Turn on **Enabled**.

### Step 2. Create your reCAPTCHA Keys
1. Go to the [reCAPTCHA Admin console](https://www.google.com/recaptcha/admin) or the [Google Cloud reCAPTCHA page](https://console.cloud.google.com/security/recaptcha).
1. Create a new website key for the mode you want to use.
1. Use a **score-based** key for **reCAPTCHA v3**.
1. Use a **challenge** key for **reCAPTCHA v2 Checkbox**.
1. For **reCAPTCHA Enterprise**, create a website key whose type matches the Enterprise key type you want to use in Formie.
1. Add the domains where the form will run.
1. Save the key.
1. Copy the **Site Key**.
1. Open the key details and use **Use Legacy Key** to reveal the **legacy secret key** for third-party integrations such as Formie.
1. If you are using **reCAPTCHA Enterprise**, also note your Google Cloud **Project ID**.

### Step 3. Connect Formie to reCAPTCHA
1. Choose the **reCAPTCHA Type** in Formie.
1. Paste the **reCAPTCHA Site Key** into the **reCAPTCHA Site Key** field.
1. Paste the **legacy secret key** into the **reCAPTCHA Secret Key** field.
1. If you are using **reCAPTCHA Enterprise**, select the **Enterprise Key Type** that matches the key you created and enter the **Project ID**.
1. Optionally configure the other settings that apply to your chosen mode, such as **Minimum Score**, **Badge Type**, **Theme**, **Size**, **Action**, **Language**, and **Script Loading Method**.
1. Save the captcha settings.

### Step 4. Form Setting
1. Go to the form you want to protect.
1. Enable **reCAPTCHA** for that form.
1. For multi-page forms, turn on **Show on All Pages** if reCAPTCHA should run on every page instead of only the final submit step.
1. For **reCAPTCHA v3** or **Enterprise score-based** keys, optionally override the global **Action** or **Minimum Score** for this form only. Leave these blank to inherit the values from **Settings → Spam Protection**.
1. Save the form.

For most forms, **reCAPTCHA v3** is the best starting point. Use the checkbox or Enterprise challenge modes only when you need a visible challenge.

## Score-based challenges and low scores

reCAPTCHA v3 and **Enterprise score-based** modes return a score when the user submits. Formie compares that score to your **Minimum Score** threshold (globally under **Settings → Spam Protection**, or per form when you override it). Submissions below the threshold are treated as spam.

Formie does **not** support a second captcha as a fallback when a score is too low — for example, automatically showing a v2 checkbox after Enterprise returns a low score. That would require different API keys, a different user flow, and server-side "soft fail" handling that Formie does not provide today.

If score-based protection is too aggressive, try one of these instead:

1. **Lower the minimum score** — start around `0.5` and adjust based on your traffic.
2. **Switch to a visible challenge** — use **reCAPTCHA v2 Checkbox**, **reCAPTCHA v2 Invisible**, or an **Enterprise** key type of **Checkbox** or **Policy** when you want users to complete a challenge up front.
3. **Use another provider** — [Cloudflare Turnstile](/integrations/captchas/cloudflare-turnstile) or [Friendly Captcha](/integrations/captchas/friendly-captcha) may fit your UX and compliance needs better.

For **Enterprise** keys migrated from classic reCAPTCHA in Google Cloud, you can usually keep your existing **reCAPTCHA Type** in Formie after migration. Switch to **reCAPTCHA Enterprise** in Formie when you want Enterprise-specific key types or features. Key migration itself happens in Google's console, not in Formie.

## Cookie consent and deferred loading

Google reCAPTCHA loads third-party scripts that may require consent under GDPR and similar regulations. Formie does not integrate directly with consent management platforms (Cookiebot, OneTrust, Klaro, and so on).

To delay captcha initialization until consent is granted:

1. Render the form with automatic initialization turned off:

```twig
{{ craft.formie.renderForm(form, {
    initJs: false,
}) }}
```

For [custom rendering](/theming/custom-rendering), set `data-formie-init="false"` on the `<form>` element instead.

2. Output Formie's assets as usual with `craft.formie.formAssets(form)` or `craft.formie.frontendAssets()`.

3. After your consent banner grants the relevant category, initialize Formie from your own bundle:

```js
import { formie } from '@verbb/formie-browser';

await formie({
  element: '[data-formie-form]',
});
```

Until you call `formie()`, captcha scripts are not loaded and captchas are not mounted. Users cannot complete a protected submit until initialization runs.

If consent is a hard requirement and you want to avoid Google scripts entirely, consider [Friendly Captcha](/integrations/captchas/friendly-captcha) or another provider that fits your compliance model.

See also [Render Options — `initJs`](/templates/render-options#initjs) and the [Browser package manual initialization guide](https://docs.verbb.io/formie/browser/behavior/manual-initialization).
