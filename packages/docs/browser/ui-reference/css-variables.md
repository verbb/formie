# CSS variables

The browser package theme is primarily configured through `--formie-*` custom properties on `.formie-form`.

This page is exhaustive for the active root-level variable surface shipped in `packages/formie-browser/src/css/theme/_tokens.css`. Commented-out legacy variables are intentionally excluded.

Use these variables to restyle the shipped UI without changing browser behavior or replacing Formie's rendered markup.

## Variable categories

The shipped root variable surface covers:

- typography, spacing, radius, and border foundations
- palette scales and semantic color aliases
- form, message, button, navigation, progress, and loading layout
- field-level sizing and specialized field surfaces such as summary, signature, repeater, and table

## Foundations

| Variable | Default | Purpose |
| --- | --- | --- |
| `--formie-font-family` | `-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif` | Base typeface |
| `--formie-font-size-xs` | `0.75rem` | Extra-small text size |
| `--formie-font-size-sm` | `0.875rem` | Small text size |
| `--formie-font-size-base` | `1rem` | Default text size |
| `--formie-font-size-lg` | `1.125rem` | Large text size |
| `--formie-font-size-xl` | `1.375rem` | Extra-large text size |
| `--formie-font-size-2xl` | `1.75rem` | Display text size |
| `--formie-font-weight-normal` | `400` | Normal font weight |
| `--formie-font-weight-medium` | `500` | Medium font weight |
| `--formie-font-weight-semibold` | `600` | Semibold font weight |
| `--formie-font-weight-bold` | `700` | Bold font weight |
| `--formie-line-height-tight` | `1.25` | Tight line height |
| `--formie-line-height-base` | `1.5` | Default line height |
| `--formie-line-height-relaxed` | `1.4` | Relaxed meta/message line height |
| `--formie-letter-spacing-tight` | `-0.02em` | Tight heading letter spacing |
| `--formie-space-1` | `0.25rem` | Spacing scale step |
| `--formie-space-1-5` | `0.375rem` | Spacing scale step |
| `--formie-space-2` | `0.5rem` | Spacing scale step |
| `--formie-space-2-5` | `0.625rem` | Spacing scale step |
| `--formie-space-3` | `0.75rem` | Spacing scale step |
| `--formie-space-3-5` | `0.875rem` | Spacing scale step |
| `--formie-space-4` | `1rem` | Spacing scale step |
| `--formie-space-4-5` | `1.125rem` | Spacing scale step |
| `--formie-space-5` | `1.25rem` | Spacing scale step |
| `--formie-space-5-5` | `1.375rem` | Spacing scale step |
| `--formie-space-6` | `1.5rem` | Spacing scale step |
| `--formie-space-7` | `1.75rem` | Spacing scale step |
| `--formie-space-8` | `2rem` | Spacing scale step |
| `--formie-space-9` | `2.25rem` | Spacing scale step |
| `--formie-space-10` | `2.5rem` | Spacing scale step |
| `--formie-space-11` | `2.75rem` | Spacing scale step |
| `--formie-space-12` | `3rem` | Spacing scale step |
| `--formie-radius-sm` | `0.25rem` | Small radius |
| `--formie-radius-md` | `0.375rem` | Medium radius |
| `--formie-radius-lg` | `0.5rem` | Large radius |
| `--formie-radius-full` | `999px` | Fully rounded radius |
| `--formie-border-width` | `1px` | Default border width |

## Palette

