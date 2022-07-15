# Upgrading from v1
While the [changelog](https://github.com/verbb/formie/blob/craft-4/CHANGELOG.md) is the most comprehensive list of changes, this guide provides high-level overview and organizes changes by category.


## Plugin Settings
Some plugin settings have been removed.

Old | What to do instead
--- | ---
| `enableGatsbyCompatibility` | No longer required

## Removed Controller
The `formie/csrf/*` actions have been removed (previously deprecated). If you relied on these to refresh the CSRF token for your forms, refer to the [docs](https://verbb.io/craft-plugins/formie/docs/template-guides/cached-forms) for the updated controller and code.


## Models

### Form
The following changes have been made to the [Form](docs:developers/form) object.

Old | What to do instead
--- | ---
| `getFields()` | `getCustomFields()`

### Page
The following changes have been made to the [Page](docs:developers/page) object.

Old | What to do instead
--- | ---
| `getFields()` | `getCustomFields()`

### Row
The following changes have been made to the [Row](docs:developers/row) object.

Old | What to do instead
--- | ---
| `getFields()` | `getCustomFields()`

### Field

The following changes have been made to the [Field](docs:developers/field) object.

Old | What to do instead
--- | ---
| `renderLabel()` | No longer required
| `getIsTextInput()` | No longer required
| `getIsSelect()` | No longer required
| `getIsFieldset()` | No longer required

### Submission
The following changes have been made to the [Submission](docs:developers/submission) object.

Old | What to do instead
--- | ---
| `getFields()` | `getCustomFields()`


## Templates
Formie v2 features a revamp of front-end templates, and as such, there are likely to be many breaking changes. If you use custom templates, or template overrides carefully read the below, and consult the following updated docs:

- [Theming Overview](docs:theming)
- [Theme Config](docs:theming/theme-config)
- [Custom Rendering](docs:theming/custom-rendering)
- [Custom Templates](docs:theming/template-overrides)

Any current custom templates, or template overrides will continue to work, despite the new template architecture.

### `{% cache %}` tag
In Craft 4, external JavaScript and CSS resources are now included in cached data. In [Craft 3](https://verbb.io/craft-plugins/formie/docs/v1/template-guides/cached-forms), you would have been required to use `craft.formie.registerAssets()` outside of your `{% cache %}` tags.

You now no longer need to do this, and any JavaScript and CSS will be captured in `{% cache %}` tags.

### `getFields()`
Any references to `getFields()` should be changed to `getCustomFields()`. This is inline with Craft 4 element field layout changes.

### Buttons
With the addition of a "Save" button, we now need to be stricter about defining what each button does. You are now required to supply a `data-submit-action` attribute with the following:

```twig
{# Submit button #}
<button type="submit" data-submit-action="submit">Submit</button>

{# Back button #}
<button type="submit" data-submit-action="back">Back</button>

{# Save button #}
<button type="submit" data-submit-action="save">Save</button>
```

### Submit Action
With the addition of a "Save" button, we need to keep track of what action the form is doing, between saving, going back and submitting.

Ensure you include the `submitAction` hidden input with the default value `submit` in your `<form>` element.

```twig
<form ...>
    {{ csrfInput() }}
    {{ hiddenInput('submitAction', 'submit') }}
    ...
```

### Render Options
Any references to `options` should be changed to `renderOptions`. This is to prevent ambiguity with other variables named `options`. The only exception to this is within option-based fields like Dropdown, Checkboxes and Radio Buttons which do in fact have a variable named `options` for the collection of options for that field. This is different to `renderOptions`.

In addition, some attributes that were managed through render options no longer exist, and have been simplified.

Old | What to do instead
--- | ---
| `options.id` | `field.getHtmlId(form)`
| `options.dataId` | `field.getHtmlDataId(form)`

### `name` HTML attribute
Without a correct `name` attribute on inputs, your content will fail to save. Previously, templates used the `field.handle`, but this can lead to complications when used in nested fields such as a Group. This is compounded in Formie v2 where the same fields are also used in sub-field compatible fields like Address and multi-Name fields (for example, where a Single-Line Text is also used).

Old | What to do instead
--- | ---
| `<input name="{{ field.handle }}"` | `<input name="{{ field.getHtmlName() }}"`
| `<input name="{{ field.handle }}[]"` | `<input name="{{ field.getHtmlName('[]') }}"`
| `<input name="{{ field.handle }}[nested][value]"` | `<input name="{{ field.getHtmlName('nested[value]') }}"`


### Translations
Previously, front-end templates wrapped all text such as field labels, instructions, error messages and more in translation filters. The translation category was a mixture of `app`, `site` and `formie` categories.

In Formie v2, these have been consolidated into the single `formie` translation category.

If you're using static translation to translate any text for front-end forms, ensure you move any of these translations in your `site.php` or `app.php` files into `formie.php`.

Read more about [static translations](https://craftcms.com/docs/4.x/sites.html#static-message-translations).

### Form ID
Previously, you were required to have at least the `id` and `data-config` attributes present on a `<form>` element. Furthermore, in order to use Formie's JS, you were required to use `form.getFormId()` or ensure your ID started with `formie-form-*`. This is no longer the case. 

You are now required to only have a `data-fui-form` attribute which combines the two.

```twig
{# Formie v1 #}
{% set attributes = {
    id: form.formId,
    method: 'post',
    data: {
        config: form.configJson,
    },
} %}

<form {{ attr(attributes) }}>

{# Formie v2 #}
{{ tag('form', {
    method: 'post',
    'data-fui-form': form.configJson,
}) }}
```

Without the `data-fui-form` attribute the Formie JS will fail to initialise.


## GraphQL

### Queries
We have changed the queries used for GraphQL so as not to conflict with other plugins.

Old | What to do instead
--- | ---
| `forms(arguments)` | `formieForms(arguments)`
| `form(arguments)` | `formieForm(arguments)`
| `formCount(arguments)` | `formieFormCount(arguments)`
| `submissions(arguments)` | `formieSubmissions(arguments)`
| `submission(arguments)` | `formieSubmission(arguments)`
| `submissionCount(arguments)` | `formieSubmissionCount(arguments)`

We have also changed some references to `fields` which conflict with GraphQL schema requirements.

Old | What to do instead
--- | ---
| `FormInterface::fields` | `FormInterface::formFields`
| `PageInterface::fields` | `PageInterface::pageFields`
| `RowInterface::fields` | `RowInterface::rowFields`

#### Example

```graphql
// Formie v1
{
    form (handle: "contactForm") {
        fields {
            handle
        }

        pages {
            fields {
                handle
            }

            rows {
                fields {
                    handle
                }
            }
        }
    }
}

// Formie v2
{
    formieForm (handle: "contactForm") {
        formFields {
            handle
        }

        pages {
            pageFields {
                handle
            }

            rows {
                rowFields {
                    handle
                }
            }
        }
    }
}
```

### `FormSettingsInterface`
The following properties have been removed.

Old | What to do instead
--- | ---
| `submitActionUrl` | `redirectUrl`


### Dropdown
The following properties have been removed.

Old | What to do instead
--- | ---
| `multiple` | `multi`


## Integrations
For custom integrations, there are some required changes.

### Form Settings Template
Due to Vue 3 no longer supporting `inline-template` for server-side-rendered templates, we've had to make the change to using slots. If you have a custom integration, and your own template for configuring form settings for the integration, you'll need to switch to using slots.

```twig
{# Formie v1 #}
<integration-form-settings handle="{{ handle }}" :form-settings="{{ formSettings | json_encode }}">
    <div>
        ...
    </div>
</integration-form-settings>

{# Formie v2 #}
<integration-form-settings handle="{{ handle }}" :form-settings="{{ formSettings | json_encode }}">
    <template v-slot="{ get, isEmpty, input, settings, sourceId, loading, refresh, error, errorMessage, getSourceFields }">
        ...
    </template>
</integration-form-settings>
```

As you can see, the only major change is the use of switching the `<div>` tag for the `<template>` tag, which is used for the default slot for the component. In order to access a data prop from the `IntegrationFormSettings` Vue component, you'll need to include it in the `v-slot` param. The above show the inclusion of most common props, but you can modify the props as required.

You'll also want to change references to `v-model` which no longer work, due to our change to slots.

```twig
{# Formie v1 #}
<select v-model="sourceId">

{# Formie v2 #}
<select :value="sourceId" @input="input('sourceId', $event.target.value)">
```

As `v-model` won't work when passed through a slot, we'll use `:value` and `input()` to manually handle state changes (essentially, the non-shorthand of `v-model`).

### Schema
We've migrate from [Vue Formulate](https://vueformulate.com/) to [FormKit](https://formkit.com/), which gives us a lot more power and flexibility for our field settings schema. However, there are some breaking changes.

#### `toggleContainer`
The `SchemaHelper::toggleContainer` helper has been removed, and can be replaced by [conditionals](https://formkit.com/advanced/schema#conditionals) which are much more powerful.

For example, you might have the following:

```php
// Formie v1
SchemaHelper::lightswitchField([
    'label' => Craft::t('formie', 'Required Field'),
    'help' => Craft::t('formie', 'Whether this field should be required when filling out the form.'),
    'name' => 'required',
]),
SchemaHelper::toggleContainer('settings.required', [
    SchemaHelper::textField([
        'label' => Craft::t('formie', 'Error Message'),
        'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
        'name' => 'errorMessage',
    ]),
]),
```

Where the "Error Message" field should be shown only if "Required Field" is truthy. In Formie v2, this can be expressed with:

```php
// Formie v2
SchemaHelper::lightswitchField([
    'label' => Craft::t('formie', 'Required Field'),
    'help' => Craft::t('formie', 'Whether this field should be required when filling out the form.'),
    'name' => 'required',
]),
SchemaHelper::textField([
    'label' => Craft::t('formie', 'Error Message'),
    'help' => Craft::t('formie', 'When validating the form, show this message if an error occurs. Leave empty to retain the default message.'),
    'name' => 'errorMessage',
    'if' => '$get(required).value',
]),
```

Continue reading about all the possibilities with [conditionals](https://formkit.com/advanced/schema#accessing-other-inputs).

### Asset Paths
Asset paths have changed, which won't affect your site, unless you're referring to the old asset URL.

Old | What to do instead
--- | ---
| `@verbb/formie/web/assets/addressproviders/dist/img/{$handle}.svg` | `@verbb/formie/web/assets/cp/dist/img/addressproviders/{$handle}.svg`
| `@verbb/formie/web/assets/captchas/dist/img/{$handle}.svg` | `@verbb/formie/web/assets/cp/dist/img/captchas/{$handle}.svg`
| `@verbb/formie/web/assets/crm/dist/img/{$handle}.svg` | `@verbb/formie/web/assets/cp/dist/img/crm/{$handle}.svg`
| `@verbb/formie/web/assets/elements/dist/img/{$handle}.svg` | `@verbb/formie/web/assets/cp/dist/img/elements/{$handle}.svg`
| `@verbb/formie/web/assets/emailmarketing/dist/img/{$handle}.svg` | `@verbb/formie/web/assets/cp/dist/img/emailmarketing/{$handle}.svg`
| `@verbb/formie/web/assets/miscellaneous/dist/img/{$handle}.svg` | `@verbb/formie/web/assets/cp/dist/img/miscellaneous/{$handle}.svg`
| `@verbb/formie/web/assets/webhooks/dist/img/{$handle}.svg` | `@verbb/formie/web/assets/cp/dist/img/webhooks/{$handle}.svg`


## Custom Fields
For custom fields, there are some required changes.

Old | What to do instead
--- | ---
| `getFrontEndInputOptions(Form $form, mixed $value, array $options = null)` | `getFrontEndInputOptions(Form $form, mixed $value, array $options = [])`
| `getFrontEndInputHtml(Form $form, mixed $value, array $options = null)` | `getFrontEndInputHtml(Form $form, mixed $value, array $options = [])`


### Hooks
The following hooks have been removed:

- `formie.subfield.field-start`
- `formie.subfield.field-end`
- `formie.subfield.input-before`
- `formie.subfield.input-after`
- `formie.subfield.input-start`
- `formie.subfield.input-end`

