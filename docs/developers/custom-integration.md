# Custom Integration
You can add your own custom integrations to be compatible with Formie by using the provided events.

```php
use modules\ExampleCaptcha;
use modules\ExampleAddressProvider;
use modules\ExampleElement;

use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\services\Integrations;
use yii\base\Event;

Event::on(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, function(RegisterIntegrationsEvent $event) {
    $event->captchas = new ExampleCaptcha();
    $event->addressProviders = new ExampleAddressProvider();
    $event->elements = new ExampleElement();
    // ...
});
```

## Captcha

For a Captcha integration, you should extend from the `Captcha` class, which in turn implements the `IntegrationInterface`.

### Attributes

Attribute | Description
--- | ---
`handle` | Create a handle for the captcha.

### Methods

Method | Description
--- | ---
`getName()` | Returns the name to be used for the captcha.
`getIconUrl()` | Returns the icon to show in the control panel, if applicable.
`getDescription()` | Returns the description for the captcha.
`getFormSettingsHtml()` | Returns the HTML for the settings for this captcha, as shown when editing a form.
`getFrontEndHtml()` | Returns the HTML for the captcha, as shown on the front-end.
`getFormSettingsJs()` | Returns the JS for the captcha, as shown on the front-end.
`getSettingsHtml()` | Returns the HTML for the settings for this captcha, as shown in Formie's settings.
`validateSubmission()` | Returns true/false on whether a submission is valid.

### Example

```php
<?php
namespace modules;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\base\Captcha;

use Craft;

class ExampleCaptcha extends Captcha
{
    public $handle = 'exampleCaptcha';

    public static function getName(): string
    {
        return Craft::t('formie', 'Example Captcha');
    }

    public function getIconUrl(): string
    {
        return __DIR__ . '/my-path/icon.svg';
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'This is an example captcha.');
    }

    public function getFrontEndHtml(Form $form, $page = null): string
    {
        return '<input type="hidden" name="example-captcha" value="Testing captcha" />';
    }

    public function getFrontEndJs(Form $form, $page = null): string
    {
        $src = Craft::$app->getAssetManager()->getPublishedUrl('path-to-js.js', true);
        $onload = 'new MyCustomFunction();';

        return [
            'src' => $src,
            'onload' => $onload,
        ];
    }

    public function validateSubmission(Submission $submission): bool
    {
        // Check the provided value
        $value = Craft::$app->request->post('example-captcha');

        if ($value !== 'Testing captcha') {   
            return false;            
        }

        return true;
    }
}
```

## Address Provider

For an Address Provider integration, you should extend from the `AddressProvider` class, which in turn implements the `IntegrationInterface`.

### Attributes

Attribute | Description
--- | ---
`handle` | Create a handle for the integration.

### Methods

Method | Description
--- | ---
`getName()` | Returns the name to be used for the integration.
`getIconUrl()` | Returns the icon to show in the control panel, if applicable.
`getDescription()` | Returns the description for the integration.
`getFrontEndHtml()` | Returns the HTML for the front-end field.
`getFormSettingsJs()` | Returns the JS for the front-end field.
`getSettingsHtml()` | Returns the HTML for the settings for this integration, as shown in Formie's settings.

### Example

```php
<?php
namespace modules;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\base\AddressProvider;

use Craft;

class ExampleAddressProvider extends AddressProvider
{
    public $handle = 'exampleAddressProvider';

    public static function getName(): string
    {
        return Craft::t('formie', 'Example Address Provider');
    }

    public function getIconUrl(): string
    {
        return __DIR__ . '/my-path/icon.svg';
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'This is an example address provider.');
    }

    public function getFrontEndHtml($field, $options): string
    {
        return '<input type="hidden" />';
    }

    public function getFrontEndJs(Form $form, $page = null): string
    {
        $src = Craft::$app->getAssetManager()->getPublishedUrl('path-to-js.js', true);
        $onload = 'new MyCustomFunction();';

        return [
            'src' => $src,
            'onload' => $onload,
        ];
    }
}
```

## Element

For an Element integration, you should extend from the `Element` class, which in turn implements the `IntegrationInterface`.

### Attributes

Attribute | Description
--- | ---
`handle` | Create a handle for the integration.

### Methods

Method | Description
--- | ---
`getName()` | Returns the name to be used for the integration.
`getIconUrl()` | Returns the icon to show in the control panel, if applicable.
`getDescription()` | Returns the description for the integration.
`getSettingsHtml()` | Returns the HTML for the settings for this integration, as shown in Formie's settings.
`getElementFields()` | Returns an array of custom fields available for the element.
`getElementFieldsFromRequest()` | Returns an array of custom fields available for the element.
`getElementAttributes()` | Returns an array of attributes available for the element.
`saveElement()` | This function is called by the queue job to actually save the element, after the submission has been successful.

### Example

```php
<?php
namespace modules;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\base\Element;

use Craft;

class ExampleElement extends Element
{
    public $handle = 'exampleElement';

    public static function getName(): string
    {
        return Craft::t('formie', 'Example Element');
    }

    public function getIconUrl(): string
    {
        return __DIR__ . '/my-path/icon.svg';
    }

    public function getDescription(): string
    {
        return Craft::t('formie', 'This is an example element.');
    }

    public function getElementFields($groupId = null)
    {
        $options = [];

        foreach ($elementGroup->getFields() as $field) {
            $options[] = [
                'name' => $field->name,
                'handle' => $field->handle,
            ];
        }

        return $options;
    }

    public function getElementFieldsFromRequest($request)
    {
        $groupId = $request->getParam('groupId');

        if (!$groupId) {
            return ['error' => Craft::t('formie', 'No “{groupId}” provided.')];
        }

        return $this->getElementFields($groupId);
    }

    public function getElementAttributes()
    {
        return [
            [
                'name' => Craft::t('app', 'Title'),
                'handle' => 'title',
            ],
            [
                'name' => Craft::t('app', 'Slug'),
                'handle' => 'slug',
            ],
        ];
    }

    public function saveElement(Submission $submission)
    {
        $element = new YourElement();

        // Set the attributes for the element, from the element mapping
        foreach ($this->attributeMapping as $entryFieldHandle => $formFieldHandle) {
            if ($formFieldHandle) {
                $formFieldHandle = str_replace(['{', '}'], ['', ''], $formFieldHandle);
                $fieldValue = $submission->{$formFieldHandle};

                $element->{$entryFieldHandle} = $fieldValue;
            }
        }

        // Set the custom fields for the element, from the element mapping
        foreach ($this->fieldMapping as $entryFieldHandle => $formFieldHandle) {
            if ($formFieldHandle) {
                $formFieldHandle = str_replace(['{', '}'], ['', ''], $formFieldHandle);
                $fieldValue = $submission->{$formFieldHandle};

                $element->setFieldValue($entryFieldHandle, $fieldValue);
            }
        }

        if (!Craft::$app->getElements()->saveElement($element)) {
            return false;
        }

        return true;
    }
}
```
