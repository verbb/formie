# Payments
Payment integrations are one of the provided integrations with Formie, and are used to capture payments (one-time, or subscription) when users fill out the form.

You can create Payment integrations by going to **Formie** → **Settings** → **Payments**. You can pick from a list of providers Formie supports, and provide details for connecting to their respective APIs. You can also create multiple Payment integrations, in case you need to connect to multiple, different providers.

:::warning
Due to their sensitive nature, it's highly recommended to store API keys in your `.env` file. This will also make switching from development to production easier.
:::

To use payment integrations in your form, add a Payment field to your form and select the configured integration to use.

## Supported Providers
Formie integrates with the following providers:
- Stripe (One-time and Subscription)
- PayPal (One-time)
- Westpac PayWay (One-time)

:::tip
Is your provider not in the list above? [Contact us](https://verbb.io/contact) to submit your interest, or look at the [Custom Integration](docs:developers/custom-integration) docs to write your own provider support.
:::

## Stripe
Follow the below steps to connect to the Stripe API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Payments**.
1. Click the **New Integration** button.
1. Select Stripe as the **Integration Provider**.

### Step 2. Connect to the Stripe API
1. Go to your <a href="https://dashboard.stripe.com/account/apikeys" target="_blank">Stripe API Keys</a> page in your Stripe dashboard.
1. On the top-right of your screen, ensure the **Test Mode** lightswitch is in the **off** position if you wish to use Live details, or **on** if you wish to use Test details.
1. On the top-right of your screen, click **Developers**.
1. On the left-hand sidebar, click **API Keys**.
1. Copy the **Publishable Key** from Stripe and paste in the **Publishable Key** field in Formie.
1. Copy the **Secret Key** from Stripe and paste in the **Secret Key** field in Formie.
1. We **strongly recommend** you use `.env` variables to store these keys.

### Step 3. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.

### Step 4. Configure Webhooks (for subscriptions)
1. In order for subscriptions to work, you must populate some settings for webhooks.
1. In Stripe, on the top-right of your screen, click **Developers**.
1. On the left-hand sidebar, click **Webhooks**.
1. Click the **Add an endpoint** button.
1. Copy the **Redirect URI** from below and paste in the **Endpoint URL** in Stripe.
1. Click the **Select Events** button under the "Select events to listen to" heading.
1. We recommend emitting all possible events, but the required events are:
    - `customer.subscription.created`
    - `customer.subscription.deleted`
    - `customer.subscription.updated`
    - `invoice.created`
    - `invoice.payment_failed`
    - `invoice.payment_succeeded`
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


## PayPal
Follow the below steps to connect to the PayPal API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Payments**.
1. Click the **New Integration** button.
1. Select PayPal as the **Integration Provider**.

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



## Westpac PayWay
Follow the below steps to connect to the Westpac PayWay API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Payments**.
1. Click the **New Integration** button.
1. Select Westpac PayWay as the **Integration Provider**.

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
