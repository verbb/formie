# Validation

Formie uses its own browser-side validator so field rules, error rendering, multi-page state, and module-enhanced inputs all stay inside the same client-side form flow instead of falling back to native browser validation UI.

For custom PHP rules, browser definitions, and JavaScript registration, see [Validation](https://verbb.io/craft-plugins/formie/docs/v4/developers/validation). This page describes the browser validator’s interaction settings and payload.

Use `validator.addValidator()` when your browser logic decides whether a field’s answer is acceptable. Formie already creates the validator for each mounted form; register your rule on that instance. Submission hooks coordinate work around a submit attempt. Their `abort(message)` method stops submission with a form-level message, without registering a field rule or associating the message with an input.

For a format check on an ordinary text field, start with [Add a Check to an Existing Field](https://verbb.io/craft-plugins/formie/docs/v4/developers/validation#add-a-check-to-an-existing-field). Validator callbacks are synchronous; remote checks must be enforced in PHP and returned as field errors. The [extension guidance](https://verbb.io/craft-plugins/formie/docs/v4/developers/validation#choose-how-to-add-a-check) explains the choice between field rules and submission hooks.

## What Formie Validates

The validator:

- sets `novalidate` on the mounted form
- reads rule definitions from `data-formie-validation` on each field wrapper
- renders errors back into Formie's field and messages markup
- skips conditionally hidden fields
- skips hidden pages until the final submit pass
- skips controls marked `data-formie-validation-skip`
- treats checkbox and radio groups as one logical field so duplicate errors are not rendered

Use `data-formie-validation-skip` on helper controls that are not the field's value carrier — for example a file picker that is cleared after selection. Put the attribute on the control itself, not the field wrapper.

## When Validation Runs

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

## Rule Payload Shape

The [built-in rules](/browser/validation/built-in-rules) describe the available rule names and options. Rules live on the field wrapper as a JSON array. Each rule needs a `type`, and rules can carry extra options for that validator.

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

## Validator Lifecycle

When a form mounts, Formie emits `formie:validator:ready` with the validator instance in `event.detail.validator`.

```js
document.addEventListener('formie:validator:ready', (event) => {
  const { validator } = event.detail;

  console.log('Validator ready:', validator);
});
```

If you already have the form element, the same instance is also available as `form.formieValidation`.

For other lifecycle hooks, see [JavaScript Events](/browser/behavior/javascript-events). The [submission handling flow](/browser/behavior/submission-handling) explains how validation fits into a submit attempt.
