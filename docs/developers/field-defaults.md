# Field Defaults

Formie can apply organisation-wide defaults when a new field is added to a form. Site owners configure these under **Formie → Settings → Defaults**, or in [project config](/get-started/configuration) via `fieldDefaults`.

Third-party fields do not participate automatically. Each field type must opt in by declaring which settings can be defaulted, and those settings must already exist in the field’s form-builder schema.

## How defaults are applied

When Formie creates a new field instance, it merges stored defaults into the field config before the field model is constructed.

```php
// verbb\formie\base\Field::__construct()
Formie::$plugin?->getFormDefaults()?->applyToNewField($config, static::class, $this->getSupportedDefaults());
```

Defaults apply when:

- a field is dragged into the form builder,
- a stencil or programmatic field layout creates a new field, or
- your code instantiates the field class without explicitly setting a supported setting.

Defaults do **not** overwrite values that are already present in the field config. If a stencil or migration sets `displayType`, that value wins over the plugin default.

## Opt in with `supportedDefaults()`

Fields that use `FieldFormBuilderTrait` (via `verbb\formie\base\Field`) can opt in by overriding `supportedDefaults()` and returning the setting handles you want to expose.

```php
use verbb\formie\base\Field;
use verbb\formie\helpers\SchemaHelper;

use Craft;

class ExampleField extends Field
{
    public bool $validateSomething = false;
    public string $displayMode = 'simple';

    protected function supportedDefaults(): array
    {
        return ['validateSomething', 'displayMode'];
    }

    public function defineFormBuilderSettingsSchema(): array
    {
        return [
            SchemaHelper::lightswitchField([
                'label' => Craft::t('formie', 'Validate Something'),
                'name' => 'validateSomething',
            ]),
            SchemaHelper::selectField([
                'label' => Craft::t('formie', 'Display Mode'),
                'name' => 'displayMode',
                'options' => [
                    ['label' => Craft::t('formie', 'Simple'), 'value' => 'simple'],
                    ['label' => Craft::t('formie', 'Detailed'), 'value' => 'detailed'],
                ],
            ]),
        ];
    }
}
```

Return an empty array, or omit the override, to keep the field out of **Settings → Defaults**.

Each handle in `supportedDefaults()` must match a schema field `name` in one of these tabs:

- `defineFormBuilderGeneralSchema()`
- `defineFormBuilderSettingsSchema()`
- `defineFormBuilderAppearanceSchema()`
- `defineFormBuilderAdvancedSchema()`

Formie does not scan the Conditions tab, preview schema, or nested layout-builder schemas.

## Schema extraction

Formie builds the Defaults UI from the same schema nodes used in the field edit modal. You do not maintain a separate defaults form.

`FieldFormBuilderTrait::getDefaultableSettingsSchema()` collects the nodes:

```php
public function getDefaultableSettingsSchema(): array
{
    $names = $this->supportedDefaults();

    if ($names === []) {
        return [];
    }

    $tabSchemas = array_values(array_filter([
        $this->defineFormBuilderGeneralSchema(),
        $this->defineFormBuilderSettingsSchema(),
        $this->defineFormBuilderAppearanceSchema(),
        $this->defineFormBuilderAdvancedSchema(),
    ]));

    return SchemaHelper::extractSettingsSchema($tabSchemas, $names);
}
```

`SchemaHelper::extractSettingsSchema()` walks the schema tree, finds the first matching `$field` node for each handle, and returns a flattened list suitable for the Defaults editor. Labels and instructions from surrounding `FieldWrap` nodes are preserved when the inner field node does not define its own.

If a handle is listed in `supportedDefaults()` but cannot be found in the tab schemas, it is skipped silently and will not appear in the Defaults UI.

### Custom defaults schema

Most fields should rely on schema extraction. Override `getDefaultableSettingsSchema()` only when you need a defaults-specific schema that differs from the field modal, for example when remapping storage keys or supplying Defaults-only options.

Form-level defaults use this pattern via `SchemaHelper::extractDefaultsSchema()` and `FormDefaultableTrait`. Field types normally do not need that level of indirection.

## Inherit vs explicit values

Project config and the Defaults UI treat empty values as “inherit Formie’s built-in behaviour for this field type”.

| Stored value | Behaviour |
| --- | --- |
| `null` or `''` | Inherit. The setting is not applied when the field is created. |
| Any other value | Applied to new fields unless the field config already defines that key. |

When nothing is stored for a field type, the Defaults editor shows each setting’s value from the field class itself (for example `displayType: dropdown` on element fields). That keeps the UI in sync with what new fields already get, without writing those values to project config. Saving unchanged class-default values stores them as inherit.

Example project config:

```php
'fieldDefaults' => [
    \MyModule\fields\ExampleField::class => [
        'validateSomething' => true,
        'displayMode' => '', // inherit
    ],
],
```

Keys must be the fully qualified field class name.

## Choosing good default settings

Prefer settings that are:

- organisation-wide policy (for example email domain validation, upload volume, decimal precision),
- independent of a specific form’s content (avoid options lists, element sources, or default values tied to one entry), and
- top-level properties on the field model.

Avoid exposing:

- per-form option rows (`options`, `sources`, `defaultValue` on option fields),
- nested subfield settings inside parent fields such as Address or Name, and
- settings that only make sense once a particular form exists.

Formie currently merges defaults into the top-level field config only. Nested subfield defaults are not supported yet.

## Field properties and validation

Defaulted settings must exist as public properties on the field class, and should be included in `settingsAttributes()` if they are not already part of the base `Field` attribute list.

If a setting needs normalisation before it is assigned to a new field, Formie handles known cases in `FormDefaults::normalizeFieldDefaultValue()`. Third-party fields rarely need this, but you can request or implement normalisation there for non-trivial value types such as dates.

## Extending Formie base fields

If your custom field extends a Formie field that already defines `supportedDefaults()`, you inherit that list unless you override it.

For example, fields that extend `verbb\formie\base\ElementField` inherit defaults for `displayType`, `labelSource`, `orderBy`, and `limitOptions`. Override `supportedDefaults()` when you want a narrower or different set.

```php
protected function supportedDefaults(): array
{
    return ['displayType'];
}
```

## Programmatic access

The Defaults editor is powered by `verbb\formie\services\FormDefaults`.

Useful methods when building tooling or tests:

Method | Description
--- | ---
`getFieldTypeDefaultsConfig()` | Returns compiled Defaults UI config for every field type that opts in.
`resolveFieldTypeDefaults(string $fieldClass)` | Returns stored defaults for one field class.
`applyToNewField(array &$config, string $fieldClass, ?array $supported = null)` | Merges defaults into a field config array.
`getSupportedDefaults()` | Returns the supported handle list for a field instance.

Registered third-party fields appear automatically in `getFieldTypeDefaultsConfig()` once `supportedDefaults()` is non-empty and schema extraction finds at least one setting.

## Checklist

1. Add public properties for the settings you want to default.
2. Define the settings in your form-builder schema with matching `name` handles.
3. Override `supportedDefaults()` and return those handles.
4. Confirm the field appears under **Settings → Defaults → Fields**.
5. Document recommended project config for your users if you expose policy-style settings.
