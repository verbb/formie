# Building a Webhook integration from scratch

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
│                └── ExampleWebhook.php
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
use modules\formieintegration\integrations\ExampleWebhook;
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
            $event->webhooks[] = ExampleWebhook::class;
        });
    }
}
```

Here our main module file is pretty simple. We tell Formie we want to register a new integration class, which points to our `integrations/ExampleWebhook.php` class. The bulk of our logic will be in this class.

We also set up a `@formie-integration` alias, which we'll get to a little later on.

#### Integration class

```php
// modules/formieintegration/src/integrations/ExampleWebhook.php

<?php
namespace modules\formieintegration\integrations;

use Craft;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use GuzzleHttp\Client;
use Throwable;
use verbb\formie\Formie;
use verbb\formie\base\Integration;
use verbb\formie\base\Webhook;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

class ExampleWebhook extends Webhook
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Example Webhook Integration');
    }

    // Properties
    // =========================================================================

    public ?string $webhook = null;


    // Public Methods
    // =========================================================================

    public function getDescription(): string
    {
        return Craft::t('formie', 'Send your form content to any URL you provide.');
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['webhook'], 'required', 'on' => [Integration::SCENARIO_FORM]];

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
        $payload = [];

        try {
            $formId = Craft::$app->getRequest()->getParam('formId');
            $form = Formie::$plugin->getForms()->getFormById($formId);

            // Generate and send a test payload to the webhook endpoint
            $submission = new Submission();
            $submission->setForm($form);

            Formie::$plugin->getSubmissions()->populateFakeSubmission($submission);

            // Ensure we're fetching the webhook from the form settings, or global integration settings
            $webhook = $form->settings->integrations[$this->handle]['webhook'] ?? $this->webhook;

            $payload = $this->generatePayloadValues($submission);
            $response = $this->getClient()->request('POST', $this->getWebhookUrl($webhook, $submission), $payload);

            $rawResponse = (string)$response->getBody();
            $json = Json::decode($rawResponse);

            $settings = [
                'response' => $response,
                'json' => $json,
            ];
        } catch (Throwable $e) {
            // Save a different payload to logs
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}. Payload: “{payload}”. Response: “{response}”', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'payload' => Json::encode($payload),
                'response' => $rawResponse ?? '',
            ]));

            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        $payload = [];
        $response = [];

        try {
            // Either construct the payload yourself manually or get Formie to do it
            $payload = $this->generatePayloadValues($submission);

            //
            // OR
            //

            $payload = [
                'id' => $submission->id,
                'title' => $submission->title,

                // Handle custom fields
                'email' => $submission->getFieldValue('emailAddress'),
                // ...
            ];

            $response = $this->getClient()->request('POST', $this->getWebhookUrl($this->webhook, $submission), $payload);
        } catch (Throwable $e) {
            // Save a different payload to logs
            Integration::error($this, Craft::t('formie', 'API error: “{message}” {file}:{line}. Payload: “{payload}”. Response: “{response}”', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'payload' => Json::encode($payload),
                'response' => $response,
            ]));

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

        // Create a Guzzle client to send the payload.
        return $this->_client = Craft::createGuzzleClient();
    }
}
```

Let's dissect the different functions of this class, which pertain to a different part of the flow from integration setup, form submission and sending to the webhook endpoint.

As each platform has different requirements, these instructions will be broad.

#### Integration settings
Your integration will likely have some settings associated with it — at the very least an endpoint for the webhook payload to be sent to. These are stored as properties in the class.

It's also a great idea to allow any sensitive settings to be set to .env variables. We'll walk through how this is done via templates further in this guide. These properties then need to be run through `App::parseEnv()` to actually get the value for use in your class.

In our example, we only need a `webhook` property/setting for defining the URL to send our payload of data to.

##### Guzzle client
We use [Guzzle](https://docs.guzzlephp.org/en/stable/) for the requests out to the third-party APIs so we can create consistent and standard connectors, along with it being easy to adopt. This handles the HTTP requests (GET, POST, PUT, etc) along with authentication like Basic HTTP, OAuth and more.

As such, each integration will have a Guzzle client configured to it. As for using the Guzzle client in your class, we use [`$this->request()`](https://github.com/verbb/formie/blob/40a98cceacaa2c648c4e9ba88e148b36ae75eb8a/src/base/Integration.php#L491) from Formie as a bit of a helper function to just handle JSON APIs, but it's totally equivalent to:

```php
use craft\helpers\Json;

