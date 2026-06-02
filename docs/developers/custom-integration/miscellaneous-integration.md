# Miscellaneous Integration
Miscellaneous integrations extend `Miscellaneous`. Use this base class when the provider does not fit a more specific type, or when a single provider combines multiple patterns.

For example, a provider might behave partly like a messaging integration and partly like a webhook integration. In that case, `Miscellaneous` gives you the common integration lifecycle without forcing the integration into a more specific base class.

## Form Settings
Miscellaneous integrations define form settings with schema, just like other integrations.

```php
use verbb\formie\base\FormInterface;
use verbb\formie\helpers\SchemaHelper;

protected function defineFormSettingsSchema(FormInterface $form): array
{
    $schema = parent::defineFormSettingsSchema($form);

    $schema[] = SchemaHelper::textField([
        'label' => Craft::t('formie', 'Endpoint'),
        'instructions' => Craft::t('formie', 'Enter the provider endpoint to send submissions to.'),
        'name' => 'endpoint',
        'required' => true,
    ]);

    return $schema;
}
```

## Payloads
`Miscellaneous` includes `generatePayloadValues()`, which builds Formie’s standard submission payload and triggers the miscellaneous payload event.

```php
use verbb\formie\elements\Submission;

public function sendPayload(Submission $submission): bool
{
    $payload = $this->generatePayloadValues($submission);
    $response = $this->deliverPayload($submission, $this->endpoint, $payload);

    return $response !== false;
}
```

If the provider has a clearer shape, prefer the more specific base class. Use `Miscellaneous` when a provider really does not fit CRM, email marketing, messaging, help desk, element, automation or another defined integration type.

