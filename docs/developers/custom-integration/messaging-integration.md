# Messaging Integration
Messaging integrations extend `Messaging`. They usually post a message to a selected channel, chat, user or webhook.

Messaging integrations are often lighter than CRM or email marketing integrations, but still use the same form builder schema and payload flow.

## Form Settings
Use `fetchFormSettings()` when the provider has selectable destinations such as channels or users. Use `defineFormSettingsSchema()` to expose destination and message settings.

```php
use verbb\formie\base\FormInterface;
use verbb\formie\helpers\SchemaHelper;

protected function defineFormSettingsSchema(FormInterface $form): array
{
    $schema = parent::defineFormSettingsSchema($form);

    $schema[] = SchemaHelper::selectField([
        'label' => Craft::t('formie', 'Channel'),
        'instructions' => Craft::t('formie', 'Select the channel where messages should be posted.'),
        'name' => 'channelId',
        'required' => true,
        'options' => $this->getCollectionOptions('channels'),
    ]);

    $schema[] = SchemaHelper::richTextField([
        'label' => Craft::t('formie', 'Message'),
        'instructions' => Craft::t('formie', 'Enter the message to send when a submission is received.'),
        'name' => 'message',
    ]);

    return $schema;
}
```

## Payloads
Render any templated message content against the submission before sending it to the provider.

```php
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use Throwable;

public function sendPayload(Submission $submission): bool
{
    try {
        $message = Formie::$plugin->getTemplates()->renderObjectTemplate($this->message, $submission);

        $response = $this->deliverPayload($submission, 'messages', [
            'channel' => $this->channelId,
            'text' => $message,
        ]);

        return $response !== false;
    } catch (Throwable $e) {
        Integration::apiError($this, $e);

        return false;
    }
}
```

Some messaging providers use OAuth and some use incoming HTTP endpoints. If the provider uses OAuth, see [OAuth Integration](/developers/custom-integration/oauth-integration). If the provider is mostly a configurable HTTP endpoint, the [Automation Integration](/developers/custom-integration/automation-integration) pattern may be closer.

