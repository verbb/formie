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

Integration results are stored in an `IntegrationRunResults`, not on the Submission element. During integration and notification execution, Formie scopes reference resolution to the active run. To inspect a particular run explicitly, use `Formie::$plugin->getIntegrationDispatcher()->loadContext($submission, $executionUid)`. Omitting the identity outside an active run returns an empty context; it does not select the latest result. Use the `executionUid` from the delivery event or attempt you are inspecting.

Most integration classes define a few common methods.

Method | Use
--- | ---
`displayName()` | The integration name shown in the control panel.
`getDescription()` | A short description shown in integration selection screens.
`getIconUrl()` | The icon URL shown in the control panel. Many core integrations use Formie’s default icon path for their category.
`defineClient()` | Creates the Guzzle client used by `request()` and `deliverPayload()`.
`fetchConnection()` | Checks whether the integration can connect to the provider.
`fetchConfig()` | Fetches provider data used by the form builder, such as lists, fields, channels or element layouts.
`defineFormSettingsSchema()` | Defines the integration settings shown inside a form’s Integrations tab.
`#[FormIntegrationSetting]` | Marks properties that each form may configure.
`execute(IntegrationRunContext $context)` | Runs the integration with the supplied context and returns an `IntegrationResult`.

`getSettingsHtml()` renders plugin-level integration settings in Formie’s settings area. Use `defineFormSettingsSchema()` for the settings shown on each form.

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

Annotate every existing property that forms may configure. Inherited annotations are included. Schema nodes, validation rules and method overrides cannot grant access to other properties.

```php
use verbb\formie\attributes\FormIntegrationSetting;

#[FormIntegrationSetting]
public ?string $url = null;

#[FormIntegrationSetting]
public ?array $fieldMapping = null;
```

For registered integrations, Formie discards undeclared form values before saving or populating the integration. If an integration class is temporarily unavailable, Formie preserves its saved settings without trying to create an instance. This keeps plugin-level settings such as API keys and base URLs separate from form-level mappings and options.

If a declared form setting contains an outbound URL, send to it with `requestPublicEndpoint()` or `deliverPayloadToPublicEndpoint()`. These methods use a credential-free client, reject private and reserved network targets, disable redirects and pin DNS resolution. Continue using `request()` and `deliverPayload()` for the integration provider's fixed API endpoints.

Many integrations also use field mapping. The helper expects provider fields that have already been fetched into `IntegrationConfig`.

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

## Integration Config

Use `fetchConfig()` to fetch data the form builder needs before a user configures the integration on a form. This data is cached by `getConfig()` and refreshed when the form builder asks Formie to refresh integration data.

```php
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationConfig;

public function fetchConfig(): IntegrationConfig
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

    return new IntegrationConfig([
        'contact' => $contactFields,
    ]);
}
```

`IntegrationConfig` can contain plain arrays, `IntegrationField` instances, and `IntegrationCollection` instances. Email marketing integrations often return lists, each with its own fields.

## Integration Option Sources

If your integration caches selectable provider fields, you can expose them as dynamic option lists for Dropdown, Radio and Checkboxes fields. Declare sources in `defineOptionSources()` on the integration class.

See [Option Sources](/developers/custom-integration/integration-option-sources) for storage shapes, builder labels, testing, and examples from Mailchimp and CRM integrations.

```php
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationConfig;

public function fetchConfig(): IntegrationConfig
{
    $config = [];
    $lists = $this->request('GET', 'lists');

    foreach ($lists as $list) {
        $config['lists'][] = new IntegrationCollection([
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

    return new IntegrationConfig($config);
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
Dispatchable integrations implement `DispatchableIntegrationInterface`; the CRM, Email Marketing, Automation, Element, Help Desk, Messaging and Miscellaneous bases already implement it. Use `execute(IntegrationRunContext $context)` for a custom implementation. The context supplies the submission, immutable execution identity, attempt UID, prior results from the same run, and execution-local delivery state. Use `getFieldMappingValues()` to resolve the configured form mapping, and `deliverPayload()` when sending to a remote endpoint so Formie can run the before/after payload events. Invoke integrations through `IntegrationRunner` so authority, policy and retry checks apply. The inherited `sendPayload()` facade and overridden Formie 3 methods are adapted in compatibility traits.

```php
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use Throwable;

public function execute(\verbb\formie\models\IntegrationRunContext $context): \verbb\formie\models\IntegrationResult
{
    $this->beginRun($context);
    $submission = $context->submission;
    $this->beginPayloadDelivery($submission);
    try {
        $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping);

        $payload = [
            'contact' => $fieldValues,
        ];

        $response = $this->deliverPayload($submission, 'contacts', $payload);

        if ($response === false) {
            return $this->resultForPayload(false);
        }
    } catch (Throwable $e) {
        Integration::apiError($this, $e);

        return $this->resultForPayload(false);
    }

    return $this->resultForPayload(true);
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

