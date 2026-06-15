# Cloudflare Turnstile

Follow the below steps to connect Formie to Cloudflare Turnstile.

### Step 1. Open the Captcha Settings
1. Navigate to **Formie** → **Settings** → **Captchas**.
1. Select **Cloudflare Turnstile** in the left-hand sidebar.
1. Turn on **Enabled**.

### Step 2. Create a Turnstile Widget
1. Go to the [Cloudflare Turnstile dashboard](https://dash.cloudflare.com/?to=/:account/turnstile) and sign in to your account.
1. Open the **Turnstile** page.
1. Click **Add widget**.
1. Enter a **Widget name**.
1. Add the hostname or hostnames where the form will run.
1. Choose the **Widget mode** you want to use in Cloudflare: **Managed**, **Non-interactive**, or **Invisible**.
1. Click **Create**.
1. Copy the **Site key** and **Secret key**.

### Step 3. Connect Formie to Turnstile
1. Paste the **Site Key** into the **Site Key** field in Formie.
1. Paste the **Secret Key** into the **Secret Key** field in Formie.
1. Choose the front-end options you want in Formie, such as **Theme**, **Size**, **Appearance**, and **Execution**.
1. Save the captcha settings.

### Step 4. Form Setting
1. Go to the form you want to protect.
1. Enable **Cloudflare Turnstile** for that form.
1. For multi-page forms, turn on **Show on All Pages** if the captcha should run on every page instead of only the final submit step.
1. Save the form.

Turnstile widget mode is chosen in Cloudflare for the site key you created. Formie does not change that mode, so make sure you pick the right widget in Cloudflare first.

### Content Security Policy (CSP)

If your site uses strict CSP headers (for example on Craft Cloud), allow scripts and frames from `https://challenges.cloudflare.com`. Formie also forwards your page CSP nonce to dynamically loaded captcha scripts when a `csp-nonce` meta tag is present.
