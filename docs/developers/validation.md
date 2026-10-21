# Validation

Formie validates submitted field values in PHP and provides matching browser rules for immediate feedback. PHP determines whether a submission can proceed; JavaScript checks the same requirements as visitors interact with the form. Keep accepted values, empty-value handling, and messages consistent across both.

Use the field’s settings for requirements already supported by Formie, such as required answers, email addresses, and number ranges. The [built-in browser rules](https://docs.verbb.io/formie/browser/validation/built-in-rules) list the rules available without custom JavaScript.

::: tip
For a complete implementation, follow [Adding a Custom Validation Rule](/guides/developers/adding-a-custom-validation-rule). The guide builds a Booking Reference field, registers both validators, and tests them together.
:::

## Choose How to Add a Check

Use a validation rule when you need to decide whether an answer is acceptable. For example, a booking code might need a particular format, or an end date might need to follow a start date. A field validator connects that decision to the input the visitor needs to correct, so Formie can display its message, mark the field as invalid and focus it when submission fails.

Use a submission hook when you need to coordinate work around a submit attempt, such as waiting for a visitor to confirm an action. Hooks can pause or stop the submission workflow, but stopping submission with a message does not register a validation rule for a field.

| What You Need | Where to Start |
| --- | --- |
| An existing requirement, such as a required answer or number range | Use the field’s settings. |
| Custom browser logic for an existing field | [Register a callback on the form’s validator](#add-a-check-to-an-existing-field). |
| A reusable field type with matching PHP and browser checks | Define a submission rule and browser rule, then [register the browser validator](#submission-rules-and-browser-definitions). |
| Work that must happen before sending, or code that reacts to the result | Use [browser submission hooks](https://docs.verbb.io/formie/browser/behavior/javascript-events#submit-lifecycle). |

A check that calls a remote service to decide whether a field value is acceptable is still field validation. Browser validator callbacks are synchronous; see [remote checks](#run-a-remote-check-before-submit) for that limitation. Keep the requirement enforced in PHP so submitting without JavaScript cannot bypass it.

## Add a Check to an Existing Field

Formie creates a validator for each mounted form. Register your callback on that validator through `formie:validator:ready`; you do not need to create a validator or a custom field type to add a browser check.

For example, suppose a server-rendered form has a Single-Line Text field with handle `bookingCode`. Add this listener to your site’s JavaScript before Formie’s automatic startup runs:

```js
document.addEventListener('formie:validator:ready', (event) => {
    const { validator } = event.detail;

    validator.addValidator(
        'booking-code-format',
        ({ input }) => {
            if (input.name !== 'fields[bookingCode]') {
                return true;
            }

            const value = input.value.replace(/^[ \t\r\n]+|[ \t\r\n]+$/g, '');

            return value === '' || /^[A-Z]{3}-[0-9]{4}$/.test(value);
        },
        () => 'Use three uppercase letters, a hyphen and four digits, for example ABC-1234.',
    );
});
```

`ABC-1234` passes this check; `abc-1234` fails with a message on the Booking Code field. An empty value is left to the field’s Required setting. The callback receives every applicable input, so it returns `true` for other fields. If several forms use the same field name, this listener applies the check to each of them; check `validator.form` when the requirement belongs to one form only.

This example adds browser feedback. Enforce the same format in PHP when accepting submissions. For a reusable field that carries the requirement and translated message with it, follow [Adding a Custom Validation Rule](/guides/developers/adding-a-custom-validation-rule).

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

When custom code already knows which input should be invalid, use the DOM validator’s `showError()` method to display the message. This changes browser feedback without registering a rule or preventing submission. Use `addValidator()` when Formie needs to evaluate and enforce the browser check during validation, and enforce the same requirement in PHP when accepting the submission.

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

`addValidator()` callbacks return `true` or `false` synchronously. They do not await a `Promise`, so an `async` callback cannot reject a field through this API. Enforce a remote booking-code or service-area check in PHP and return its error against the relevant field; Formie can then display the server’s field error after submission.

Submission hooks registered with `instance.on()` are awaited and can coordinate asynchronous workflow steps. Their `abort(message)` method stops submission and produces a form-level message. It does not attach the message to a field or add a field validation rule. Ordinary DOM listeners registered with `addEventListener()` are not awaited.

Calling `showError()` and then `abort()` does not combine the two behaviours: Formie clears existing field feedback when it applies the abort result. Use a registered field validator for synchronous browser checks and PHP validation for remote field checks.
