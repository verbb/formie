# Build a custom validator

If the built-in rules are not enough, register your own validator when Formie mounts a form. Each mounted form gets its own validator instance, exposed through `formie:validator:ready`.

## 1. Add the rule to your field markup

Custom validators still use the same `data-formie-validation` payload as the built-in rules.

### Direct markup

```html
<div
  data-formie-field-handle="workEmail"
  data-formie-validation='[
    { "type": "company-email", "domain": "verbb.io" }
  ]'
>
  ...
</div>
```

### Custom PHP field

If you are building a custom field in PHP, the normal pattern is to add browser validation rules from `defineValidationRules()`. Formie's field wrapper will then emit `data-formie-validation` for you automatically.

```php
protected function defineValidationRules(): array
{
    $validators = parent::defineValidationRules();
    $validators[] = ['type' => 'company-email'];

    return $validators;
}
```

That is the same pattern Formie's own fields use for rules like `email` and `number`.

If your custom validator needs extra payload options beyond Formie's standard normalized keys, override `validationRules()` instead. The default normalization currently only keeps `type`, `fieldId`, `fieldHandle`, `min`, and `max`.

```php
public function validationRules(): array
{
    return array_merge(parent::validationRules(), [
        [
            'type' => 'company-email',
            'domain' => 'verbb.io',
        ],
    ]);
}
```

## 2. Register the validator

```js
document.addEventListener('formie:validator:ready', (event) => {
  const { validator } = event.detail;

  validator.addValidator(
    'company-email',
    ({ input, getRule }) => {
      const rule = getRule('company-email');

      // Every validator runs across Formie's validation inputs, so opt out early
      // when this field does not declare the custom rule.
      if (!rule || !input.value) {
        return true;
      }

      const domain = rule !== true && typeof rule === 'object' && typeof rule.domain === 'string'
        ? rule.domain
        : 'example.com';

      return input.value.toLowerCase().endsWith(`@${domain.toLowerCase()}`);
    },
    ({ label, getRule }) => {
      const rule = getRule('company-email');
      const domain = rule !== true && rule && typeof rule === 'object' && typeof rule.domain === 'string'
        ? rule.domain
        : 'example.com';

      return `${label} must use an @${domain} address.`;
    },
  );
});
```

## 3. Use the validation context

Your validator callback receives a context object with:

- `input` for the current input element
- `label` for the field label text
- `field` for the `[data-formie-field-handle]` wrapper
- `form` for the mounted form
- `rules` for the parsed field rules
- `getRule(name)` for looking up one rule by name
- `t(message, replacements)` for translated messages

In practice, `getRule()` is usually the most important part because it lets one validator read its own payload options.

## Replace or remove a rule

If you need to swap out behavior for a mounted form, register the same name again or remove it:

```js
document.addEventListener('formie:validator:ready', (event) => {
  const { validator } = event.detail;

  validator.removeValidator('company-email');
});
```

## Tips

- Bail out early when your rule is not declared on the current field.
- Put rule options into the `data-formie-validation` JSON instead of hard-coding them in JavaScript.
- Resolve related fields by Formie field handle for consistency with the rest of Formie's browser behavior.

## Related pages

- [Overview](/browser/validation/)
- [Built-in rules](/browser/validation/built-in-rules)
- [JavaScript events](/browser/behavior/javascript-events)
- [Submission handling](/browser/behavior/submission-handling)
