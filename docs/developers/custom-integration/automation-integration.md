# Automation Integration
Automation integrations extend `Automation`. They are useful for webhook-style providers where Formie sends submission data to another service.

::: tip
For a full walkthrough, see [Building a Webhook Integration from Scratch](https://verbb.io/craft-plugins/formie/user-guides/building-a-webhook-integration-from-scratch).
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

public function sendPayload(Submission $submission): bool
{
    $payload = $this->generatePayloadValues($submission);
    $endpoint = $this->getEndpointUrl($this->url, $submission);
    $response = $this->deliverPayload($submission, $endpoint, $payload, $this->method ?: 'POST');

    return $response !== false;
}
```

Use `deliverPayload()` rather than calling the client directly when you want Formie’s payload events, opt-in handling and queue debug context.

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

For more on reusing the client outside the integration itself, see [Using Guzzle Clients from Formie Integrations in Your Own Code](https://verbb.io/craft-plugins/formie/user-guides/using-guzzle-clients-from-formie-integrations-in-your-own-code).

