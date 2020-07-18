# Custom Integration
You can add your own custom integrations to be compatible with Formie by using the provided events. Currently, Captchas are the only type of integration, but more will be added.

```php
use modules\ExampleCaptcha;

use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\services\Integrations;
use yii\base\Event;

Event::on(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, function(RegisterIntegrationsEvent $event) {
    $event->captchas = new ExampleCaptcha();
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

