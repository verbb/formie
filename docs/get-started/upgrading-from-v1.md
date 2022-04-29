# Upgrading from v1
While the [changelog](https://github.com/verbb/formie/blob/craft-4/CHANGELOG.md) is the most comprehensive list of changes, this guide provides high-level overview and organizes changes by category.

## Plugin Settings
We've removed `enableGatsbyCompatibility` as it is no longer required.

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
