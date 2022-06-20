# Upgrading from v1
While the [changelog](https://github.com/verbb/formie/blob/craft-4/CHANGELOG.md) is the most comprehensive list of changes, this guide provides high-level overview and organizes changes by category.

## Plugin Settings
We've removed `enableGatsbyCompatibility` as it is no longer required.

## Removed Controller
The `formie/csrf/*` actions have been removed (previously deprecated). If you relied on these to refresh the CSRF token for your forms, refer to the [docs](https://verbb.io/craft-plugins/formie/docs/template-guides/cached-forms) for the updated controller and code.

## Templates
There have been a number of template changes, some which have been inherited from Craft 4 changes.

### `getFields()`
Any references to `getFields()` should be changed to `getCustomFields()`. This is inline with Craft 4 element field layout changes.

### `{% cache %}` tag
In Craft 4, external JavaScript and CSS resources are now included in cached data. In [Craft 3](https://verbb.io/craft-plugins/formie/docs/v1/template-guides/cached-forms), you would have been required to use `craft.formie.registerAssets()` outside of your `{% cache %}` tags.

You now no longer need to do this, and any JavaScript and CSS will be captured in `{% cache %}` tags.

## Form
The following changes have been made to the [Form](docs:developers/form) object.

Old | What to do instead
--- | ---
| `getFields()` | `getCustomFields()`

## Page
The following changes have been made to the [Page](docs:developers/page) object.

Old | What to do instead
--- | ---
| `getFields()` | `getCustomFields()`

## Row
The following changes have been made to the [Row](docs:developers/row) object.

Old | What to do instead
--- | ---
| `getFields()` | `getCustomFields()`

## Submission
The following changes have been made to the [Submission](docs:developers/submission) object.

Old | What to do instead
--- | ---
| `getFields()` | `getCustomFields()`

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

As `v-model` won't work when passed through a slot, we'll use `:value` an `input()` to manually handle state changes (essentially, the non-shorthand of `v-model`).

### Schema
We've migrate from [Vue Formulate](https://vueformulate.com/) to [FormKit](https://formkit.com/), which gives us a lot more power and flexibility for our field settings schema. However, there are some breaking changes.

#### `toggleContainer`
The `SchemaHelper::toggleContainer` helper has been removed, and can be replaced by [conditionals](https://formkit.com/advanced/schema#conditionals) which are much more powerful.

For example, you might have the following:

```php
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

