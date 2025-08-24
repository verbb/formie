# Payments
Payment integrations are one of the provided integrations with Formie, and are used to capture payments (one-time, or subscription) when users fill out the form.

You can create Payment integrations by going to **Formie** → **Settings** → **Payments**. You can pick from a list of providers Formie supports, and provide details for connecting to their respective APIs. You can also create multiple Payment integrations, in case you need to connect to multiple, different providers.

:::warning
Due to their sensitive nature, it's highly recommended to store API keys in your `.env` file. This will also make switching from development to production easier.
:::

To use payment integrations in your form, add a Payment field to your form and select the configured integration to use.

## Supported Providers
Formie integrates with the following providers:
- BPOINT
- Eway
- GoCardless
- Mollie
- Moneris
- Paddle
- PayPal
- Square
- Stripe
- Westpac PayWay

:::tip
Is your provider not in the list above? [Contact us](https://verbb.io/contact) to submit your interest, or look at the [Custom Integration](docs:developers/custom-integration) docs to write your own provider support.
:::


## BPOINT
Follow the below steps to connect to the BPOINT API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Payments**.
1. Click the **New Integration** button.
1. Select **BPOINT** as the **Integration Provider**.

### Step 2. Connect to the BPOINT API
1. Log in to the <a href="https://www.bpoint.com.au/webapi/" target="_blank">BPOINT API Portal</a>.
1. Copy the **Username** from BPOINT and paste in the **Username** field in Formie.
1. Copy the **Password** from BPOINT and paste in the **Password** field in Formie.
1. Copy the **Merchant Number** from BPOINT and paste in the **Merchant Number** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Field Setting
1. Go to the form you want to enable this integration on.
1. Add a **Payment** field to your form.
1. Select BPOINT for the **Payment Provider**.

The BPOINT payment integration supports only once-off payments.


## Eway
Follow the below steps to connect to the Eway API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Payments**.
1. Click the **New Integration** button.
1. Select **Eway** as the **Integration Provider**.

### Step 2. Connect to the Eway API
1. Log in to the <a href="https://my.eway.io/" target="_blank">Eway Partner Portal</a>.
1. Navigate to **My Account** → **API Key**.
1. Copy the **API Key** from Eway and paste in the **API Key** field in Formie.
1. Copy the **Password** from Eway and paste in the **API Password** field in Formie.
1. Copy the **Client Side Encryption Key** from Eway and paste in the **Client Side Encryption Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Field Setting
1. Go to the form you want to enable this integration on.
1. Add a **Payment** field to your form.
1. Select Eway for the **Payment Provider**.


## GoCardless
Follow the below steps to connect to the GoCardless API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Payments**.
1. Click the **New Integration** button.
1. Select **GoCardless** as the **Integration Provider**.

### Step 2. Connect to the GoCardless API
1. Go to the <a href="https://manage.gocardless.com/" target="_blank">GoCardless Dashboard</a> (or use the <a href="https://manage-sandbox.gocardless.com/" target="_blank">sandbox environment</a> for testing).
1. Navigate to **Developers** → **Developers**.
1. Click the **Create Access Token** button.
1. Copy the **Access Token** from GoCardless and paste in the **Access Token** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Field Setting
1. Go to the form you want to enable this integration on.
1. Add a **Payment** field to your form.
1. Select GoCardless for the **Payment Provider**.


## Mollie
Follow the below steps to connect to the Mollie API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Payments**.
1. Click the **New Integration** button.
1. Select **Mollie** as the **Integration Provider**.

### Step 2. Connect to the Mollie API
1. Go to the <a href="https://www.mollie.com/dashboard" target="_blank">Mollie Dashboard</a>.
1. Navigate to **Developers** → **API keys**.
1. Copy the **Live API key** and/or **Test API key**.
1. Paste the appropriate key into the **API Key** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Field Setting
1. Go to the form you want to enable this integration on.
1. Add a **Payment** field to your form.
1. Select Mollie for the **Payment Provider**.

The Mollie payment integration supports only once-off payments.


## Moneris
Follow the below steps to connect to the Moneris API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Payments**.
1. Click the **New Integration** button.
1. Select **Moneris** as the **Integration Provider**.

### Step 2. Connect to the Moneris API
1. Go to the <a href="https://www3.moneris.com/mpg" target="_blank">Moneris Dashboard</a>.
1. Navigate to **Admin** → **Store Settings**.
1. Copy the **Store ID** from Moneris and paste in the **Store ID** field in Formie.
1. Copy the **API Token** from Moneris and paste in the **API Token** field in Formie.
1. Navigate to **Admin** → **Hosted Tokenization**.
1. Enter the domain for your site.
1. Click the **Create Profile** button.
1. Copy the **Profile ID** from Moneris and paste in the **Profile ID** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Field Setting
1. Go to the form you want to enable this integration on.
1. Add a **Payment** field to your form.
1. Select Moneris for the **Payment Provider**.

The Moneris payment integration supports only once-off payments.


## Paddle
Follow the below steps to connect to the Paddle API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Payments**.
1. Click the **New Integration** button.
1. Select **Paddle** as the **Integration Provider**.

### Step 2. Connect to the Paddle API
1. Go to the <a href="https://vendors.paddle.com" target="_blank">Paddle Dashboard</a>.
1. Navigate to **Developer Tools** → **Authentication**.
1. Click the **New API Key** button.
1. Copy the **API Key** from Paddle and paste in the **API Key** field in Formie.
1. Navigate to **Client-side tokens**.
1. Click the **New Client-side Token** button.
1. Copy the **Token** from Paddle and paste in the **Client Side Token** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Field Setting
1. Go to the form you want to enable this integration on.
1. Add a **Payment** field to your form.
1. Select Paddle for the **Payment Provider**.

The Paddle payment integration supports only once-off payments.


## PayPal
Follow the below steps to connect to the PayPal API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Payments**.
1. Click the **New Integration** button.
1. Select **PayPal** as the **Integration Provider**.

### Step 2. Connect to the PayPal API
1. Go to your <a href="https://developer.paypal.com/developer/applications/" target="_blank">PayPal REST API</a> application settings.
1. Select either **Sandbox** or **Live** and click the **Create App** button.
1. Enter a **App Name** and select **Merchant** for the **App Type**.
1. Copy the **Client ID** from PayPal and paste in the **Client ID** field in Formie.
1. Copy the **Secret** from PayPal and paste in the **Client Secret** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Field Setting
1. Go to the form you want to enable this integration on.
1. Add a **Payment** field to your form.
1. Select PayPal for the **Payment Provider**.

The PayPal payment integration supports only once-off payments.


## Square
Follow the steps below to connect to the Square API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Payments**.
1. Click the **New Integration** button.
1. Select **Square** as the **Integration Provider**.

### Step 2. Create API Credentials
1. Go to the <a href="https://developer.squareup.com/apps" target="_blank">Square Developer Dashboard</a>.
1. Create a new application or choose an existing one.
1. Navigate to **Application** → **Credentials**.
1. Copy the **Application ID** from Square and paste in the **Application ID** field in Formie.
1. Copy the **Access Token** from Square and paste in the **Access tToken** field in Formie.
1. Navigate to **Application** → **Locations**.
1. Copy the **Location ID** from Square and paste in the **Location ID** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Field Setting
1. Go to the form you want to enable this integration on.
1. Add a **Payment** field to your form.
1. Select Square for the **Payment Provider**.

The Square payment integration supports only once-off payments.


## Stripe
Follow the below steps to connect to the Stripe API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Payments**.
1. Click the **New Integration** button.
1. Select **Stripe** as the **Integration Provider**.

### Step 2. Connect to the Stripe API
1. Go to your <a href="https://dashboard.stripe.com/apikeys" target="_blank">Stripe Developers - API Keys</a> page in your Stripe dashboard.
1. On the top-right of your screen, ensure the **Test Mode** lightswitch is in the **off** position if you wish to use Live details, or **on** if you wish to use Test details.
1. Copy the **Publishable Key** from Stripe and paste in the **Publishable Key** field in Formie.
1. Copy the **Secret Key** from Stripe and paste in the **Secret Key** field in Formie.
1. We **strongly recommend** you use `.env` variables to store these keys.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Configure Webhooks (for subscriptions)
1. In order for subscriptions to work, you must populate some settings for webhooks.
1. In Stripe, on the left-hand sidebar menu, click **Developers**.
1. On the top sub-menu, click **Webhooks**.
1. Click the **Create an event destination** button.
1. Copy the **Redirect URI** from the Formie integration settings and paste in the **Endpoint URL** in Stripe.
1. Click the **Select Events** button under the "Select events to listen to" heading.
1. We recommend emitting all possible events, but the required events are:
    - `customer.subscription.created`
    - `customer.subscription.deleted`
    - `customer.subscription.updated`
    - `invoice.created`
    - `invoice.payment_failed`
    - `invoice.payment_succeeded`
    - `payment_intent.succeeded`
    - `payment_intent.payment_failed`
    - `payment_intent.canceled`
    - `plan.deleted`
    - `plan.updated`
1. Once created look for the **Signing secret** item and click **Reveal Secret**.
1. Copy the **Signing secret** from Stripe and paste in the **Webhook Signing Secret** field in Formie.

### Step 5. Field Setting
1. Go to the form you want to enable this integration on.
1. Add a **Payment** field to your form.
1. Select Stripe for the **Payment Provider**.

:::warning
Your form **must** use the Ajax (Client-side) submission method when using the Stripe payment integration in your form.
:::

The Stripe payment integration supports both once-off payments and subscription-based payments.


## Westpac PayWay
Follow the below steps to connect to the Westpac PayWay API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Payments**.
1. Click the **New Integration** button.
1. Select **Westpac PayWay** as the **Integration Provider**.

### Step 2. Connect to the Westpac PayWay API
1. Go to your <a href="https://www.payway.com.au/" target="_blank">PayWay account</a>.
1. Click on the **Settings** navigation item in the top-right of the main header navigation.
1. Click the **REST API Keys** link.
1. Click the **Add** button, and select **Publishable** as the API Key type. Click the **Save** button.
1. Copy the **API Key** from Westpac PayWay and paste in the **Publishable Key** field in Formie.
1. Go back and click the click the **Add** button, and select **Secret** as the API Key type. Click the **Save** button.
1. Copy the **API Key** from Westpac PayWay and paste in the **Secret Key** field in Formie.
1. Click on the **Settings** navigation item in the top-right of the main header navigation.
1. Click the **Merchants** link.
1. Copy the **Merchant ID** from Westpac PayWay and paste in the **Merchant ID** field in Formie.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Field Setting
1. Go to the form you want to enable this integration on.
1. Add a **Payment** field to your form.
1. Select Westpac PayWay for the **Payment Provider**.

The Westpac PayWay payment integration supports only once-off payments.
