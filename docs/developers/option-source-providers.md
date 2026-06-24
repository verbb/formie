# Custom Option Source Providers

Register lightweight server-resolved option lists for Dropdown, Radio, Checkboxes, and Recipients fields. Custom providers appear in the form builder as **Custom Provider** when they declare support for the current field usage.

Use this API when you need local Craft data — such as entries, categories, or users — without building a full Formie integration.

## Register a provider

Listen for `OptionSources::EVENT_REGISTER_OPTION_SOURCE_PROVIDERS` in your module's `init()` method and push one or more provider classes onto the event:

```php
use verbb\formie\events\RegisterOptionSourceProvidersEvent;
use verbb\formie\services\OptionSources;
use yii\base\Event;

Event::on(OptionSources::class, OptionSources::EVENT_REGISTER_OPTION_SOURCE_PROVIDERS, function(RegisterOptionSourceProvidersEvent $event) {
    $event->providers[] = ClubRecipientsProvider::class;
});
```

Each provider class must implement `OptionSourceProviderInterface`.

## Provider contract

| Method | Purpose |
| --- | --- |
| `handle(): string` | Unique provider handle, stored on the field as `optionSource.provider`. |
| `displayName(): string` | Author-facing label in the form builder. |
| `usages(): string[]` | Supported usages: `options` for Dropdown/Radio/Checkboxes, `recipients` for Recipients fields. |
| `getBuilderConfig(array $params): array` | Builder UI config (`paramFields`, `defaults`, optional `warning`). |
| `resolveOptions(array $params, OptionSourceContext $context): OptionList` | Resolve `{ label, value }` rows at render and submit time. |

For Recipients fields, option values must be valid email addresses. Invalid rows are filtered automatically.

## Example: entry-backed recipients

This example exposes club contact emails from a Craft channel as Recipients field options. It reads values from an Email or Plain Text field on each entry — use your field handle, not a placeholder like `contactEmail`, unless that field actually exists.

```php
use craft\base\FieldInterface;
use craft\elements\Entry;
use craft\fields\Email as EmailField;
use craft\fields\PlainText;
use verbb\formie\options\OptionList;
use verbb\formie\options\OptionSourceContext;
use verbb\formie\options\OptionSourceProviderHelper;
use verbb\formie\options\OptionSourceProviderInterface;

class ClubRecipientsProvider implements OptionSourceProviderInterface
{
    public static function handle(): string
    {
        return 'club-recipients';
    }

    public static function displayName(): string
    {
        return 'Club contacts';
    }

    public static function usages(): array
    {
        return [OptionSourceProviderHelper::USAGE_RECIPIENTS];
    }

    public function getBuilderConfig(array $params = []): array
    {
        $sections = [];
        $emailFieldOptionsBySection = [];

        foreach (Craft::$app->getEntries()->getAllSections() as $section) {
            if ($section->type === 'single') {
                continue;
            }

            $sections[] = [
                'label' => $section->name,
                'value' => (string)$section->id,
            ];

            $emailFieldOptionsBySection[(string)$section->id] = $this->_getEmailFieldOptions($section->id);
        }

        $defaultSectionId = (string)($sections[0]['value'] ?? '');

        return [
            'paramFields' => [
                [
                    'handle' => 'sectionId',
                    'label' => Craft::t('app', 'Section'),
                    'type' => 'select',
                    'options' => $sections,
                ],
                [
                    'handle' => 'emailFieldHandle',
                    'label' => Craft::t('formie', 'Email field'),
                    'type' => 'select',
                    'dependsOn' => 'sectionId',
                    'optionsByParam' => [
                        'sectionId' => $emailFieldOptionsBySection,
                    ],
                ],
            ],
            'defaults' => [
                'sectionId' => $defaultSectionId,
                'emailFieldHandle' => $emailFieldOptionsBySection[$defaultSectionId][0]['value'] ?? '',
            ],
        ];
    }

    public function resolveOptions(array $params, OptionSourceContext $context): OptionList
    {
        $sectionId = (int)($params['sectionId'] ?? 0);
        $emailFieldHandle = trim((string)($params['emailFieldHandle'] ?? ''));

        if ($sectionId <= 0 || $emailFieldHandle === '') {
            return OptionList::error('Complete the provider settings.');
        }

        $rows = [];

        foreach (Entry::find()->sectionId($sectionId)->all() as $entry) {
            $email = trim((string)$entry->getFieldValue($emailFieldHandle));

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $rows[] = [
                'label' => $entry->title,
                'value' => $email,
            ];
        }

        return OptionList::fromRows($rows);
    }

    private function _getEmailFieldOptions(int $sectionId): array
    {
        $section = Craft::$app->getEntries()->getSectionById($sectionId);

        if (!$section) {
            return [];
        }

        $options = [];

        foreach ($section->getEntryTypes() as $entryType) {
            foreach ($entryType->getFieldLayout()->getCustomFields() as $field) {
                if (!$field instanceof EmailField && !$field instanceof PlainText) {
                    continue;
                }

                $options[$field->handle] = [
                    'label' => $field->name,
                    'value' => $field->handle,
                ];
            }
        }

        return array_values($options);
    }
}
```

Authors configure the field with **Options → Custom Provider**, choose **Club contacts**, then pick the section and email field. Formie resolves the rows on the front-end and at submit time.

## Builder param fields

`getBuilderConfig()` uses the same `paramFields` shape as integration option sources:

- `handle` — stored in `optionSource.params`.
- `label` — author-facing field label.
- `type` — currently `select`.
- `options` — `[{ label, value }]` rows for the select input.
- `dependsOn` and `optionsByParam` — optional dependent selects.

Return `defaults` for any params that should be pre-filled when the provider is first selected.

## When to use integrations instead

Custom providers are ideal for local Craft data and simple server-side lookups. Use a [custom integration](/developers/custom-integration/option-sources) when you need OAuth, cached remote metadata, refresh workflows, or reusable provider fields shared across multiple forms.
