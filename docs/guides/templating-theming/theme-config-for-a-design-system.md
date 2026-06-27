# Theme config for a design system

If your site uses a design system — Tailwind utilities, Bootstrap components, or shared BEM classes — theme config is usually the best way to align Formie forms with it. You define HTML tags and attributes once, then reuse that configuration across every form.

## Prerequisites

- [Theming Overview](/theming/overview) — understand when theme config fits vs template overrides
- [Theme Config](/theming/theme-config) reference

## Why theme config for design systems

Formie's default markup uses stable `formie-*` classes and semantic `data-formie-*` hooks. Theme config lets you:

- Add framework classes to forms, fields, inputs, and buttons
- Replace or remove default classes with `reset: true`
- Change wrapper tags (for example, `<fieldset>` for pages)
- Remove elements entirely by returning `false` for a slot
- Target specific field types (`singleLineText`, `repeater`, etc.)

You get consistent output without maintaining dozens of Twig overrides.

## Understand the form structure

A simplified view of default output:

```html
<form class="formie-form" data-formie-form>
    <div class="formie-pages">
        <div class="formie-page">
            <div class="formie-row">
                <div class="formie-field">
                    <label class="formie-label">Name</label>
                    <div class="formie-field-control">
                        <input type="text">
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
```

Each named part — `form`, `field`, `fieldLabel`, `fieldInput`, `submitButton`, and many more — is a **theme tag** you can configure. See the full tag list in [Theme Config](/theming/theme-config#theme-tags).

## Start with a shared config object

Centralise theme config in a Twig variable or PHP array your templates include:

```twig
{# templates/_forms/_theme-config.twig #}

{% set formieThemeConfig = {
    form: {
        attributes: {
            class: 'space-y-8',
        },
    },
    field: {
        attributes: {
            class: 'mb-6',
        },
    },
    fieldLabel: {
        attributes: {
            class: 'block text-sm font-medium text-gray-700 mb-1',
        },
    },
    fieldInput: {
        attributes: {
            class: 'block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500',
        },
    },
    submitButton: {
        attributes: {
            class: 'inline-flex justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500',
        },
    },
} %}
```

Use it when rendering any form:

```twig
{% include '_forms/theme-config' %}

{{ craft.formie.renderForm('contactForm', {
    themeConfig: formieThemeConfig,
}) }}
```

## Register globally through plugin config

For site-wide defaults, put theme config in `config/formie.php`. Render-time config and events can still override it:

```php
<?php

return [
    'themeConfig' => [
        'form' => [
            'attributes' => [
                'class' => 'space-y-8',
            ],
        ],
        'field' => [
            'attributes' => [
                'class' => 'mb-6',
            ],
        ],
        'fieldInput' => [
            'attributes' => [
                'class' => 'block w-full rounded-md border-gray-300',
            ],
        ],
    ],
];
```

Priority (lowest to highest): plugin config → Twig `renderForm()` → PHP events.

## Replace Formie classes entirely

When your design system should not inherit `formie-*` classes, use `reset: true`:

```twig
{% set formieThemeConfig = {
    form: {
        reset: true,
        attributes: {
            id: 'contact-form',
            class: 'my-form',
        },
    },
    field: {
        reset: true,
        attributes: {
            class: 'form-group',
        },
    },
} %}
```

## Target specific field types

Apply config only to one field type using its camelCase key:

```twig
themeConfig: {
    singleLineText: {
        fieldInput: {
            attributes: {
                class: 'input input-bordered w-full',
            },
        },
    },
    checkboxes: {
        fieldOptionLabel: {
            attributes: {
                class: 'label cursor-pointer',
            },
        },
    },
}
```

Sub-field keys (`address1`, `nameFirst`, `dateDate`, etc.) target inner parts of Address, Name, and Date/Time fields without affecting other inputs.

## Conditional classes

Theme config supports structured conditionals — useful when a class should depend on field state:

```twig
recipients: {
    field: {
        attributes: {
            class: [
                'recipients-field',
                {
                    if: 'field.isHidden',
                    then: 'hidden',
                },
            ],
        },
    },
},
```

Available condition context includes `form`, `field`, `page`, `currentPage`, `row`, and `submission`.

## Ajax and client-side state

Some UI states change in the browser without a server re-render — tab changes, hidden pages, loading buttons, validation errors. Twig conditionals in `themeConfig` will not re-evaluate for those updates.

Define **root-level semantic class keys** at the top level of `themeConfig` (not inside a slot). Formie embeds resolved classes on `data-formie-theme` and the browser package toggles them:

| Key | When applied |
| --- | --- |
| `pageHidden` | Page is not active |
| `tabCurrent` / `tabLinkCurrent` | Current multi-page tab |
| `tabError` | Tab page has field errors |
| `loading` | Submit in progress |
| `fieldLayoutError` | Field has errors |

Example for Tailwind tab styling:

```twig
themeConfig: {
    pageTabLink: {
        attributes: {
            class: ['py-4', 'px-1', 'border-b-2', 'font-medium', 'text-sm'],
        },
    },
    tabLinkInactive: {
        attributes: {
            class: ['border-transparent', 'text-gray-500'],
        },
    },
    tabLinkCurrent: {
        attributes: {
            class: ['border-indigo-500', 'text-indigo-600'],
        },
    },
}
```

If you use Tailwind JIT, safelist classes defined in PHP config or prefer Twig `renderForm()` theme config so utilities are discovered at build time.

## Use ready-made presets

The [Formie theme configs](https://github.com/verbb/formie-theme-configs) repository includes full Tailwind and Bootstrap examples. Copy a preset into your project and adjust tokens to match your design system rather than starting from scratch.

## PHP events for dynamic config

When config must vary by form handle, site, or user role, register theme config through `EVENT_MODIFY_SLOT_TAG`:

```php
use verbb\formie\elements\Form;
use verbb\formie\events\ModifyFormSlotTagEvent;
use yii\base\Event;

Event::on(Form::class, Form::EVENT_MODIFY_SLOT_TAG, function (ModifyFormSlotTagEvent $event) {
    if ($event->key === 'submitButton') {
        $event->tag->attributes['class'][] = 'btn btn-primary';
    }
});
```

Field-level events work the same way on `Field::EVENT_MODIFY_SLOT_TAG`.

## When to step up to template overrides

Theme config covers most design-system alignment. Reach for [Template Overrides](/theming/template-overrides) when you need different HTML structure — not just different classes. Reach for [Custom Rendering](/theming/custom-rendering) only when you are taking over the entire form output.

## Related

- [Theming Overview](/theming/overview)
- [Theme Config](/theming/theme-config)
- [Render Options](/templates/render-options)
- [Add floating labels to your form fields](/guides/templating-theming/add-floating-labels-to-your-form-fields)
