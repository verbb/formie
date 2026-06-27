# Creating a Formie field type from scratch

In this guide we build a URL field from scratch — similar to Single-line Text, but with URL validation and optional front-end behaviour. By the end you will have a field that appears in the form builder palette, renders on the front end, validates submissions, and supports Theme Config.

This guide complements the [Custom Field developer reference](/developers/custom-field). Read that page for the full method list; this walkthrough focuses on the pieces you touch most often.

## Prerequisites

- A Craft module (see [Craft's module documentation](https://craftcms.com/docs/5.x/extend/module.html))
- Familiarity with [Schema](/developers/schema) for form-builder settings

## Create your module

First, you need a Craft module (see [Craft's module documentation](https://craftcms.com/docs/5.x/extend/module-guide.html)). Your field class lives in that module, along with the templates Formie renders on the front end and in email notifications.

When creating your module, set the namespace to `modules\formieurlfield` and the module ID to `formie-url-field`. Create this structure:

```treeview
my-project/
├── modules/
│   └── formieurlfield/
│       └── src/
│           ├── fields/
│           │   └── UrlField.php
│           ├── templates/
│           │   ├── icon.svg
│           │   ├── input.html
│           │   └── reference-block.html
│           └── FormieUrlField.php
└── ...
```

The `fields/` folder holds your field class. The `templates/` folder holds the front-end input partial, the reference block for notifications, and the SVG icon shown in the form builder palette.

Register the module in `config/app.php`, then tell Formie about your field and register template roots for Craft:

```php [modules/formieurlfield/src/FormieUrlField.php]
<?php
namespace modules\formieurlfield;

use Craft;
use craft\events\RegisterTemplateRootsEvent;
use craft\web\View;
use modules\formieurlfield\fields\UrlField;
use verbb\formie\events\RegisterFieldsEvent;
use verbb\formie\services\Fields;
use yii\base\Event;
use yii\base\Module;

class FormieUrlField extends Module
{
    public function init(): void
    {
        parent::init();

        Craft::setAlias('@formie-url-field', __DIR__);

        Event::on(View::class, View::EVENT_REGISTER_CP_TEMPLATE_ROOTS, function(RegisterTemplateRootsEvent $event) {
            $event->roots[$this->id] = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates';
        });

        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELDS, function(RegisterFieldsEvent $event) {
            $event->fields[] = UrlField::class;
        });
    }
}
```

Two things happen here. The template root registration lets Craft resolve paths like `formie-url-field/input` when Formie renders your field. The `RegisterFieldsEvent` listener adds `UrlField` to the palette — without it, your class never appears in the form builder.

## Build the field class

Create `modules/formieurlfield/src/fields/UrlField.php`. Extend `verbb\formie\base\Field` and implement the methods Formie expects for builder settings, front-end rendering, validation, and Theme Config.

A custom field touches four surfaces:

1. **Form builder** — settings tabs, preview, palette icon
2. **Front end** — input template or Theme Config slot tags
3. **Validation** — rules when someone submits
4. **Output contexts** — notifications, exports, integrations (via reference blocks and value helpers)

Here is a complete URL field implementation. We walk through each surface in the sections below.

```php [modules/formieurlfield/src/fields/UrlField.php]
<?php
namespace modules\formieurlfield\fields;

use Craft;
use craft\base\ElementInterface;
use craft\validators\UrlValidator;
use verbb\formie\base\Field;
use verbb\formie\helpers\SchemaHelper;
use verbb\formie\models\SlotTag;
use verbb\formie\theme\context\RenderContext;

class UrlField extends Field
{
    public static function displayName(): string
    {
        return Craft::t('formie', 'URL');
    }

    public static function getSvgIconPath(): string
    {
        return 'formie-url-field/icon.svg';
    }

    public static function getInputTemplatePath(): string
    {
        return 'formie-url-field/input';
    }

    public static function getReferenceBlockTemplatePath(): string
    {
        return 'formie-url-field/reference-block';
    }

    public function defineFormBuilderPreviewSchema(): array
    {
        return [
            SchemaHelper::previewInput(),
        ];
    }

    public function defineFormBuilderGeneralSchema(): array
    {
        return [
            SchemaHelper::labelField(),
            SchemaHelper::textField([
                'label' => Craft::t('formie', 'Placeholder'),
                'instructions' => Craft::t('formie', 'The text that will be shown if the field doesn’t have a value.'),
                'name' => 'placeholder',
            ]),
            SchemaHelper::variableTextField([
                'label' => Craft::t('formie', 'Default Value'),
                'instructions' => Craft::t('formie', 'Set a default value for the field when it doesn’t have a value.'),
                'name' => 'defaultValue',
            ]),
        ];
    }

    public function defineFormBuilderValidationSchema(): array
    {
        return [
            SchemaHelper::requiredField(),
            SchemaHelper::requiredValidationMessage(),
        ];
    }

    public function defineFormBuilderAppearanceSchema(): array
    {
        return [
            SchemaHelper::visibility(),
            SchemaHelper::labelPosition($this),
            SchemaHelper::instructions(),
            SchemaHelper::instructionsPosition($this),
            SchemaHelper::errorMessagePosition($this),
        ];
    }

    public function defineFormBuilderAdvancedSchema(): array
    {
        return [
            SchemaHelper::handleField(),
            SchemaHelper::cssClasses(),
            SchemaHelper::containerAttributesField(),
            SchemaHelper::inputAttributesField(),
            SchemaHelper::enableContentEncryptionField(),
        ];
    }

    public function defineFormBuilderConditionsSchema(): array
    {
        return [
            SchemaHelper::enableConditionsField(),
            SchemaHelper::conditionsField(),
        ];
    }

    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        $rules[] = [UrlValidator::class, 'pattern' => '/' . UrlValidator::URL_PATTERN . '/i'];

        return $rules;
    }

    protected function defineSubmissionHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        return Craft::$app->getView()->renderTemplate('formie-url-field/input', [
            'name' => $this->handle,
            'value' => $value,
            'field' => $this,
        ]);
    }

    protected function defineFieldSlotTag(string $key, RenderContext $context): ?SlotTag
    {
        if ($key !== 'fieldInput') {
            return parent::defineFieldSlotTag($key, $context);
        }

        $form = $context->form;
        $errors = $context->errors;
        $id = $this->getHtmlId($form);
        $dataId = $this->getHtmlDataId($form);
        $value = $context->get('value');

        return SlotTag::make('input')
            ->core([
                'type' => 'url',
                'id' => $id,
                'name' => $this->getHtmlName(),
                'value' => $value ?? false,
                'placeholder' => $this->placeholder ?: null,
                'required' => $this->required ? true : null,
                'data-formie-input' => true,
                'data-formie-input-id' => $dataId,
                'data-formie-input-type' => 'url',
                'data-formie-input-error-state' => $errors ? true : false,
                'data-formie-required-message' => $this->errorMessage ?: null,
                'aria-describedby' => $this->instructions ? "{$id}-instructions" : null,
            ])
            ->theme([
                'class' => [
                    'formie-input',
                    $errors ? 'formie-input-error' : false,
                ],
            ])
            ->instanceAttributes($this->getInputAttributes());
    }
}
```

### Form builder settings

Each settings tab in the form builder maps to a `defineFormBuilder*Schema()` method — General, Validation, Appearance, Advanced, Conditions. Return schema nodes from [SchemaHelper](/developers/schema) or raw arrays. See [Everything you need to know about Formie schemas](/guides/developers/everything-you-need-to-know-about-formie-schemas) for patterns.

The preview palette uses `defineFormBuilderPreviewSchema()` with helpers like `previewInput()`.

### Front-end rendering

Point `getInputTemplatePath()` at a lean Twig partial that calls `fieldtag()`. Formie handles labels, errors, and instructions around your input.

For Theme Config overrides, implement `defineFieldSlotTag()` and return a `SlotTag` for `fieldInput` — that is how design systems attach classes without forking templates.

### Validation

Add rules in `getElementValidationRules()`. The URL field uses Craft's `UrlValidator` with Formie's standard required-field handling from the Validation tab schema.

### Reference blocks

Email notifications and other reference contexts use `getReferenceBlockTemplatePath()` — a small Twig partial that formats the stored value for humans reading an email.

## Create templates

With the field class in place, add the templates Formie loads when rendering the field.

**Front-end input** — lean template using `fieldtag()`:

```twig
{# modules/formieurlfield/src/templates/input.html #}
{{ fieldtag('fieldInput', {
    value: value ?? false,
}) }}
```

**Reference block** (email notifications and reference contexts):

```twig
{# modules/formieurlfield/src/templates/reference-block.html #}
<p>
    <strong>{{ field.name | t('formie') }}</strong><br>
    {% if value %}
        {{ value }}
    {% else %}
        {{ 'No response.' | t('formie') }}
    {% endif %}
</p>
```

Add an SVG icon at `templates/icon.svg` for the form builder palette.

## Test it

Reload the control panel. Your **URL** field should appear in the field picker. Add it to a form, save, and preview on the front end. Enter invalid text — validation should reject it with a URL error.

## Add a client module (optional)

When a field needs companion JavaScript, register a client module so Formie lazy-loads it only when the field is on the form:

```php
use verbb\formie\models\ClientModule;

protected function defineClientModules(): array
{
    $modules = parent::defineClientModules();

    $modules[] = new ClientModule([
        'id' => 'url-field',
        'type' => 'field',
        'src' => '/assets/formie/url-field-module.js',
        'config' => [
            'validateOnInput' => true,
        ],
    ]);

    return $modules;
}
```

Author the module as a `FormieModuleDefinition` in that JS file. See [Build a custom module](/browser/modules/build-a-custom-module) for the full pattern with `match()`, `setup()`, and `destroy()`.

## Value handling for complex fields

Scalar fields like URL can rely on Formie's defaults. When your field stores structured data, override the protected `defineValue*()` methods:

- `defineValueAsString()` — exports, summaries
- `defineValueAsArray()` — array contexts
- `defineValueForIntegration()` — CRM and automation payloads

Override the `defineValue*()` methods, not the public `getValue*()` wrappers — the public methods fire Formie value events.

## When to use a full field vs a Custom Field adapter

Register a full Formie field type when you own the complete behaviour and want a dedicated palette item.

If you are bridging an existing Craft field from another plugin, consider a [Custom Field adapter](/guides/fields/bringing-your-craft-field-into-formie-custom-field-adapters) instead.

## Related

- [Custom Field (developer reference)](/developers/custom-field)
- [Field object reference](/reference/field)
- [Schema](/developers/schema)
- [Theme Config](/theming/theme-config)
