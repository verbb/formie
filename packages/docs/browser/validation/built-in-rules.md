# Built-in Rules

Formie's core validator registry currently ships six rule names: `required`, `email`, `url`, `number`, `match`, and `dateParts`.

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

## `dateParts`

Checks a Date/Time field that uses separate inputs or dropdowns for its parts against its `minDate` and `maxDate`. Formie adds this rule for the **Text Inputs** and **Dropdowns** display types.

```html
<div
  data-formie-field-handle="startDate"
  data-formie-validation='[
    {
      "type": "dateParts",
      "minDate": "2026-12-01T00:00:00",
      "messages": { "dateMin": "The date must be on or after Dec 1, 2026." }
    }
  ]'
>
  ...
</div>
```

The rule sits on the Date/Time field's wrapper, while each part is a nested field with its own input. It runs once the year, month and day are all filled in, and reads `minDate` and `maxDate` as wall-clock times. The message shows under the last date part, and uses `messages.dateMin` or `messages.dateMax` from the rule. Changing any part checks the whole date again. An impossible date such as 31 February is left to the server, and so is a date an edited submission already has, when the form has a `submissionId` input and the parts still hold their rendered values.

When the built-in rules do not cover your requirement, follow [Validation](https://verbb.io/craft-plugins/formie/docs/v4/developers/validation) to implement and test a matching PHP and JavaScript rule. The [browser validation overview](/browser/validation/) explains when the rules run and how their payload is read.
