# Loading

Loading is the shared in-progress treatment used for submit buttons and standalone async surfaces.

Use this page to inspect the default spinner treatment and the browser attributes behind button loading.

## Standalone Loading

<FormiePreview src="../examples/loading.preview.ts" />

Standalone loading indicators use the same `formie-loading` class as loading buttons and can be reused in custom async surfaces that still follow the browser theme.

## Button Loading

<FormiePreview src="../examples/loading-buttons.preview.ts" />

Formie applies loading state during submission by marking the active form and submitter. The spinner is theme-driven rather than requiring separate DOM markup.

## Sizes and Colours

<FormiePreview src="../examples/loading-sizes-colors.preview.ts" />

Use `--formie-loading-size` and `--formie-loading-color` when a custom async surface needs a different loading treatment.

## Button Variants

<FormiePreview src="../examples/loading-button-variants.preview.ts" />

Loading states use the same mechanics across default, primary, secondary and ghost buttons.

## Browser Hooks

| Hook | Purpose |
| --- | --- |
| `.formie-loading` | Shared loading treatment class |
| `data-formie-loading="true"` | Loading-state marker on form or submitter |
| `data-formie-loading-indicator` | Loading indicator style metadata |
| `data-formie-loading-text` | Loading text metadata on submitters |

## Accessibility Notes

- Standalone indicators should use `role="status"` or equivalent surrounding copy when they need to announce activity.
- Buttons in loading state should still communicate their current action context.
- Loading treatment is visual only; disabled and submit lifecycle state should still come from real form behaviour.

## Related Pages

- [Buttons](/browser/ui-reference/components/buttons)
- [CSS variables](/browser/ui-reference/css-variables)
- [Submission handling](/browser/behavior/submission-handling)
