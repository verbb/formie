# Query Submissions

::: tip
For rendering submission field values in Twig or PHP after you fetch them, see [The complete guide to rendering submission content](/guides/templating-theming/the-complete-guide-to-rendering-submission-content).
:::

Formie submissions are Craft elements, so GraphQL submission queries follow the same general pattern as other element queries. Use `formieSubmission` when you need one submission, `formieSubmissions` when you need a list, and `formieSubmissionCount` when you only need the total.

```graphql
{
    formieSubmissions(form: "contactForm", limit: 10) {
        title
        status
        isIncomplete
        isSpam

        ... on contactForm_Submission {
            yourName
            emailAddress
            message
        }
    }
}
```

## Submission Queries

The submission queries accept Craft’s standard element query arguments, Formie’s submission-specific arguments, and field-handle arguments for the fields in forms that are available to the active GraphQL schema.

Argument | Type | Description
--- | --- | ---
`form` | `[String]` | Narrows results by form handle.
`status` | `String` | Narrows results by submission status.
`statusId` | `Int` | Narrows results by submission status ID.
`siteId` | `Int` | Narrows results by site ID.
`isIncomplete` | `Boolean` | Narrows results by incomplete state.
`isSpam` | `Boolean` | Narrows results by spam state.
`id` | `[QueryArgument]` | Narrows results by element ID.
`uid` | `[String]` | Narrows results by UID.
`title` | `[String]` | Narrows results by title.
`search` | `String` | Narrows results by a search query.
`dateCreated` | `[String]` | Narrows results by creation date.
`dateUpdated` | `[String]` | Narrows results by last-updated date.
`offset` | `Int` | Sets the offset for paginated results.
`limit` | `Int` | Sets the result limit.
`orderBy` | `String` | Sets the sort order.
`fixedOrder` | `Boolean` | Returns results in the order provided by the `id` argument.
`inReverse` | `Boolean` | Reverses the result order.
`archived` | `Boolean` | Narrows results to archived elements.
`trashed` | `Boolean` | Narrows results to soft-deleted elements.
`unique` | `Boolean` | Returns only elements with unique IDs.

You can also query by field handle, similar to querying other Craft elements by custom field content.

Field-handle filters are only supported when the `form` argument targets exactly one form handle. Cross-form queries should stick to the generic submission arguments such as `form`, `status`, `siteId`, `isIncomplete`, and `isSpam`.

If you see an error like `Field handle filters on submission queries require the form argument to target exactly one form.`, it means the query is using one or more field-handle filters without narrowing `form` to a single handle.

```graphql
{
    formieSubmissions(form: "contactForm", emailAddress: "peter@example.com") {
        title
    }
}
```

But this is not, because field-handle filters become ambiguous across multiple forms:

```graphql
{
    formieSubmissions(form: ["contactForm", "supportForm"], emailAddress: "peter@example.com") {
        title
    }
}
```

## Submission Fields

Every submission implements `SubmissionInterface`. The fields available inside the inline fragment depend on the form’s field layout.

Field | Type | Description
--- | --- | ---
`status` | `String` | The submission status handle.
`statusId` | `Int` | The submission status ID.
`ipAddress` | `String` | The submission IP address.
`isIncomplete` | `Boolean` | Whether the submission is incomplete.
`isSpam` | `Boolean` | Whether the submission is marked as spam.
`spamReason` | `String` | The submission’s spam reason.
`spamClass` | `String` | The spam check or class that marked the submission as spam.

Use an inline fragment for the form-specific submission type when you need field content.

When you query submissions without field-handle filters, Formie can infer the form context from a single inline fragment such as `... on contactForm_Submission`. That is enough for selecting field content, but it does not replace the explicit `form` requirement for field-handle query arguments. If you mix multiple different submission fragments in one selection, the query should also include an explicit `form` filter so the intent is unambiguous.

```graphql
{
    formieSubmission(id: 123) {
        title

        ... on contactForm_Submission {
            yourName
            emailAddress
            message
        }
    }
}
```

## Nested Field Content

Group and Repeater fields return structured content. The exact field names depend on your nested fields.

```graphql
{
    formieSubmissions(form: "contactForm") {
        title

        ... on contactForm_Submission {
            groupFieldHandle {
                myFieldHandle
            }

            repeaterFieldHandle {
                rows {
                    myFieldHandle
                }
            }
        }
    }
}
```

For a quick way to discover the correct output types for a form’s fields, query the form’s field layout and include each field’s `typeName` and `inputTypeName`.
