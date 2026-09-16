# Submission Handling

Submission handling covers the browser-side flow around validation, stage processing, page submits, final submits, loading state, and provider follow-up work. This page is about understanding where Formie's submit lifecycle gives you extension points before you move down to lower-level API control.

## Submit Flow

Formie intercepts the normal browser submit flow and wraps it in a structured lifecycle.

On every submit attempt, Formie can:

- validate inputs
- evaluate conditions and stage logic
- update button and loading state
- move between pages on multi-page forms
- emit submit and stage events
- apply the submit result back into the mounted form

On multi-page forms, moving to the next page is still a submit attempt. That is why `formie:submit:*` events can fire on intermediate page submits, while `formie:submit:final:*` is reserved for the final form submission.

## Submit Lifecycle Events

Use [JavaScript events](/browser/behavior/javascript-events) when you want to intercept submit flow at the DOM-event level.

The most common events are:

- `formie:submit:before` before Formie starts handling a submit attempt
- `formie:stage:validate:before` and `formie:stage:validate:after` around validation
- `formie:submit:result` after Formie has applied the result back into the form

For example, to react to the final result:

```js
document.addEventListener('formie:submit:result', (event) => {
  if (event.detail?.ok) {
    console.log('Submission succeeded:', event.detail);
    return;
  }

  console.log('Submission did not complete successfully:', event.detail);
});
```

## Browser-Side Validation

If you need custom client-side validation before Formie continues with submission, register validators when the validator API is ready:

```js
document.addEventListener('formie:validator:ready', (event) => {
  const { validator } = event.detail;

  validator.addValidator('no-example-domain', ({ input, getRule }) => {
    if (!getRule('no-example-domain') || !input.value) {
      return true;
    }

    return !input.value.endsWith('@example.com');
  }, () => 'Example.com addresses are not allowed.');
});
```

This keeps your custom rule inside Formie's normal validation and error-display flow. Use [Build a custom validator](/browser/validation/build-a-custom-validator) for the full authoring pattern.

## Loading and Action State

During an active submit cycle, Formie updates the form and submit controls with loading state so you can react in CSS or surrounding UI.

Useful hooks include:

- `data-formie-loading="true"` on the form while submission is active
- `data-formie-loading="true"` on the active submitter
- `data-formie-loading-text` and `data-formie-loading-indicator` on the submitter when loading text/indicator state is applied

These are most useful for styling and UX adjustments, not for replacing Formie's submit pipeline.

## Multi-Page Submissions

On multi-page forms, submission handling also updates page and tab state as the user progresses.

Formie will:

- mark inactive pages with `data-formie-page-hidden`
- update current and completed tab state
- update progress UI when a next page becomes active

If you only need to respond to page changes, use the page lifecycle events on [JavaScript events](/browser/behavior/javascript-events) rather than treating every page step as a full custom submit flow.

## Payment and Provider Follow-up Actions

Some submissions can pause for payment-provider follow-up work such as a Stripe confirm step, an Opayo challenge, or an external redirect.

Those flows surface through the payment events documented on [JavaScript events](/browser/behavior/javascript-events), for example:

- `formie:payment:authorize:error`
- `formie:payment:stripe:confirm`
- `formie:payment:mollie:redirect`

## JavaScript API

If you need to mount, unmount, update, or re-initialise forms programmatically, move from event handling to [JavaScript API](/browser/).

## Start Here

- Use [JavaScript events](/browser/behavior/javascript-events) to understand which submit lifecycle event to hook into.
- Use [JavaScript API](/browser/) if you need to submit or manage mounted forms programmatically.

## Related Pages

- [Validation](/browser/validation/)
- [JavaScript events](/browser/behavior/javascript-events)
- [Manual initialisation](/browser/behavior/manual-initialization)
