# Validation

Formie uses its own browser-side validator so field rules, error rendering, multi-page state, and module-enhanced inputs all stay inside the same client-side form flow instead of falling back to native browser validation UI.

## What Formie validates

The validator:

- sets `novalidate` on the mounted form
- reads rule definitions from `data-formie-validation` on each field wrapper
- renders errors back into Formie's field and messages markup
- skips conditionally hidden fields
- skips hidden pages until the final submit pass
- skips controls marked `data-formie-validation-skip`
- treats checkbox and radio groups as one logical field so duplicate errors are not rendered

Use `data-formie-validation-skip` on helper controls that are not the field's value carrier — for example a file picker that is cleared after selection. Put the attribute on the control itself, not the field wrapper.

## When validation runs

On submit, Formie validates the active page first and validates hidden pages too on the final submit pass.

If the form is configured for live validation, Formie also validates while the user is interacting with the form:

```html
<form
  data-formie-form
  data-formie-validation-on-focus="true"
  data-formie-validation-on-submit="true"
>
  ...
</form>
```

After the first submit attempt, Formie keeps validation live while the user fixes errors even if the initial interaction mode was lighter.

## Rule payload shape

Rules live on the field wrapper as a JSON array. Each rule needs a `type`, and rules can carry extra options for that validator.

```html
<div
  data-formie-field-handle="email"
  data-formie-validation='[
    { "type": "required" },
    { "type": "email" }
  ]'
>
  ...
</div>
```

The validator turns that payload into a field-level rule map, so custom validators can inspect their own options with `getRule('your-rule-name')`.

## Validator lifecycle

When a form mounts, Formie emits `formie:validator:ready` with the validator instance in `event.detail.validator`.

```js
document.addEventListener('formie:validator:ready', (event) => {
  const { validator } = event.detail;

  console.log('Validator ready:', validator);
});
```

If you already have the form element, the same instance is also available as `form.formieValidation`.

## What to read next

- Use [Built-in rules](/browser/validation/built-in-rules) for the shipped rule names and payload shapes.
- Use [Build a custom validator](/browser/validation/build-a-custom-validator) to register your own rules.
- Use [JavaScript events](/browser/behavior/javascript-events) if you only need the event reference.

## Related pages

- [Built-in rules](/browser/validation/built-in-rules)
- [Build a custom validator](/browser/validation/build-a-custom-validator)
- [JavaScript events](/browser/behavior/javascript-events)
- [Submission handling](/browser/behavior/submission-handling)
