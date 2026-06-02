# UI reference

This section is the canonical visual and structural reference for the browser-owned Formie UI surface.

Use it to inspect how fields and shared components look, which classes and `data-formie-*` hooks they expose, and which CSS variables are intended for styling overrides.

This area is owned by `@verbb/formie-browser` because that package owns:

- the default rendered HTML structure
- the shared CSS bundles and theme classes
- browser state attributes such as loading, error, page, and tab markers
- field and component contracts used across Craft-rendered and browser-managed forms

Framework package docs should link back here when they are using the same browser-owned markup and styling contracts.

## In this section

- [Fields](/browser/ui-reference/fields/)
- [Single Line Text](/browser/ui-reference/fields/single-line-text)
- [Address](/browser/ui-reference/fields/address)
- [Components](/browser/ui-reference/components/)
- [Buttons](/browser/ui-reference/components/buttons)
- [Loading](/browser/ui-reference/components/loading)
- [CSS variables](/browser/ui-reference/css-variables)

## How to use these pages

Field and component pages are designed to answer four questions:

- what the default UI looks like
- which attributes are required for browser behavior
- which classes are optional styling hooks
- which CSS variables and browser states are safe to target

If you need browser lifecycle, module, or submit behavior, use the main Browser docs:

- [JavaScript events](/browser/behavior/javascript-events)
- [JavaScript API](/browser/)
- [Submission handling](/browser/behavior/submission-handling)
