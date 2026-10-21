# Adding a Custom Validation Rule

Build a Booking Reference field that accepts values such as `ABC-1234`, shows a helpful message for invalid input, and enforces the same requirement in PHP and JavaScript. The example extends Single-Line Text so you can concentrate on validation while keeping its existing input and settings.

You’ll need a local Craft project with Formie installed and an existing module loaded by Craft. The main walkthrough uses a server-rendered form with Formie’s standard assets. An optional section shows the browser registration for client-rendered forms.

For validation scopes, rule APIs, and error handling, see [Validation](/developers/validation).

Use this guide when a reusable field type should carry its own validation requirement. Formie also lets you [add a browser check to an existing field](/developers/validation#add-a-check-to-an-existing-field) without creating a field type. In either case, use a validator for logic that decides whether an answer is acceptable. Submission hooks coordinate the submit workflow; stopping that workflow with `abort(message)` produces a form-level message rather than a validation error on the answer.

The browser callback in this guide is synchronous. If the check requires a remote service, enforce it in PHP and return a field error; an `async` callback is not awaited by `addValidator()`. The [extension guidance](/developers/validation#choose-how-to-add-a-check) explains how to choose the appropriate approach.

## Add a Rule and Its Browser Definition

Start with an existing Craft module whose namespace is registered with Composer and which Craft loads during bootstrap. The example uses `modules\site`; replace that namespace with your own. If you need to create the module first, follow [Creating a Formie Field Type from Scratch](/guides/fields/creating-a-formie-field-type-from-scratch#create-your-module).

Create `fields/BookingReference.php` inside that module. Extending Single-Line Text keeps its existing settings and value handling. The class explicitly reuses its input and reference templates and its client-rendered field type, then adds the booking-reference requirement:

```php
<?php
namespace modules\site\fields;

use Craft;
use verbb\formie\fields\SingleLineText;
use yii\validators\RegularExpressionValidator;

class BookingReference extends SingleLineText
{
    public static function displayName(): string
    {
        return Craft::t('site', 'Booking Reference');
    }

    public static function getInputTemplatePath(): string
    {
        return SingleLineText::getInputTemplatePath();
    }

    public static function getReferenceBlockTemplatePath(): string
    {
        return SingleLineText::getReferenceBlockTemplatePath();
    }

    protected function defineClientRenderedType(): string
    {
        return 'single-line-text';
    }

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();
        $rules[] = [
            RegularExpressionValidator::class,
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
            return Craft::t('site', '{label} must use three uppercase letters, a hyphen and four digits, for example ABC-1234.', [
                'label' => $this->label,
            ]);
        }

        return parent::getValidationMessage($key, $params);
    }
}
```

`getElementValidationRules()` adds a Yii validator for the submitted field value. Formie supplies the field attribute when it applies the rule, so this array starts with the validator class. Keep the parent rules to retain inherited behaviour. The expression accepts exactly three uppercase ASCII letters, a hyphen and four digits; lowercase letters and additional characters are rejected. Single-Line Text trims surrounding spaces, tabs and line breaks before PHP validation, so the browser checks below apply the same trimming.

`defineBrowserValidationRules()` adds a named rule to the browser payload. Formie’s `browserValidationRules()` retains the rule’s options and adds resolved, plain-text messages. Here it calls `getValidationMessage('booking-reference')`, so the browser receives the same translated message as the PHP validator. Formie’s standard field wrapper emits that payload as `data-formie-validation`; client-rendered definitions carry the rules too. Use `getBrowserValidationRulesJson()` when writing a custom field wrapper.

An empty value is handled by the separate Required rule. The PHP format validator skips it, and the JavaScript rule below does the same. Turn on **Required Field** when a booking reference must be supplied.

`defineRules()` serves a different purpose: it validates settings on the field model, such as configuration entered in the builder. Use `getElementValidationRules()` for the visitor’s submitted answer.

### Register the Field

Add these imports to your module file and the listener to its existing `init()` method, after `parent::init()`:

```php
use modules\site\fields\BookingReference;
use verbb\formie\events\RegisterFieldsEvent;
use verbb\formie\services\Fields;
use yii\base\Event;

Event::on(Fields::class, Fields::EVENT_REGISTER_FIELDS, function(RegisterFieldsEvent $event) {
    $event->fields[] = BookingReference::class;
});
```

Reload the control panel, create a form with handle `bookingForm`, and add the **Booking Reference** field with handle `bookingReference`. Make it required and save the form. The PHP validation rule is now part of that field; the next step connects its browser rule.

## Register the Browser Validator

For a server-rendered form using Formie’s standard assets, create `web/assets/js/booking-reference.js` in the Craft project. This script registers a validator for each form as it mounts:

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

Load the script before Formie mounts the form. For example, create `templates/booking.twig` containing:

```twig
<script src="{{ alias('@web') }}/assets/js/booking-reference.js"></script>
{{ craft.formie.renderForm('bookingForm') }}
```

This script tag registers the listener before Formie’s normal startup runs. If you bundle the script, register the listener before your application mounts Formie. Use one registration per page; dynamically mounted forms will receive their own validator through the same event.

## Test the Complete Rule

Open `/booking` and try `ABC-1234`, `abc-1234`, `ABC-123`, a value with surrounding spaces, and an empty value. The first value and the reference with surrounding spaces should pass. The malformed values should show the format message; the empty value should show Formie’s required-field message. Correct an invalid value and submit again to check that the error clears.

Then check the PHP path directly. With the form saved as above, run this code inside a local module method after Craft and Formie have loaded. It creates unsaved submissions and uses Craft’s live validation scenario, as Formie’s submission workflow does, so required-field rules are included. It does not save records or send a notification or integration:

```php
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

$form = Form::find()->handle('bookingForm')->one();

if (!$form || !$form->getFieldByHandle('bookingReference')) {
    throw new \RuntimeException('Create bookingForm with its bookingReference field first.');
}

foreach (['ABC-1234', 'abc-1234', 'ABC-123', ' ABC-1234 ', ''] as $value) {
    $submission = new Submission(['title' => 'Validation example']);
    $submission->setScenario(Submission::SCENARIO_LIVE);
    $submission->setForm($form);
    $submission->setFieldValue('bookingReference', $value);
    $valid = $submission->validate();
    $errors = $submission->getSubmissionErrors()->toValuePathMap();

    // Inspect $valid and $errors for each sample value.
}
```

The first and fourth samples should validate on this one-field form; the fourth is stored in memory as `ABC-1234` after trimming. If your form has other required fields, populate those too before expecting a valid result. On a local test form, also bypass the browser check and submit an invalid answer to confirm that the server rejects it and the returned error appears on the same field.

For an optional field, turn off Required Field and repeat the empty-value test: both paths should accept it. If the field can appear in a Group, Repeater or multi-page form, check that its error appears on the correct child input and page. When [conditions](/developers/conditions#visibility-and-navigation) hide or disable it, test the applicable clearing and validation behaviour as well.

## Client-Rendered Forms and Shared Value Rules

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

