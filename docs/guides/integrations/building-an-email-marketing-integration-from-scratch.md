# Building an Email Marketing integration from scratch

Formie ships with dozens of email marketing integrations — Mailchimp, Campaign Monitor, Klaviyo, and more — but you can register your own when your provider is not covered out of the box. This walkthrough builds a list-and-mapping integration from a Craft module: connect with an API key, let editors pick a list per form, map fields, and subscribe contacts when someone submits.

We also cover extending a built-in integration when you only need to adjust payload handling.

Read the [Custom Integration Overview](/developers/custom-integration/overview) and [Email Marketing Integration](/developers/custom-integration/email-marketing-integration) reference first if you have not built an integration before — this guide walks through a complete example and explains how the pieces connect.

## Create your module

First, you need a [Craft module](https://craftcms.com/docs/5.x/extend/module-guide.html). All of the PHP in this guide lives in that module.

When creating your module, set the namespace to `modules\formieintegration` and the module ID to `formie-integration`. Create the following directory and file structure:

```treeview
my-project/
├── modules/
│   └── formieintegration/
│       └── src/
│           ├── integrations/
│           │   └── ExampleEmailMarketing.php
│           ├── templates/
│           │   └── example-integration/
│           │       └── _plugin-settings.html
│           └── FormieIntegration.php
└── ...
```

You will add `ExampleEmailMarketing.php` in the next section. The `_plugin-settings.html` template is for global integration credentials — we will come back to that once the integration class exists.

Register the module in your project config, then wire it up to Formie in `FormieIntegration.php`:

```php [modules/formieintegration/src/FormieIntegration.php]
<?php
namespace modules\formieintegration;

use Craft;
use modules\formieintegration\integrations\ExampleEmailMarketing;
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
            $event->emailMarketing[] = ExampleEmailMarketing::class;
        });
    }
}
```

The module file stays deliberately small. You tell Formie which integration class to load by pushing it onto `$event->emailMarketing[]`. The alias `@formie-integration` points at your module's `src/` folder so Craft can resolve template paths like `formie-integration/example-integration/_plugin-settings`.

The bulk of the work lives in `ExampleEmailMarketing.php`.

## The integration class

Create `modules/formieintegration/src/integrations/ExampleEmailMarketing.php`. The class extends Formie's `EmailMarketing` base class. That base class already handles a lot of the form-builder UI — list selection, field mapping, opt-in field, and conditions — so your class focuses on connecting to the provider API and sending the right payload shape.

Here is the full class. Each method maps to a step in the lifecycle from **plugin settings** through **list discovery** to **subscribe on submit** — we walk through them in the sections below.

```php [modules/formieintegration/src/integrations/ExampleEmailMarketing.php]
<?php
namespace modules\formieintegration\integrations;

use Craft;
use craft\helpers\App;
use GuzzleHttp\Client;
use Throwable;
use verbb\formie\base\EmailMarketing;
use verbb\formie\base\FormInterface;
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

class ExampleEmailMarketing extends EmailMarketing
{
    public static function displayName(): string
    {
        return Craft::t('formie', 'Example Email Marketing Integration');
    }

    public ?string $apiKey = null;

    public function getDescription(): string
    {
        return Craft::t('formie', 'Sign up users to your Example Email Marketing lists.');
    }

    public function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['apiKey'], 'required'];

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
        $settings = [];

        try {
            $response = $this->request('GET', 'lists');
            $lists = $response['lists'] ?? [];

            foreach ($lists as $list) {
                $listFields = [
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

                $settings['lists'][] = new IntegrationCollection([
                    'id' => (string)$list['id'],
                    'name' => $list['name'] . ' (' . $list['id'] . ')',
                    'fields' => $listFields,
                ]);
            }
        } catch (Throwable $e) {
            Integration::apiError($this, $e);
        }

        return new IntegrationFormSettings($settings);
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping);

            $payload = [
                'contact' => [
                    'Email' => $fieldValues['email'] ?? null,
                    'FirstName' => $fieldValues['firstName'] ?? null,
                    'LastName' => $fieldValues['lastName'] ?? null,
                ],
            ];

            $response = $this->deliverPayload($submission, 'contact', $payload);

            return $response !== false;
        } catch (Throwable $e) {
            Integration::apiError($this, $e);

            return false;
        }
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
            'label' => Craft::t('formie', 'Resubscribe'),
            'instructions' => Craft::t('formie', 'Whether existing unsubscribed contacts should be resubscribed.'),
            'name' => 'resubscribe',
        ]);

        return $schema;
    }
}
```

Every provider has different list APIs and payload shapes — treat the method names and response parsing as patterns to adapt.

### Integration settings

Your integration needs global credentials — typically an API key — stored as public properties on the class (`$apiKey` in the example).

Mark required settings in `defineRules()`. The parent rules handle integration-level validation; add yours for credentials saved under **Formie → Integrations → Email Marketing**.

Sensitive values should support Craft environment variables. Store `$MY_PROVIDER_API_KEY` in the control panel, then read the resolved value with `App::parseEnv($this->apiKey)` when building requests.

For OAuth-based email marketing providers, see [Creating OAuth integrations with Formie](/guides/integrations/creating-oauth-integrations-with-formie) instead of API keys.

### Guzzle client

Formie uses [Guzzle](https://docs.guzzlephp.org/) for outbound HTTP. Implement `defineClient()` to configure authentication and your provider's base URL.

Use `$this->request('GET', 'lists')` for JSON APIs — Formie decodes responses and handles common error paths. Use `fetchConnection()` for a lightweight test when an editor clicks **Test connection** in the control panel.

### Fetching lists and mappable fields

When an editor opens the **Integrations** tab on a form, Formie needs to know which lists exist and which fields can be mapped on each list. That is what `fetchFormSettings()` returns.

Email marketing integrations use **`IntegrationCollection`** for each list — not plain object keys like CRM contacts and deals. Each collection has:

| Attribute | Purpose |
| --- | --- |
| `id` | List identifier the provider expects when subscribing |
| `name` | Label shown in the list picker |
| `fields` | Array of `IntegrationField` instances mappable on that list |

Return them under the `lists` key inside `IntegrationFormSettings`:

```php
$settings['lists'][] = new IntegrationCollection([
    'id' => (string) $list['id'],
    'name' => $list['name'],
    'fields' => $listFields,
]);
```

The `EmailMarketing` base class renders the list selector and field-mapping UI from this data. When you call `getFieldMappingValues($submission, $this->fieldMapping)` in `sendPayload()`, Formie resolves values for the list the editor selected on this form.

Many providers expose custom merge fields or attributes through their API. Fetch them and merge with your static fields:

```php
$fields = $this->request('GET', 'custom_fields');

foreach ($lists as $list) {
    $listFields = array_merge([
        new IntegrationField([
            'handle' => 'email',
            'name' => Craft::t('formie', 'Email'),
            'required' => true,
        ]),
    ], $this->convertApiFields($fields));
}
```

Map provider field types to `IntegrationField::TYPE_*` when the API returns typed metadata so Formie converts submission values correctly.

### Form settings schema

The `EmailMarketing` base class already defines the core per-form UI:

- **Enabled** lightswitch
- **Conditions** (optional dispatch rules)
- **Opt-in field** (only subscribe when a specific field is filled — commonly an Agree checkbox)
- **List** selector with refresh
- **Field mapping** for the selected list

Override `defineFormSettingsSchema()` when you need extra per-form settings. The example adds a **Resubscribe** lightswitch — whether to resubscribe contacts who previously unsubscribed.

Start with `parent::defineFormSettingsSchema($form)` so you keep the standard controls. Per-form integration UI is declared as schema nodes — see [Everything you need to know about Formie schemas](/guides/developers/everything-you-need-to-know-about-formie-schemas) for helper details.

### Plugin settings template

Global credentials and setup instructions are rendered with Twig. `getSettingsHtml()` points at `_plugin-settings.html`, shown when creating or editing the integration under **Formie → Integrations → Email Marketing**.

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

Step-by-step instructions in the template reduce support burden — tell editors exactly where to find the API key in the provider's dashboard.

### Sending the payload

`sendPayload()` runs when a submission completes and this integration is dispatched for the form.

1. Call `getFieldMappingValues($submission, $this->fieldMapping)` to read mapped submission values for the selected list.
2. Build the array shape your provider's subscribe or upsert endpoint expects.
3. Send with `deliverPayload($submission, 'contact', $payload)`.

The base class respects the **Opt-in field** — if configured, the integration skips when the opt-in condition is not met. Return `false` on failure so Formie records the integration run as failed. Exceptions should go through `Integration::apiError()`.

## Extending an existing integration

Creating a full integration from scratch is not always worth it. If Formie already ships your provider — Mailchimp, for example — extend the built-in class and override only what you need:

```php [modules/formieintegration/src/integrations/MailchimpCustom.php]
<?php
namespace modules\formieintegration\integrations;

use verbb\formie\elements\Submission;
use verbb\formie\integrations\emailmarketing\Mailchimp;

class MailchimpCustom extends Mailchimp
{
    public function sendPayload(Submission $submission): bool
    {
        // Custom payload handling, then optionally call parent.
        return parent::sendPayload($submission);
    }
}
```

Register `MailchimpCustom::class` on `$event->emailMarketing[]` instead of a from-scratch class.

## Finishing up

With the module in place:

1. Go to **Formie → Integrations → Email Marketing** and create a new **Example Email Marketing Integration** instance. Enter credentials and run **Test connection**.
2. Edit a form, open the **Integrations** tab, and attach the integration. Pick a list, map form fields to list fields, and configure opt-in if needed.
3. Submit the form on the front end and confirm the contact appears in the provider.

Integrations run as part of the [submission workflow](/developers/submission-workflow) — typically queued after the submission is saved. For dispatch order, conditions, and re-run behaviour, see [Integration dispatch and policies](/guides/integrations/integration-dispatch-and-policies).

## Related

- [Email Marketing Integration](/developers/custom-integration/email-marketing-integration)
- [Custom Integration Overview](/developers/custom-integration/overview)
- [Creating OAuth integrations with Formie](/guides/integrations/creating-oauth-integrations-with-formie)
- [Integration dispatch and policies](/guides/integrations/integration-dispatch-and-policies)