| Variable | Default | Purpose |
| --- | --- | --- |
| `--formie-black` | `#000000` | Absolute black |
| `--formie-white` | `#ffffff` | Absolute white |
| `--formie-neutral-50` | `#f8fafc` | Neutral scale 50 |
| `--formie-neutral-100` | `#f1f5f9` | Neutral scale 100 |
| `--formie-neutral-200` | `#e2e8f0` | Neutral scale 200 |
| `--formie-neutral-300` | `#cbd5e1` | Neutral scale 300 |
| `--formie-neutral-400` | `#94a3b8` | Neutral scale 400 |
| `--formie-neutral-500` | `#64748b` | Neutral scale 500 |
| `--formie-neutral-600` | `#475569` | Neutral scale 600 |
| `--formie-neutral-700` | `#334155` | Neutral scale 700 |
| `--formie-neutral-800` | `#1e293b` | Neutral scale 800 |
| `--formie-neutral-900` | `#0f172a` | Neutral scale 900 |
| `--formie-neutral-950` | `#020617` | Neutral scale 950 |
| `--formie-primary-50` | `#e8ecfc` | Primary scale 50 |
| `--formie-primary-100` | `#d2d9f9` | Primary scale 100 |
| `--formie-primary-200` | `#a4b3f4` | Primary scale 200 |
| `--formie-primary-300` | `#778dee` | Primary scale 300 |
| `--formie-primary-400` | `#4967e9` | Primary scale 400 |
| `--formie-primary-500` | `#1c41e3` | Primary scale 500 |
| `--formie-primary-600` | `#1634b6` | Primary scale 600 |
| `--formie-primary-700` | `#112788` | Primary scale 700 |
| `--formie-primary-800` | `#0b1a5b` | Primary scale 800 |
| `--formie-primary-900` | `#060d2d` | Primary scale 900 |
| `--formie-primary-950` | `#040920` | Primary scale 950 |
| `--formie-danger-50` | `#fef2f2` | Danger scale 50 |
| `--formie-danger-100` | `#fee2e2` | Danger scale 100 |
| `--formie-danger-200` | `#fecaca` | Danger scale 200 |
| `--formie-danger-300` | `#fca5a5` | Danger scale 300 |
| `--formie-danger-400` | `#f87171` | Danger scale 400 |
| `--formie-danger-500` | `#ef4444` | Danger scale 500 |
| `--formie-danger-600` | `#dc2626` | Danger scale 600 |
| `--formie-danger-700` | `#b91c1c` | Danger scale 700 |
| `--formie-danger-800` | `#991b1b` | Danger scale 800 |
| `--formie-danger-900` | `#7f1d1d` | Danger scale 900 |
| `--formie-danger-950` | `#450a0a` | Danger scale 950 |
| `--formie-success-50` | `#f0fdf4` | Success scale 50 |
| `--formie-success-100` | `#dcfce7` | Success scale 100 |
| `--formie-success-200` | `#bbf7d0` | Success scale 200 |
| `--formie-success-300` | `#86efac` | Success scale 300 |
| `--formie-success-400` | `#4ade80` | Success scale 400 |
| `--formie-success-500` | `#22c55e` | Success scale 500 |
| `--formie-success-600` | `#16a34a` | Success scale 600 |
| `--formie-success-700` | `#15803d` | Success scale 700 |
| `--formie-success-800` | `#166534` | Success scale 800 |
| `--formie-success-900` | `#14532d` | Success scale 900 |
| `--formie-success-950` | `#052e16` | Success scale 950 |

## Semantic colors and focus

| Variable | Default | Purpose |
| --- | --- | --- |
| `--formie-color-background` | `var(--formie-white)` | Form background |
| `--formie-color-surface` | `var(--formie-white)` | Main component surface |
| `--formie-color-surface-subtle` | `var(--formie-neutral-50)` | Subtle surface fill |
| `--formie-color-surface-muted` | `var(--formie-neutral-100)` | Muted surface fill |
| `--formie-color-text` | `var(--formie-neutral-700)` | Default text color |
| `--formie-color-text-muted` | `var(--formie-neutral-500)` | Muted text color |
| `--formie-color-heading` | `var(--formie-neutral-900)` | Heading/high-emphasis color |
| `--formie-color-border` | `var(--formie-neutral-300)` | Default border color |
| `--formie-color-border-soft` | `var(--formie-neutral-200)` | Soft border color |
| `--formie-color-primary` | `var(--formie-primary-400)` | Primary action color |
| `--formie-color-primary-hover` | `var(--formie-primary-500)` | Hover primary color |
| `--formie-color-primary-border` | `var(--formie-primary-500)` | Primary border color |
| `--formie-color-primary-soft` | `var(--formie-primary-100)` | Soft primary tint |
| `--formie-color-focus-ring` | `var(--formie-primary-300)` | Focus-ring color |
| `--formie-color-danger` | `var(--formie-danger-500)` | Error/danger color |
| `--formie-color-danger-soft` | `var(--formie-danger-50)` | Soft danger tint |
| `--formie-color-danger-dark` | `var(--formie-danger-900)` | Dark danger text/accent |
| `--formie-color-success` | `var(--formie-success-500)` | Success color |
| `--formie-color-success-soft` | `var(--formie-success-50)` | Soft success tint |
| `--formie-color-success-dark` | `var(--formie-success-900)` | Dark success text/accent |
| `--formie-color-button-text` | `var(--formie-color-surface)` | High-contrast button text alias |
| `--formie-focus-ring-border-color` | `var(--formie-color-focus-ring)` | Focus border alias |
| `--formie-shadow-focus` | `0 0 0 3px rgba(119, 141, 238, 0.45)` | Default focus ring shadow |
| `--formie-shadow-danger-focus` | `0 0 0 3px rgba(248, 180, 180, 0.45)` | Error focus ring shadow |

