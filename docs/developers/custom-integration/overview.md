# Overview

::: tip
For end-to-end walkthroughs, see [Guides → Integrations](/guides/integrations/) — [Building a CRM integration from scratch](/guides/integrations/building-a-crm-integration-from-scratch), [Building an Automation integration from scratch](/guides/integrations/building-an-automation-integration-from-scratch), and related guides.
:::

You can add custom integrations by registering an integration class with Formie. Pick the base class that matches the integration you are building, then implement the pieces that are specific to your provider.

```php
namespace modules\sitemodule;

use modules\sitemodule\ExampleAddressProvider;
use modules\sitemodule\ExampleAutomation;
use modules\sitemodule\ExampleCaptcha;
use modules\sitemodule\ExampleCrm;
use modules\sitemodule\ExampleElement;
use modules\sitemodule\ExampleEmailMarketing;
use modules\sitemodule\ExampleHelpDesk;
use modules\sitemodule\ExampleMessaging;
use modules\sitemodule\ExampleMiscellaneous;
use modules\sitemodule\ExamplePayment;
use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\services\Integrations;
use yii\base\Event;

Event::on(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, function(RegisterIntegrationsEvent $event) {
    $event->addressProviders[] = ExampleAddressProvider::class;
    $event->automations[] = ExampleAutomation::class;
    $event->captchas[] = ExampleCaptcha::class;
    $event->crm[] = ExampleCrm::class;
    $event->elements[] = ExampleElement::class;
    $event->emailMarketing[] = ExampleEmailMarketing::class;
    $event->helpDesk[] = ExampleHelpDesk::class;
    $event->messaging[] = ExampleMessaging::class;
    $event->miscellaneous[] = ExampleMiscellaneous::class;
    $event->payments[] = ExamplePayment::class;
});
```

## Integration Types
Most integrations should extend one of Formie’s integration base classes.

Type | Base class | Use
--- | --- | ---
Address provider | `AddressProvider` | Address autocomplete providers used by Address fields. See [Address Provider Integration](/developers/custom-integration/address-provider-integration).
Automation | `Automation` | Automation integrations that send submission payloads to another service. See [Automation Integration](/developers/custom-integration/automation-integration).
Captcha | `Captcha` | Spam-protection integrations that validate submissions. See [Captcha Integration](/developers/custom-integration/captcha-integration).
CRM | `Crm` | CRM integrations with one or more mappable provider objects. See [CRM Integration](/developers/custom-integration/crm-integration).
Element | `Element` | Integrations that create or update Craft elements. See [Element Integration](/developers/custom-integration/element-integration).
Email marketing | `EmailMarketing` | Subscriber/list integrations with a selected list and field mapping. See [Email Marketing Integration](/developers/custom-integration/email-marketing-integration).
Help desk | `HelpDesk` | Ticket or conversation integrations. See [Help Desk Integration](/developers/custom-integration/help-desk-integration).
Messaging | `Messaging` | Message-posting integrations such as chat or notification tools. See [Messaging Integration](/developers/custom-integration/messaging-integration).
Miscellaneous | `Miscellaneous` | Integrations that do not fit a more specific pattern. See [Miscellaneous Integration](/developers/custom-integration/miscellaneous-integration).
Payment | `Payment` | Payment provider integrations used by Payment fields. See [Payment Integration](/developers/custom-integration/payment-integration).

OAuth can apply to several integration types. See [OAuth Integration](/developers/custom-integration/oauth-integration) if your provider needs users to connect an account before Formie can send or fetch data.

## Common Methods
Most integration classes define a few common methods.

Method | Use
--- | ---
`displayName()` | The integration name shown in the control panel.
`getDescription()` | A short description shown in integration selection screens.
`getIconUrl()` | The icon URL shown in the control panel. Many core integrations use Formie’s default icon path for their category.
`defineClient()` | Creates the Guzzle client used by `request()` and `deliverPayload()`.
`fetchConnection()` | Checks whether the integration can connect to the provider.
`fetchFormSettings()` | Fetches provider data used by the form builder, such as lists, fields, channels or element layouts.
`defineFormSettingsSchema()` | Defines the integration settings shown inside a form’s Integrations tab.
`sendPayload()` | Sends or saves data after a submission has completed.

`getSettingsHtml()` still exists for plugin-level integration settings in Formie’s settings area. Form-specific integration settings are now defined with `defineFormSettingsSchema()`, not a Twig template.

## Form Settings Schema
Integrations use schema for the form builder UI. Start with `parent::defineFormSettingsSchema($form)` so the standard `enabled` setting is included, then append your own fields.

```php
use verbb\formie\base\FormInterface;
use verbb\formie\helpers\SchemaHelper;

protected function defineFormSettingsSchema(FormInterface $form): array
{
    $schema = parent::defineFormSettingsSchema($form);

    $schema[] = SchemaHelper::textField([
        'label' => Craft::t('formie', 'URL'),
        'instructions' => Craft::t('formie', 'Enter the URL that will be triggered when a submission is made.'),
        'name' => 'url',
        'required' => true,
    ]);

    return $schema;
}
```

Many integrations also use field mapping. The helper expects provider fields that have already been fetched into `IntegrationFormSettings`.

```php
protected function defineFormSettingsSchema(FormInterface $form): array
{
    $schema = parent::defineFormSettingsSchema($form);
    $schema[] = $this->getOptInFieldSchema();

    $schema[] = $this->getIntegrationFieldMappingField([
        'name' => 'contactFieldMapping',
        'dataLabel' => 'Contact',
        'dataKey' => 'contact',
    ]);

    return $schema;
}
```

