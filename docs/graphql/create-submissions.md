# Create Submissions

Formie creates a form-specific GraphQL mutation for each form. The mutation name follows this pattern:

```graphql
save_<formHandle>_Submission
```

For a form with the handle `contactForm`, the mutation is `save_contactForm_Submission`.

You can also use the generic `saveSubmission` mutation when you want one stable mutation for every form. See [Generic `saveSubmission`](#generic-savesubmission).

## Basic Submission

Field handles become mutation arguments. This example assumes the form has a Single-Line Text field with the handle `yourName`.

```graphql
mutation SaveSubmission($yourName: String) {
    save_contactForm_Submission(yourName: $yourName) {
        title

        ... on contactForm_Submission {
            yourName
        }
    }
}
```

```json
{
    "yourName": "Peter Sherman"
}
```

The response returns the saved submission.

```json
{
    "data": {
        "save_contactForm_Submission": {
            "title": "2026-04-10 10:29:06",
            "yourName": "Peter Sherman"
        }
    }
}
```

## Mutation Arguments

Each form-specific mutation accepts Craft’s standard element mutation arguments, Formie’s submission arguments, the form’s field handles, and any enabled captcha arguments.

Argument | Type | Description
--- | --- | ---
`id` | `ID` | Set this when updating an existing submission.
`uid` | `String` | Set the submission UID.
`enabled` | `Boolean` | Whether the submission should be enabled.
`title` | `String` | Set the submission title.
`status` | `String` | Set the submission status by handle.
`statusId` | `Int` | Set the submission status ID.
`siteId` | `Int` | Set the submission site ID.
`isIncomplete` | `Boolean` | Set whether the submission is incomplete.
`requestToken` | `String` | Optional token for duplicate-submit and replay protection.
`isNewSubmission` | `Boolean` | Useful when editing an existing submission and you need to control whether it is treated as new.
`...` |  | Additional arguments are generated from the form’s field layout.

Query the form’s fields and include `inputTypeName` when you need to discover the correct variable type for each field.

```graphql
{
    formieForm(handle: "contactForm") {
        formFields {
            handle
            inputTypeName
        }
    }
}
```

## Complex Fields

Some fields accept structured input rather than a single string. Name, Address, Group, Repeater, Table and File Upload fields are the most common examples.

The generated input type usually follows this pattern:

```text
<formHandle>_<fieldHandle>_Formie<Name>Input
```

For example, a Name field with the handle `yourName` on the `contactForm` form would use `contactForm_yourName_FormieNameInput`.

## Name And Address Fields

```graphql
mutation SaveSubmission(
    $yourName: contactForm_yourName_FormieNameInput
    $yourAddress: contactForm_yourAddress_FormieAddressInput
) {
    save_contactForm_Submission(yourName: $yourName, yourAddress: $yourAddress) {
        title

        ... on contactForm_Submission {
            yourName
            yourAddress
        }
    }
}
```

```json
{
    "yourName": {
        "firstName": "Peter",
        "lastName": "Sherman"
    },
    "yourAddress": {
        "address1": "42 Wallaby Way",
        "city": "Sydney",
        "zip": "2000",
        "state": "NSW",
        "country": "Australia"
    }
}
```

## File Upload Fields

File Upload fields accept an array. You can pass base64 file data so Formie can create the asset.

```graphql
mutation SaveSubmission($fileUploadField: [FileUploadInput]) {
    save_contactForm_Submission(fileUploadField: $fileUploadField) {
        title
    }
}
```

```json
{
    "fileUploadField": [
        {
            "fileData": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAQUA...",
            "filename": "testing.png"
        },
        {
            "fileData": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAQUA..."
        }
    ]
}
```

If the asset already exists, pass the asset ID instead.

```json
{
    "fileUploadField": [
        {
            "assetId": 1234
        },
        {
            "assetId": 4562
        }
    ]
}
```

## Group Fields

Group fields accept an object containing the nested field handles.

```graphql
mutation SaveSubmission($groupField: contactForm_groupField_FormieGroupInput) {
    save_contactForm_Submission(groupField: $groupField) {
        title

        ... on contactForm_Submission {
            groupField {
                firstValue
                secondValue
            }
        }
    }
}
```

```json
{
    "groupField": {
        "firstValue": "This content",
        "secondValue": "is for groups"
    }
}
```

## Repeater Fields

Repeater fields accept a `rows` array. Each row contains the nested field handles for that row.

```graphql
mutation SaveSubmission($repeaterField: contactForm_repeaterField_FormieRepeaterInput) {
    save_contactForm_Submission(repeaterField: $repeaterField) {
        title

        ... on contactForm_Submission {
            repeaterField {
                rows {
                    field1
                    field2
                }
            }
        }
    }
}
```

```json
{
    "repeaterField": {
        "rows": [
            {
                "field1": "First row",
                "field2": "First row value"
            },
            {
                "field1": "Second row",
                "field2": "Second row value"
            }
        ]
    }
}
```

## Captchas

When a form has a captcha enabled, Formie adds a captcha argument to that form’s submission mutation. The argument name is the captcha integration handle with `Captcha` appended and converted to camel case.

For example, a captcha integration with the handle `turnstile` becomes the argument `turnstileCaptcha`.

```graphql
mutation SaveSubmission(
    $yourName: contactForm_yourName_FormieNameInput
    $turnstileCaptcha: FormieCaptchaInput
) {
    save_contactForm_Submission(yourName: $yourName, turnstileCaptcha: $turnstileCaptcha) {
        title
    }
}
```

```json
{
    "yourName": {
        "firstName": "Peter",
        "lastName": "Sherman"
    },
    "turnstileCaptcha": {
        "name": "cf-turnstile-response",
        "value": "<token from the Turnstile widget>"
    }
}
```

The `FormieCaptchaInput` type contains `name` and `value`. Different captcha providers need different front-end handling, so the exact token you pass depends on the captcha integration. Query the generated schema for the form to confirm the argument name that Formie has added.

For full front-end package flows, use the package docs linked from [Frontend Assets](/frontend/frontend-assets).

## Generic `saveSubmission`

Use `saveSubmission` when your client should not depend on form-specific mutation names. Pass the form handle and a `fields` map keyed by field handle.

```graphql
mutation SaveSubmission($formHandle: String!, $fields: ArrayType) {
    saveSubmission(formHandle: $formHandle, fields: $fields) {
        id
        title

        ... on contactForm_Submission {
            yourName
            emailAddress
        }
    }
}
```

```json
{
    "formHandle": "contactForm",
    "fields": {
        "yourName": "Peter Sherman",
        "emailAddress": "peter@example.test"
    }
}
```

Argument | Type | Description
--- | --- | ---
`formHandle` | `String!` | The form handle.
`fields` | `ArrayType` | Field values keyed by field handle. Accepts the same shapes as the per-form mutation arguments.
`captchas` | `ArrayType` | Captcha payloads keyed by captcha GraphQL handle. Each value uses the same `{ name, value }` shape as `FormieCaptchaInput`.
`id` | `ID` | Set when updating an existing submission.
`uid` | `String` | Set the submission UID.
`enabled` | `Boolean` | Whether the submission should be enabled.
`title` | `String` | Set the submission title.
`status` | `String` | Set the submission status by handle.
`statusId` | `Int` | Set the submission status ID.
`siteId` | `Int` | Set the submission site ID.
`isIncomplete` | `Boolean` | Set whether the submission is incomplete.
`requestToken` | `String` | Optional token for duplicate-submit and replay protection.
`isNewSubmission` | `Boolean` | Useful when editing an existing submission and you need to control whether it is treated as new.

`saveSubmission` returns `SubmissionInterface`. Use inline fragments on the form-specific submission type when you need field values in the response.

Per-form `save_<formHandle>_Submission` mutations remain available for typed clients. For Formie front-end packages, prefer `submitFormieClientForm` instead.

## Validation Errors

If validation fails, Formie returns a GraphQL error with the validation errors encoded in the message and included in the error extensions.

```json
{
    "errors": [
        {
            "message": "{\"emailAddress\":[\"Email Address cannot be blank.\"]}",
            "extensions": {
                "category": "validation",
                "errors": {
                    "emailAddress": [
                        "Email Address cannot be blank."
                    ]
                }
            }
        }
    ],
    "data": {
        "save_contactForm_Submission": null
    }
}
```

## Deleting A Submission

Use `deleteSubmission` to delete a submission. It requires both `id` and `siteId`.

```graphql
mutation DeleteSubmission {
    deleteSubmission(id: 1110, siteId: 2)
}
```

The mutation returns `true` when the submission was deleted.

```json
{
    "data": {
        "deleteSubmission": true
    }
}
```
