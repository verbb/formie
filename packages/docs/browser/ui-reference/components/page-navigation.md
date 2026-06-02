# Page Navigation

Page navigation is the shared multipage UI surface for page tabs, current step treatment, completed-step treatment, hidden future pages, and page-level navigation actions.

Use this page to preserve the tab and page-state hooks that multipage forms rely on as users move through steps.

## Preview

<FormiePreview src="../examples/page-navigation-only.preview.ts" />

## Browser attributes

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

## Styling classes

| Class | Purpose |
| --- | --- |
| `formie-page-tabs` | Tab collection |
| `formie-tab` | Individual tab |
| `formie-tab-current` | Current tab state |
| `formie-tab-complete` | Completed tab state |
| `formie-tab-error` | Error tab state |
| `formie-page-hidden` | Hidden page styling fallback |

## Behavior

These surfaces are updated by the broader multipage submit and page-navigation flow:

- the active page receives current-state treatment
- completed steps receive `formie-tab-complete`
- invalid future or review steps can receive `formie-tab-error`
- inactive pages are hidden with `data-formie-page-hidden`

For the lifecycle side of these transitions, use [JavaScript events](/browser/behavior/javascript-events) and [Submission handling](/browser/behavior/submission-handling).

## Related pages

- [Progress](/browser/ui-reference/components/progress)
- [Buttons](/browser/ui-reference/components/buttons)
- [Submission handling](/browser/behavior/submission-handling)