## Form and messages

| Variable | Default | Purpose |
| --- | --- | --- |
| `--formie-title-form-size` | `1.4rem` | Form title size |
| `--formie-body-size` | `0.9375rem` | Base form body size |
| `--formie-gap-form` | `0` | Gap between top-level form regions |
| `--formie-gap-form-header` | `var(--formie-space-4)` | Header spacing |
| `--formie-gap-form-messages` | `var(--formie-space-4)` | Message stack spacing |
| `--formie-gap-form-navigation` | `var(--formie-space-4)` | Navigation spacing |
| `--formie-gap-form-body` | `0` | Body spacing |
| `--formie-gap-form-footer` | `var(--formie-space-4)` | Footer spacing |
| `--formie-message-padding` | `var(--formie-space-4)` | Message padding |
| `--formie-message-margin-bottom` | `var(--formie-space-4)` | Message bottom margin |
| `--formie-message-size` | `var(--formie-font-size-sm)` | Message text size |
| `--formie-message-line-height` | `var(--formie-line-height-relaxed)` | Message line height |

## Buttons and icons

| Variable | Default | Purpose |
| --- | --- | --- |
| `--formie-button-border` | `var(--formie-border-width) solid var(--formie-color-border)` | Base button border |
| `--formie-button-border-hover` | `var(--formie-button-secondary-border-hover)` | Base button hover border |
| `--formie-button-border-radius` | `var(--formie-radius-sm)` | Button radius |
| `--formie-button-background` | `var(--formie-neutral-100)` | Base button background |
| `--formie-button-background-hover` | `var(--formie-neutral-200)` | Base button hover background |
| `--formie-button-text-color` | `var(--formie-color-heading)` | Base button text color |
| `--formie-button-color` | `var(--formie-button-text-color)` | Shared button current color |
| `--formie-button-line-height` | `var(--formie-line-height-tight)` | Button line height |
| `--formie-button-font-weight` | `var(--formie-font-weight-medium)` | Button font weight |
| `--formie-button-min-height` | `var(--formie-space-10)` | Minimum button height |
| `--formie-button-padding-y` | `var(--formie-space-2)` | Vertical button padding |
| `--formie-button-padding-x` | `var(--formie-space-4)` | Horizontal button padding |
| `--formie-button-font-size` | `var(--formie-font-size-sm)` | Button font size |
| `--formie-button-gap` | `var(--formie-space-2)` | Button content gap |
| `--formie-button-icon-size` | `0.9375rem` | Button icon size |
| `--formie-button-icon-button-size` | `1.875rem` | Icon-only button size |
| `--formie-button-icon-border-radius` | `var(--formie-radius-full)` | Icon button radius |
| `--formie-button-icon-background` | `var(--formie-neutral-100)` | Icon button background |
| `--formie-button-icon-background-hover` | `var(--formie-neutral-200)` | Icon button hover background |
| `--formie-button-icon-border` | `var(--formie-border-width) solid var(--formie-neutral-300)` | Icon button border |
| `--formie-button-icon-border-hover` | `var(--formie-border-width) solid var(--formie-neutral-400)` | Icon button hover border |
| `--formie-button-icon-color` | `var(--formie-neutral-950)` | Icon button color |
| `--formie-button-opacity-disabled` | `0.7` | Disabled button opacity |
| `--formie-button-shadow-focus` | `0 0 0 3px var(--formie-color-border-soft)` | Button focus shadow |
| `--formie-icon-mask-plus` | `url(...)` | Add/plus icon mask |
| `--formie-icon-mask-arrow-left` | `url(...)` | Back arrow icon mask |
| `--formie-icon-mask-arrow-right` | `url(...)` | Forward arrow icon mask |
| `--formie-icon-mask-close` | `url(...)` | Close/remove icon mask |
| `--formie-button-primary-background` | `var(--formie-color-primary)` | Primary button background |
| `--formie-button-primary-background-hover` | `var(--formie-color-primary-hover)` | Primary button hover background |
| `--formie-button-primary-text-color` | `var(--formie-white)` | Primary button text color |
| `--formie-button-primary-border` | `var(--formie-border-width) solid transparent` | Primary button border |
| `--formie-button-primary-border-hover` | `var(--formie-border-width) solid var(--formie-color-primary-hover)` | Primary button hover border |
| `--formie-button-primary-shadow-focus` | `0 0 0 3px var(--formie-primary-300)` | Primary button focus shadow |
| `--formie-button-secondary-border` | `var(--formie-border-width) solid var(--formie-color-border)` | Secondary button border |
| `--formie-button-secondary-border-hover` | `var(--formie-button-secondary-border)` | Secondary button hover border |
| `--formie-button-secondary-background` | `var(--formie-color-surface)` | Secondary button background |
| `--formie-button-secondary-background-hover` | `var(--formie-neutral-100)` | Secondary button hover background |
| `--formie-button-secondary-text-color` | `var(--formie-color-heading)` | Secondary button text color |
| `--formie-button-ghost-border` | `var(--formie-border-width) solid transparent` | Ghost button border |
| `--formie-button-ghost-border-hover` | `var(--formie-button-ghost-border)` | Ghost button hover border |
| `--formie-button-ghost-background` | `transparent` | Ghost button background |
| `--formie-button-ghost-background-hover` | `var(--formie-neutral-100)` | Ghost button hover background |
| `--formie-button-ghost-text-color` | `var(--formie-color-heading)` | Ghost button text color |
| `--formie-button-ghost-shadow-focus` | `var(--formie-button-shadow-focus)` | Ghost button focus shadow |
| `--formie-button-link-text-color` | `var(--formie-color-primary)` | Link-style button color |
| `--formie-button-link-text-color-hover` | `var(--formie-color-primary-hover)` | Link-style button hover color |

