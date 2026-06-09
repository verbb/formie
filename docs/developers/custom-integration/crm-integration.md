# CRM Integration
CRM integrations extend `Crm`. They usually fetch one or more provider objects, define field-mapping schema for those objects, then send mapped submission data to the CRM after a submission completes.

::: tip
For a full walkthrough, see [Building a CRM Integration from Scratch](https://verbb.io/craft-plugins/formie/user-guides/building-a-crm-integration-from-scratch).
:::

## Form Settings Data
Use `fetchFormSettings()` to fetch the CRM fields Formie can map to. A CRM often has multiple objects, such as contacts, deals, leads, accounts or opportunities.

```php
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

public function fetchFormSettings(): IntegrationFormSettings
{
    $contactFields = [
        new IntegrationField([
            'handle' => 'email',
            'name' => Craft::t('formie', 'Email'),
            'required' => true,
        ]),
        new IntegrationField([
            'handle' => 'name',
            'name' => Craft::t('formie', 'Name'),
        ]),
    ];

    $dealFields = [
        new IntegrationField([
            'handle' => 'title',
            'name' => Craft::t('formie', 'Title'),
            'required' => true,
        ]),
    ];

    return new IntegrationFormSettings([
        'contact' => $contactFields,
        'deal' => $dealFields,
    ]);
}
```

## Form Settings Schema
CRM integrations commonly expose a lightswitch for each object they can map to, followed by a field-mapping schema for that object.

```php
use verbb\formie\base\FormInterface;
use verbb\formie\helpers\SchemaHelper;

protected function defineFormSettingsSchema(FormInterface $form): array
{
    $schema = parent::defineFormSettingsSchema($form);
    $schema[] = $this->getOptInFieldSchema();

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

    return $schema;
}
```

## Option Sources

CRM integrations often cache picklist fields on each object. Expose them to Dropdown, Radio and Checkboxes fields with `defineOptionSources()`, using `storage: objects` and the same object keys returned from `fetchFormSettings()`.

See [Option Sources](/developers/custom-integration/option-sources).

## Sending Payloads
Use `getFieldMappingValues()` to resolve each object mapping. When the mapping source contains a Formie field reference, Formie converts the value using the destination `IntegrationField` type.

```php
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use Throwable;

public function sendPayload(Submission $submission): bool
{
    try {
        $contactValues = $this->getFieldMappingValues($submission, $this->contactFieldMapping, 'contact');

        $response = $this->deliverPayload($submission, 'contacts', [
            'contact' => $contactValues,
        ]);

        return $response !== false;
    } catch (Throwable $e) {
        Integration::apiError($this, $e);

        return false;
    }
}
```

If your CRM has selectable objects, use `IntegrationCollection` and pass the selected collection field to your mapping schema. That lets Formie show the right destination fields for the selected CRM object.

