# Page Navigation

Page navigation shows the current and completed pages of a multi-page form and lets visitors move between available pages.

Use this page to preserve the tab and page-state hooks that multipage forms rely on as users move through steps.

## Preview

<FormiePreview src="../examples/page-navigation-only.preview.ts" />

## Browser Attributes

Useful hooks include:

| Hook | Purpose |
| --- | --- |
| `data-formie-tab` | Page-tab item |
| `data-formie-tab-link` | Tab link |
| `data-formie-tab-complete` | Completed tab state |
| `data-formie-tab-error` | Error tab state |
| `data-formie-page-hidden` | Hidden inactive page marker |
| `data-formie-page` | Page section marker |
| `data-formie-page-id` | Stable page identity |

## Styling Classes

| Class | Purpose |
| --- | --- |
| `formie-page-tabs` | Tab collection |
| `formie-tab` | Individual tab |
| `formie-tab-current` | Current tab state |
| `formie-tab-complete` | Completed tab state |
| `formie-tab-error` | Error tab state |
| `formie-page-hidden` | Hidden page styling fallback |

<span id="behavior"></span>

## Behaviour

As visitors submit pages and move through the form:

- the active page receives current-state treatment
- completed steps receive `formie-tab-complete`
- invalid future or review steps can receive `formie-tab-error`
- inactive pages are hidden with `data-formie-page-hidden`

For the lifecycle side of these transitions, use [JavaScript events](/browser/behavior/javascript-events) and [Submission handling](/browser/behavior/submission-handling).
