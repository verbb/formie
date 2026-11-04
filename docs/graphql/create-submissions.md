# Create Submissions

::: tip
For bootstrap, tokens, page navigation, and final submit end-to-end, see [GraphQL submission flow end-to-end](/guides/frontend-headless/graphql-submission-flow-end-to-end).
:::

Formie creates a form-specific GraphQL mutation for each form. The mutation name follows this pattern:

```graphql
save_<formHandle>_Submission
```

For a form with the handle `contactForm`, the mutation is `save_contactForm_Submission`.

These mutations submit a form. A submission made this way behaves like one made on your site: Formie applies the form's availability rules and field conditions, validates the answers, screens for spam, then sends notifications and triggers integrations. Use a private GraphQL schema with an access token, and call the mutation from your server so the token stays out of browser code.

Send every answer in one request to complete the submission. To save progress instead, set `isIncomplete: true`. That saves a draft without requiring every field, and nothing is sent until you complete it with `isIncomplete: false`. Editing a completed submission requires its current `expectedVersion`, so a stale or missing version can't overwrite another save.

Payment fields need provider-specific steps that one mutation can't return. See [Payment Fields](#payment-fields).

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
`operationId` | `String` | Stable identity for safely retrying the same request.
`expectedVersion` | `Int` | Required for an existing submission; use its current `stateVersion`.
`requestToken` | `String` | Optional operation identity when `operationId` is omitted.
`sendNotifications` | `Boolean` | Set to `false` to complete the submission without sending its email notifications. Defaults to `true`.
`triggerIntegrations` | `Boolean` | Set to `false` to complete the submission without triggering its integrations. Defaults to `true`.
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

Fields nested in a Group or Repeater also include their parents’ handles, such as `contactForm_groupField_yourName_FormieNameInput`. Table fields use a list of rows, such as `[contactForm_tableField_TableRowInput]`.

## Name and Address Fields

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

## Notifications and Integrations

Completing a submission sends the form's enabled email notifications and triggers its integrations, following the notification timing and integration order you've set for the form. Conditions on notifications and integrations apply as usual, and Formie's queue settings decide whether they run during the request or in the queue.

To complete a particular submission without sending one of these, set `sendNotifications` or `triggerIntegrations` to `false`:

```graphql
mutation SaveWithoutEmail($yourName: String) {
    save_contactForm_Submission(yourName: $yourName, sendNotifications: false) {
        id
    }
}
```

These arguments only apply to the request that completes the submission.

## Spam Protection

A completed submission goes through the same spam checks as a form submitted on your site, using your [spam settings](/forms/spam-protection). If the submission is treated as spam, it doesn't send notifications or trigger integrations, unless you've turned on email notifications for spam. Your **Spam Submission Behavior** setting decides what the mutation returns:

- **Show error message:** the response contains an error with your message, in the same format as a validation error.
- **Show success:** the mutation responds as usual, so nothing tells the sender it was caught. If Formie isn't saving spam there's no submission to return, so the mutation's value is `null`.

## Captchas

When a form has a captcha enabled, Formie adds a captcha argument to that form’s submission mutation. The argument name is the captcha integration handle with `Captcha` appended and converted to camel case.

For example, a captcha integration with the handle `turnstile` becomes the argument `turnstileCaptcha`.

To complete the submission, send a valid token in this argument. Without one, the submission is treated as spam. On a multi-page form, send it with the request that completes the submission.

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
`operationId` | `String` | Stable identity for safely retrying the same request.
`expectedVersion` | `Int` | Required for an existing submission; use its current `stateVersion`.
`requestToken` | `String` | Optional operation identity when `operationId` is omitted.
`sendNotifications` | `Boolean` | Set to `false` to complete the submission without sending its email notifications. Defaults to `true`.
`triggerIntegrations` | `Boolean` | Set to `false` to complete the submission without triggering its integrations. Defaults to `true`.

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

## Deleting a Submission

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

## Payment Fields

Payment fields require provider UI in your front-end (Stripe.js, PayPal SDK, and so on). Formie accepts provider references via GraphQL and returns structured payment follow-up metadata on submit.

Use `submitFormieClientForm` for headless payment flows so session continuity and 3DS replay work correctly. See [Headless Payments](/graphql/headless-payments) for the full BYO guide and per-provider reference keys.


Upload values may use `{ uploadUid, attachToken }` from the upload response. Asset IDs alone are accepted only for proven browser-owned staged uploads or existing assets on an authorised revision. View and delete tokens cannot be used as attach credentials.
