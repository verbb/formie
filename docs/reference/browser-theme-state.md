# Browser Theme State

<!-- Generated from src/config/browser-theme-state.json. Do not edit by hand. -->

These semantic keys are the shared contract used by PHP-rendered markup and browser-created state. Configure the keys at the root of `themeConfig`; the resolved map is emitted as `data-formie-theme-classes`.

| Key | Default classes | Purpose |
| --- | --- | --- |
| `errors` | `formie-errors` | Form-level error collection |
| `successes` | `formie-successes` | Form-level success collection |
| `message` | `formie-message` | Shared message container |
| `messageError` | `formie-message-error` | Error message state |
| `messageSuccess` | `formie-message-success` | Success message state |
| `tabError` | `formie-tab-error` | Page tab with an error |
| `tabCurrent` | `formie-tab-current` | Current page tab |
| `tabComplete` | `formie-tab-complete` | Completed page tab |
| `tabLinkCurrent` | None | Current page-tab link |
| `tabLinkInactive` | None | Inactive page-tab link |
| `pageHidden` | `formie-page-hidden` | Hidden page |
| `conditionalHidden` | `formie-conditionally-hidden` | Conditionally hidden field |
| `rowHidden` | `formie-row-hidden` | Layout row with no visible fields |
| `loading` | `formie-loading` | Loading state |
| `success` | `formie-success` | Individual success item |
| `error` | `formie-error` | Individual error item |
| `fieldLayoutError` | `formie-field-has-error` | Field layout with an error |
| `fieldControlError` | `formie-input-error` | Invalid field control |
| `fieldErrors` | `formie-field-errors` | Field error collection |
| `fieldError` | `formie-field-error` | Individual field error |
