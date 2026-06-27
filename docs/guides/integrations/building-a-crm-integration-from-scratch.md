# Building a CRM integration from scratch

Formie supports over **50** different types of integrations, from **Mailchimp**, **Zoho**, **Salesforce** and more. But you can also create your own integrations.

We'll cover creating an integration from scratch, along with extending an existing one.

:::tip
This guide is a good companion to the [custom field](https://verbb.io/craft-plugins/formie/docs/developers/custom-integration) docs, so be sure to have a browse through the documentation first.
:::

### Create your module
First, you'll need to get familiar with [creating a module](/blog/everything-you-need-to-know-about-modules). The code we will be writing will be in PHP, and added to our custom module. 

When creating your module, set the namespace to `modules\formieintegration` and the module ID to `formie-integration`.

Create the following directory and file structure:

```treeview
my-project/
├── modules/
│    └── formieintegration/
│        └── src/
│            └── integrations/
│                └── ExampleCrm.php
│            └── templates/
│                └── example-integration/
│                    └── _form-settings.html
│                    └── _plugin-settings.html
│            └── FormieIntegration.php
└── ...
```

```php
// modules/formieintegration/src/FormieIntegration.php

<?php
namespace modules\formieintegration;

use Craft;
use modules\formieintegration\integrations\ExampleCrm;
use verbb\base\base\Module;
use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\services\Integrations;
use yii\base\Event;

class FormieIntegration extends Module
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        // Call the `Module::init()` method, which will do its own initializations
        parent::init();

        // Define a custom alias named after the namespace
        Craft::setAlias('@formie-integration', __DIR__);

        // Register our custom integration
        Event::on(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, function(RegisterIntegrationsEvent $event) {
            $event->crm[] = ExampleCrm::class;
        });
    }
}
```

Here our main module file is pretty simple. We tell Formie we want to register a new integration class, which points to our `integrations/ExampleCrm.php` class. The bulk of our logic will be in this class.

We also set up a `@formie-integration` alias, which we'll get to a little later on.

#### Integration class

```php
// modules/formieintegration/src/integrations/ExampleCrm.php

<?php
namespace modules\formieintegration\integrations;

use Craft;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use GuzzleHttp\Client;
use Throwable;
use verbb\formie\Formie;
use verbb\formie\base\Crm;
use verbb\formie\base\Integration;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

class ExampleCrm extends Crm
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Example CRM Integration');
    }

    // Properties
    // =========================================================================

    public ?string $apiKey = null;
    public bool $mapToContact = false;
    public bool $mapToDeal = false;
    public ?array $contactFieldMapping = null;
    public ?array $dealFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage your Example CRM customers by providing important information on their conversion on your site.');
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        // Set up any required integration settings. These are for when saving this integration, not for field mapping
        // or when the payload is sent.
        $rules[] = [['apiKey'], 'required'];

        $contact = $this->getFormSettingValue('contact');
        $deal = $this->getFormSettingValue('deal');

        // Validate the following when saving form settings
        $rules[] = [
            ['contactFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
                return $model->enabled && $model->mapToContact;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['dealFieldMapping'], 'validateFieldMapping', 'params' => $deal, 'when' => function($model) {
                return $model->enabled && $model->mapToDeal;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }

    public function getIconUrl(): string
    {
        return '';
    }

    public function getSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate("formie-integration/example-integration/_plugin-settings", [
            'integration' => $this,
        ]);
    }

    public function getFormSettingsHtml($form): string
    {
        return Craft::$app->getView()->renderTemplate("formie-integration/example-integration/_form-settings", [
            'integration' => $this,
            'form' => $form,
        ]);
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = [];

        try {
            // Create the available fields for contact objects
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
                new IntegrationField([
                    'handle' => 'lastName',
                    'name' => Craft::t('formie', 'Last Name'),
                ]),
            ];

            // Create the available fields for deal objects
            $dealFields = [
                new IntegrationField([
                    'handle' => 'title',
                    'name' => Craft::t('formie', 'Title'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'description',
                    'name' => Craft::t('formie', 'Description'),
                ]),
                new IntegrationField([
                    'handle' => 'value',
                    'name' => Craft::t('formie', 'Value'),
                    'required' => true,
                ]),
            ];
            
            // Add all objects to settings
            $settings = [
                'contact' => $contactFields,
                'deal' => $dealFields,
            ];
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        // Return a `IntegrationFormSettings` collection to represent our objects
        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            // Defined by our field mapping settings in the form, get the mapped values from the submission into an array.
            // So you might pick `Email` (Provider) map to `Email Address` (Formie Field), this will retrieve the value the user entered.
            // We also want to pick specific objects, which we define in `IntegrationFormSettings`.
            $contactValues = $this->getFieldMappingValues($submission, $this->contactFieldMapping, 'contact');
            $dealValues = $this->getFieldMappingValues($submission, $this->dealFieldMapping, 'deal');

            // Check if we've enabled mapping to a contact
            if ($this->mapToContact) {
                // Construct the payload for the provider, according to their API docs.
                $contactPayload = [
                    'email' => $contactValues['email'],
                    'firstName' => $contactValues['firstName'],
                    'lastName' => $contactValues['lastName'],
                ];

                // Send the response to the API, any errors will be safely caught and logged
                $response = $this->deliverPayload($submission, 'contacts', $contactPayload);

                // Allow event hooks to explicitly return false here
                if ($response === false) {
                    return true;
                }
            }

            // Check if we've enabled mapping to a deal
            if ($this->mapToDeal) {
                // Construct the payload for the provider, according to their API docs.
                $dealPayload = [
                    'title' => $dealValues['title'],
                    'description' => $dealValues['description'],
                    'value' => $dealValues['value'],
                ];

                $response = $this->deliverPayload($submission, 'deals', $dealPayload);

                // Allow event hooks to explicitly return false here
                if ($response === false) {
                    return true;
                }
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    public function fetchConnection(): bool
    {
        try {
            // Create a simple API call to `/account` to test the connection (in the integration settings)
            // any errors will be safely caught, logged and shown in the UI.
            $response = $this->request('GET', 'account');
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    public function getClient(): Client
    {
        // We memoize the client for performance, in case we make multiple requests.
        if ($this->_client) {
            return $this->_client;
        }

        // Create a Guzzle client to make API requests. The connection details will be specific to the provider.
        // Don't forget to use `App::parseEnv()` for any integration settings that can be .env variables!
        return $this->_client = Craft::createGuzzleClient([
            'base_uri' => 'https://api.provider.com/v1/',
            'headers' => ['apikey' => App::parseEnv($this->apiKey)],
        ]);
    }
}
```

Let's dissect the different functions of this class, which pertain to a different part of the flow from integration setup, form submission and sending to the third-party API.

As each platform has different requirements, these instructions will be broad.

#### Integration settings
Your integration will likely have some settings associated with it — at the very least an API Key to communicate with their API. These are stored as properties in the class (see the above `apiKey` property).

It's also a great idea to allow any sensitive settings to be set to .env variables. We'll walk through how this is done via templates further in this guide. These properties then need to be run through `App::parseEnv()` to actually get the value for use in your class.

In our example, we only need an `apiKey` property/setting. We also provide some validation when saving the mapping values, so long as we've enabled certain objects to be mapped.

##### Guzzle client
We use [Guzzle](https://docs.guzzlephp.org/en/stable/) for the requests out to the third-party APIs so we can create consistent and standard connectors, along with it being easy to adopt. This handles the HTTP requests (GET, POST, PUT, etc) along with authentication like Basic HTTP, OAuth and more.

As such, each integration will have a Guzzle client configured to it. As for using the Guzzle client in your class, we use [`$this->request()`](https://github.com/verbb/formie/blob/40a98cceacaa2c648c4e9ba88e148b36ae75eb8a/src/base/Integration.php#L491) from Formie as a bit of a helper function to just handle JSON APIs, but it's totally equivalent to:

```php
use craft\helpers\Json;

$response = $this->getClient()->request($method, $url, $params);
$json = Json::decode((string)$response->getBody());
```

##### Fetching settings
With the integration setup and working, we'll want to configure how submission content is sent via the API. This is done through mapping Formie fields to Integration fields. The available fields will entirely depend on your integration's API.

When loading the integrations area when editing a form in the control panel, you'll have access to the mapping interface. Through the integration class, we provide some settings for this mapping for every "object" that we want to support. For CRM integrations, this commonly would be Contacts, Leads, Deals, Accounts, Prospects and more. We can provide the fields available for each of these objects, but if the provider supports an API to fetch these properties or custom fields, we can use that.

To provide this functionality, we define an `IntegrationFormSettings` object which takes a keyed array of multiple `IntegrationField` objects. The key for this collection should reflect what collection it is (`contact`, `deal`, etc). In our payload-sending function, we can then access the mapping setup for the object we want and get values.

`IntegrationField` objects have `name`, `handle`, `type`, `required`, `options`. The type allows us to set what the **destination** variable type is — what the provider's API requires the data to be supplied as. For example, you might have a Single-Line Text field in Formie, but the integration requires this as a Number. You can define what variable type to convert it to. The handle is also important for you to reference later when fetching mapped data.

In our example, we're adding support for `contact` and `deal` objects. For some APIs, the ability to fetch available properties and custom fields is also possible. Our example shows as hard-coding them in the integration, but you could do something like:

```php
// ...

// Fetch the custom fields for contacts
$fields = $this->request('GET', 'contacts/custom_fields');

$contactFields = array_merge([
    new IntegrationField([
        'handle' => 'email',
        'name' => Craft::t('formie', 'Email'),
        'required' => true,
    ]),
    new IntegrationField([
        'handle' => 'firstName',
        'name' => Craft::t('formie', 'First Name'),
    ]),
    new IntegrationField([
        'handle' => 'lastName',
        'name' => Craft::t('formie', 'Last Name'),
    ]),
], $this->_getCustomFields($fields));

// ...

private function _getCustomFields($fields, $excludeNames = []): array
{
    $customFields = [];

    foreach ($fields as $field) {
        // Exclude any names
        if (in_array($field['name'], $excludeNames)) {
            continue;
        }

        // Add custom field according to the response back from the API.
        $customFields[] = new IntegrationField([
            'handle' => $field['id'],
            'name' => $field['name'],

            // We'll need to convert the type of field in the API this is, to an `IntegrationField`
            'type' => $this->_convertFieldType($field['type']),
        ]);
    }

    return $customFields;
}

private function _convertFieldType($fieldType)
{
    $fieldTypes = [
        'number' => IntegrationField::TYPE_NUMBER,
        'date' => IntegrationField::TYPE_DATETIME,
    ];

    return $fieldTypes[$fieldType] ?? IntegrationField::TYPE_STRING;
}
```

##### Sending payload
Sending the data to the third-party provider is the final step in the integration lifecycle. Here, we fetch the mapping for the form (for each object), and we use `getFieldMappingValues()` to get the mapped values from the submission (which handles different field types and converting to the destination variable types). With the submission data, we check the objects we want to send, and construct the payload to be sent to the API. This will depend entirely on the API you're using.

All going well, your payload will be successfully sent! If not, errors are caught and logged.
 
#### Templates
We also have some Twig templates to set up for the integration settings, and the settings used for the integration when editing a form.

```twig
// modules/formieintegration/src/templates/example-integration/_form-settings.html

{% import '_includes/forms' as forms %}

{% set handle = integration.handle %}
{% set formSettings = integration.getFormSettings().getSettings() %}
{% set mapToContact = form.settings.integrations[handle].mapToContact ?? '' %}
{% set mapToDeal = form.settings.integrations[handle].mapToDeal ?? '' %}

<field-select
    label="{{ 'Opt-In Field' | t('formie') }}"
    instructions="{{ 'Choose a field to opt-in to {name}. For example, you might only wish to record user data if they provide a value for a field of your choice - commonly, an Agree field.' | t('formie', { name: integration.displayName() }) }}"
    id="opt-in-field"
    name="optInField"
    :value="get(form, 'settings.integrations.{{ handle }}.optInField')"
></field-select>

<hr>

<integration-form-settings handle="{{ handle }}" :form-settings="{{ formSettings | json_encode }}">
    <template v-slot="{ get, isEmpty, input, settings, sourceId, loading, refresh, error, errorMessage, getSourceFields }">
        <div class="field">
            <button class="btn" :class="{ 'fui-loading fui-loading-sm': loading }" data-icon="refresh" @click.prevent="refresh">{{ 'Refresh Integration' | t('formie') }}</button>
        </div>

        <div v-if="error" class="error" style="margin-top: 10px;" v-cloak>
            <span data-icon="alert"></span>
            <span v-html="errorMessage"></span>
        </div>

        {{ forms.lightswitchField({
            label: 'Map to Contact' | t('formie'),
            instructions: 'Whether to map form data to {name} Contacts.' | t('formie', { name: integration.displayName() }),
            id: 'mapToContact',
            name: 'mapToContact',
            on: mapToContact,
            toggle: 'map-to-contact',
        }) }}

        <div id="map-to-contact" class="{{ not mapToContact ? 'hidden' }}">
            <integration-field-mapping
                label="{{ 'Contact Field Mapping' | t('formie') }}"
                instructions="{{ 'Choose how your form fields should map to your {name} Contact fields.' | t('formie', { name: integration.displayName() }) }}"
                name-label="{{ integration.displayName() }}"
                id="contact-field-mapping"
                name="contactFieldMapping"
                :value="get(form, 'settings.integrations.{{ handle }}.contactFieldMapping')"
                :rows="get(settings, 'contact')"
            ></integration-field-mapping>

            <ul v-if="!isEmpty(get(form, 'settings.integrations.{{ handle }}.errors.contactFieldMapping'))" class="errors" v-cloak>
                <li v-for="(error, index) in get(form, 'settings.integrations.{{ handle }}.errors.contactFieldMapping')" :key="index">
                    ${ error }
                </li>
            </ul>
        </div>

        {{ forms.lightswitchField({
            label: 'Map to Deal' | t('formie'),
            instructions: 'Whether to map form data to {name} Deals.' | t('formie', { name: integration.displayName() }),
            id: 'mapToDeal',
            name: 'mapToDeal',
            on: mapToDeal,
            toggle: 'map-to-deal',
        }) }}

        <div id="map-to-deal" class="{{ not mapToDeal ? 'hidden' }}">
            <integration-field-mapping
                label="{{ 'Deal Field Mapping' | t('formie') }}"
                instructions="{{ 'Choose how your form fields should map to your {name} Deal fields.' | t('formie', { name: integration.displayName() }) }}"
                name-label="{{ integration.displayName() }}"
                id="deal-field-mapping"
                name="dealFieldMapping"
                :value="get(form, 'settings.integrations.{{ handle }}.dealFieldMapping')"
                :rows="get(settings, 'deal')"
            ></integration-field-mapping>

            <ul v-if="!isEmpty(get(form, 'settings.integrations.{{ handle }}.errors.dealFieldMapping'))" class="errors" v-cloak>
                <li v-for="(error, index) in get(form, 'settings.integrations.{{ handle }}.errors.dealFieldMapping')" :key="index">
                    ${ error }
                </li>
            </ul>
        </div>
    </template>
</integration-form-settings>
```

This template is shown when you click the integration in the **Integrations** tab when editing a form in the control panel. This will be where you set up your field mapping.

This can contain Vue and Twig code, and we commonly use `<field-select>`, `<integration-form-settings>` and `<integration-field-mapping>` Vue components.

For CRM integrations, we usually have multiple objects of data to map to, with each being able to be enabled/disabled.

```twig
// modules/formieintegration/src/templates/example-integration/_plugin-settings.html

{% import '_includes/forms' as forms %}
{% import 'verbb-base/_macros' as macros %}

{% set instructions %}
### Step 1. Connect to the {name} API
1. Go to <a href="https://www.provider.com/" target="_blank">{name}</a> and login to your account.
1. In the left-hand sidebar menu, click on **Settings**, then **API**.
1. Copy the **API Key** from {name} and paste in the **API Key** field below.

### Step 2. Test Connection
1. Save this integration.
1. Click on the **Refresh** button in the right-hand sidebar.
{% endset %}

<div class="fui-settings-block">
    {{ instructions | t('formie', { name: integration.displayName() }) | md }}
</div>

{% if not craft.app.config.general.allowAdminChanges %}
    <span class="warning with-icon">
        {{ 'Integration settings can only be editable on an environment with `allowAdminChanges` enabled.' | t('formie') | md(inlineOnly=true) }}
    </span>
{% endif %}

{{ forms.autosuggestField({
    label: 'API Key' | t('formie'),
    instructions: 'Enter your {name} API key here.' | t('formie', { name: integration.displayName() }),
    name: 'apiKey',
    required: true,
    suggestEnvVars: true,
    value: integration.settings.apiKey ?? '',
    warning: macros.configWarning('apiKey', 'formie'),
    errors: integration.getErrors('apiKey'),
}) }}
```

This template is shown when editing the integration settings (in **Formie** → **Settings** → **CRM**. Here, we show instructions on how to fetch the required settings this integration will need to connect to the provider's API. It's a good idea to make use of Craft's control panel forms macros and the `autosuggestField` field.

### Extending an existing integration
An alternative to creating (and maintaining) your own custom integration is to extend an existing one. This is particularly useful if you want to integrate with an existing supported provider, but require a little more control over it.

Following the same guide as above, you'll need to create a module. This time, however, you should only need to supply the class for the integration (unless you also want to override the templates).

For example, let's say we want to modify some behaviour in the Salesforce integration.

```php
// modules/formieintegration/src/integrations/SalesforceCustom.php

<?php
namespace modules\formieintegration\integrations;

use Craft;
use Throwable;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\integrations\crm\Salesforce;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

class SalesforceCustom extends Salesforce
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Custom Salesforce');
    }

    // Properties
    // =========================================================================

    // New objects we want to support
    public bool $mapToPipeline = false;
    public ?array $pipelineFieldMapping = null;


    // Public Methods
    // =========================================================================

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $pipeline = $this->getFormSettingValue('pipeline');
        
        // Validate the following when saving form settings
        $rules[] = [
            ['pipelineFieldMapping'], 'validateFieldMapping', 'params' => $contact, 'when' => function($model) {
                return $model->enabled && $model->mapToPipeline;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        // Fetch the existing `IntegrationFormSettings` from the parent class
        $settings = parent::fetchFormSettings();

        // Add our new object to existing parent ones.
        $settings->setSettings([
            'pipeline' => [
                new IntegrationField([
                    'handle' => 'name',
                    'name' => Craft::t('formie', 'Name'),
                ]),
                new IntegrationField([
                    'handle' => 'value',
                    'name' => Craft::t('formie', 'Value'),
                ]),
            ],
        ]);

        return $settings;
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            // Do all parent payload-sending first
            parent::sendPayload($submission);

            // Perform our pipeline handling...

        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }
}
```

Here, we've added a new `pipeline` object to map to, all while keeping the existing Salesforce integration setup.

### Finishing up
With all that in place, you should be able to create a new custom integration, add it to your form, configure mapping, and make a submission. A payload will be sent to the providers' API.

Don't forget that integrations are only run when visiting the control panel (read more about [why this happens](/get-started/troubleshooting)).
