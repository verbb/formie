# Element Integration
Element integrations extend `Element` and create or update Craft elements from submissions. They are useful when a submission should become an entry, user, product or another element type.

## Field Layouts
Element integrations often expose a destination element type or field layout in the form builder, then use that choice to build field mapping rows. Formie’s `Element` base class includes helpers for turning Craft field layouts into `IntegrationField` objects.

Method | Use
--- | ---
`getElementAttributes()` | Returns destination element attributes that can be mapped.
`getFieldLayoutFields()` | Builds `IntegrationField` objects from a Craft field layout.
`getElementForPayload()` | Gets or creates the destination element for the payload.
`sendPayload()` | Creates or updates the destination element.

## Form Settings
Use schema to expose the destination element choices and field mapping. For example, an Entry integration might expose an entry type, default author, attribute mapping and field mapping.

```php
use verbb\formie\base\FormInterface;
use verbb\formie\helpers\SchemaHelper;

protected function defineFormSettingsSchema(FormInterface $form): array
{
    $schema = parent::defineFormSettingsSchema($form);

    $schema[] = SchemaHelper::selectField([
        'name' => 'entryTypeSection',
        'label' => Craft::t('formie', 'Entry Type'),
        'instructions' => Craft::t('formie', 'Select an entry type to map content to.'),
        'required' => true,
        'options' => $this->getEntryTypeOptions(),
    ]);

    $schema[] = SchemaHelper::integrationFieldMappingField([
        'name' => 'attributeMapping',
        'label' => Craft::t('formie', 'Entry Attribute Mapping'),
        'instructions' => Craft::t('formie', 'Choose how your form fields should map to entry attributes.'),
        'integrationLabel' => Craft::t('formie', 'Element Field'),
        'showRefreshButton' => false,
        'valueLabel' => Craft::t('formie', 'Form Field / Static Value'),
        'integrationFields' => $this->convertIntegrationFieldsToSchema($this->getElementAttributes()),
    ]);

    return $schema;
}
```

## Sending Payloads
In `sendPayload()`, resolve the attribute mapping, resolve the field mapping, set those values on the element, then save it.

```php
use verbb\formie\elements\Submission;

public function sendPayload(Submission $submission): bool
{
    $element = new YourElement();

    $attributeValues = $this->getFieldMappingValues($submission, $this->attributeMapping, $this->getElementAttributes());

    foreach ($attributeValues as $attribute => $value) {
        $element->{$attribute} = $value;
    }

    $fields = $this->getFormSettingValue('elements')->fields ?? [];
    $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping, $fields);

    $element->setFieldValues($fieldValues);

    return Craft::$app->getElements()->saveElement($element);
}
```

The base class handles several value conversions for Craft field types, including relation fields, date fields, option fields and table fields. If your destination element needs special conversion, listen for the element integration events or adjust the value before saving.

## Updating Elements
Element integrations can support update behavior with settings such as `updateElement`, `updateElementMapping`, `overwriteValues` and `updateSearchIndexes`. Use these when the integration should find an existing element and update it instead of always creating a new element.

When a submission is edited on the front end (`PROCESS_MODE_EDIT_EXISTING`), integrations only run if they opt in via `shouldTriggerOnSubmissionEdit()`. The Entry integration exposes this as **Update on Submission Edit**, shown when **Update Entries** is enabled.
