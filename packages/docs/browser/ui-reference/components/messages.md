# Messages

Form messages show success, errors and follow-up instructions outside individual fields.

Use this page to preserve the message containers, semantics, and status variants that appear across submit handling and server-side validation flows.

## Preview

<FormiePreview src="../examples/messages.preview.ts" />

## Browser Attributes

Useful message-level hooks include:

| Hook | Purpose |
| --- | --- |
| `data-formie-form-messages-top` | Top-of-form message region |
| `data-formie-form-messages-bottom` | Bottom-of-form message region |
| `data-formie-message` | Shared message wrapper |
| `data-formie-message-error` | Error-message variant |
| `data-formie-message-success` | Success-message variant |
| `data-formie-errors` | Error message collection |
| `data-formie-success-container` | Success message collection |
| `data-formie-error` | Inline error message content |
| `data-formie-success` | Success message content |

## Styling Classes

| Class | Purpose |
| --- | --- |
| `formie-message` | Shared message container |
| `formie-message-error` | Error styling |
| `formie-message-success` | Success styling |
| `formie-errors` | Error stack container |
| `formie-successes` | Success stack container |

## Accessibility Notes

- Error messages should use alert-like semantics such as `role="alert"` and assertive live-region behaviour when they need immediate attention.
- Success messages should prefer polite live-region semantics such as `role="status"`.
- Long form-level guidance should remain outside field controls so visitors can read it in one place.
