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

## Show a field error manually

When custom code already knows which input should be invalid, use the validator's `showError()` method instead of building error markup by hand.

```js
document.addEventListener('formie:validator:ready', (event) => {
  const { validator } = event.detail;
  const form = validator.form;
  const input = form.querySelector('[name="fields[zipCode]"]');

  if (input instanceof HTMLInputElement) {
    validator.showError(input, 'service-area', 'Sorry, that ZIP code is outside our service area.');
  }
});
```

`showError()` adds Formie's normal error attributes, message markup, theme classes, and page-tab error state for the field.

## Run a remote check before submit

Validator callbacks are synchronous. If your validation needs to call a controller action or another API, mount the form from your own bundle and use the mounted instance event bus. Instance event handlers are awaited by the submit pipeline, so they can update validation state before Formie's normal validation pass runs.

```js
import { formie } from '@verbb/formie-browser';
import '@verbb/formie-browser/css/formie.css';

await formie({
  element: '#contact-form',
  onReady(instance) {
    const form = instance.root instanceof HTMLFormElement
      ? instance.root
      : instance.root.querySelector('form');

    const validator = form?.formieValidation;

    if (!form || !validator) {
      return;
    }

    const serviceArea = {
      value: '',
      allowed: true,
      message: 'Sorry, that ZIP code is outside our service area.',
    };

    validator.addValidator(
      'service-area',
      ({ input }) => {
        if (!(input instanceof HTMLInputElement) || input.name !== 'fields[zipCode]' || !input.value) {
          return true;
        }

        // Until the submit-stage hook has checked this exact value, do not block live validation.
        return serviceArea.value !== input.value || serviceArea.allowed;
      },
      () => serviceArea.message,
    );

    instance.on('formie:stage:validate:before', async (stage) => {
      const input = form.querySelector('[name="fields[zipCode]"]');

      if (!(input instanceof HTMLInputElement) || !input.value) {
        return;
      }

      const response = await fetch(`/actions/site/check-service-area?zip=${encodeURIComponent(input.value)}`);
      const result = await response.json();

      serviceArea.value = input.value;
      serviceArea.allowed = result.allowed;
      serviceArea.message = result.message || serviceArea.message;
    });
  },
});
```

Use the stage payload's `formData` when your remote check needs the submitted values exactly as Formie will send them.

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
- Mark helper controls that are not value carriers with `data-formie-validation-skip` instead of special-casing them in a rule. The validator will not collect or live-validate those inputs.

## Related pages

- [Overview](/browser/validation/)
- [Built-in rules](/browser/validation/built-in-rules)
- [JavaScript events](/browser/behavior/javascript-events)
- [Submission handling](/browser/behavior/submission-handling)
