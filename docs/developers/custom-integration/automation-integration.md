# Automation Integration
Automation integrations extend `Automation`. They send submission data to another service — typically an HTTP endpoint you configure per form.

::: tip
For a full walkthrough, see [Building an Automation integration from scratch](/guides/integrations/building-an-automation-integration-from-scratch).
:::

## Form Settings
Automation integrations commonly expose an endpoint URL, request method, request type and any headers or authentication settings.

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

    $schema[] = SchemaHelper::selectField([
        'label' => Craft::t('formie', 'HTTP Method'),
        'instructions' => Craft::t('formie', 'Select the HTTP method used to send data.'),
        'name' => 'method',
        'required' => true,
        'options' => [
            ['label' => 'GET', 'value' => 'GET'],
            ['label' => 'POST', 'value' => 'POST'],
            ['label' => 'PUT', 'value' => 'PUT'],
            ['label' => 'PATCH', 'value' => 'PATCH'],
            ['label' => 'DELETE', 'value' => 'DELETE'],
        ],
    ]);

    return $schema;
}
```

## Payloads
Use `generatePayloadValues()` when you want Formie’s standard submission payload shape. It wraps `generateSubmissionPayloadValues()` and triggers the automation payload event.

```php
use verbb\formie\elements\Submission;

protected function executePayload(Submission $submission): \verbb\formie\models\IntegrationResult
{
    $this->beginPayloadDelivery($submission);
    $payload = $this->generatePayloadValues($submission);
    $endpoint = $this->getEndpointUrl($this->url, $submission);
    $response = $this->deliverPayload($submission, $endpoint, $payload, $this->method ?: 'POST');

    return $this->resultForPayload($response !== false);
}
```

Use `deliverPayload()` rather than calling the client directly when you want Formie’s payload events, opt-in handling and delivery diagnostics.

## Guzzle Clients
If the integration needs custom client configuration, override `defineClient()`.

```php
use craft\helpers\App;
use GuzzleHttp\Client;

protected function defineClient(): Client
{
    return Craft::createGuzzleClient([
        'headers' => [
            'Authorization' => 'Bearer ' . App::parseEnv($this->apiKey),
        ],
    ]);
}
```

For more on reusing the client outside the integration itself, see [Using Guzzle Clients from Formie Integrations in Your Own Code](/guides/integrations/using-guzzle-clients-from-formie-integrations-in-your-own-code).


## Test Payloads

The builder’s explicit **Send Test Payload** action creates an unsaved sample submission, applies permitted form settings and calls `sendTestPayload(Submission $submission): IntegrationResult`. Its default implementation uses the same `executePayload()` hook as real delivery. Keep shared payload construction there; override `sendTestPayload()` only when the destination requires different test behaviour.

Test sends require an authorized control-panel POST with CSRF protection. Unsaved samples are sent once without creating a fake saved submission or a production retry record. They still use the hardened transport and payload events, and a failed or skipped result is not shown as successful delivery. Metadata refresh remains separate: `fetchConfig()` and `refreshConfig()` must not send test payloads.
