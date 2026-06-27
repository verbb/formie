# Creating a custom option source provider

In this guide we build **Club contacts** — a custom option source provider that lists contact emails from entries in a chosen section. Authors pick the provider on a Recipients field, choose a section and email field, and Formie resolves `{ label, value }` rows at render time and again at submit time — so tampered POST values are rejected.

Dropdown, Radio, Checkboxes, and Recipients fields can pull options from several built-in sources. When your options come from **local Craft data** (entries, categories, users) without OAuth or remote APIs, a custom **option source provider** is the right tool.

This guide complements the [Custom Option Source Providers](/developers/option-source-providers) developer reference. Read [Option Sources](/fields/option-sources) for built-in source types; [Dynamic option sources in practice](/guides/fields/dynamic-option-sources-in-practice) helps you choose between them.

## Prerequisites

- A Craft module (see [Craft's module documentation](https://craftcms.com/docs/5.x/extend/module-guide.html))
- Familiarity with the option source contract in [Custom Option Source Providers](/developers/option-source-providers)

## Create your module

First, you need a [Craft module](https://craftcms.com/docs/5.x/extend/module-guide.html). Your provider class lives in that module and is registered with Formie at boot time.

When creating your module, set the namespace to `modules\formieoptionproviders` and the module ID to `formie-option-providers`. Create this structure:

```treeview
my-project/
├── modules/
│   └── formieoptionproviders/
│       └── src/
│           ├── providers/
│           │   └── ClubRecipientsProvider.php
│           └── FormieOptionProviders.php
└── ...
```

The `providers/` folder holds your provider class. The module class registers it with Formie — without that listener, your provider never appears in the form builder.

Register the module in your project config, then wire it up in `FormieOptionProviders.php`:

```php [modules/formieoptionproviders/src/FormieOptionProviders.php]
<?php
namespace modules\formieoptionproviders;

use modules\formieoptionproviders\providers\ClubRecipientsProvider;
use verbb\formie\events\RegisterOptionSourceProvidersEvent;
use verbb\formie\services\OptionSources;
use yii\base\Event;
use yii\base\Module;

class FormieOptionProviders extends Module
{
    public function init(): void
    {
        parent::init();

        Event::on(OptionSources::class, OptionSources::EVENT_REGISTER_OPTION_SOURCE_PROVIDERS, function(RegisterOptionSourceProvidersEvent $event) {
            $event->providers[] = ClubRecipientsProvider::class;
        });
    }
}
```

Reload the control panel after adding the module — providers are registered at boot time.

The provider class implements `OptionSourceProviderInterface` directly. There is no Formie base class to extend.

## Build the provider class

Create `modules/formieoptionproviders/src/providers/ClubRecipientsProvider.php`. The class declares its handle, which field types it supports, builder UI for authors, and how to resolve rows from Craft elements:

```php [modules/formieoptionproviders/src/providers/ClubRecipientsProvider.php]
<?php
namespace modules\formieoptionproviders\providers;

use craft\fields\Email as EmailField;
use craft\fields\PlainText;
use craft\elements\Entry;
use verbb\formie\options\OptionList;
use verbb\formie\options\OptionSourceContext;
use verbb\formie\options\OptionSourceProviderHelper;
use verbb\formie\options\OptionSourceProviderInterface;
use Craft;

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

Replace field types and query logic with your own data source. The important parts are `getBuilderConfig()` — what authors configure in the form builder — and `resolveOptions()` — what the front end and submit validation see.

## Configure a field in the builder

With the provider registered, authors can use it on a form:

1. Add a **Recipients** field to a form.
2. Set **Options** to **Custom Provider**.
3. Choose **Club contacts**.
4. Pick the section and email field in the provider settings.
5. Save and preview — the front end should list one row per qualifying entry.

Formie resolves options at render time and again at submit time, so tampered POST values are validated against the same list.

## Provider contract summary

| Method | Purpose |
| --- | --- |
| `handle()` | Unique handle stored as `optionSource.provider` |
| `displayName()` | Label in the form builder |
| `usages()` | `options` for Dropdown/Radio/Checkboxes; `recipients` for Recipients |
| `getBuilderConfig()` | Builder UI: `paramFields`, `defaults`, optional `warning` |
| `resolveOptions()` | Return `OptionList::fromRows()` or `OptionList::error()` |

For Dropdown/Radio/Checkboxes providers, include `OptionSourceProviderHelper::USAGE_OPTIONS` in `usages()`.

## Builder param fields

`getBuilderConfig()` uses the same shape as integration option sources:

- `handle` — stored in `optionSource.params`
- `type` — currently `select`
- `options` — `[{ label, value }]` rows
- `dependsOn` + `optionsByParam` — dependent selects (section → email field)

Return `defaults` for params that should pre-fill when the provider is first selected.

## When to use an integration instead

Custom providers suit local Craft data and simple server lookups. Use a [custom integration option source](/developers/custom-integration/option-sources) when you need OAuth, cached remote metadata, refresh workflows, or reusable provider fields shared across multiple forms.

## Related

- [Custom Option Source Providers](/developers/option-source-providers)
- [Option Sources](/fields/option-sources)
- [Dynamic option sources in practice](/guides/fields/dynamic-option-sources-in-practice)
- [Creating a Formie field type from scratch](/guides/fields/creating-a-formie-field-type-from-scratch)
