# Progress

The progress bar shows visitors how far they have moved through a multi-page form. Use [page navigation](/browser/ui-reference/components/page-navigation) when you also want to show individual steps.

Use this page to preserve the progress wrapper, bar, value, and placement hooks used by browser-managed multipage forms.

## Preview

<FormiePreview src="../examples/progress.preview.ts" />

## Browser Attributes

Useful progress hooks include:

| Hook | Purpose |
| --- | --- |
| `data-formie-progress-wrapper` | Progress component wrapper |
| `data-formie-progress-position` | Whether progress appears at the start or end |
| `data-formie-progress` | Progress track |
| `data-formie-progress-bar` | Active progress bar |
| `data-formie-progress-state` | Start, middle, or end visual state |
| `data-formie-progress-value` | Visible progress label |

## Styling Classes

| Class | Purpose |
| --- | --- |
| `formie-progress-wrapper` | Progress wrapper |
| `formie-progress` | Progress track |
| `formie-progress-bar` | Filled progress bar |
| `formie-progress-value` | Visible progress label |

## Calculation Modes

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
