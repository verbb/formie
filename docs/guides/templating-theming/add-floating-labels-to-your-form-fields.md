# Add floating labels to your form fields

Floating labels sit inside the input and animate out of the way when the user focuses or enters a value — similar to [Bootstrap's form-floating pattern](https://getbootstrap.com/docs/5.3/forms/floating-labels/). This guide adds floating labels as an opt-in label position editors can choose per field, without forking every field template.

## Prerequisites

- A Craft [module](https://craftcms.com/docs/5.x/extend/module.html) (or existing module to extend)
- [Theme config](/theming/theme-config) and [Field Events](/developers/events/field-events) basics

## Overview

The implementation has three parts:

1. Register a custom **Floating** label position
2. Override the field wrapper template so the label can render after the input
3. Use **theme config** (via `EVENT_MODIFY_SLOT_TAG`) to add Bootstrap-compatible classes when floating is selected

## Create your module

Floating labels are not a core Formie field type — they are a **label position** editors opt into per field. You implement that with a Craft module that registers a custom position, adjusts field wrapper markup, and applies Theme Config classes when floating is selected.

First, create or extend a Craft module. Set the namespace to `modules\formiefloatinglabels` and the module ID to `formie-floating-labels`. Create this structure:

```text
modules/
└── formiefloatinglabels/
    └── src/
        ├── positions/
        │   └── Floating.php
        └── FormieFloatingLabels.php
```

Register the module in `config/app.php` as you would for any Craft module. The three parts below — position class, wrapper template override, and Theme Config hook — can live in one module file to start; split them as your project grows.

### Main module class

The module class wires together label position registration and Theme Config class injection:

```php
<?php
namespace modules\formiefloatinglabels;

use Craft;
use modules\formiefloatinglabels\positions\Floating;
use verbb\formie\base\Field;
use verbb\formie\events\ModifyFieldSlotTagEvent;
use verbb\formie\events\RegisterFieldOptionsEvent;
use verbb\formie\services\Fields;
use yii\base\Event;
use yii\base\Module;

class FormieFloatingLabels extends Module
{
    public function init(): void
    {
        parent::init();

        Event::on(Fields::class, Fields::EVENT_REGISTER_LABEL_POSITIONS, function (RegisterFieldOptionsEvent $event) {
            array_unshift($event->options, Floating::class);
        });

        Event::on(Field::class, Field::EVENT_MODIFY_SLOT_TAG, function (ModifyFieldSlotTagEvent $event) {
            if ($event->field->labelPosition !== Floating::class) {
                return;
            }

            if ($event->key === 'fieldContent') {
                $event->tag->attributes['class'][] = 'form-floating';
            }

            if ($event->key === 'fieldInput') {
                $event->tag->attributes['class'][] = 'form-floating-control';
                $event->tag->attributes['placeholder'] = $event->field->label;
            }
        });
    }
}
```

Formie uses `Field::EVENT_MODIFY_SLOT_TAG` to modify field markup. Classes are applied to `fieldContent` (the wrapper) and `fieldInput` (the control).

### Floating position class

```php
<?php
namespace modules\formiefloatinglabels\positions;

use Craft;
use verbb\formie\base\FieldInterface;
use verbb\formie\base\Position;
use verbb\formie\fields\Dropdown;
use verbb\formie\fields\Email;
use verbb\formie\fields\FileUpload;
use verbb\formie\fields\MultiLineText;
use verbb\formie\fields\Name;
use verbb\formie\fields\Number;
use verbb\formie\fields\Phone;
use verbb\formie\fields\SingleLineText;
use verbb\formie\positions\BelowInput;

class Floating extends Position
{
    protected static ?string $position = 'floating';

    public static function displayName(): string
    {
        return Craft::t('formie', 'Floating');
    }

    public static function supports(FieldInterface $field = null): bool
    {
        if (!$field) {
            return true;
        }

        return in_array(get_class($field), [
            SingleLineText::class,
            MultiLineText::class,
            Dropdown::class,
            Number::class,
            Email::class,
            FileUpload::class,
            Name::class,
            Phone::class,
        ], true);
    }

    public static function fallback(FieldInterface $field = null): ?string
    {
        return BelowInput::class;
    }
}
```

Limit `supports()` to field types where floating labels make sense. Unsupported types fall back to below-input.

After installing the module, edit any field in the form builder and choose **Floating** as the label position.

## Add the floating label to the field template

Formie's default `field.html` renders labels above, left, right, and below the control — but not floating. Add a **field.html** override in your Form Template directory (see [Template Overrides](/theming/template-overrides)):

```twig
{# templates/_forms/field.html — copy Formie's default, then add the floating include #}

{% set value = value ?? field.getElementValue(element) %}
{% set errors = element ? element.getErrors(field.errorKey()) : [] %}

{% set labelPosition = craft.formie.getLabelPosition(field, form) %}
{% set subFieldLabelPosition = craft.formie.getLabelPosition(field, form, true) %}
{% set instructionsPosition = craft.formie.getInstructionsPosition(field, form) %}
{% set errorMessagePosition = craft.formie.getErrorMessagePosition(field, form) %}

{% fieldtag 'field' %}
    {% fieldtag 'fieldLayout' %}
        {{ formieInclude('field/label', { position: 'above' }) }}
        {{ formieInclude('field/label', { position: 'left' }) }}

        {% fieldtag 'fieldContent' %}
            {{ formieInclude('field/instructions', { position: 'above' }) }}
            {{ formieInclude('field/errors', { position: 'above' }) }}

            {% fieldtag 'fieldControl' %}
                {{ field.renderInput(form, value) }}
            {% endfieldtag %}

            {{ formieInclude('field/label', { position: 'floating' }) }}

            {{ formieInclude('field/instructions', { position: 'below' }) }}
            {{ formieInclude('field/errors', { position: 'below' }) }}
        {% endfieldtag %}

        {{ formieInclude('field/label', { position: 'right' }) }}
        {{ formieInclude('field/label', { position: 'below' }) }}
    {% endfieldtag %}
{% endfieldtag %}
```

The floating label must come **after** the input so CSS sibling selectors (`:focus ~ label`, `:not(:placeholder-shown) ~ label`) work. The label include only outputs when the field's label position is floating.

## Add CSS

Bootstrap's form-floating CSS relies on `:placeholder-shown`. The module sets the placeholder to the field label and hides it visually with CSS:

```css
.form-floating {
    position: relative;
}

.form-floating > .form-floating-control {
    height: calc(3rem + 2px);
    line-height: 1.25;
    padding: 1rem 0.75rem;
}

.form-floating > .form-floating-control::placeholder {
    color: transparent;
}

.form-floating > label {
    position: absolute;
    top: 0;
    left: 0;
    padding: 1rem 0.75rem;
    pointer-events: none;
    transform-origin: 0 0;
    transition: opacity .1s ease-in-out, transform .1s ease-in-out;
}

.form-floating > .form-floating-control:focus,
.form-floating > .form-floating-control:not(:placeholder-shown) {
    padding-top: 1.625rem;
    padding-bottom: 0.625rem;
}

.form-floating > .form-floating-control:focus ~ label,
.form-floating > .form-floating-control:not(:placeholder-shown) ~ label {
    opacity: 0.65;
    transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
}
```

Add this to your site's CSS bundle rather than inline Twig in production.

## Related

- [Theme config](/theming/theme-config)
- [Template Overrides](/theming/template-overrides)
- [Field Events](/developers/events/field-events)