## Navigation, progress, loading, and page layout

| Variable | Default | Purpose |
| --- | --- | --- |
| `--formie-tab-padding-y` | `var(--formie-space-2)` | Page tab vertical padding |
| `--formie-tab-padding-x` | `var(--formie-space-4)` | Page tab horizontal padding |
| `--formie-tab-font-size` | `var(--formie-font-size-sm)` | Page tab font size |
| `--formie-gap-tabs` | `var(--formie-space-4)` | Gap below tab row |
| `--formie-progress-height` | `1.2rem` | Progress bar height |
| `--formie-progress-padding` | `var(--formie-space-4)` | Progress wrapper padding |
| `--formie-progress-size` | `0.8rem` | Progress text size |
| `--formie-loading-size` | `var(--formie-space-4)` | Spinner size |
| `--formie-loading-margin-top` | `calc(var(--formie-loading-size) * -0.5)` | Spinner top offset |
| `--formie-loading-margin-left` | `calc(var(--formie-loading-size) * -0.5)` | Spinner left offset |
| `--formie-loading-border-width` | `2px` | Spinner stroke width |
| `--formie-loading-animation` | `loading 0.5s infinite linear` | Spinner animation |
| `--formie-loading-left` | `50%` | Spinner horizontal anchor |
| `--formie-loading-top` | `50%` | Spinner vertical anchor |
| `--formie-loading-z-index` | `1` | Spinner stacking order |
| `--formie-gap-pages` | `0` | Gap between pages |
| `--formie-gap-page` | `var(--formie-space-4)` | Gap inside page wrapper |
| `--formie-gap-page-container` | `0` | Gap inside page container |
| `--formie-gap-page-header` | `var(--formie-space-4)` | Page header gap |
| `--formie-gap-page-body` | `var(--formie-space-4)` | Page body gap |
| `--formie-gap-page-footer` | `var(--formie-space-4)` | Page footer gap |
| `--formie-gap-page-buttons` | `var(--formie-space-4)` | Button group gap |
| `--formie-title-page-size` | `var(--formie-font-size-lg)` | Page title size |

