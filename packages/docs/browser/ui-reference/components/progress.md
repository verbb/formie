# Progress

Progress is the shared multipage completion surface that communicates how far a visitor has moved through the current form flow.

Use this page to preserve the progress wrapper, bar, value, and placement hooks used by browser-managed multipage forms.

## Preview

<FormiePreview src="../examples/progress.preview.ts" />

## Browser attributes

Useful progress hooks include:

| Hook | Purpose |
| --- | --- |
| `data-formie-progress-wrapper` | Progress component wrapper |
| `data-formie-progress-position` | Start or end placement contract |
| `data-formie-progress` | Progress track |
| `data-formie-progress-bar` | Active progress bar |
| `data-formie-progress-state` | Start, middle, or end visual state |
| `data-formie-progress-value` | Visible progress label |

## Styling classes

| Class | Purpose |
| --- | --- |
| `formie-progress-wrapper` | Progress wrapper |
| `formie-progress` | Progress track |
| `formie-progress-bar` | Filled progress bar |
| `formie-progress-value` | Visible progress label |

## Calculation modes

The form builder’s **Page Progress Calculation** setting controls the percentage shown in the same progress markup.

| Mode | Meaning |
| --- | --- |
| **Completion** | Progress reflects how much has been completed before the current page. |
| **Page position** | Progress reflects where the current page sits in the full page sequence. |

For a four-page form, the values differ like this:

| Current page | Completion | Page position |
| --- | ---: | ---: |
| Page 1 | 0% | 25% |
| Page 2 | 25% | 50% |
| Page 3 | 50% | 75% |
| Page 4 | 75% | 100% |

## Related pages

- [Page navigation](/browser/ui-reference/components/page-navigation)
- [Submission handling](/browser/behavior/submission-handling)
- [CSS variables](/browser/ui-reference/css-variables)
