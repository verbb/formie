# Buttons

Buttons are the shared action surface for submit, back, save, and related form actions.

Use this page to inspect default button treatment, alignment behavior, and loading-state hooks.

## Preview

<FormiePreview src="../examples/buttons.preview.ts" />

## Button variants

<FormiePreview src="../examples/buttons-variants.preview.ts" />

The shipped theme includes these common button variants:

| Class | Purpose |
| --- | --- |
| `formie-button` | Base button styling |
| `formie-button-primary` | Primary action treatment |
| `formie-button-secondary` | Secondary action treatment |
| `formie-button-ghost` | Low-emphasis action treatment |
| `formie-button-icon` | Icon-only button treatment |

Action-role classes also exist for default ordering and layout:

- `formie-button-back`
- `formie-button-submit`
- `formie-button-save`

## Browser attributes

Useful button-level hooks include:

| Attribute | Purpose |
| --- | --- |
| `data-formie-action="submit"` | Submit action button |
| `data-formie-action="back"` | Back action button |
| `data-formie-action="save"` | Save action button |
| `data-formie-loading="true"` | Loading state marker |
| `data-formie-loading-indicator` | Loading indicator style metadata |
| `data-formie-loading-text` | Loading text metadata |

Group-level alignment uses:

| Attribute | Purpose |
| --- | --- |
| `data-formie-page-buttons` | Page button wrapper |
| `data-formie-buttons-position` | Alignment and split-layout contract |

Common position values include `left`, `right`, `center`, `left-right`, `save-right`, and `save-left`.

## Button positions

<FormiePreview src="../examples/buttons-positions.preview.ts" />

Button groups use `data-formie-buttons-position` to control alignment and save-button placement.

## Loading state

<FormiePreview src="../examples/buttons-loading.preview.ts" />

During an active submit cycle, Formie sets loading state on the active form and submitter. Keep those attributes intact if you replace button markup.

## Accessibility notes

- Buttons use `:focus-visible` styling from the theme token set.
- Loading buttons remain visually active but should continue to communicate action state clearly.
- Disabled buttons rely on reduced opacity plus disabled semantics.

## Related pages

- [Loading](/browser/ui-reference/components/loading)
- [CSS variables](/browser/ui-reference/css-variables)
- [Submission handling](/browser/behavior/submission-handling)
