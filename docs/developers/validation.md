# Validation

Formie validates submitted field values in PHP and provides matching browser rules for immediate feedback. PHP determines whether a submission can proceed; JavaScript checks the same requirements as visitors interact with the form. Keep accepted values, empty-value handling, and messages consistent across both.

Use the field’s settings for requirements already supported by Formie, such as required answers, email addresses, and number ranges. A custom requirement combines a submission validator, a browser rule definition, and a JavaScript validator. The [built-in browser rules](https://docs.verbb.io/formie/browser/validation/built-in-rules) list the rules available without custom JavaScript.

::: tip
For a complete implementation, follow [Adding a Custom Validation Rule](/guides/developers/adding-a-custom-validation-rule). The guide builds a Booking Reference field, registers both validators, and tests them together.
:::

## When Validation Runs

Formie applies defaults and submitted values, evaluates [conditions](/developers/conditions), clears hidden or disabled values, and resolves the visible-page transition before validating the applicable fields. Validation failures stop processing before CAPTCHA, content-spam screening, persistence or page advancement. Request-integrity and abuse checks remain separate from field validation.

| Operation | Validation Scope |
| --- | --- |
| Back, Save Draft | No field validation. |
| Advance | Current visible page. |
| Completion | All visible pages. |
| Control panel, Revise | Complete submission, subject to the trusted visibility policy. |

The browser can check values as visitors interact and before submitting, according to the form’s validation settings. PHP checks submitted answers even when browser validation is unavailable or bypassed. A rejected forward transition leaves the visitor on the current page.

## Submission Rules and Browser Definitions

Use `getElementValidationRules()` to validate a visitor’s submitted field value. It returns Yii validator definitions, with the validator class first; Formie supplies the field attribute when applying the rule. Keep the parent rules to retain inherited validation. `defineRules()` instead validates the field model’s settings, such as configuration entered in the form builder.

Pair a custom submission rule with `defineBrowserValidationRules()`. Each browser definition has a `type` identifying the JavaScript validator and any options it needs. Override `getValidationMessage()` to supply a translated message for that type, and use the same message in the PHP rule.

For example, these methods belong inside a custom `SingleLineText` subclass. They declare a booking-reference format on both sides; the JavaScript registration below implements its browser check.

```php
public function getElementValidationRules(): array
{
    $rules = parent::getElementValidationRules();
    $rules[] = [
        \yii\validators\RegularExpressionValidator::class,
        'pattern' => '/\A[A-Z]{3}-[0-9]{4}\z/',
        'message' => $this->getValidationMessage('booking-reference'),
        'skipOnEmpty' => true,
    ];

    return $rules;
}

protected function defineBrowserValidationRules(): array
{
    $rules = parent::defineBrowserValidationRules();
    $rules[] = ['type' => 'booking-reference'];

    return $rules;
}

public function getValidationMessage(string $key, array $params = []): string
{
    if ($key === 'booking-reference') {
        return \Craft::t('site', '{label} must use three uppercase letters, a hyphen and four digits, for example ABC-1234.', [
            'label' => $this->label,
        ]);
    }

    return parent::getValidationMessage($key, $params);
}
```

Formie’s `browserValidationRules()` retains each rule’s options and adds resolved, plain-text messages from `getValidationMessage()`. The standard field wrapper emits that payload as `data-formie-validation`; client-rendered field definitions carry the rules too. Use `getBrowserValidationRulesJson()` when writing a custom field wrapper.

Required answers are handled by a separate rule. A format validator should skip empty values in PHP and JavaScript, allowing Formie’s Required rule to decide whether an answer must be supplied. Match the field’s normalisation too: Single-Line Text trims surrounding spaces, tabs, and line breaks before PHP validation, so the browser example applies the same trimming.

## Register the Browser Validator

For server-rendered forms using Formie’s standard assets, register DOM validators through `formie:validator:ready`. Add the listener in your site’s JavaScript before Formie mounts any forms. Each mounted form receives its own validator:

```js
document.addEventListener('formie:validator:ready', (event) => {
    const { validator } = event.detail;

    validator.addValidator(
        'booking-reference',
        ({ input, getRule }) => {
            const rule = getRule('booking-reference');

            // Validators see every input; only check fields declaring this rule.
            if (!rule) {
                return true;
            }

            const value = input.value.replace(/^[ \t\r\n]+|[ \t\r\n]+$/g, '');

            return value === '' || (value.length === 8 && /^[A-Z]{3}-[0-9]{4}$/.test(value));
        },
        ({ getRule }) => getRule('booking-reference').messages['booking-reference'],
    );
});
```

`addValidator()` takes the rule name, a callback that returns whether the value is valid, and a callback that returns the error message. `getRule()` reads this field’s rule definition, including the message supplied by PHP. The rule name must match the `type` in `defineBrowserValidationRules()`.

Register the listener once per page, before Formie’s startup or your application’s mounting code runs. The same listener handles dynamically mounted forms. The [worked guide](/guides/developers/adding-a-custom-validation-rule#register-the-browser-validator) includes script placement and a Twig template.

### Client-Rendered Forms and Shared Value Rules

For React, Vue, Web Components or a custom client that uses `@verbb/formie-core`, register the matching value validator in the application’s JavaScript entry point before mounting forms:

```js
import { registerBrowserValidationRule } from '@verbb/formie-core';

const unregisterBookingReference = registerBrowserValidationRule(
    'booking-reference',
    (value, rule) => {
        const text = typeof value === 'string'
            ? value.replace(/^[ \t\r\n]+|[ \t\r\n]+$/g, '')
            : '';
        const valid = typeof value === 'string'
            && (text === '' || (text.length === 8 && /^[A-Z]{3}-[0-9]{4}$/.test(text)));

        return valid ? null : rule.messages['booking-reference'];
    },
);
```

The shared validator returns `null` for a valid value or an error string for an invalid one. `validateBrowserValue()` handles empty values before invoking custom rules; the separate Required rule still controls whether an answer is mandatory. Register against the same core package instance used by your form adapter. The returned function removes this registration when the application no longer needs it; do not unregister it while other mounted forms still use the rule.

This value-based API does not install a DOM `addValidator()` callback. Use the DOM registration for server-rendered controls and the core registration for clients that validate structured field values. Both consume the rule and message emitted by the PHP field. Unknown browser rules defer to server validation, so test that your registration actually loads on every rendering path you use.

## Testing Validation

Check valid, malformed, empty, and normalised values through both PHP and the browser. Test optional and required fields separately, and verify that server errors appear on the correct input when browser checks are bypassed. Nested fields, conditions, and multi-page forms also affect which values are validated and where errors appear.

The [worked guide’s validation checks](/guides/developers/adding-a-custom-validation-rule#test-the-complete-rule) show how to validate unsaved submissions and compare PHP results with browser feedback.

## Submission Errors and Messages

`$submission->getSubmissionErrors()` converts Yii/Craft errors into paths that identify the affected field on this form, including nested fields. `toClient()` returns `form` and `fields`; a field key is `<field-instance-id>.<nested-path>`, such as `123.1.email`. It never uses a shared field definition ID. `toValuePathMap()` returns full handle paths such as `people.1.email` for template and PHP consumers. `getErrors()` returns the underlying Yii/Craft errors.

Validation messages are plain text; they do not support the rich text available in completion messages. CAPTCHA, spam and provider diagnostics are private. `forPage()` and `firstPageId()` build summaries from the field errors and the current layout; there is no persisted page-error map. Preserve full error paths when rendering results and focus the exact child control instead of collapsing errors onto a Repeater or Group root.

REST-style actions return 422 for validation/progression errors, 409 for stale state, 429 for rate limits and 403 for authorisation. Interactive GraphQL submit/page mutations return expected domain failures in result data, including `httpStatus`; GraphQL protocol errors remain separate. Handle-based AJAX responses may return HTTP 200 with validation errors in the response body. Page reload rerenders the submitted form and its exact controls.

## Browser Validator Context

A DOM validator callback receives `input`, `label`, `field`, `form`, `rules`, `getRule(name)` and `t(message, replacements)`. Use `getRule()` to read options from the current field’s rule payload. Keep browser-visible options free of secrets, and use the PHP rule to enforce the requirement when the form is submitted.

Registering the same name on a mounted validator replaces its callback. Use `validator.removeValidator('booking-reference')` to remove that DOM registration. Controls that are helpers rather than submitted values can use `data-formie-validation-skip` so they are not collected or live-validated.

## Show a Field Error Manually

When custom code already knows which input should be invalid, use the DOM validator’s `showError()` method to display the message. This only changes browser feedback; the same requirement must be enforced in PHP when the form is submitted.

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

## Run a Remote Check Before Submit

This example assumes a custom `/actions/site/check-service-area` controller action that returns JSON with `allowed` (a boolean) and an optional `message`. Implement the same service-area check in the PHP submission validator; the browser response is feedback and does not authorise the submission.

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