For a broader explanation of schema nodes, helpers, conditions and layout, see [Schema](/developers/schema).

## Form Settings Data
Use `fetchFormSettings()` to fetch data the form builder needs before a user configures the integration on a form. This data is cached by `getFormSettings()` and refreshed when the form builder asks Formie to refresh integration data.

```php
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

public function fetchFormSettings(): IntegrationFormSettings
{
    $contactFields = [
        new IntegrationField([
            'handle' => 'email',
            'name' => Craft::t('formie', 'Email'),
            'required' => true,
        ]),
        new IntegrationField([
            'handle' => 'firstName',
            'name' => Craft::t('formie', 'First Name'),
        ]),
    ];

    return new IntegrationFormSettings([
        'contact' => $contactFields,
    ]);
}
```

`IntegrationFormSettings` can contain plain arrays, `IntegrationField` instances, and `IntegrationCollection` instances. Email marketing integrations often return lists, each with its own fields.

## Integration Option Sources

If your integration caches selectable provider fields, you can expose them as dynamic option lists for Dropdown, Radio and Checkboxes fields. Declare sources in `defineOptionSources()` on the integration class.

See [Option Sources](/developers/custom-integration/option-sources) for storage shapes, builder labels, testing, and examples from Mailchimp and CRM integrations.

```php
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

public function fetchFormSettings(): IntegrationFormSettings
{
    $settings = [];
    $lists = $this->request('GET', 'lists');

    foreach ($lists as $list) {
        $settings['lists'][] = new IntegrationCollection([
            'id' => (string)$list['id'],
            'name' => $list['name'],
            'fields' => [
                new IntegrationField([
                    'handle' => 'email',
                    'name' => Craft::t('formie', 'Email'),
                    'required' => true,
                ]),
            ],
        ]);
    }

    return new IntegrationFormSettings($settings);
}
```

## Integration Fields
`IntegrationField` represents a field from the provider or destination system. Formie uses it to build field-mapping schema and to convert Formie values into the format the provider expects.

Attribute | Use
--- | ---
`handle` | The provider field identifier.
`name` | The provider field label.
`type` | The value type Formie should convert to.
`sourceType` | The original provider or Craft field type, when useful.
`required` | Whether the mapping should be required.
`defaultValue` | A default value for the integration field.
`options` | Options for selectable provider fields.
`data` | Extra provider-specific metadata.

If `type` is omitted, Formie treats the field as `TYPE_STRING`. Available types are `TYPE_STRING`, `TYPE_NUMBER`, `TYPE_FLOAT`, `TYPE_BOOLEAN`, `TYPE_DATE`, `TYPE_DATETIME`, `TYPE_DATECLASS`, `TYPE_ARRAY` and `TYPE_PHONE`.

## Sending Payloads
Use `sendPayload()` to send data after a submission has completed. Use `getFieldMappingValues()` to resolve the configured form mapping, and `deliverPayload()` when sending to a remote endpoint so Formie can run the before/after payload events.

```php
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use Throwable;

public function sendPayload(Submission $submission): bool
{
    try {
        $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping);

        $payload = [
            'contact' => $fieldValues,
        ];

        $response = $this->deliverPayload($submission, 'contacts', $payload);

        if ($response === false) {
            return false;
        }
    } catch (Throwable $e) {
        Integration::apiError($this, $e);

        return false;
    }

    return true;
}
```

`deliverPayload()` sends through `request()`, then triggers Formie’s payload events. It also enforces the opt-in field configured by `getOptInFieldSchema()`.

## API Clients
For non-OAuth integrations, override `defineClient()` and return a Guzzle client. `request()` will use this client and decode JSON responses when possible.

```php
use craft\helpers\App;
use GuzzleHttp\Client;

protected function defineClient(): Client
{
    return Craft::createGuzzleClient([
        'base_uri' => 'https://api.provider.test/v1/',
        'headers' => [
            'Authorization' => 'Bearer ' . App::parseEnv($this->apiKey),
        ],
    ]);
}
```

If the provider has a connection test endpoint, implement `fetchConnection()`.

```php
use verbb\formie\base\Integration;
use Throwable;

public function fetchConnection(): bool
{
    try {
        $response = $this->request('GET', 'me');

        return (bool)($response['id'] ?? false);
    } catch (Throwable $e) {
        Integration::apiError($this, $e);

        return false;
    }
}
```

## Type Guides
The integration type pages cover the details that differ between base classes:

- [Option Sources](/developers/custom-integration/option-sources)
- [Address Provider Integration](/developers/custom-integration/address-provider-integration)
- [Automation Integration](/developers/custom-integration/automation-integration)
- [Captcha Integration](/developers/custom-integration/captcha-integration)
- [CRM Integration](/developers/custom-integration/crm-integration)
- [Element Integration](/developers/custom-integration/element-integration)
- [Email Marketing Integration](/developers/custom-integration/email-marketing-integration)
- [Help Desk Integration](/developers/custom-integration/help-desk-integration)
- [Messaging Integration](/developers/custom-integration/messaging-integration)
- [Miscellaneous Integration](/developers/custom-integration/miscellaneous-integration)
- [Payment Integration](/developers/custom-integration/payment-integration)
- [OAuth Integration](/developers/custom-integration/oauth-integration)
