# Stripe
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

### Subscription payment limits
For subscription payments, you can optionally limit how many recurring payments Stripe collects before the subscription is cancelled automatically.

1. Edit your **Payment** field and set **Payment Type** to **Subscription**.
1. Under **Payment Limit**, choose **Fixed Value** or **Dynamic Value**.
1. For a fixed limit, enter the number of payments (for example `3` for three installments).
1. For a dynamic limit, select a field that provides the payment count (for example a **Number** field).

When a limit is set, Formie creates the subscription through a Stripe subscription schedule. Stripe cancels the subscription after the configured number of successful billing cycles. Leave **Payment Limit** set to **No limit** for ongoing subscriptions.

### Subscription setup fees
For subscription payments, you can optionally charge a one-time setup fee on the first invoice, in addition to the recurring subscription amount.

1. Edit your **Payment** field and set **Payment Type** to **Subscription**.
1. Under **Setup Fee**, choose **Fixed Value** or **Dynamic Value**.
1. For a fixed fee, enter the amount (for example `50`).
1. For a dynamic fee, select a field that provides the fee amount.
1. Optionally set **Setup Fee Description** for the Stripe invoice line item.

The setup fee is added to the first subscription invoice through Stripe `add_invoice_items`. It works with both standard subscriptions and subscriptions that use a payment limit schedule.

You can modify subscription payloads through the `modifySubscriptionPayload` and `modifySubscriptionSchedulePayload` events.