- [Option Sources](/developers/custom-integration/integration-option-sources)
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

## Configuration and Delivery Ownership

`Integration` holds the global connection settings and provider behaviour. `FormIntegration` combines that connection with a form's enabled state, conditions, opt-in, trigger policy, execution lane and annotated provider-specific settings. This binding is immutable; extensions use it without creating a binding subclass. The common policy settings do not need property annotations. Triggers are stored under `settings.integrations.<handle>.trigger`.

The dispatch plan puts integrations into synchronous or queued lanes, as described in [Integration Dispatch and Policies](/guides/integrations/integration-dispatch-and-policies). Formie clones the connection for each binding and attempt, so one form's settings do not affect another. Avoid static mutable provider state. If your integration caches additional clients, clear those caches in `__clone()` after calling the parent implementation. Assign a new conditions array when updating conditions, rather than mutating a nested value in place.

`IntegrationConfig` stores versioned non-secret builder metadata, its fetch time and invalidation key. Metadata is stale after 24 hours but remains available for display until an explicit refresh. Editing a connection invalidates its metadata. Only inert field and collection metadata is hydrated; arbitrary class names are rejected. `IntegrationField` remains the mapping-field model. Environment references are preferred for credentials. Literal global settings and permitted per-form secrets are encrypted at rest using Formie's Craft-compatible security key; retain that key when restoring data.

Annotate sensitive properties with `#[\verbb\formie\attributes\Sensitive]`, including credentials whose names do not contain “secret” or “token”. Inherited annotations are recognized. This metadata supplies known values for log, delivery-result, support-bundle and configuration-cache redaction, and for encryption of permitted per-form settings. It does not grant form-builder assignment: that still requires `#[FormIntegrationSetting]`. Native credentials are annotated explicitly; conservative name-based recognition remains for older third-party integrations. Prefer environment references for project-managed credentials and never deliberately return credentials as integration metadata.

`Integrations` registers and persists connections. `IntegrationDispatcher` chooses execution order and notification timing. `IntegrationRunner` runs each configured integration using an immutable `IntegrationExecutionContext`. Run through these services rather than calling a cached provider directly.

## Results and Safe Retries

Return `IntegrationResult` from providers. `succeeded()` confirms completion; `skipped()` records ineligibility or cancellation; `rejected()` means validation or the provider refused the operation; `failed($code, true)` permits retry only when the operation is known not to have occurred; `unknown()` requires reconciliation. An `IntegrationBatchResult` retains every step result. Returning an arbitrary array or truthy object does not establish success. A configured API-error policy can acknowledge a queue job without recording a successful delivery. The stored result still records the rejection or failure; `shouldFailQueue()` controls the job outcome. Unknown provider outcomes always require reconciliation.

The inherited `execute(IntegrationRunContext $context)` method prepares the current run and calls the protected `executePayload(Submission $submission)` hook. Override `executePayload()` for most providers; implement `execute()` directly when you need the full run context. Return data that later integrations or notifications should use through `IntegrationResult::withOutputs()`. Do not put it in a provider's mutable `context` array. Earlier results are available through `IntegrationRunResults` on the run context. Raw provider responses are stored separately for diagnostics.

Before running a provider, Formie saves a delivery attempt. Each write made through `request()`, `requestPublicEndpoint()` or `requestWithProviderClient()` gets a child attempt. Confirmed responses are encrypted and reused on retry when the payload hash matches. A successful write is not repeated. An unknown outcome blocks further runs until an authorised operator confirms what happened. HTTP errors retain their response details so providers can handle cases such as documented duplicate-record responses.

If your integration uses an additional HTTP client, pass it through `requestWithProviderClient()` with its fixed configured origin. For an API that writes using GET, explicitly wrap the call with `executeDeliveryWrite($method, $url, $options, $send, true)`.

Custom SDKs that bypass these helpers receive the coarse root guard only. Before publishing a provider, wrap each SDK side effect in a named child operation using `DeliveryAttempts::write()` and the runner's execution context and root attempt. Never mark an uncertain transport error as retryable. A confirmed response that has expired cannot be used to resume dependent operations automatically.

Payment providers extend `base\Payment` and use their separate payment state machine.

Queue jobs contain an attempt UID only. Do not attach submission objects, credentials, payloads or debug data to jobs. Append bounded checkpoints through `DeliveryAttempts::checkpoint()` instead. See [Integration Dispatch and Policies](/guides/integrations/integration-dispatch-and-policies) for operator recovery and [Integration Events](/developers/events/integration-events) for events you can observe during delivery.

### Provider Field Formatting

Override `modifyFieldMappingValue()` or `modifyFieldMappingValues()` for formatting owned by your integration. Call the parent implementation when extending an existing provider. Formie calls these methods once for each mapping, then dispatches the public mapping events so other extensions can modify the result. Register application-wide event listeners during your plugin or module initialization, rather than each time an integration instance is constructed.
