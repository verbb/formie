# Everything you need to know about Formie schemas

Formie schemas are PHP arrays that describe control panel UI — field settings tabs, integration form settings, notification editors, and page button panels. They replace ad-hoc Twig/Vue templates in the form builder with a structured, normalizable format built on [SchemaForm](https://verbb.io/plugin-kit/forms/overview). This guide explains **when and why** to use schemas, and patterns for fields and integrations.

For node anatomy, `SchemaHelper` method tables, conditions, layout nodes, table fields, and preview schema, see [Schema](/developers/schema).

## Prerequisites

- [Schema](/developers/schema) reference
- [Custom Field](/developers/custom-field) or [Custom Integration](/developers/custom-integration/overview) docs open for your use case
- [SchemaForm overview](https://verbb.io/plugin-kit/forms/overview) — the shared form engine Formie builds on

This guide covers **CP builder schemas** — not headless React consumer docs or GraphQL types.

## Like Craft's Twig form macros, but in PHP

If you have built Craft plugin settings pages before, you have probably used Twig [form macros](https://craftcms.com/docs/5.x/reference/forms/forms.html) such as `forms.textField()`:

```twig
{{ forms.textField({
    label: 'Name' | t('app'),
    instructions: 'What this section will be called in the control panel.' | t('app'),
    id: 'name',
    name: 'name',
    value: section.name,
    errors: section.getErrors('name'),
    required: true,
}) }}
```

Formie schemas aim at the same developer experience — describe fields in PHP with translated labels, defaults, validation rules, and errors, and let the UI layer render them. The difference is that Formie's builder runs in React, so those arrays feed [SchemaForm](https://verbb.io/plugin-kit/forms/overview) instead of Twig templates.

Your PHP side still owns the field definition:

```php
$schema = [
    [
        '$field' => 'text',
        'name' => 'name',
        'label' => Craft::t('app', 'Name'),
        'instructions' => Craft::t('app', 'What this section will be called in the control panel.'),
        'required' => true,
        'validation' => 'required',
    ],
];

$formConfig = [
    'schema' => $schema,
    'values' => [
        'name' => $section->name,
    ],
    'errors' => [
        'name' => $section->getErrors('name'),
    ],
];
```

Formie's `SchemaHelper` methods are convenience wrappers around that same idea — shorter nodes for common field types, plus Formie-specific settings such as field handles, conditions, and integration mapping controls. See the [Plugin Kit blog post on schema-driven forms](https://verbb.io/blog/plugin-kit#schema-driven-forms) for the broader picture of why Verbb built this pattern.

## What a schema is

A schema is an ordered array of **nodes**. Each node describes an input, layout wrapper, or registered component. The form builder renders the array; saved values map to setting keys on the field, form, or integration model.

Prefer `SchemaHelper` for common inputs and Formie field settings. Use raw nodes when you need a field or layout the helper does not cover. See [Schema](/developers/schema) for the intro example, [Helpers](/developers/schema#helpers), [Schema Nodes](/developers/schema#schema-nodes), [Conditions](/developers/schema#conditions), [Layout and HTML](/developers/schema#layout-and-html), and [Tables](/developers/schema#tables).

## Field schema methods

Custom fields implement tab-specific methods:

| Method | Tab |
| --- | --- |
| `defineFormBuilderGeneralSchema()` | General |
| `defineFormBuilderSettingsSchema()` | Settings |
| `defineFormBuilderAppearanceSchema()` | Appearance |
| `defineFormBuilderAdvancedSchema()` | Advanced |
| `defineFormBuilderConditionsSchema()` | Conditions |

Implement the tab-specific methods above for custom fields. Each method returns an array of schema nodes for that part of the builder.

Field previews in the builder palette use `defineFormBuilderPreviewSchema()` — see [Preview Schema](/developers/schema#preview-schema).

## Integration form settings schema

Integrations that expose form-level settings implement `defineFormSettingsSchema()`:

```php
use verbb\formie\base\FormInterface;
use verbb\formie\helpers\SchemaHelper;

protected function defineFormSettingsSchema(FormInterface $form): array
{
    $schema = parent::defineFormSettingsSchema($form);

    $schema[] = SchemaHelper::textField([
        'label' => Craft::t('formie', 'Endpoint URL'),
        'instructions' => Craft::t('formie', 'Enter the URL this integration should send submissions to.'),
        'name' => 'endpointUrl',
        'required' => true,
    ]);

    return $schema;
}
```

Always start with `parent::defineFormSettingsSchema($form)` unless you have a specific reason not to — the parent includes the standard **Enabled** setting. Per-form integration UI is declared entirely through schema.

## Settings schema vs front-end modules

Schema covers **control panel configuration**. Captchas and address providers also need **client modules** (`getClientModule()`) for browser behaviour — schema alone does not inject JavaScript.

See [Captcha Integration](/developers/custom-integration/captcha-integration) for the full split.

## Normalization and validation

Formie normalizes saved settings against schema definitions on save. Benefits:

- Consistent defaults when new settings are added
- Type coercion for numbers, booleans, and selects
- Validation rules declared on nodes surface in the builder

When adding a new setting to an existing field type, add the schema node and a default in the field's settings model — old saved fields pick up defaults on next edit.

## Debugging schema issues

| Symptom | Check |
| --- | --- |
| Setting not saving | `name` matches model attribute; no typo in `if` expression |
| Field hidden unexpectedly | `if` condition against wrong key in nested group |
| Preview blank | `defineFormBuilderPreviewSchema()` returns valid preview helpers |
| Integration tab empty | `defineFormSettingsSchema()` returns array; parent called |

Hard-refresh the CP after PHP changes. Schema is server-rendered — no Vite rebuild for plugin PHP.

## Related

- [Schema](/developers/schema)
- [SchemaForm overview](https://verbb.io/plugin-kit/forms/overview)
- [Custom Field](/developers/custom-field)
- [Custom Integration overview](/developers/custom-integration/overview)
- [Creating a Formie field type from scratch](/guides/fields/creating-a-formie-field-type-from-scratch)
- [Building a CRM integration from scratch](/guides/integrations/building-a-crm-integration-from-scratch)
