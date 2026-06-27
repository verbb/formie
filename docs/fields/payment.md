# Payment

::: tip
For a full Stripe checkout walkthrough, see [Payment field with Stripe — full flow](/guides/fields/payment-field-with-stripe-full-flow).
:::

Use Payment when the form should collect or authorize a payment

Use Payment when the form itself is responsible for taking payment. If the project already has a full checkout flow, it may be better to keep payment there and link the form to that process.

## Key settings

- **Payment integration** - Choose the provider used by this field.
- **Amount and currency** - Configure the amount to charge or authorize.
- **Billing details** - Map form fields into the payment provider’s expected customer or billing data.
- **Provider behavior** - Configure provider-specific settings such as hosted UI, tokenization or redirects.
- **Submission behavior** - Account for any Ajax or workflow requirements surfaced by the provider.

## Submitted value

Payment stores payment-related submission data rather than normal text input. The exact value and records depend on the configured provider and whether the payment is authorized, captured, redirected or reconciled later.

When querying or saving submissions through GraphQL, payment behavior depends on the provider and submission workflow. Query the form’s `formFields` and include `inputTypeName` when building a custom front end.

## Payment providers

Payment providers are configured in two places:

1. Create and configure a payment integration in **Formie** → **Settings** → **Payments**.
2. Edit the Payment field, then choose the provider for **Payment Integration**.

Provider setup is documented on the payment integration pages:

- [BPOINT](/integrations/payments/bpoint)
- [eWAY](/integrations/payments/eway)
- [GoCardless](/integrations/payments/gocardless)
- [Mollie](/integrations/payments/mollie)
- [Moneris](/integrations/payments/moneris)
- [Opayo](/integrations/payments/opayo)
- [Paddle](/integrations/payments/paddle)
- [PayPal](/integrations/payments/paypal)
- [PayWay](/integrations/payments/payway)
- [Square](/integrations/payments/square)
- [Stripe](/integrations/payments/stripe)

Payment behavior depends on the selected provider. Some providers use hosted payment UI, some tokenize card details in the browser, and some complete payment through redirects or webhook updates. If you are building a custom provider, see [Payment Integration](/developers/custom-integration/payment-integration).

## Theme config

The Payment field can be targeted with the `payment` theme config key.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        payment: {
            field: {
                attributes: {
                    class: 'my-payment-field',
                },
            },
            stripePlaceholder: {
                attributes: {
                    class: 'my-stripe-placeholder',
                },
            },
        },
    },
}) }}
```

Use theme config for surrounding markup and attributes. Be careful with template overrides because payment providers may require specific elements or scripts.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Front-end reference

Payment fields often depend on provider JavaScript, Ajax submission, redirects or webhook reconciliation. If you customize rendering, keep the provider’s required front-end assets and submission flow intact.

The front-end docs live on the separate browser UI reference site and cover rendered markup, data attributes, styling classes and JavaScript behavior for custom front-end implementations.

- [Payment](/browser/ui-reference/fields/payment)

## Related fields

- Use [Products](/fields/products) or [Variants](/fields/variants) when the form should select Commerce elements.
- Use [Calculations](/fields/calculations) when the payment amount is derived from other field values.

