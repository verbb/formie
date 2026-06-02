# Built-in rules

Formie's core validator registry currently ships five rule names: `required`, `email`, `url`, `number`, and `match`.

This page covers the validator registry only. Some field-specific constraints, such as upload limits, checkbox max selections, or provider-specific checks, are enforced by field modules rather than this shared validator registry.

## `required`

Marks the field as mandatory.

```html
<div
  data-formie-field-handle="fullName"
  data-formie-validation='[
    { "type": "required" }
  ]'
>
  ...
</div>
```

For checkbox and radio groups, the rule passes as soon as any enabled option in the group is checked.

If you need a custom message, use `data-formie-required-message` on the input.

## `email`

Validates the input as an email address.

```html
<div
  data-formie-field-handle="email"
  data-formie-validation='[
    { "type": "email" }
  ]'
>
  ...
</div>
```

If you need a custom message, use `data-formie-pattern-email-message` on the input.

## `url`

Validates the input as a URL.

```html
<div
  data-formie-field-handle="website"
  data-formie-validation='[
    { "type": "url" }
  ]'
>
  ...
</div>
```

If you need a custom message, use `data-formie-pattern-url-message` on the input.

## `number`

Validates the value as a number and can also enforce `min` and `max` bounds from the rule payload.

```html
<div
  data-formie-field-handle="guestCount"
  data-formie-validation='[
    { "type": "number", "min": 1, "max": 10 }
  ]'
>
  ...
</div>
```

When `min` or `max` is present, Formie generates the matching range message automatically. Otherwise you can override the default message with `data-formie-pattern-number-message`.

## `match`

Checks that the field matches another field by Formie field handle.

```html
<div
  data-formie-field-handle="confirmPassword"
  data-formie-validation='[
    { "type": "match", "fieldHandle": "password" }
  ]'
>
  ...
</div>
```

`match` resolves by `fieldHandle`, not raw input `name`, so it stays aligned with the rest of Formie's field-level DOM contract.

## Related pages

- [Overview](/browser/validation/)
- [Build a custom validator](/browser/validation/build-a-custom-validator)
- [Submission handling](/browser/behavior/submission-handling)
