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
Every provider must extend `verbb\formie\base\Integration` or one of its category bases below. `IntegrationInterface` describes caller-facing methods, and `DispatchableIntegrationInterface` identifies execution capability. Implementing either interface without the base class is insufficient; registration and connection creation reject that shape. The base supplies configuration, binding, events and delivery lifecycle behaviour.

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

Integration results are stored in an `IntegrationRunResults`, not on the Submission element. During integration and notification execution, Formie scopes reference resolution to the active run. To inspect a particular run explicitly, use `Formie::$plugin->getIntegrationDispatcher()->loadRunResults($submission, $executionUid)`. Omitting the identity outside an active run returns an empty result collection; it does not select the latest result. Use the `executionUid` from the delivery event or attempt you are inspecting.

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
`executePayload(Submission $submission)` | Protected author hook for dispatchable integrations; returns an `IntegrationResult`.
`execute(IntegrationRunContext $context)` | Public orchestration method used by the runner; normally inherited.

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

    $schema[] = $this->createFieldMappingField([
        'name' => 'contactFieldMapping',
        'dataLabel' => 'Contact',
        'dataKey' => 'contact',
    ]);

    return $schema;
}
```

For a broader explanation of schema nodes, helpers, conditions and layout, see [UI Schemas](/developers/ui-schemas).

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
Dispatchable integrations implement `DispatchableIntegrationInterface`; the CRM, Email Marketing, Automation, Element, Help Desk, Messaging and Miscellaneous bases already implement it. Override the protected `executePayload(Submission $submission)` hook for normal integrations. The inherited public `execute(IntegrationRunContext $context)` prepares the run and adapts older overrides before invoking that hook. The context supplies the submission, immutable execution identity, attempt UID, prior results from the same run, and execution-local delivery state. Use `getFieldMappingValues()` to resolve the configured form mapping, and `deliverPayload()` when sending to a remote endpoint so Formie can run the before/after payload events. Application code invokes the [execution entry points](#calling-integrations) below so eligibility, authority and delivery history are applied.

```php
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use Throwable;

