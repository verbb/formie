# Form

Use this page when you are overriding Formie's rendered form wrapper instead of only styling the default output.

If you replace the default `<form>` tag or its immediate browser-facing data, keep these attributes intact:

| Attribute | Purpose | Importance |
| --- | --- | --- |
| `data-formie` | Primary Formie root discovery hook | Required |
| `data-formie-form` | Form root hook for browser behavior | Required |
| `data-formie-handle` | Form identity for browser loads and submits | Required |
| `data-formie-static-cache` | Static-cache behavior hint | Preserve when present |
| `data-formie-submit-method` | Submit transport behavior | Preserve when present |
| `data-formie-submit-action` | Submit action behavior | Preserve when present |
| `data-formie-submit-action-form-hide` | Post-submit form hiding behavior | Preserve when present |
| `data-formie-submit-action-message-timeout` | Success-message timing | Preserve when present |
| `data-formie-submit-action-message-position` | Success-message placement | Preserve when present |
| `data-formie-error-message` | Shared browser error message | Preserve when present |
| `data-formie-error-message-position` | Shared browser error message placement | Preserve when present |
| `data-formie-loading-indicator` | Loading indicator mode | Preserve when present |
| `data-formie-loading-indicator-text` | Loading label mode | Preserve when present |
| `data-formie-progress-calculation` | Progress UI calculation mode | Preserve when present |
| `data-formie-validation-on-focus` | Live-validation behavior | Preserve when present |
| `data-formie-validation-on-submit` | Submit-time validation behavior | Preserve when present |
| `data-formie-scroll-to-top` | Page-navigation scroll behavior | Preserve when present |
| `data-formie-clear-submission-endpoint` | Clear-submission follow-up endpoint | Preserve when present |
| `data-formie-modules` | Browser module manifest payload | Required when modules exist |
| `data-formie-theme` | Browser theme-class map | Required when theme classes are applied |

Formie already renders this for you. These attributes matter most when you fully replace the form wrapper in a custom theme.
