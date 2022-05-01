# Upgrading from v1
While the [changelog](https://github.com/verbb/formie/blob/craft-4/CHANGELOG.md) is the most comprehensive list of changes, this guide provides high-level overview and organizes changes by category.

## Plugin Settings
We've removed `enableGatsbyCompatibility` as it is no longer required.

## Removed Controller
The `formie/csrf/*` actions have been removed. If you relied on these to refresh the CSRF token for your forms, refer to the [docs](https://verbb.io/craft-plugins/formie/docs/template-guides/cached-forms) for the updated controller and code.

Old | What to do instead
--- | ---
| `Comment::trashUrl` | Use [form](https://verbb.io/craft-plugins/comments/docs/developers/comment) instead
| `Comment::flagUrl` | Use [form](https://verbb.io/craft-plugins/comments/docs/developers/comment) instead
| `Comment::downvoteUrl` | Use [form](https://verbb.io/craft-plugins/comments/docs/developers/comment) instead
| `Comment::upvoteUrl` | Use [form](https://verbb.io/craft-plugins/comments/docs/developers/comment) instead
| `craft.comments.all()` | Use `craft.comments.fetch()` instead.
| `craft.comments.form()` | Use `craft.comments.render()` instead.

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

