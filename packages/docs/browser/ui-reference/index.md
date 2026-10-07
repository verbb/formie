# UI Reference

Use this reference when styling Formie’s default markup or writing a template override.

Use it to inspect how fields and shared components look, which classes and `data-formie-*` hooks they expose, and which CSS variables are intended for styling overrides.

The pages cover the HTML and styles used with `@verbb/formie-browser`:

- the default rendered HTML structure
- the shared CSS bundles and theme classes
- browser state attributes such as loading, error, page, and tab markers
- field and component markup required for browser behaviour

These styles also apply to server-rendered forms loaded through the React, Vue and Web Components packages.

## In This Section

- [Fields](/browser/ui-reference/fields/)
- [Single Line Text](/browser/ui-reference/fields/single-line-text)
- [Address](/browser/ui-reference/fields/address)
- [Components](/browser/ui-reference/components/)
- [Buttons](/browser/ui-reference/components/buttons)
- [Loading](/browser/ui-reference/components/loading)
- [CSS variables](/browser/ui-reference/css-variables)

## How to Use These Pages

Field and component pages are designed to answer four questions:

- what the default UI looks like
- which attributes are required for browser behaviour
- which classes are optional styling hooks
- which CSS variables and browser states are safe to target

If you need browser lifecycle, module, or submit behaviour, use the main Browser docs:

- [JavaScript events](/browser/behavior/javascript-events)
- [JavaScript API](/browser/)
- [Submission handling](/browser/behavior/submission-handling)
