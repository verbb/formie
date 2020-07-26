# GraphQL

Formie supports accessing [Form](docs:developers/form) and [Submission](docs:developers/submission) objects via GraphQL. Be sure to read about [Craft's GraphQL support](https://docs.craftcms.com/v3/graphql.html).

## Forms

### Query payload

```
{
    form (handle: "contactForm") {
        title
        handle
        
        pages {
            name

            rows {
                fields {
                    name
                    handle
                    type
                    displayName
                    columnWidth

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

```
{
    "data": {
        "form": {
            "title": "Contact Form",
            "handle": "contactForm",
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
                                    "columnWidth": "12",
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
                                    "columnWidth": "12",
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
                                    "displayName": "MultiLineText",
                                    "columnWidth": "12"
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
| `columnWidth`| `Int` | The field’s column width.
| `type`| `String` | The field’s full class type.
| `displayName`| `String` | The field’s display name (last portion of the class).

Once using the necessary [Inline Fragments](https://graphql.org/learn/queries/#inline-fragments) for each field type, you'll have access to the same variables as described on the [Field](docs:developers/field) docs.

#### Nested Fields
For nested fields like Group and Repeater, you have access to `nestedRows` and `fields`.

```
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

```
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

```
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
