# GraphQL

Formie supports accessing [Form](docs:developers/form) and [Submission](docs:developers/submission) objects via GraphQL. Be sure to read about [Craft's GraphQL support](https://docs.craftcms.com/v3/graphql.html).

:::tip
Have a look at our [headless Formie demo](https://formie-headless.verbb.io/?form=contactForm) to get a feel for what's possible with GraphQL.
:::

## Forms

### Query payload

```json
{
    formieForm (handle: "contactForm") {
        title
        handle

        settings {
            errorMessageHtml
        }
        
        pages {
            name

            rows {
                rowFields {
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
        "formieForm": {
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
                            "rowFields": [
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
                            "rowFields": [
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
                            "rowFields": [
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

### The `formieForms` query
This query is used to query for [Form](docs:developers/form) objects. You can also use the singular `formieForm` to fetch a single form.

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
| `formFields`| `[FieldInterface]` | The form’s fields.
| `settings`| `[FormSettingsInterface]` | The form’s settings.
| `configJson`| `String` | The form’s config as JSON.
| `templateHtml`| `String` | The form’s rendered HTML.
| `csrfToken`| `[CsrfTokenInterface]` | A CSRF token (name and value).


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
| `redirectUrl`| `String` | The form’s submit action redirect URL.
| `redirectEntry`| `EntryInterface` | The form’s submit action entry (for redirection).
| `errorMessageHtml`| `String` | The form’s submit error message.
| `loadingIndicator`| `Boolean` | Whether to show the form’s loading indicator.
| `loadingIndicatorText`| `String` | The form’s loading indicator text.
| `validationOnSubmit`| `Boolean` | Whether to validate the form’s on submit.
| `validationOnFocus`| `Boolean` | Whether to validate the form’s on focus.
| `defaultLabelPosition`| `String` | The form’s default label position for fields.
| `defaultInstructionsPosition`| `String` | The form’s default instructions position for fields.
| `progressPosition`| `String` | The form’s progress bar position.
| `integrations`| `[FormIntegrationsInterface]` | The form’s enabled integrations.


### The `FormIntegrationsInterface` interface
This is the interface implemented by all form integrations.

| Field | Type | Description
| - | - | -
| `name`| `String` | The integration’s name.
| `handle`| `String` | The integration’s handle.
| `enabled`| `Boolean` | Whether the integration is enabled.
| `settings`| `String` | The integration’s settings as a JSON string.


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
| `pageFields`| `[FieldInterface]` | The pages’s fields.
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
| `rowFields`| `[FieldInterface]` | The row’s fields.


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
| `typeName`| `String` | The field’s full GQL type.
| `displayName`| `String` | The field’s display name (last portion of the class).
| `inputTypeName`| `String` | The field’s full GQL input type. Useful for mutations.
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
| `includeInEmail`| `Boolean` | Whether the field should be included in email content.
| `enableConditions`| `Boolean` | Whether the field has conditions enabled.
| `conditions`| `String` | The field’s conditions.
| `enableContentEncryption`| `Boolean` | Whether the field has content encryption enabled.
| `visibility`| `String` | The field’s visibility.

Once using the necessary [Inline Fragments](https://graphql.org/learn/queries/#inline-fragments) for each field type, you'll have access to the same variables as described on the [Field](docs:developers/field) docs.

####  Address Fields
| Field | Type | Description
| - | - | -
|`address1Label` | `String` | The label for the Address 1 sub-field.
|`address1Placeholder` | `String` | The placeholder for the Address 1 sub-field.
|`address1DefaultValue` | `String` | The default value for the Address 1 sub-field.
|`address1Required` | `String` | Whether the Address 1 sub-field should be required.
|`address1ErrorMessage` | `String` | The error message for the Address 1 sub-field.
|`address1Collapsed` | `Boolean` | Whether the Address 1 sub-field is collapsed in the control panel.
|`address1Enabled` | `Boolean` | Whether the Address 1 sub-field is enabled in the control panel.
|`address2Label` | `String` | The label for the Address 2 sub-field.
|`address2Placeholder` | `String` | The placeholder for the Address 2 sub-field.
|`address2DefaultValue` | `String` | The default value for the Address 2 sub-field.
|`address2Required` | `String` | Whether the Address 2 sub-field should be required.
|`address2ErrorMessage` | `String` | The error message for the Address 2 sub-field.
|`address2Collapsed` | `Boolean` | Whether the Address 2 sub-field is collapsed in the control panel.
|`address2Enabled` | `Boolean` | Whether the Address 2 sub-field is enabled in the control panel.
|`address3Label` | `String` | The label for the Address 3 sub-field.
|`address3Placeholder` | `String` | The placeholder for the Address 3 sub-field.
|`address3DefaultValue` | `String` | The default value for the Address 3 sub-field.
|`address3Required` | `String` | Whether the Address 3 sub-field should be required.
|`address3ErrorMessage` | `String` | The error message for the Address 3 sub-field.
|`address3Collapsed` | `Boolean` | Whether the Address 3 sub-field is collapsed in the control panel.
|`address3Enabled` | `Boolean` | Whether the Address 3 sub-field is enabled in the control panel.
|`cityLabel` | `String` | The label for the City sub-field.
|`cityPlaceholder` | `String` | The placeholder for the City sub-field.
|`cityDefaultValue` | `String` | The default value for the City sub-field.
|`cityRequired` | `String` | Whether the City sub-field should be required.
|`cityErrorMessage` | `String` | The error message for the City sub-field.
|`cityCollapsed` | `Boolean` | Whether the City sub-field is collapsed in the control panel.
|`cityEnabled` | `Boolean` | Whether the City sub-field is enabled in the control panel.
|`stateLabel` | `String` | The label for the State sub-field.
|`statePlaceholder` | `String` | The placeholder for the State sub-field.
|`stateDefaultValue` | `String` | The default value for the State sub-field.
|`stateRequired` | `String` | Whether the State sub-field should be required.
|`stateErrorMessage` | `String` | The error message for the State sub-field.
|`stateCollapsed` | `Boolean` | Whether the State sub-field is collapsed in the control panel.
|`stateEnabled` | `Boolean` | Whether the State sub-field is enabled in the control panel.
|`zipLabel` | `String` | The label for the Zip sub-field.
|`zipPlaceholder` | `String` | The placeholder for the Zip sub-field.
|`zipDefaultValue` | `String` | The default value for the Zip sub-field.
|`zipRequired` | `String` | Whether the Zip sub-field should be required.
|`zipErrorMessage` | `String` | The error message for the Zip sub-field.
|`zipCollapsed` | `Boolean` | Whether the Zip sub-field is collapsed in the control panel.
|`zipEnabled` | `Boolean` | Whether the Zip sub-field is enabled in the control panel.
|`countryLabel` | `String` | The label for the Country sub-field.
|`countryPlaceholder` | `String` | The placeholder for the Country sub-field.
|`countryDefaultValue` | `String` | The default value for the Country sub-field.
|`countryRequired` | `String` | Whether the Country sub-field should be required.
|`countryErrorMessage` | `String` | The error message for the Country sub-field.
|`countryCollapsed` | `Boolean` | Whether the Country sub-field is collapsed in the control panel.
|`countryEnabled` | `Boolean` | Whether the Country sub-field is enabled in the control panel.
|`countryOptions` | `FieldAttribute` | An array of options available to pick a country from.


####  Agree
| Field | Type | Description
| - | - | -
|`description` | `String` | The description for the field.
|`descriptionHtml` | `String` | The HTML description for the field.
|`checkedValue ` | `String` | The value of this field when it is checked.
|`uncheckedValue` | `String` | The value of this field when it is unchecked.
|`defaultValue` | `String` | The default value for the field when it loads.


####  Categories
| Field | Type | Description
| - | - | -
|`placeholder` | `String` | The option shown initially, when no option is selected.
|`source` | `String` | Which source do you want to select categories from?
|`branchLimit` | `String` | Limit the number of selectable category branches.
|`categories` | `CategoryQuery` | The category query for available categories.


####  Checkboxes
| Field | Type | Description
| - | - | -
|`options` | `KeyValue` | Define the available options for users to select from.
|`layout` | `String` | Select which layout to use for these fields.
|`defaultValue` | `String` | Entering a default value will place the value in the field when it loads.


####  Date/Time
| Field | Type | Description
| - | - | -
|`includeTime` | `Boolean` | Whether this field should include the time.
|`defaultValue` | `String` | Entering a default value will place the value in the field when it loads.
|`displayType` | `String` | Set different display layouts for this field.


####  Dropdown
| Field | Type | Description
| - | - | -
|`multiple` | `Boolean` | Whether this field should allow multiple options to be selected.
|`options` | `String` | Define the available options for users to select from.
|`defaultValue` | `String` | Entering a default value will place the value in the field when it loads.


####  Email Address
| Field | Type | Description
| - | - | -
|`placeholder` | `String` | The text that will be shown if the field doesn’t have a value.
|`defaultValue` | `String` | Entering a default value will place the value in the field when it loads.


####  Entries
| Field | Type | Description
| - | - | -
|`placeholder` | `String` | The option shown initially, when no option is selected.
|`sources` | `String` | Which sources do you want to select entries from?
|`limit` | `String` | Limit the number of selectable entries.
|`entries` | `EntryQuery` | The entry query for available entries.


####  File Upload
| Field | Type | Description
| - | - | -
|`uploadLocationSource` | `String` | The volume for files to be uploaded into.
|`uploadLocationSubpath` | `String` | The sub-path for the files to be uploaded into.
|`limitFiles` | `String` | Limit the number of files a user can upload.
|`sizeLimit` | `String` | Limit the size of the files a user can upload.
|`allowedKinds` | `String` | A collection of allowed mime-types the user can upload.
|


####  Heading
| Field | Type | Description
| - | - | -
|`headingSize` | `String` | Choose the size for the heading.


####  Hidden
| Field | Type | Description
| - | - | -
|`defaultOption` | `String` | The selected option for the preset default value chosen.
|`defaultValue` | `String` | Entering a default value will place the value in the field when it loads. This will be dependant on the value chosen for the `defaultOption`.
|`queryParameter` | `String` | If `query` string is selected for the `defaultOption`, this will contain the query string parameter to look up.


####  Html
| Field | Type | Description
| - | - | -
|`htmlContent` | `String` | Enter HTML content to be rendered for this field.


####  Multi-Line Text
| Field | Type | Description
| - | - | -
|`placeholder` | `String` | The text that will be shown if the field doesn’t have a value.
|`defaultValue` | `String` | Entering a default value will place the value in the field when it loads.
|`limit` | `String` | Whether to limit the content of this field.
|`limitType` | `String` | Either `words` or `characters`.
|`limitAmount` | `String` | The number of character or words to limit this field by.
|`useRichText` | `String` | Whether the front-end of the field should use a Rich Text editor. This is powered by [Pell](https://github.com/jaredreich/pell).
|`richTextButtons` | `String` | An array of available buttons the Rich Text field should use. Consult the [Pell](https://github.com/jaredreich/pell) docs for these options.


####  Name
| Field | Type | Description
| - | - | -
|`useMultipleFields` | `String` | Whether this field should use multiple fields for users to enter their details.
|`prefixLabel` | `String` | The label for the Prefix sub-field.
|`prefixPlaceholder` | `String` | The placeholder for the Prefix sub-field.
|`prefixDefaultValue` | `String` | The default value for the Prefix sub-field.
|`prefixRequired` | `String` | Whether the Prefix sub-field should be required.
|`prefixErrorMessage` | `String` | The error message for the Prefix sub-field.
|`prefixCollapsed` | `Boolean` | Whether the Prefix sub-field is collapsed in the control panel.
|`prefixEnabled` | `Boolean` | Whether the Prefix sub-field is enabled in the control panel.
|`prefixOptions` | `FieldAttribute` | An array of options available to pick a prefix from.
|`firstNameLabel` | `String` | The label for the First Name sub-field.
|`firstNamePlaceholder` | `String` | The placeholder for the First Name sub-field.
|`firstNameDefaultValue` | `String` | The default value for the First Name sub-field.
|`firstNameRequired` | `String` | Whether the First Name sub-field should be required.
|`firstNameErrorMessage` | `String` | The error message for the First Name sub-field.
|`firstNameCollapsed` | `Boolean` | Whether the First Name sub-field is collapsed in the control panel.
|`firstNameEnabled` | `Boolean` | Whether the First Name sub-field is enabled in the control panel.
|`middleNameLabel` | `String` | The label for the Middle Name sub-field.
|`middleNamePlaceholder` | `String` | The placeholder for the Middle Name sub-field.
|`middleNameDefaultValue` | `String` | The default value for the Middle Name sub-field.
|`middleNameRequired` | `String` | Whether the Middle Name sub-field should be required.
|`middleNameErrorMessage` | `String` | The error message for the Middle Name sub-field.
|`middleNameCollapsed` | `Boolean` | Whether the Middle Name sub-field is collapsed in the control panel.
|`middleNameEnabled` | `Boolean` | Whether the Middle Name sub-field is enabled in the control panel.
|`lastNameLabel` | `String` | The label for the Last Name sub-field.
|`lastNamePlaceholder` | `String` | The placeholder for the Last Name sub-field.
|`lastNameDefaultValue` | `String` | The default value for the Last Name sub-field.
|`lastNameRequired` | `String` | Whether the Last Name sub-field should be required.
|`lastNameErrorMessage` | `String` | The error message for the Last Name sub-field.
|`lastNameCollapsed` | `Boolean` | Whether the Last Name sub-field is collapsed in the control panel.
|`lastNameEnabled` | `Boolean` | Whether the Last Name sub-field is enabled in the control panel.


####  Number
| Field | Type | Description
| - | - | -
|`placeholder` | `String` | The text that will be shown if the field doesn’t have a value.
|`defaultValue` | `String` | Entering a default value will place the value in the field when it loads.
|`limit` | `String` | Whether to limit the numbers for this field.
|`min` | `String` | The minimum number that can be entered for this field.
|`max` | `String` | The maximum number that can be entered for this field.
|`decimals` | `String` | Set the number of decimal points to format the field value.


####  Phone
| Field | Type | Description
| - | - | -
|`showCountryCode` | `String` | Whether to show an additional dropdown for selecting the country code.
|`countryLabel` | `String` | The label for the Country sub-field.
|`countryPlaceholder` | `String` | The placeholder for the Country sub-field.
|`countryDefaultValue` | `String` | The default value for the Country sub-field.
|`countryCollapsed` | `Boolean` | Whether the Country sub-field is collapsed in the control panel.
|`countryEnabled` | `Boolean` | Whether the Country sub-field is enabled in the control panel.
|`countryOptions` | `FieldAttribute` | An array of options available to pick a country from.
|`numberLabel` | `String` | The label for the Number sub-field.
|`numberPlaceholder` | `String` | The placeholder for the Number sub-field.
|`numberDefaultValue` | `String` | The default value for the Number sub-field.
|`numberCollapsed` | `Boolean` | Whether the Number sub-field is collapsed in the control panel.


####  Products
| Field | Type | Description
| - | - | -
|`placeholder` | `String` | The option shown initially, when no option is selected.
|`sources` | `String` | Which sources do you want to select products from?
|`limit` | `String` | Limit the number of selectable products.
|`products` | `ProductQuery` | The product query for available products.


####  Radio
| Field | Type | Description
| - | - | -
|`options` | `String` | Define the available options for users to select from.
|`layout` | `String` | Select which layout to use for these fields.


####  Repeater
| Field | Type | Description
| - | - | -
|`addLabel` | `String` | The label for the button that adds another instance.
|`minRows` | `String` | The minimum required number of instances of this repeater's fields that must be completed.
|`maxRows` | `String` | The maximum required number of instances of this repeater's fields that must be completed.


####  Recipients
| Field | Type | Description
| - | - | -
|`displayType` | `String` | What sort of field to show on the front-end for users.
|`options` | `String` | Define the available options for users to select from.


####  Section
| Field | Type | Description
| - | - | -
|`border` | `String` | Add a border to this section.
|`borderWidth` | `String` | Set the border width (in pixels).
|`borderColor` | `String` | Set the border color.


####  Single-Line Text
| Field | Type | Description
| - | - | -
|`placeholder` | `String` | The text that will be shown if the field doesn’t have a value.
|`defaultValue` | `String` | Entering a default value will place the value in the field when it loads.
|`limit` | `String` | Whether to limit the content of this field.
|`limitType` | `String` | Either `words` or `characters`.
|`limitAmount` | `String` | The number of character or words to limit this field by.


####  Table
| Field | Type | Description
| - | - | -
|`columns` | `String` | Define the columns your table should have.
|`defaults` | `String` | Define the default values for the field.
|`addRowLabel` | `String` | The label for the button that adds another row.
|`static` | `String` | Whether this field should disallow adding more rows, showing only the default rows.
|`minRows` | `String` | The minimum required number of rows in this table that must be completed.
|`maxRows` | `String` | The maximum required number of rows in this table that must be completed.


####  Tags
| Field | Type | Description
| - | - | -
|`placeholder` | `String` | The option shown initially, when no option is selected.
|`source` | `String` | Which source do you want to select tags from?
|`tags` | `TagQuery` | The tag query for available tags.


####  Users
| Field | Type | Description
| - | - | -
|`placeholder` | `String` | The option shown initially, when no option is selected.
|`sources` | `String` | Which sources do you want to select users from?
|`limit` | `String` | Limit the number of selectable users.
|`users` | `UserQuery` | The user query for available users.


####  Variants
| Field | Type | Description
| - | - | -
|`placeholder` | `String` | The option shown initially, when no option is selected.
|`source ` | `String` | Which source do you want to select variants from?
|`limit` | `String` | Limit the number of selectable variants.
|`variants` | `VariantQuery` | The variant query for available variants.


#### Nested Fields
For nested fields like Group and Repeater, you have access to `nestedRows` and `fields`.

```json
{
    formieForm (handle: "contactForm") {
        title
        handle
        
        formFields {
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

### The `CsrfTokenInterface` interface
This is the interface to allow easy retrieval of a CSRF token and value.

| Field | Type | Description
| - | - | -
| `name`| `String` | The CSRF name.
| `value`| `String` | The CSRF token.


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

You'll notice the `contactForm_yourName_FormieNameInput` type being used. This follows the structure of `{formHandle}_{fieldHandle}_FormieNameInput`. There are also a number of other input types to consider.

#### Address Field
```json
// Query
mutation saveSubmission($yourAddress:contactForm_yourAddress_FormieAddressInput) {
    save_contactForm_Submission(yourAddress: $yourAddress) {
        yourAddress
    }
}

// Query Variables
{
    "yourAddress": {
        "address1": "42 Wallaby Way",
        "city": "Sydney",
        "zip": "2000",
        "state": "NSW",
        "country": "Australia"
    }
}
```

#### Group Field
```json
// Query
mutation saveSubmission($groupField:contactForm_groupField_FormieGroupInput) {
    save_contactForm_Submission(groupField: $groupField) {
        groupField {
            firstValue: firstValue
            secondValue: secondValue
        }
    }
}

// Query Variables
{
    "groupField": {
        "firstValue": "This content",
        "secondValue": "is for groups"
    }
}
```

#### Repeater Field
```json
// Query
mutation saveSubmission($repeaterField:contactForm_repeaterField_FormieRepeaterInput) {
    save_contactForm_Submission(repeaterField: $repeaterField) {
        repeaterField {
            rows: {
                field1: field1
                field2: field2
            }
        }
    }
}

// Query Variables
{
    "repeaterField": {
        "rows": [
            {
                "field1": "First Block - Field 1",
                "field2": "First Block - Field 2"
            },
            {
                "field1": "Second Block - Field 1",
                "field2": "Second Block - Field 2"
            }
        ]
    }
}
```


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


#### Validation
If a mutation triggers a validation error, you'll get a response back, similar to the below. Here, the example shows the user didn't provide an email address, for the `emailAddress` field, despite it being required. The `message` will always be a JSON-encoded response of errors.

```json
{
    "errors": [
        {
            "message": "{\"emailAddress\":[\"Email Address cannot be blank.\"]}",
            "extensions": {
                "category": "graphql"
            }
        }
    ],
    "data": {
        "save_contactForm_Submission": null
    }
}
```