protected function executePayload(Submission $submission): \verbb\formie\models\IntegrationResult
{
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

If the provider has a connection test endpoint, implement `fetchConnection()` and return a boolean. Call `checkConnection()` to apply caching and connection events; `getIsConnected()` reads the cached status or OAuth token presence. The default `fetchConnection()` checks OAuth token presence and returns false for other integrations. Integrations without connection checking should return false from `supportsConnection()`. The legacy connection event still stores the strings `success` and `fail`; `checkConnection()` returns a genuine boolean after listeners run.

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

`IntegrationConfig` stores versioned non-secret builder metadata, its fetch time and invalidation key. Remote metadata is stale after 24 hours but remains available for display until an explicit refresh. Element integrations acquire local Craft metadata on every read without persisting a metadata cache. Both categories use the same acquisition events and cancellation lifecycle; a cancelled refresh returns the previous available metadata. Editing a connection invalidates its metadata. Only inert field and collection metadata is hydrated; arbitrary class names are rejected. `IntegrationField` remains the mapping-field model. Literal integration settings are stored as configured; prefer environment references for credentials. Existing encrypted settings remain readable when their original key is available.

Annotate sensitive properties with `#[\verbb\formie\attributes\Sensitive]`, including credentials whose names do not contain “secret” or “token”. Inherited annotations are recognized. This metadata supplies known values for log, delivery-result, support-bundle and configuration-cache redaction. It does not grant form-builder assignment: that still requires `#[FormIntegrationSetting]`. Native credentials are annotated explicitly; conservative name-based recognition remains for older third-party integrations. Prefer environment references for project-managed credentials and never deliberately return credentials as integration metadata.

`Integrations` registers and persists connections. `IntegrationDispatcher` chooses execution order and notification timing. `IntegrationRunner` runs each configured integration using an immutable `IntegrationExecutionContext`.

### Connections, Bindings and Runtimes

Use a configured connection to inspect global provider settings. A `FormIntegration` describes that connection's enabled settings on one form. Its `createRuntime()` method returns a separate integration instance with those settings applied, so preparing one form cannot alter another form's connection.

The `Integrations` service exposes these operations:

Method | Result
--- | ---
`getIntegrationByHandle($handle)` | A configured connection.
`getBuilderIntegrationByHandle($handle)` | An available builder choice, including captchas; no form binding is applied.
`getGroupedBuilderIntegrations()` | Available builder choices grouped by category label.
`getGroupedBuilderIntegrationSummaries()` | Lightweight grouped builder summaries.
`resolveIntegrationDefinitionByHandle($handle)` | A definition including globally disabled choices, for configuration and authorisation checks. Builder visibility is not an authorisation check.
`getEnabledFormIntegrations($form)` | Enabled `FormIntegration` bindings for a particular form.
`getEnabledIntegrationRuntimes($form)` | Separate integration instances created from those enabled bindings.
`createIntegrationRuntime($connection, $settings)` | A clone with allowlisted form settings applied; the original connection is unchanged.
`createProjectConfig($connection)` | The connection's project-config array, including configured settings. Treat this as connection configuration, not non-secret builder metadata.

For example, inside application code where `$form` is a saved Formie form, inspect its bindings and prepare a runtime like this:

```php
use verbb\formie\Formie;

foreach (Formie::$plugin->getIntegrations()->getEnabledFormIntegrations($form) as $binding) {
    $runtime = $binding->createRuntime();
    $settingsSchema = $runtime->getFormSettingsSchema($form);
}
```

### Calling Integrations

Choose the entry point that matches the action you intend to perform. Calling a provider's `execute()` or `deliverPayload()` directly does not run the complete submission dispatch policy.

Intent | Entry point
--- | ---
Process a normal submission | Use the submission workflow, which dispatches integrations at the configured stage. See [Submission Workflow](/developers/submission-workflow).
Dispatch from an application-owned workflow | `IntegrationTriggers::dispatchFromWorkflowWithAuthority()` with the accepted operation, trigger event and authority. This is trusted application orchestration, not a visitor endpoint.
Manually run one configured integration | `IntegrationTriggers::runManualIntegration($runtime, $submission)`. It runs synchronously, checks the web actor's permission to save submissions for that form, and respects conditions and opt-in. Console calls are trusted internal operations.
Force a run despite conditions or opt-in | `IntegrationTriggers::forceIntegration($runtime, $submission, $reason)` requires scoped submission permission, the force-integrations permission and an audit reason.
Implement provider behaviour | Override protected `executePayload()`. The runner calls the inherited `execute()` lifecycle.

In a controller action that has already required POST and resolved a saved `$submission`, the following fragment runs the enabled CRM binding named `customerCrm`. The service checks the current actor's form scope; it does not replace the controller's request-method or CSRF checks.

```php
use verbb\formie\Formie;

foreach (Formie::$plugin->getIntegrations()->getEnabledIntegrationRuntimes($submission->getForm()) as $runtime) {
    if ($runtime->getHandle() === 'customerCrm') {
        $result = Formie::$plugin->getIntegrationTriggers()->runManualIntegration($runtime, $submission);
        break;
    }
}
```

`IntegrationRunner::runIntegration()` and `Integrations::sendIntegrationPayload()` are lower-level orchestration methods. Application controllers should use the trigger entry points above instead of constructing execution IDs or trigger-context arrays themselves. Provider HTTP helpers remain appropriate inside provider implementations.

Use `IntegrationDispatcher::loadRunResults($submission, $executionUid)` to inspect earlier results. `saveRunResults()` merges results within that run; normal provider implementations return outputs through `IntegrationResult::withOutputs()` and let the runner persist them. `IntegrationRunContext` supplies provider inputs and earlier results, while `IntegrationExecutionContext` holds execution identity and authority. Provider `populateContext()` builds submission/template variables and is separate from these run objects. Call `populateSubmissionContext($submission)` when preparing those variables for a particular submission; it supplies persisted metadata while invoking the existing zero-argument `populateContext()` override.

## Results and Safe Retries

Return `IntegrationResult` from providers. `succeeded()` confirms completion; `skipped()` records ineligibility or cancellation; `rejected()` means validation or the provider refused the operation; `failed($code, true)` permits retry only when the operation is known not to have occurred; `unknown()` requires reconciliation. An `IntegrationBatchResult` retains every step result. Returning an arbitrary array or truthy object does not establish success. A configured API-error policy can acknowledge a queue job without recording a successful delivery. The stored result still records the rejection or failure; `shouldFailQueue()` controls the job outcome. Unknown provider outcomes always require reconciliation.

The inherited `execute(IntegrationRunContext $context)` method prepares the current run and calls the protected `executePayload(Submission $submission)` hook. Override `executePayload()` for most providers; implement `execute()` directly only when you need the full run context, calling `beginRun($context)` before your implementation. A direct implementation owns the lifecycle and any legacy payload adaptation it needs. Return data that later integrations or notifications should use through `IntegrationResult::withOutputs()`. Do not put it in a provider's mutable `context` array. Earlier results are available through `IntegrationRunResults` on the run context. Raw provider responses are stored separately for diagnostics.

Before running a provider, Formie saves a delivery attempt. Each write made through `request()`, `requestPublicEndpoint()` or `requestWithProviderClient()` gets a child attempt. Confirmed responses are encrypted and reused on retry when the payload hash matches. A successful write is not repeated. An unknown outcome blocks further runs until an authorised operator confirms what happened. HTTP errors retain their response details so providers can handle cases such as documented duplicate-record responses.

If your integration uses an additional HTTP client, pass it through `requestWithProviderClient()` with its fixed configured origin. For an API that writes using GET, explicitly wrap the call with `executeDeliveryWrite($method, $url, $options, $send, true)`.

Custom SDKs that bypass these helpers receive the coarse root guard only. Before publishing a provider, wrap each SDK side effect in a named child operation using `DeliveryAttempts::write()` and the runner's execution context and root attempt. Never mark an uncertain transport error as retryable. A confirmed response that has expired cannot be used to resume dependent operations automatically.

Payment providers extend `base\Payment` and use their separate payment state machine.

Queue jobs contain an attempt UID only. Do not attach submission objects, credentials, payloads or debug data to jobs. The payload lifecycle calls `recordDeliveryRequest()` to append prepared request evidence; subclasses can customize the protected `recordDeliveryRequestEvidence()` hook. Append other bounded checkpoints through `DeliveryAttempts::checkpoint()`. See [Integration Dispatch and Policies](/guides/integrations/integration-dispatch-and-policies) for operator recovery and [Integration Events](/developers/events/integration-events) for events you can observe during delivery.

### Provider Field Formatting

Override `modifyFieldMappingValue()` or `modifyFieldMappingValues()` for formatting owned by your integration. Call the parent implementation when extending an existing provider. Formie calls these methods once for each mapping, then dispatches the public mapping events so other extensions can modify the result. Register application-wide event listeners during your plugin or module initialization, rather than each time an integration instance is constructed.

## Method Contracts and Compatibility

Use `getConfig()` to read cached builder metadata, `refreshConfig()` to refresh it, and `fetchConfig()` to implement acquisition. Metadata methods must not send sample submissions. Automation test sends use `sendTestPayload()` explicitly.

Public orchestration methods apply Formie’s lifecycle; protected hooks supply integration-specific behaviour. New integrations normally override `defineClient()`, `defineFormSettingsSchema()` and `executePayload()`. `createFieldMappingField()` constructs one mapping control; `getFieldMappingValues()` resolves mapped submission values. Payment and captcha guides describe their separate hooks.

`getDescription()` defaults to an empty string. Connection checks and optional payment/schema hooks have base declarations, so extensions can discover their defaults without relying on `method_exists()`. Some established hooks intentionally omit return types to keep older third-party overrides loadable; use appropriate native types in new implementations. `IntegrationInterface` is a caller-facing subset; provider implementations extend the base class as described under [Integration Types](#integration-types).

Use `::class` for PHP class identity, `getShortClassName()` for the short class name, `getClassHandle()` for the implementation handle and `getHandle()` for the configured connection. Historical `className()` and `kebabClassName()` behaviour remains available for compatibility, but new integrations should use these explicit names. HTTP `getClient()` and `defineClient()` retain their established meanings. The historical `OAuthRequest()` alias remains callable; native delivery uses the protected `requestWithOAuthProvider()` transport behind `request()`.

Public lifecycle wrappers honour compatible provider overrides, including parent calls. For metadata, call `getConfig()` or `refreshConfig()` to apply the lifecycle; `fetchConfig()` supplies acquisition behaviour. Prefer overriding the documented author hook for the operation. See the [integration method migration table](/get-started/upgrading-from-v3#integration-method-names).


### Preserving Existing Overrides

The deprecated `sendPayload()` facade returns a boolean for native payload results, so existing `sendPayload(Submission $submission): bool` overrides may return or check `parent::sendPayload($submission)`. Legacy providers may also return `IntegrationResponse` with messages. The canonical execution path retains status, outputs and retry details when an old override delegates to its native parent. If the override then records uncertainty, or records an accepted write and returns `false`, execution requires reconciliation instead of inheriting the parent’s retry policy. Outputs remain available. New provider implementations return `IntegrationResult` from `executePayload()`; new application callers use the execution entry points above.

Metadata compatibility covers acquisition and reads: `fetchFormSettings()`, `getFormSettings()` and `getFormSettingValue()` overrides participate in their canonical paths, including parent calls. A cancelled metadata read retains the old `false` result through `getFormSettings(false)`; canonical reads return the previous metadata. Refresh events run around acquisition once.

Keep the original signatures when maintaining existing overrides: `populateContext(): void`, `enforceOptInField(Submission $submission): bool` and `getMappedFieldValue(string $mappedFieldValue, Submission $submission, IntegrationField $integrationField): mixed`. The inherited `getFormSettingValue(string $key)` intentionally has no return declaration. These signatures also apply to subclasses of native providers.

`resolveMappedFieldValue($value, $submission, $integrationField)` accepts a string or a structured reference slot. It adapts older string-based `getMappedFieldValue()` overrides and preserves the slot's literal, reference or text semantics through parent calls. Bulk `getFieldMappingValues()` uses the same path. Prefer `modifyFieldMappingValue()` for new provider formatting hooks.

When a canonical wrapper and its deprecated name are overridden at different levels of inheritance, canonical calls prefer a canonical override declared on the same or a more-derived class. Parent calls reach the native implementation once. Avoid overriding both spellings for the same operation in new code.