## Rows, fields, and shared field surfaces

| Variable | Default | Purpose |
| --- | --- | --- |
| `--formie-gap-rows` | `var(--formie-space-4)` | Gap between row groups |
| `--formie-gap-row` | `var(--formie-space-4)` | Gap inside a row |
| `--formie-gap-subfield-rows` | `var(--formie-space-2)` | Gap between subfield rows |
| `--formie-gap-subfield-row` | `var(--formie-space-2)` | Gap inside a subfield row |
| `--formie-gap-nested-field-rows` | `var(--formie-space-2)` | Gap between nested field rows |
| `--formie-gap-nested-field-row` | `var(--formie-space-2)` | Gap inside a nested field row |
| `--formie-subfield-row-column-min-width` | `12rem` | Minimum subfield column width |
| `--formie-nested-field-row-column-min-width` | `16rem` | Minimum nested field column width |
| `--formie-gap-errors` | `var(--formie-space-2)` | Gap between form/page errors |
| `--formie-gap-field-errors` | `var(--formie-space-2)` | Gap between field errors |
| `--formie-label-size` | `var(--formie-font-size-sm)` | Field label size |
| `--formie-meta-size` | `var(--formie-font-size-sm)` | Meta/instructions size |
| `--formie-control-height` | `2.375rem` | Standard control height |
| `--formie-control-padding-y` | `var(--formie-space-2)` | Control vertical padding |
| `--formie-control-padding-x` | `var(--formie-space-3)` | Control horizontal padding |
| `--formie-control-font-size` | `var(--formie-font-size-sm)` | Control font size |
| `--formie-textarea-min-height` | `9rem` | Textarea minimum height |
| `--formie-select-indicator-size` | `1.4rem` | Select indicator size |
| `--formie-list-indent` | `var(--formie-space-5)` | Indented list spacing |
| `--formie-link-underline-offset` | `0.15em` | Link underline offset |
| `--formie-gap-field` | `var(--formie-space-2)` | Field wrapper gap |
| `--formie-gap-field-layout` | `var(--formie-space-2)` | Field layout gap |
| `--formie-gap-field-content` | `var(--formie-space-2)` | Field content gap |
| `--formie-gap-field-control` | `var(--formie-space-2)` | Field control gap |
| `--formie-gap-options` | `var(--formie-space-2)` | Option list gap |

## Field-specific variables

