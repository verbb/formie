# Help Desk Integration
Help desk integrations extend `HelpDesk`. They usually create tickets, conversations or contacts from submission data.

Help desk providers vary a lot in their object model, so these integrations tend to look closer to CRM integrations than email marketing integrations: fetch the destination fields, define mapping schema for the form builder, then send mapped values in `sendPayload()`.

## Form Settings
The `HelpDesk` base class includes the common integration form settings and opt-in handling. Add provider-specific fields with `defineFormSettingsSchema()`.

```php
use verbb\formie\base\FormInterface;
use verbb\formie\helpers\SchemaHelper;

protected function defineFormSettingsSchema(FormInterface $form): array
{
    $schema = parent::defineFormSettingsSchema($form);

    $schema[] = SchemaHelper::selectField([
        'label' => Craft::t('formie', 'Mailbox'),
        'instructions' => Craft::t('formie', 'Select the mailbox where new conversations should be created.'),
        'name' => 'mailboxId',
        'required' => true,
        'options' => $this->getCollectionOptions('mailboxes'),
    ]);

    $schema[] = $this->getIntegrationFieldMappingField([
        'name' => 'fieldMapping',
        'dataLabel' => 'Ticket',
        'dataKey' => 'ticket',
    ]);

    return $schema;
}
```

## Payloads
Use `getFieldMappingValues()` to resolve the form’s configured field mapping, then send the provider payload through `deliverPayload()`.

```php
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use Throwable;

public function sendPayload(Submission $submission): bool
{
    try {
        $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping, 'ticket');

        $response = $this->deliverPayload($submission, 'tickets', [
            'mailboxId' => $this->mailboxId,
            'ticket' => $fieldValues,
        ]);

        return $response !== false;
    } catch (Throwable $e) {
        Integration::apiError($this, $e);

        return false;
    }
}
```

If the provider has separate contact and ticket objects, treat them as separate collections in `IntegrationFormSettings` and expose separate field mappings in the schema.

