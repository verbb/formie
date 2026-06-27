# Building a CRM integration from scratch

Formie ships with dozens of CRM integrations — Salesforce, HubSpot, Pipedrive, and many more — but you can register your own when your provider is not covered out of the box. This walkthrough builds a multi-object CRM integration from a Craft module: connection settings, per-form field mapping, and sending submission data to a third-party API.

We also cover extending a built-in integration when you only need a little extra behaviour, rather than maintaining a full custom connector.

Read the [Custom Integration Overview](/developers/custom-integration/overview) and [CRM Integration](/developers/custom-integration/crm-integration) reference first if you have not built an integration before — this guide walks through a complete example and explains how the pieces connect.

## Create your module

First, you need a [Craft module](https://craftcms.com/docs/5.x/extend/module-guide.html). All of the PHP in this guide lives in that module.

When creating your module, set the namespace to `modules\formieintegration` and the module ID to `formie-integration`. Create the following directory and file structure:

```treeview
my-project/
├── modules/
│   └── formieintegration/
│       └── src/
│           ├── integrations/
│           │   └── ExampleCrm.php
│           ├── templates/
│           │   └── example-integration/
│           │       └── _plugin-settings.html
│           └── FormieIntegration.php
└── ...
```

You will add `ExampleCrm.php` in the next section. The `_plugin-settings.html` template is for global integration credentials (API keys and similar) — we will come back to that once the integration class exists.

Register the module in your project config, then wire it up to Formie in `FormieIntegration.php`:

```php [modules/formieintegration/src/FormieIntegration.php]
<?php
namespace modules\formieintegration;

use Craft;
use modules\formieintegration\integrations\ExampleCrm;
use verbb\formie\events\RegisterIntegrationsEvent;
use verbb\formie\services\Integrations;
use yii\base\Event;
use yii\base\Module;

class FormieIntegration extends Module
{
    public function init(): void
    {
        parent::init();

        Craft::setAlias('@formie-integration', __DIR__);

        Event::on(Integrations::class, Integrations::EVENT_REGISTER_INTEGRATIONS, function(RegisterIntegrationsEvent $event) {
            $event->crm[] = ExampleCrm::class;
        });
    }
}
```

The module file stays deliberately small. You tell Formie which integration class to load by pushing it onto `$event->crm[]`. The alias `@formie-integration` points at your module's `src/` folder so Craft can resolve template paths like `formie-integration/example-integration/_plugin-settings`.

The bulk of the work lives in `ExampleCrm.php`.

## The integration class

Create `modules/formieintegration/src/integrations/ExampleCrm.php`. The class extends Formie's `Crm` base class and implements the lifecycle from **plugin settings** (connect to the API) through **form settings** (map fields per form) to **send payload** (push data when someone submits).

Here is the full class. Each method maps to a step in that lifecycle — we walk through them in the sections below.

```php [modules/formieintegration/src/integrations/ExampleCrm.php]
<?php
namespace modules\formieintegration\integrations;

use Craft;
use craft\helpers\App;
use GuzzleHttp\Client;
use Throwable;
use verbb\formie\base\Crm;
use verbb\formie\base\FormInterface;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

class ExampleCrm extends Crm
{
    public static function displayName(): string
    {
        return Craft::t('formie', 'Example CRM Integration');
    }

    public ?string $apiKey = null;
    public bool $mapToContact = false;
    public bool $mapToDeal = false;
    public ?array $contactFieldMapping = null;
    public ?array $dealFieldMapping = null;

    public function getDescription(): string
    {
        return Craft::t('formie', 'Manage Example CRM contacts and deals from form submissions.');
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['apiKey'], 'required'];

        $contact = $this->getFormSettingValue('contact');
        $deal = $this->getFormSettingValue('deal');

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

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('formie-integration/example-integration/_plugin-settings', [
            'integration' => $this,
        ]);
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        try {
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
                    'type' => IntegrationField::TYPE_NUMBER,
                ]),
            ];

            return new IntegrationFormSettings([
                'contact' => $contactFields,
                'deal' => $dealFields,
            ]);
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return new IntegrationFormSettings();
        }
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            if ($this->mapToContact) {
                $contactValues = $this->getFieldMappingValues($submission, $this->contactFieldMapping, 'contact');

                $response = $this->deliverPayload($submission, 'contacts', [
                    'email' => $contactValues['email'] ?? null,
                    'firstName' => $contactValues['firstName'] ?? null,
                    'lastName' => $contactValues['lastName'] ?? null,
                ]);

                if ($response === false) {
                    return false;
                }
            }

            if ($this->mapToDeal) {
                $dealValues = $this->getFieldMappingValues($submission, $this->dealFieldMapping, 'deal');

                $response = $this->deliverPayload($submission, 'deals', [
                    'title' => $dealValues['title'] ?? null,
                    'description' => $dealValues['description'] ?? null,
                    'value' => $dealValues['value'] ?? null,
                ]);

                if ($response === false) {
                    return false;
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
            $this->request('GET', 'account');
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }

    protected function defineClient(): Client
    {
        return Craft::createGuzzleClient([
            'base_uri' => 'https://api.provider.com/v1/',
            'headers' => ['apikey' => App::parseEnv($this->apiKey)],
        ]);
    }

    protected function defineFormSettingsSchema(FormInterface $form): array
    {
        $schema = parent::defineFormSettingsSchema($form);

        $schema[] = SchemaHelper::lightswitchField([
            'name' => 'mapToContact',
            'label' => Craft::t('formie', 'Map to Contact'),
            'instructions' => Craft::t('formie', 'Whether to map form data to a contact.'),
        ]);

        $schema[] = $this->getIntegrationFieldMappingField([
            'name' => 'contactFieldMapping',
            'if' => 'mapToContact',
            'dataLabel' => 'Contact',
            'dataKey' => 'contact',
        ]);

        $schema[] = SchemaHelper::lightswitchField([
            'name' => 'mapToDeal',
            'label' => Craft::t('formie', 'Map to Deal'),
            'instructions' => Craft::t('formie', 'Whether to map form data to a deal.'),
        ]);

        $schema[] = $this->getIntegrationFieldMappingField([
            'name' => 'dealFieldMapping',
            'if' => 'mapToDeal',
            'dataLabel' => 'Deal',
            'dataKey' => 'deal',
        ]);

        return $schema;
    }
}
```

Every provider has different API requirements, so treat the method names and payload shapes as patterns to adapt — not copy-paste values.

### Integration settings

Your integration almost always needs global settings — at minimum an API key to talk to the provider. Store these as public properties on the class (`$apiKey` in the example).

Mark required settings in `defineRules()`. The parent rules already handle integration-level validation; add yours for credentials saved under **Formie → Integrations → CRM**.

Sensitive values should support Craft environment variables. Store `$MY_CRM_API_KEY` in the control panel, then read the resolved value with `App::parseEnv($this->apiKey)` when building requests — never hard-code secrets in PHP.

Form-level mapping properties (`$mapToContact`, `$contactFieldMapping`, and so on) are validated separately when the form is saved, using `Integration::SCENARIO_FORM` and `validateFieldMapping`. That keeps "is the API key present?" separate from "did the editor map required CRM fields for this form?".

### Guzzle client

Formie uses [Guzzle](https://docs.guzzlephp.org/) for outbound HTTP. Implement `defineClient()` to return a configured client for your provider's base URL and authentication headers.

For most JSON APIs, call `$this->request('GET', 'account')` or `$this->request('POST', 'contacts', ['json' => $payload])` rather than using the client directly — Formie decodes JSON responses and handles common error paths. It is equivalent to:

```php
use craft\helpers\Json;

$response = $this->getClient()->request($method, $url, $params);
$json = Json::decode((string) $response->getBody());
```

Use `fetchConnection()` for a lightweight test request when an editor clicks **Test connection** in the control panel. Any exception should flow through `Integration::apiError()` so the message appears in the UI and in logs.

### Fetching form settings

When an editor opens the **Integrations** tab on a form, Formie needs to know which CRM fields can be mapped. CRM integrations usually support multiple **objects** — contacts, deals, leads, accounts, opportunities, and so on.

`fetchFormSettings()` returns an `IntegrationFormSettings` object: a keyed array where each key is an object handle (`contact`, `deal`, …) and each value is a list of `IntegrationField` instances.

```php
return new IntegrationFormSettings([
    'contact' => $contactFields,
    'deal' => $dealFields,
]);
```

Each `IntegrationField` describes a destination field on the provider side:

| Attribute | Purpose |
| --- | --- |
| `handle` | Key you use in `getFieldMappingValues()` and when building the API payload |
| `name` | Label shown in the mapping UI |
| `required` | Mapping must be filled when the object is enabled |
| `type` | Destination type Formie converts to (`TYPE_STRING`, `TYPE_NUMBER`, `TYPE_DATETIME`, …) |

The `type` matters when a Formie field does not match the API. A Single-Line Text field might need to arrive as a number — set `IntegrationField::TYPE_NUMBER` on the destination and Formie handles conversion when resolving mapped values.

Many CRMs expose custom properties through their API. You can hard-code fields as in the example above, or fetch them dynamically and merge:

```php
$fields = $this->request('GET', 'contacts/custom_fields');

$contactFields = array_merge([
    new IntegrationField([
        'handle' => 'email',
        'name' => Craft::t('formie', 'Email'),
        'required' => true,
    ]),
    // …
], $this->getCustomFields($fields));
```

Map provider field types to `IntegrationField::TYPE_*` in a small helper so dropdowns, dates, and numbers convert correctly at submit time.

If your CRM uses selectable object types rather than fixed keys, return an `IntegrationCollection` instead — see [CRM Integration](/developers/custom-integration/crm-integration) for that pattern.

### Form settings schema

Plugin settings (API keys) use a Twig template — see below. **Per-form** settings — enable contact mapping, map deal fields, set conditions — are defined in PHP with `defineFormSettingsSchema()`.

Start with `parent::defineFormSettingsSchema($form)`. The parent schema already includes **Enabled**, dispatch behaviour, conditions, and other shared controls.

For each CRM object you support, add:

1. A lightswitch (`mapToContact`, `mapToDeal`, …) so editors can turn that object on for this form.
2. A field-mapping block via `getIntegrationFieldMappingField()`, with `dataKey` matching the key you used in `fetchFormSettings()`.

The `if` attribute hides the mapping UI until the lightswitch is on. When an editor saves the form, values land on the public properties you declared on the class (`$mapToContact`, `$contactFieldMapping`, etc.).

Each object follows the same pattern: opt in with a lightswitch, then map fields when that object is enabled. Per-form integration UI is declared as schema nodes — see [Everything you need to know about Formie schemas](/guides/developers/everything-you-need-to-know-about-formie-schemas) for helper details.

### Plugin settings template

Global credentials and setup instructions are still rendered with Twig. `getSettingsHtml()` points at `_plugin-settings.html`, shown when creating or editing the integration under **Formie → Integrations → CRM**.

```twig [modules/formieintegration/src/templates/example-integration/_plugin-settings.html]
{% import '_includes/forms' as forms %}
{% import 'verbb-base/_macros' as macros %}

{% set instructions %}
### Step 1. Connect to the {name} API
1. Go to <a href="https://www.provider.com/" target="_blank">{name}</a> and log in.
1. Open **Settings → API** and copy your **API Key**.
1. Paste the key into the field below.

### Step 2. Test connection
1. Save this integration.
1. Click **Test connection** in the sidebar.
{% endset %}

<div class="fui-settings-block">
    {{ instructions | t('formie', { name: integration.displayName() }) | md }}
</div>

{% if not craft.app.config.general.allowAdminChanges %}
    <span class="warning with-icon">
        {{ 'Integration settings can only be edited when `allowAdminChanges` is enabled.' | t('formie') | md(inlineOnly=true) }}
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

Use Craft's `autosuggestField` so editors can pick `$ENV_VAR` names for secrets. Step-by-step instructions in the template reduce support burden — tell people exactly where to find the API key in the provider's dashboard.

### Sending the payload

`sendPayload()` runs when a submission completes and this integration is dispatched for the form. That is the last step in the lifecycle.

For each enabled object:

1. Call `getFieldMappingValues($submission, $this->contactFieldMapping, 'contact')` — the third argument must match the object key from `fetchFormSettings()`.
2. Build the array shape your provider's API expects.
3. Send it with `deliverPayload($submission, 'contacts', $payload)`.

`getFieldMappingValues()` reads what the user submitted and applies the type conversions you defined on each `IntegrationField`. If an editor mapped **Email Address** (Formie) → **Email** (CRM), that is the value you get under `$contactValues['email']`.

Check each object's enable flag before sending — `$this->mapToContact` and `$this->mapToDeal` in the example — so you do not POST empty payloads for objects the editor turned off.

Return `false` on failure so Formie records the integration run as failed. Exceptions should go through `Integration::apiError()` for consistent logging.

### Picklists and option sources

If your CRM caches picklist values (industry, lead source, pipeline stage), you can expose them to Dropdown, Radio, and Checkboxes fields through `defineOptionSources()`. See [Option Sources](/developers/custom-integration/option-sources).

## Extending an existing integration

Creating a full integration from scratch is not always worth it. If Formie already ships your provider — Salesforce, for example — extend the built-in class and override only what you need.

Follow the same module setup as above, but register a subclass instead of a from-scratch `Crm` implementation. You typically only need the integration PHP class unless you also want custom plugin-settings templates.

Suppose you want Salesforce's default contact and deal mapping, plus a custom **Pipeline** object:

```php [modules/formieintegration/src/integrations/SalesforceCustom.php]
<?php
namespace modules\formieintegration\integrations;

use Craft;
use Throwable;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\integrations\crm\Salesforce;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

class SalesforceCustom extends Salesforce
{
    public bool $mapToPipeline = false;
    public ?array $pipelineFieldMapping = null;

    public function defineRules(): array
    {
        $rules = parent::defineRules();
        $pipeline = $this->getFormSettingValue('pipeline');

        $rules[] = [
            ['pipelineFieldMapping'], 'validateFieldMapping', 'params' => $pipeline, 'when' => function($model) {
                return $model->enabled && $model->mapToPipeline;
            }, 'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        $settings = parent::fetchFormSettings();

        $settings->setSettings(array_merge($settings->getSettings(), [
            'pipeline' => [
                new IntegrationField(['handle' => 'name', 'name' => Craft::t('formie', 'Name')]),
                new IntegrationField(['handle' => 'value', 'name' => Craft::t('formie', 'Value')]),
            ],
        ]));

        return $settings;
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            parent::sendPayload($submission);

            if ($this->mapToPipeline) {
                // Custom pipeline handling…
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }

        return true;
    }
}
```

Register `SalesforceCustom::class` on `$event->crm[]` instead of `ExampleCrm::class`. Add matching lightswitch and field-mapping nodes in `defineFormSettingsSchema()` for the new `pipeline` object.

You keep the provider's OAuth flow, connection handling, and existing objects — and layer your extra object on top.

## Finishing up

With the module in place:

1. Go to **Formie → Integrations → CRM** and create a new **Example CRM Integration** instance. Enter credentials and run **Test connection**.
2. Edit a form, open the **Integrations** tab, and attach the integration. Enable **Map to Contact** or **Map to Deal**, then map form fields to CRM fields.
3. Submit the form on the front end and confirm records appear in the provider (or in your API logs while developing).

Integrations run as part of the [submission workflow](/developers/submission-workflow) — typically queued after the submission is saved. If nothing fires immediately, check your queue and the submission's integration log in the control panel.

For OAuth-based CRMs, see [Creating OAuth integrations with Formie](/guides/integrations/creating-oauth-integrations-with-formie). For dispatch order, conditions, and re-run behaviour, see [Integration dispatch and policies](/guides/integrations/integration-dispatch-and-policies).

## Related

- [CRM Integration](/developers/custom-integration/crm-integration)
- [Custom Integration Overview](/developers/custom-integration/overview)
- [Option Sources](/developers/custom-integration/option-sources)
- [Submission Workflow](/developers/submission-workflow)
