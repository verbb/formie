# Building an Email Marketing integration from scratch

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
│                └── ExampleEmailMarketing.php
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
use modules\formieintegration\integrations\ExampleEmailMarketing;
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
            $event->emailMarketing[] = ExampleEmailMarketing::class;
        });
    }
}
```

Here our main module file is pretty simple. We tell Formie we want to register a new integration class, which points to our `integrations/ExampleEmailMarketing.php` class. The bulk of our logic will be in this class.

We also set up a `@formie-integration` alias, which we'll get to a little later on.

#### Integration class

```php
// modules/formieintegration/src/integrations/ExampleEmailMarketing.php

<?php
namespace modules\formieintegration\integrations;

use Craft;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use GuzzleHttp\Client;
use Throwable;
use verbb\formie\Formie;
use verbb\formie\base\EmailMarketing;
use verbb\formie\base\Integration;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

class ExampleEmailMarketing extends EmailMarketing
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Example Email Marketing Integration');
    }

    // Properties
    // =========================================================================

    public ?string $apiKey = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your Example Email Marketing Integration lists to grow your audience for campaigns.');
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        //Set up any required integration settings. These are for when saving this integration, not for field mapping
        // or when the payload is sent.
        $rules[] = [['apiKey'], 'required'];

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
            // Fetch all the available lists from the provider (assuming `/lists` in the API)
            $response = $this->request('GET', 'lists');
            $lists = $response['lists'] ?? [];

            foreach ($lists as $list) {
                // Create the fields that are available to be mapped to, for a list. The `handle` is important, as we'll refer to
                // those later when trying to get the values from the submission to send in the payload.
                $listFields =[
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
                    // ...
                ];

                // Create a new `IntegrationCollection` to represent a "list" with the ID, name and custom fields we'll need
                // for mapping purposes, and later when we're sending the payload after form submission.
                $settings['lists'][] = new IntegrationCollection([
                    'id' => (string)$list['id'],
                    'name' => $list['name'] . ' (' . $list['id'] . ')',
                    'fields' => $listFields,
                ]);
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        // Return a `IntegrationFormSettings` collection to represent a group of different "lists".
        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            // Defined by our field mapping settings in the form, get the mapped values from the submission into an array.
            // So you might pick `Email` (Provider) map to `Email Address` (Formie Field), this will retrieve the value the user entered.
            $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping);
            
            // Construct the payload for the provider, according to their API docs.
            $payload = [
                'contact' => [
                    'Email' => $fieldValues['email'],
                    'FirstName' => $fieldValues['firstName'],
                    'LastName' => $fieldValues['lastName'],
                ],
            ];

            // Send the response to the API, any errors will be safely caught and logged
            $response = $this->deliverPayload($submission, 'contact', $payload);

            // Allow event hooks to explicitly return false here
            if ($response === false) {
                return true;
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

In our example, we only need an `apiKey` property/setting.

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

When loading the integrations area when editing a form in the control panel, you'll have access to the mapping interface. Through the integration class, we provide some settings for this mapping — namely the "lists" available for email marketing platforms, and their fields related to them. Typically, you'll be mapping at least an email address from your form to a contact in a list, but if the provider supports custom fields or other content, you can map that as well.

To provide this functionality, we define an `IntegrationFormSettings` object, which has multiple `IntegrationCollection` objects, which in turn have multiple `IntegrationField` objects. In our payload-sending function, we can then access the mapping setup for the `IntegrationCollection` we want and get values.

`IntegrationCollection` objects have a `name`, `id` and `fields`. `IntegrationField` objects have `name`, `handle`, `type`, `required`, `options`. The type allows us to set what the **destination** variable type is — what the provider's API requires the data to be supplied as. For example, you might have a Single-Line Text field in Formie, but the integration requires this as a Number. You can define what variable type to convert it to. The handle is also important for you to reference later when fetching mapped data.

In our example, we create a single `IntegrationCollection` with `lists` as it's key (we use this to refer to it later when getting mapped values), which have `email`, `firstName` and `lastName` `IntegrationField`'s contained within it. We then create an `IntegrationCollection` for every list as defined in our third-party platform's API. So depending on how many lists are defined there, will determine what we can pick in Formie (and which fields to then show).

For some APIs, the ability to fetch available properties and custom fields is also possible. Our example shows as hard-coding them in the integration, but you could do something like:

```php
// ...

$response = $this->request('GET', 'lists');
$lists = $response['lists'] ?? [];

// While we're at it, fetch the fields for the list
$fields = $this->request('GET', 'custom_fields');

foreach ($lists as $list) {
    $listFields = array_merge([
        // Add any static, hard-coded properties to map to
        new IntegrationField([
            'handle' => 'Email',
            'name' => Craft::t('formie', 'Email'),
            'required' => true,
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
Sending the data to the third-party provider is the final step in the integration lifecycle. Here, we fetch the mapping for the form, we use `getFieldMappingValues()` to get the mapped values from the submission (which handles different field types and converting to the destination variable types). With this submission data, we construct the payload to be sent to the API. This will depend entirely on the API you're using.

All going well, your payload will be successfully sent! If not, errors are caught and logged.
 
#### Templates
We also have some Twig templates to set up for the integration settings, and the settings used for the integration when editing a form.

```twig
// modules/formieintegration/src/templates/example-integration/_form-settings.html

{% import '_includes/forms' as forms %}

{% set handle = integration.handle %}
{% set formSettings = integration.getFormSettings().getSettings() %}
{% set listId = form.settings.integrations[handle].listId ?? '' %}

<field-select
    label="{{ 'Opt-In Field' | t('formie') }}"
    instructions="{{ 'Choose a field to opt-in to {name}. For example, you might only wish to subscribe users if they provide a value for a field of your choice - commonly, an Agree field.' | t('formie', { name: integration.displayName() }) }}"
    id="opt-in-field"
    name="optInField"
    :value="get(form, 'settings.integrations.{{ handle }}.optInField')"
></field-select>

<integration-form-settings handle="{{ handle }}" :form-settings="{{ formSettings | json_encode }}" source="{{ listId }}">
    <template v-slot="{ get, isEmpty, input, settings, sourceId, loading, refresh, error, errorMessage, getSourceFields }">
        <div class="field">
            <div class="heading">
                <label id="sourceId-label" for="sourceId" class="required">{{ 'List' | t('formie') }}</label>

                <div class="instructions">
                    <p>{{ 'Select your {name} list to create contacts on.' | t('formie', { name: integration.displayName() }) }}</p>
                </div>
            </div>

            <div class="input ltr">
                <div class="select">
                    <select :value="sourceId" @input="input('sourceId', $event.target.value)" name="listId" required>
                        <option value="">{{ 'Select an option' | t('formie') }}</option>

                        <option v-for="(option, index) in get(settings, 'lists')" :key="index" :value="option.id">${ option.name }</option>
                    </select>
                </div>

                <button class="btn fui-btn-transparent" :class="{ 'fui-loading fui-loading-sm': loading }" data-icon="refresh" @click.prevent="refresh"></button>
            </div>

            <ul v-if="!isEmpty(get(form, 'settings.integrations.{{ handle }}.errors.listId'))" class="errors" v-cloak>
                <li v-for="(error, index) in get(form, 'settings.integrations.{{ handle }}.errors.listId')" :key="index">
                    ${ error }
                </li>
            </ul>
        </div>

        <div v-if="error" class="error" style="margin-top: 10px;" v-cloak>
            <span data-icon="alert"></span>
            <span v-html="errorMessage"></span>
        </div>

        <integration-field-mapping
            label="{{ 'Field Mapping' | t('formie') }}"
            instructions="{{ 'Choose how your form fields should map to your {name} fields.' | t('formie', { name: integration.displayName() }) }}"
            id="field-mapping"
            name-label="{{ integration.displayName() }}"
            name="fieldMapping"
            :value="get(form, 'settings.integrations.{{ handle }}.fieldMapping')"
            :rows="getSourceFields('lists')"
        ></integration-field-mapping>

        <ul v-if="!isEmpty(get(form, 'settings.integrations.{{ handle }}.errors.fieldMapping'))" class="errors" v-cloak>
            <li v-for="(error, index) in get(form, 'settings.integrations.{{ handle }}.errors.fieldMapping')" :key="index">
                ${ error }
            </li>
        </ul>
    </template>
</integration-form-settings>
```

This template is shown when you click the integration in the **Integrations** tab when editing a form in the control panel. This will be where you set up your field mapping.

This can contain Vue and Twig code, and we commonly use `<field-select>`, `<integration-form-settings>` and `<integration-field-mapping>` Vue components.

For email marketing integrations, we really only have a single collection of data, which are lists. The user can pick the list they require and the fields within that list will be shown to map.

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

This template is shown when editing the integration settings (in **Formie** → **Settings** → **Email Marketing**. Here, we show instructions on how to fetch the required settings this integration will need in order to connect to the provider's API. It's a good idea to make use of Craft's control panel forms macros and the `autosuggestField` field.

### Extending an existing integration
An alternative to creating (and maintaining) your own custom integration is to extend an existing one. This is particularly useful if you want to integrate with an existing supported provider, but require a little more control over it.

Following the same guide as above, you'll need to create a module. This time, however, you should only need to supply the class for the integration (unless you also want to override the templates).

For example, let's say we want to modify some behaviour in the Mailchimp integration.

```php
// modules/formieintegration/src/integrations/MailchimpCustom.php

<?php
namespace modules\formieintegration\integrations;

class MailchimpCustom extends Mailchimp
{
    // Public Methods
    // =========================================================================

    public function sendPayload(Submission $submission): bool
    {
        // Implement custom code to handle the payload sending.
    }
}
```

In this instance, we might like to completely override the handling of sending the payload to Mailchimp for our own needs.

### Finishing up
With all that in place, you should be able to create a new custom integration, add it to your form, configure mapping, and make a submission. A payload will be sent to the providers' API.

Don't forget that integrations are only run when visiting the control panel (read more about [why this happens](/craft-plugins/formie/docs/get-started/troubleshooting)).
