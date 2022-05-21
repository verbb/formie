# Upgrading from v1
While the [changelog](https://github.com/verbb/formie/blob/craft-4/CHANGELOG.md) is the most comprehensive list of changes, this guide provides high-level overview and organizes changes by category.

## Plugin Settings
We've removed `enableGatsbyCompatibility` as it is no longer required.

## Removed Controller
The `formie/csrf/*` actions have been removed (previously deprecated). If you relied on these to refresh the CSRF token for your forms, refer to the [docs](https://verbb.io/craft-plugins/formie/docs/template-guides/cached-forms) for the updated controller and code.

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
