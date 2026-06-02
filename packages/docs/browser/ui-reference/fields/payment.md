# Payment

Payment is the **field** markup that wraps provider-backed payment UI such as Stripe, PayPal, Square, and other gateway-specific mounts.

Use this page to preserve the field container and provider placeholders while letting the provider modules own the embedded payment UI itself.

## Preview

<FormiePreview src="../examples/payment.preview.ts" />

## Attributes

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie-field-type="payment"` | Payment field discovery hook | Required |
| `data-formie-stripe-elements-placeholder` / related provider placeholders | Provider mount targets | Required for the matching provider |
| `data-formie-paypal-button` | PayPal button placeholder | Required for PayPal |
| Standard field layout and messages | Shared validation and submit-state surface | Required |

## Behavior

Payment behavior is owned by the payment module stack rather than one small field module:

- provider modules mount their UI inside the payment field
- payment actions participate in the submit lifecycle
- provider-specific events are exposed through the public JavaScript event surface

## Related pages

- [JavaScript events](/browser/behavior/javascript-events)
- [Submission handling](/browser/behavior/submission-handling)
- [JavaScript API](/browser/)