$response = $this->getClient()->request($method, $url, $params);
$json = Json::decode((string)$response->getBody());
```

##### Fetching settings
With the integration setup, we'll want to test if it's all working. For webhook integrations, there's no mapping functionality, unlike other integrations. That's because webhooks by nature are just sent a payload of data, and it's on the provider end to determine what to do with it. As such, it's also another way of doing POST forwarding.

Selecting the integration in your form settings, you'll be able to send a test payload of your data, which is useful for testing.

##### Sending payload
Sending the data to the endpoint is the final step in the integration lifecycle. Here, we construct the payload to be sent to the webhook endpoint. You can use either the `generatePayloadValues()` function for Formie to generate an all-inclusive payload or construct your own.

All going well, your payload will be successfully sent! If not, errors are caught and logged.
 
#### Templates
We also have some Twig templates to set up for the integration settings, and the settings used for the integration when editing a form.

```twig
// modules/formieintegration/src/templates/example-integration/_form-settings.html

{% import '_includes/forms' as forms %}

{% set handle = integration.handle %}
{% set formSettings = integration.getFormSettings().getSettings() %}
{% set webhook = form.settings.integrations[handle].webhook ?? null %}

{{ forms.textField({
    label: 'Webhook URL' | t('formie'),
    instructions: 'Enter the {name} webhook URL that will be triggered when a submission is made.' | t('formie', { name: integration.displayName() }),
    name: 'webhook',
    required: true,
    value: webhook ?? integration.settings.webhook ?? '',
    errors: integration.getErrors('webhook'),
}) }}

<integration-form-settings handle="{{ handle }}" :form-settings="{{ formSettings | json_encode }}">
    <template v-slot="{ get, isEmpty, input, settings, sourceId, loading, refresh, error, errorMessage, success, getSourceFields }">
        <div class="field">
            <button class="btn" :class="{ 'fui-loading fui-loading-sm': loading }" data-icon="refresh" @click.prevent="refresh">{{ 'Send Test Payload' | t('formie') }}</button>

            <div style="padding-top: 10px;">
                <span class="warning with-icon">{{ 'You must save your form before sending a test payload.' | t('formie') }}</span>
            </div>
        </div>

        <div v-if="error" class="error" style="margin-top: 10px;" v-cloak>
            <span data-icon="alert"></span>
            <span v-html="errorMessage"></span>
        </div>

        <div v-if="success" class="success" style="margin-top: 10px;" v-cloak>
            {{ 'Success!' | t('formie') }}<br>
            <pre><code v-if="get(settings, 'json')">${ get(settings, 'json') }</code><code v-else="get(settings, 'response')"><br>${ get(settings, 'response') }</code></pre>
        </div>
    </template>
</integration-form-settings>
```

This template is shown when you click the integration in the **Integrations** tab when editing a form in the control panel.

This can contain Vue and Twig code, and we commonly use `<field-select>`, `<integration-form-settings>` and `<integration-field-mapping>` Vue components.

For Webhook integrations, you can send a test payload to ensure everything is set up correctly on the provider end.

```twig
// modules/formieintegration/src/templates/example-integration/_plugin-settings.html

{% import '_includes/forms' as forms %}
{% import 'verbb-base/_macros' as macros %}

{{ forms.autosuggestField({
    label: 'Webhook URL' | t('formie'),
    instructions: 'Enter the {name} webhook URL that will be triggered when a submission is made.' | t('formie', { name: integration.displayName() }),
    name: 'webhook',
    suggestEnvVars: true,
    value: integration.settings.webhook ?? '',
    warning: macros.configWarning('webhook', 'formie'),
    errors: integration.getErrors('webhook'),
}) }}
```

This template is shown when editing the integration settings (in **Formie** → **Settings** → **Webhooks**. Typically, you'll only need to define the endpoint for your webhook, but you can add other settings as required.

### Extending an existing integration
An alternative to creating (and maintaining) your own custom integration is to extend an existing one. This is particularly useful if you want to integrate with an existing supported provider, but require a little more control over it.

Following the same guide as above, you'll need to create a module. This time, however, you should only need to supply the class for the integration (unless you also want to override the templates).

For example, let's say we want to modify some behaviour in the Webhook integration.

```php
// modules/formieintegration/src/integrations/WebhookCustom.php

<?php
namespace modules\formieintegration\integrations;

class WebhookCustom extends Webhook
{
    // Protected Methods
    // =========================================================================

    protected function generatePayloadValues(Submission $submission): array
    {
        // Implement a custom payload value for webhooks to send.
    }
}
```

In this instance, we might be happy with the default webhook integration, but just want to modify the structure of the payload.

### Finishing up
With all that in place, you should be able to create a new custom integration, add it to your form, test, and make a submission. A payload will be sent to the provided webhook endpoint.

Don't forget that integrations are only run when visiting the control panel (read more about [why this happens](/get-started/troubleshooting)).