| Variable | Default | Purpose |
| --- | --- | --- |
| `--formie-summary-padding` | `var(--formie-space-4)` | Summary block padding |
| `--formie-gap-summary` | `var(--formie-space-4)` | Gap inside summary blocks |
| `--formie-file-summary-padding` | `var(--formie-space-4)` | File summary padding |
| `--formie-gap-file-summary` | `var(--formie-space-3)` | Gap inside file summary |
| `--formie-rich-text-min-height` | `12rem` | Rich text editor minimum height |
| `--formie-signature-width` | `100%` | Signature canvas width |
| `--formie-signature-height` | `8rem` | Signature canvas height |
| `--formie-signature-background` | `var(--formie-color-surface-subtle)` | Signature canvas background |
| `--formie-signature-border` | `1px solid var(--formie-color-border)` | Signature canvas border |
| `--formie-signature-border-radius` | `var(--formie-radius-sm)` | Signature canvas radius |
| `--formie-signature-remove-button-top` | `0` | Signature remove button top offset |
| `--formie-signature-remove-button-right` | `-14px` | Signature remove button right offset |
| `--formie-signature-remove-button-transform` | `translate(0, -50%)` | Signature remove button transform |
| `--formie-check-font-size` | `var(--formie-font-size-sm)` | Checkbox/radio text size |
| `--formie-check-line-height` | `var(--formie-line-height-base)` | Checkbox/radio line height |
| `--formie-check-margin-bottom` | `var(--formie-space-2)` | Checkbox/radio bottom margin |
| `--formie-check-margin-right` | `var(--formie-space-4)` | Checkbox/radio right margin |
| `--formie-check-background-color` | `var(--formie-color-surface-muted)` | Checkbox/radio control background |
| `--formie-check-size` | `var(--formie-space-4)` | Checkbox/radio control size |
| `--formie-check-label-padding-left` | `var(--formie-space-6)` | Checkbox/radio label left padding |
| `--formie-check-label-line-height` | `var(--formie-space-6)` | Checkbox/radio label line height |
| `--formie-check-label-top` | `0.3125rem` | Checkbox/radio label top offset |
| `--formie-check-label-transition` | `all 0.15s cubic-bezier(0.4, 0, 0.2, 1)` | Checkbox/radio label transition |
| `--formie-check-label-background-color` | `var(--formie-color-surface)` | Checkbox/radio label background |
| `--formie-check-check-border-radius` | `2px` | Checkbox check radius |
| `--formie-check-check-background-image` | `url(...)` | Checkbox check icon |
| `--formie-check-check-background-size` | `8px auto` | Checkbox check icon size |
| `--formie-check-radio-border-radius` | `50%` | Radio indicator radius |
| `--formie-check-radio-background-image` | `url(...)` | Radio selected icon |
| `--formie-check-radio-background-size` | `8px auto` | Radio selected icon size |
| `--formie-group-border` | `1px solid var(--formie-color-border)` | Group field border |
| `--formie-group-border-radius` | `var(--formie-radius-sm)` | Group field radius |
| `--formie-group-padding` | `var(--formie-space-4)` | Group field padding |
| `--formie-repeater-add-button-padding-left` | `var(--formie-space-8)` | Repeater add button left padding |
| `--formie-repeater-add-button-icon-mask` | `var(--formie-icon-mask-plus)` | Repeater add icon mask |
| `--formie-repeater-add-button-height` | `14px` | Repeater add icon height |
| `--formie-repeater-add-button-width` | `14px` | Repeater add icon width |
| `--formie-repeater-add-button-left` | `var(--formie-space-3)` | Repeater add icon left offset |
| `--formie-repeater-remove-button-top` | `0` | Repeater remove button top offset |
| `--formie-repeater-remove-button-right` | `-14px` | Repeater remove button right offset |
| `--formie-repeater-remove-button-transform` | `translate(0, -50%)` | Repeater remove button transform |
| `--formie-table-width` | `100%` | Table width |
| `--formie-table-margin-bottom` | `1rem` | Table bottom margin |
| `--formie-table-border-collapse` | `collapse` | Table border-collapse mode |
| `--formie-table-row-padding` | `0.2rem` | Table row cell padding |
| `--formie-table-th-text-align` | `inherit` | Table heading alignment |
| `--formie-table-th-font-size` | `0.75rem` | Table heading font size |
| `--formie-table-th-font-weight` | `600` | Table heading font weight |
| `--formie-table-add-button-padding-left` | `var(--formie-space-8)` | Table add button left padding |
| `--formie-table-add-button-icon-mask` | `var(--formie-icon-mask-plus)` | Table add icon mask |
| `--formie-table-add-button-height` | `14px` | Table add icon height |
| `--formie-table-add-button-width` | `14px` | Table add icon width |
| `--formie-table-add-button-left` | `var(--formie-space-3)` | Table add icon left offset |
| `--formie-table-remove-button-top` | `0` | Table remove button top offset |
| `--formie-table-remove-button-right` | `-14px` | Table remove button right offset |
| `--formie-table-remove-button-transform` | `translate(0, -50%)` | Table remove button transform |

## Override example

```css
.marketing-signup .formie-form {
  --formie-color-primary: #0f766e;
  --formie-color-primary-hover: #115e59;
  --formie-color-heading: #0f172a;
  --formie-color-border: #cbd5e1;
  --formie-button-border-radius: 999px;
  --formie-loading-size: 1.25rem;
  --formie-textarea-min-height: 12rem;
}
```

## Override guidance

- Prefer CSS variable overrides before replacing component classes.
- Scope overrides to a wrapper when only one form family needs a different treatment.
- Keep semantic variables aligned with interaction state, especially focus, error, and success colors.
- Use this page as the root variable reference; component-local variables that are introduced inside specific selectors are documented on their component pages when relevant.

## Related pages

- [Buttons](/browser/ui-reference/components/buttons)
- [Loading](/browser/ui-reference/components/loading)
- [Single Line Text](/browser/ui-reference/fields/single-line-text)
