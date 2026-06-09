# Email Marketing Integration
Email marketing integrations extend `EmailMarketing`. They usually fetch lists from a provider, expose a list selector in the form builder, then map Formie fields to subscriber fields for the selected list.

::: tip
For a full walkthrough, see [Building an Email Marketing Integration from Scratch](https://verbb.io/craft-plugins/formie/user-guides/building-an-email-marketing-integration-from-scratch).
:::

## Form Settings Data
Return an `IntegrationFormSettings` object containing provider lists. Each list is usually an `IntegrationCollection` with its own `fields`.

```php
use verbb\formie\models\IntegrationCollection;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;

public function fetchFormSettings(): IntegrationFormSettings
{
    $settings = [];
    $lists = $this->request('GET', 'lists');

    foreach ($lists as $list) {
        $settings['lists'][] = new IntegrationCollection([
            'id' => (string)$list['id'],
            'name' => $list['name'],
            'fields' => [
                new IntegrationField([
                    'handle' => 'email',
                    'name' => Craft::t('formie', 'Email'),
                    'required' => true,
                ]),
                new IntegrationField([
                    'handle' => 'first_name',
                    'name' => Craft::t('formie', 'First Name'),
                ]),
            ],
        ]);
    }

    return new IntegrationFormSettings($settings);
}
```

## Form Settings Schema
`EmailMarketing` already defines a common form settings schema for list selection and field mapping. It includes the opt-in field, a refreshable list selector and the field-mapping UI for the selected list.

Override `defineFormSettingsSchema()` when your provider needs extra per-form settings.

```php
use verbb\formie\base\FormInterface;
use verbb\formie\helpers\SchemaHelper;

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
```

## Sending Payloads
Use `getFieldMappingValues()` with `$this->fieldMapping`. The `EmailMarketing` base class resolves the selected list and passes the correct list fields to Formie’s mapping conversion.

```php
use verbb\formie\base\Integration;
use verbb\formie\elements\Submission;
use Throwable;

public function sendPayload(Submission $submission): bool
{
    try {
        $fieldValues = $this->getFieldMappingValues($submission, $this->fieldMapping);
        $email = $fieldValues['email'] ?? null;

        $response = $this->deliverPayload($submission, "lists/{$this->listId}/subscribers", [
            'email' => $email,
            'fields' => $fieldValues,
        ]);

        return $response !== false;
    } catch (Throwable $e) {
        Integration::apiError($this, $e);

        return false;
    }
}
```

Email marketing providers differ in how they represent custom fields, groups and resubscribe behavior. Keep provider-specific API decisions in `fetchFormSettings()` and `sendPayload()`, and let the base class handle the common Formie mapping flow.

## Option Sources

If your provider caches list fields with selectable options — such as Mailchimp interest groups or tags — you can expose them to Dropdown, Radio and Checkboxes fields through `defineOptionSources()`. Use `collectionKey: lists` and `storage: collections`.

See [Option Sources](/developers/custom-integration/option-sources).
