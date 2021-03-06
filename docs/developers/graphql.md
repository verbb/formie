# GraphQL

Formie supports accessing [Form](docs:developers/form) and [Submission](docs:developers/submission) objects via GraphQL. Be sure to read about [Craft's GraphQL support](https://docs.craftcms.com/v3/graphql.html).

## Forms

### Query payload

```json
{
    form (handle: "contactForm") {
        title
        handle

        settings {
            errorMessageHtml
        }
        
        pages {
            name

            rows {
                fields {
                    name
                    handle
                    type
                    displayName

                    ... on Field_Name {
                        firstNameLabel
                        firstNameRequired
                        lastNameLabel
                        lastNameRequired
                    }

                    ... on Field_Email {
                        placeholder
                    }
                }
            }
        }
    }
}
```

### The response

```json
{
    "data": {
        "form": {
            "title": "Contact Form",
            "handle": "contactForm",
            "settings": {
                "errorMessageHtml": "Couldn’t save submission due to errors."
            },
            "pages": [
                {
                    "name": "Page 1",
                    "rows": [
                        {
                            "fields": [
                                {
                                    "name": "Your Name",
                                    "handle": "yourName",
                                    "type": "verbb\\formie\\fields\\formfields\\Name",
                                    "displayName": "Name",
                                    "firstNameLabel": "First Name",
                                    "firstNameRequired": true,
                                    "lastNameLabel": "Last Name",
                                    "lastNameRequired": true
                                }
                            ]
                        },
                        {
                            "fields": [
                                {
                                    "name": "Email Address",
                                    "handle": "emailAddress",
                                    "type": "verbb\\formie\\fields\\formfields\\Email",
                                    "displayName": "Email",
                                    "placeholder": "eg. psherman@wallaby.com"
                                }
                            ]
                        },
                        {
                            "fields": [
                                {
                                    "name": "Message",
                                    "handle": "message",
                                    "type": "verbb\\formie\\fields\\formfields\\MultiLineText",
                                    "displayName": "MultiLineText"
                                }
                            ]
                        }
                    ]
                }
            ]
        }
    }
}
```

### The `forms` query
This query is used to query for [Form](docs:developers/form) objects. You can also use the singular `form` to fetch a single form. There are also `formieForms` and `formieForm` aliases.

| Argument | Type | Description
| - | - | -
| `id`| `[QueryArgument]` | Narrows the query results based on the elements’ IDs.
| `uid`| `[String]` | Narrows the query results based on the elements’ UIDs.
| `archived`| `Boolean` | Narrows the query results to only elements that have been archived.
| `trashed`| `Boolean` | Narrows the query results to only elements that have been soft-deleted.
| `unique`| `Boolean` | Determines whether only elements with unique IDs should be returned by the query.
| `title`| `[String]` | Narrows the query results based on the elements’ titles.
| `search`| `String` | Narrows the query results to only elements that match a search query.
| `relatedTo`| `[Int]` | Narrows the query results to elements that relate to *any* of the provided element IDs. This argument is ignored, if `relatedToAll` is also used.
| `relatedToAll`| `[Int]` | Narrows the query results to elements that relate to *all* of the provided element IDs. Using this argument will cause `relatedTo` argument to be ignored.
| `ref`| `[String]` | Narrows the query results based on a reference string.
| `fixedOrder`| `Boolean` | Causes the query results to be returned in the order specified by the `id` argument.
| `inReverse`| `Boolean` | Causes the query results to be returned in reverse order.
| `dateCreated`| `[String]` | Narrows the query results based on the elements’ creation dates.
| `dateUpdated`| `[String]` | Narrows the query results based on the elements’ last-updated dates.
| `offset`| `Int` | Sets the offset for paginated results.
| `limit`| `Int` | Sets the limit for paginated results.
| `orderBy`| `String` | Sets the field the returned elements should be ordered by.
| `handle`| `[String]` | Narrows the query results based on the form’s handle.


### The `FormInterface` interface
This is the interface implemented by all forms.

| Field | Type | Description
| - | - | -
| `id`| `ID` | The id of the entity.
| `uid`| `String` | The uid of the entity.
| `title`| `String` | The element’s title.
| `enabled`| `Boolean` | Whether the element is enabled or not.
| `archived`| `Boolean` | Whether the element is archived or not.
| `searchScore`| `String` | The element’s search score, if the `search` parameter was used when querying for the element.
| `trashed`| `Boolean` | Whether the element has been soft-deleted or not.
| `dateCreated`| `DateTime` | The date the element was created.
| `dateUpdated`| `DateTime` | The date the element was last updated.
| `pages`| `[PageInterface]` | The form’s pages.
| `rows`| `[RowInterface]` | The form’s rows.
| `fields`| `[FieldInterface]` | The form’s fields.
| `settings`| `[FormSettingsInterface]` | The form’s settings.
| `configJson`| `String` | The form’s config as JSON.
| `templateHtml`| `String` | The form’s rendered HTML.


### The `FormSettingsInterface` interface
This is the interface implemented by all forms.

| Field | Type | Description
| - | - | -
| `displayFormTitle`| `Boolean` | Whether to show the form’s title.
| `displayPageTabs`| `Boolean` | Whether to show the form’s page tabs.
| `displayCurrentPageTitle`| `Boolean` | Whether to show the form’s current page title.
| `displayPageProgress`| `Boolean` | Whether to show the form’s page progress.
| `submitMethod`| `String` | The form’s submit method.
| `submitAction`| `String` | The form’s submit action.
| `submitActionTab`| `String` | The form’s submit redirect option (if in new tab or same tab).
| `submitActionUrl`| `String` | The form’s submit action URL.
| `submitActionFormHide`| `Boolean` | Whether to hide the form’s success message.
| `submitActionMessageHtml`| `String` | The form’s submit success message.
| `submitActionMessageTimeout`| `Integer` | The form’s submit success message timeout.
| `errorMessageHtml`| `String` | The form’s submit error message.
| `loadingIndicator`| `Boolean` | Whether to show the form’s loading indicator.
| `loadingIndicatorText`| `String` | The form’s loading indicator text.
| `validationOnSubmit`| `Boolean` | Whether to validate the form’s on submit.
| `validationOnFocus`| `Boolean` | Whether to validate the form’s on focus.
| `defaultLabelPosition`| `String` | The form’s default label position for fields.
| `defaultInstructionsPosition`| `String` | The form’s default instructions position for fields.
| `progressPosition`| `String` | The form’s progress bar position.


### The `PageInterface` interface
This is the interface implemented by all pages.

| Field | Type | Description
| - | - | -
| `id`| `ID` | The id of the entity.
| `uid`| `String` | The uid of the entity.
| `dateCreated`| `DateTime` | The date the element was created.
| `dateUpdated`| `DateTime` | The date the element was last updated.
| `name`| `String` | The name of the page.
| `rows`| `[RowInterface]` | The pages’s rows.
| `fields`| `[FieldInterface]` | The pages’s fields.
| `settings`| `[PageSettingsInterface]` | The pages’s settings


### The `PageSettingsInterface` interface
This is the interface implemented by all pages.

| Field | Type | Description
| - | - | -
| `submitButtonLabel`| `String` | The page’s submit button label.
| `backButtonLabel`| `String` | The page’s back button label.
| `showBackButton`| `Boolean` | Whether to show the page’s back button.
| `buttonsPosition`| `String` | The page’s button positions.


### The `RowInterface` interface
This is the interface implemented by all rows.

| Field | Type | Description
| - | - | -
| `id`| `ID` | The id of the entity.
| `uid`| `String` | The uid of the entity.
| `fields`| `[FieldInterface]` | The row’s fields.


### The `FieldInterface` interface
This is the interface implemented by all fields. Note that as settings are specific to fields, you'll need to use [Inline Fragments](https://graphql.org/learn/queries/#inline-fragments).

| Field | Type | Description
| - | - | -
| `id`| `ID` | The id of the entity.
| `uid`| `String` | The uid of the entity.
| `name`| `String` | The field’s name.
| `handle`| `String` | The field’s handle.
| `instructions`| `String` | The field’s instructions.
| `required`| `Boolean` | Whether the field is required.
| `type`| `String` | The field’s full class type.
| `displayName`| `String` | The field’s display name (last portion of the class).
| `limit`| `Boolean` | Whether the field should limit content.
| `limitType`| `String` | The field’s limit type.
| `limitAmount`| `Int` | The field’s limit amount.
| `placeholder`| `String` | The field’s placeholder.
| `errorMessage`| `String` | The field’s error message.
| `labelPosition`| `String` | The field’s label position.
| `instructionsPosition`| `String` | The field’s instructions position.
| `cssClasses`| `String` | The field’s CSS classes.
| `containerAttributes`| `String` | The field’s container attributes.
| `inputAttributes`| `String` | The field’s input attributes.

Once using the necessary [Inline Fragments](https://graphql.org/learn/queries/#inline-fragments) for each field type, you'll have access to the same variables as described on the [Field](docs:developers/field) docs.

#### Agree Fields
| Field | Type | Description
| - | - | -
| `defaultState`| `Boolean` | The field’s default value.

#### Date Fields
| Field | Type | Description
| - | - | -
| `defaultDate`| `Date` | The field’s default value.

#### Nested Fields
For nested fields like Group and Repeater, you have access to `nestedRows` and `fields`.

```json
{
    form (handle: "contactForm") {
        title
        handle
        
        fields {
            name

            ... on Field_Group {
                nestedRows {
                    fields {
                        name
                    }
                }
            }
        }
    }
}
```

## Submissions

### Query payload

```json
{
    submissions (form: "contactForm") {
        title

        ... on contactForm_Submission {
            yourName
            emailAddress
            message
        }
    }
}
```

### The response

```json
{
    "data": {
        "submissions": [
            {
                "title": "2020-07-24 22:01:59",
                "yourName": "Peter Sherman",
                "emailAddress": "psherman@wallaby.com",
                "message": "Just wanted to say hi!"
            }
        ]
    }
}
```

### The `submissions` query
This query is used to query for [Submission](docs:developers/submission) objects. You can also use the singular `submission` to fetch a single submission. There are also `formieSubmissions` and `formieSubmission` aliases.

| Argument | Type | Description
| - | - | -
| `id`| `[QueryArgument]` | Narrows the query results based on the elements’ IDs.
| `uid`| `[String]` | Narrows the query results based on the elements’ UIDs.
| `archived`| `Boolean` | Narrows the query results to only elements that have been archived.
| `trashed`| `Boolean` | Narrows the query results to only elements that have been soft-deleted.
| `unique`| `Boolean` | Determines whether only elements with unique IDs should be returned by the query.
| `title`| `[String]` | Narrows the query results based on the elements’ titles.
| `search`| `String` | Narrows the query results to only elements that match a search query.
| `relatedTo`| `[Int]` | Narrows the query results to elements that relate to *any* of the provided element IDs. This argument is ignored, if `relatedToAll` is also used.
| `relatedToAll`| `[Int]` | Narrows the query results to elements that relate to *all* of the provided element IDs. Using this argument will cause `relatedTo` argument to be ignored.
| `ref`| `[String]` | Narrows the query results based on a reference string.
| `fixedOrder`| `Boolean` | Causes the query results to be returned in the order specified by the `id` argument.
| `inReverse`| `Boolean` | Causes the query results to be returned in reverse order.
| `dateCreated`| `[String]` | Narrows the query results based on the elements’ creation dates.
| `dateUpdated`| `[String]` | Narrows the query results based on the elements’ last-updated dates.
| `offset`| `Int` | Sets the offset for paginated results.
| `limit`| `Int` | Sets the limit for paginated results.
| `orderBy`| `String` | Sets the field the returned elements should be ordered by.
| `form`| `[String]` | Narrows the query results based on the form’s handle.

#### Nested Fields
An example for querying Repeater and Group field content.

```json
{
    submissions (handle: "contactForm") {
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

## Mutations
Mutations in GraphQL provide a way of modifying data. The actual mutations will vary depending on the schema. There are some common mutations per GraphQL object type as well as type-specific mutations.

Be sure to read the [GraphQL docs](https://craftcms.com/docs/3.x/graphql.html#mutations).

### Submissions

#### Saving a submission

To create or update a submission use the form-specific mutation, which will have the name in the form of `save_<formHandle>_Submission`.

<!-- BEGIN SUBMISSION MUTATION ARGS -->

| Argument | Type | Description
| - | - | -
| `id`| `ID` | Set the element’s ID.
| `uid`| `String` | Set the element’s UID.
| `enabled`| `Boolean` | Whether the element should be enabled.
| `title`| `String` | Set the element’s title.
| `status`| `String` | Set the element’s status as its handle.
| `statusId`| `Int` | Set the element’s statusId.
| `...`|  | More arguments depending on the field layout for the form

<!-- END SUBMISSION MUTATION ARGS -->

The below shows an example request to create a new submission. For this form, we have a single-line text field with the handle `yourName`. In our query variables, we pass the value(s) we want to use in the query.

```json
// Query
mutation saveSubmission($yourName:String) {
    save_contactForm_Submission(yourName: $yourName) {
        title
        yourName
    }
}

// Query Variables
{
    "yourName": "Peter Sherman"
}
```

With the resulting output:

```json
{
    "data": {
        "save_contactForm_Submission": {
            "title": "2020-08-18 10:29:06",
            "yourName": "Peter Sherman"
        }
    }
}
```

#### Complex Fields
Some fields, such as Name and Address fields are much more than primitive values. Instead, their content needs to be provided as an object. The Name field is an exception, as it can be set to have multiple fields, or a single field.

For example, you can populate a name and address field using the below:

```json
// Query
mutation saveSubmission($yourName:contactForm_yourName_FormieNameInput $yourAddress:contactForm_yourAddress_FormieAddressInput) {
    save_contactForm_Submission(yourName: $yourName, yourAddress: $yourAddress) {
        yourName
        yourAddress
    }
}

// Query Variables
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

You'll notice the `contactForm_yourName_FormieNameInput` type being used. This follows the structure of `{formHandle}_{fieldHandle}_FormieNameInput`.


#### Deleting a submission

To delete a submission use the `deleteSubmission` mutation, which requires the `id` of the submission that must be deleted. It returns a boolean value as the result to indicate whether the operation was successful.

```json
// Query to delete a submission with ID of `1110` for a site with an ID of `2`.
mutation deleteSubmission {
    deleteSubmission(id:1110 siteId:2)
}
```

With the resulting output:

```json
{
    "data": {
        "deleteSubmission": true
    }
}
```
