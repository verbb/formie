# Query Forms

Formie exposes forms, pages, rows and fields through GraphQL. This is useful when you need to build your own front-end, inspect a form’s field layout, or fetch the settings your app needs before rendering a form.

Use `formieForm` when you expect one form, or `formieForms` when you need a list.

```graphql
{
    formieForm(handle: "contactForm") {
        title
        handle
        isAvailable

        settings {
            errorMessageHtml
            submitMethod
            submitAction
        }

        pages {
            label

            rows {
                rowFields {
                    label
                    handle
                    typeName
                    inputTypeName

                    ... on Field_Name {
                        fields {
                            label
                            handle
                            enabled
                            required
                        }
                    }

                    ... on Field_Email {
                        placeholder
                        validateDomain
                    }
                }
            }
        }
    }
}
```

## Form Queries

The `formieForms`, `formieForm` and `formieFormCount` queries accept Craft’s standard element query arguments, along with Formie’s `handle` argument.

Argument | Type | Description
--- | --- | ---
`handle` | `[String]` | Narrows results by form handle.
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

## Form Fields

Every form implements `FormInterface`.

Field | Type | Description
--- | --- | ---
`handle` | `String` | The form handle.
`pages` | `[PageInterface]` | The form’s pages.
`rows(includeDisabled: Boolean)` | `[RowInterface]` | The form’s rows.
`formFields(includeDisabled: Boolean)` | `[FieldInterface]` | The form’s fields.
`settings` | `FormSettingsType` | The form’s settings.
`isAvailable` | `Boolean` | Whether the form is available according to scheduling, user checks and other restrictions.

Use `rows` when you need the form’s layout structure. Use `formFields` when you just need the fields, without their row and page positions. Disabled fields are omitted by default; pass `includeDisabled: true` when you need them.

```graphql
{
    formieForm(handle: "contactForm") {
        formFields(includeDisabled: true) {
            label
            handle
            visibility
        }
    }
}
```

Older examples may refer to fields such as `templateHtml`, `csrfToken`, `captchas`, `submissionMutationName` or `submissionEndpoint` on `formieForm`. Those values now live in the dedicated rendering and front-end payload queries covered in [Rendering Forms](/graphql/rendering-forms).

## Form Settings

The `settings` field exposes the form-level settings that commonly affect rendering and submission behavior.

Field | Type | Description
--- | --- | ---
`displayFormTitle` | `Boolean` | Whether to show the form title.
`displayCurrentPageTitle` | `Boolean` | Whether to show the current page title.
`displayPageTabs` | `Boolean` | Whether to show page tabs.
`displayPageProgress` | `Boolean` | Whether to show page progress.
`progressPosition` | `String` | The progress bar position.
`progressValuePosition` | `String` | The progress value position.
`scrollToTop` | `Boolean` | Whether the form should scroll to the top after submission.
`defaultLabelPosition` | `String` | The default field label position.
`defaultInstructionsPosition` | `String` | The default field instructions position.
`requiredIndicator` | `String` | How required or optional fields are marked.
`submitMethod` | `String` | The submit method, such as `page-reload` or `ajax`.
`submitAction` | `String` | The submit action, such as `message`, `entry`, `url` or `reload`.
`submitActionTab` | `String` | Whether redirects open in the same tab or a new tab.
`submitActionFormHide` | `Boolean` | Whether to hide the form after success.
`automaticSubmissionState` | `Boolean` | Whether Formie should automatically restore an in-progress submission when the visitor returns.
`submitActionMessageHtml` | `String` | The success message HTML.
`submitActionMessageTimeout` | `Int` | The success message timeout, in seconds.
`submitActionMessagePosition` | `String` | The success message position.
`loadingIndicator` | `String` | The loading indicator type.
`loadingIndicatorText` | `String` | The loading indicator text.
`validationOnSubmit` | `Boolean` | Whether to validate on submit.
`validationOnFocus` | `Boolean` | Whether to validate on focus.
`errorMessageHtml` | `String` | The submit error message HTML.
`errorMessagePosition` | `String` | The error message position.
`redirectUrl` | `String` | The resolved redirect URL.
`redirectEntry` | `EntryInterface` | The redirect entry, when the form redirects to an entry.
`integrations` | `[FormIntegrationsType]` | Enabled integrations for the form.

## Pages And Rows

Pages expose their label, rows, fields and page settings.

Field | Type | Description
--- | --- | ---
`label` | `String` | The page label.
`rows` | `[RowInterface]` | The page’s rows.
`pageFields(includeDisabled: Boolean)` | `[FieldInterface]` | The page’s fields.
`settings` | `PageSettingsInterface` | Page and button settings.

Rows expose `rowFields(includeDisabled: Boolean)`, which is the list of fields in that row.

```graphql
{
    formieForm(handle: "contactForm") {
        pages {
            label
            pageFields {
                handle
                label
            }
        }
    }
}
```

Page settings include page button labels, conditions, save buttons and client event data.

Field | Type | Description
--- | --- | ---
`submitButtonLabel` | `String` | The submit or next button label.
`backButtonLabel` | `String` | The back button label.
`showBackButton` | `Boolean` | Whether the back button is shown.
`saveButtonLabel` | `String` | The save button label.
`showSaveButton` | `Boolean` | Whether the save button is shown.
`buttonsPosition` | `String` | The page button position.
`cssClasses` | `String` | CSS classes for the page buttons.
`containerAttributes` | `[FieldAttribute]` | Button container attributes.
`inputAttributes` | `[FieldAttribute]` | Button input attributes.
`enablePageConditions` | `Boolean` | Whether page conditions are enabled.
`pageConditions` | `Json` | Page conditions.
`enableNextButtonConditions` | `Boolean` | Whether next button conditions are enabled.
`nextButtonConditions` | `Json` | Next button conditions.
`enableClientEvents` | `Boolean` | Whether client event payloads are enabled for the page submit.
`clientEventFields` | `Json` | Key/value rows for the client event payload.

## Field Interface

Every field implements `FieldInterface`. Field-specific settings are available with GraphQL inline fragments.

Field | Type | Description
--- | --- | ---
`label` | `String` | The field label.
`handle` | `String` | The field handle.
`instructions` | `String` | The field instructions.
`required` | `Boolean` | Whether the field is required.
`enabled` | `Boolean` | Whether the field is enabled.
`type` | `String` | The field’s PHP class.
`displayName` | `String` | The field class name without the namespace.
`typeName` | `String` | The field’s GraphQL type name.
`inputTypeName` | `String` | The GraphQL input type to use when creating submissions.
`matchField` | `String` | The handle of another field this value should match.
`placeholder` | `String` | The field placeholder.
`defaultValue` | `String` | The field’s default value, when it can be represented as a string.
`emailFieldSummaryValue` | `String` | The value format used in email field summaries.
`emailValue` | `String` | Deprecated alias for `emailFieldSummaryValue`.
`prePopulate` | `String` | The query-string parameter used to prefill the field’s initial value.
`errorMessage` | `String` | The field’s error message.
`labelPosition` | `String` | The label position.
`instructionsPosition` | `String` | The instructions position.
`cssClasses` | `String` | Field CSS classes.
`containerAttributes` | `[FieldAttribute]` | Field container attributes.
`inputAttributes` | `[FieldAttribute]` | Field input attributes.
`includeInEmailFieldSummaries` | `Boolean` | Whether the field is included in email field summaries.
`includeInEmail` | `Boolean` | Deprecated alias for `includeInEmailFieldSummaries`.
`enableConditions` | `Boolean` | Whether field conditions are enabled.
`conditions` | `Json` | Field conditions.
`enableContentEncryption` | `Boolean` | Whether content encryption is enabled for this field.
`visibility` | `String` | The field visibility.

```graphql
{
    formieForm(handle: "contactForm") {
        formFields {
            label
            handle

            ... on Field_Dropdown {
                options {
                    label
                    value
                    isDefault
                }
            }

            ... on Field_Group {
                nestedRows {
                    fields {
                        label
                        handle
                    }
                }
            }
        }
    }
}
```

## Field-Specific Settings

These fields are available through inline fragments in addition to the shared `FieldInterface` fields. The generated GraphQL schema remains the source of truth for a project, because element queries and third-party field types can change based on the installed plugins and the form’s field layout.

### Address

Address inherits the nested field settings used by sub-field types.

Field | Type | Description
--- | --- | ---
`nestedRows` | `[RowInterface]` | The field’s nested rows.
`fields` | `[FieldInterface]` | The field’s nested fields.
`subFieldLabelPosition` | `String` | The label position for the sub-fields.

### Agree

Field | Type | Description
--- | --- | ---
`checkedValue` | `String` | The value stored when the checkbox is checked.
`uncheckedValue` | `String` | The value stored when the checkbox is unchecked.
`defaultValue` | `String` | The default value as a string.
`defaultState` | `Boolean` | Whether the field is checked by default.
`descriptionHtml` | `String` | The field description HTML.

### Calculations

Field | Type | Description
--- | --- | ---
`formula` | `String` | The calculated formula as a JSON string.
`formatting` | `String` | The output formatting mode.
`prefix` | `String` | Text shown before the calculated value.
`suffix` | `String` | Text shown after the calculated value.
`decimals` | `Int` | The number of decimal places.

### Categories

Categories inherits element field settings.

Field | Type | Description
--- | --- | ---
`sources` | `String` | The selected sources as a JSON string.
`source` | `String` | The selected source.
`limitOptions` | `String` | The selectable item limit.
`displayType` | `String` | The front-end display type.
`useSearchable` | `Boolean` | Whether the front-end dropdown is filterable when `displayType` is `dropdown`.
`labelSource` | `String` | Where option labels should come from.
`orderBy` | `String` | The element query sort order.
`multi` | `Boolean` | Whether multiple values can be selected.
`layout` | `String` | The option layout.
`defaultValue` | `String` | The default value as a string.
`defaultCategory` | `CategoryInterface` | The default category element.
`categories` | `[CategoryInterface]` | The selectable category elements.
`rootCategory` | `CategoryInterface` | The root category element.

### Checkboxes

Checkboxes inherits option field settings.

Field | Type | Description
--- | --- | ---
`multi` | `Boolean` | Whether multiple values can be selected.
`layout` | `String` | The option layout.
`options` | `[FieldOption]` | The field options.
`limitOptions` | `Boolean` | Whether selected options are limited.
`min` | `Int` | The minimum number of selected options.
`max` | `Int` | The maximum number of selected options.
`toggleCheckbox` | `String` | Whether a select-all checkbox is shown.
`toggleCheckboxLabel` | `String` | The select-all checkbox label.

### Date/Time

Field | Type | Description
--- | --- | ---
`defaultValue` | `String` | The default value as a string.
`displayType` | `String` | The date/time display type.
`defaultDate` | `DateTime` | The default date value.
`minDate` | `DateTime` | The minimum allowed date.
`maxDate` | `DateTime` | The maximum allowed date.
`datePickerOptions` | `[FieldAttribute]` | Date picker options.

### Dropdown

Dropdown inherits option field settings.

Field | Type | Description
--- | --- | ---
`multi` | `Boolean` | Whether multiple values can be selected.
`layout` | `String` | The option layout.
`useSearchable` | `Boolean` | Whether the front-end dropdown is filterable.
`options` | `[FieldOption]` | The field options.

### Email Address

Field | Type | Description
--- | --- | ---
`blockedDomains` | `[String]` | Domains that cannot be submitted.

### Entries

Entries inherits element field settings.

Field | Type | Description
--- | --- | ---
`sources` | `String` | The selected sources as a JSON string.
`source` | `String` | The selected source.
`limitOptions` | `String` | The selectable item limit.
`displayType` | `String` | The front-end display type.
`useSearchable` | `Boolean` | Whether the front-end dropdown is filterable when `displayType` is `dropdown`.
`labelSource` | `String` | Where option labels should come from.
`orderBy` | `String` | The element query sort order.
`multi` | `Boolean` | Whether multiple values can be selected.
`layout` | `String` | The option layout.
`defaultValue` | `String` | The default value as a string.
`defaultEntry` | `EntryInterface` | The default entry element.
`entries` | `[EntryInterface]` | The selectable entry elements.

### File Upload

File Upload inherits element field settings, except `limitOptions` and `multi`.

Field | Type | Description
--- | --- | ---
`sources` | `String` | The selected sources as a JSON string.
`source` | `String` | The selected source.
`displayType` | `String` | The front-end display type.
`labelSource` | `String` | Where option labels should come from.
`orderBy` | `String` | The element query sort order.
`layout` | `String` | The option layout.
`defaultValue` | `String` | The default value as a string.
`sizeLimit` | `String` | The maximum file size.
`sizeMinLimit` | `String` | The minimum file size.
`limitFiles` | `String` | The file count limit.
`allowedKinds` | `[String]` | Allowed file kinds.
`volumeHandle` | `String` | The upload volume handle.

### Group

Field | Type | Description
--- | --- | ---
`nestedRows` | `[RowInterface]` | The field’s nested rows.
`fields` | `[FieldInterface]` | The field’s nested fields.

### Heading

Field | Type | Description
--- | --- | ---
`headingSize` | `String` | The heading tag size.

### Hidden

Field | Type | Description
--- | --- | ---
`defaultOption` | `String` | The selected default value option.
`queryParameter` | `String` | The query-string parameter to read from.
`cookieName` | `String` | The cookie name to read from.

### HTML

Field | Type | Description
--- | --- | ---
`htmlContent` | `String` | The HTML or Twig content rendered for the field.

### Multi-Line Text

Field | Type | Description
--- | --- | ---
`limit` | `Boolean` | Whether content length limits are enabled.
`min` | `Int` | The minimum length.
`minType` | `String` | Whether the minimum is counted as characters or words.
`max` | `Int` | The maximum length.
`maxType` | `String` | Whether the maximum is counted as characters or words.
`useRichText` | `Boolean` | Whether the field uses rich text.
`richTextButtons` | `[String]` | The enabled rich text buttons.
`uniqueValue` | `Boolean` | Whether the value must be unique.

### Name

Field | Type | Description
--- | --- | ---
`nestedRows` | `[RowInterface]` | The field’s nested rows.
`fields` | `[FieldInterface]` | The field’s nested fields.
`subFieldLabelPosition` | `String` | The label position for the sub-fields.
`useMultipleFields` | `Boolean` | Whether the Name field uses multiple sub-fields.

### Number

Field | Type | Description
--- | --- | ---
`limit` | `Boolean` | Whether numeric limits are enabled.
`min` | `Int` | The minimum value cast to an integer.
`max` | `Int` | The maximum value cast to an integer.
`minValue` | `Float` | The minimum value.
`maxValue` | `Float` | The maximum value.
`decimals` | `Int` | The number of decimal places.

### Payment

Field | Type | Description
--- | --- | ---
`paymentIntegration` | `String` | The payment integration handle.
`paymentIntegrationType` | `String` | The payment integration class.
`providerSettings` | `String` | Provider settings as a JSON string.

### Phone

Field | Type | Description
--- | --- | ---
`countryOptions` | `[CountryOption]` | The available country options.
`countryEnabled` | `Boolean` | Whether the country sub-field is enabled.
`countryDefaultValue` | `String` | The default country value.
`countryAllowed` | `String` | Allowed countries as a JSON string.

### Products

Products inherits element field settings.

Field | Type | Description
--- | --- | ---
`sources` | `String` | The selected sources as a JSON string.
`source` | `String` | The selected source.
`limitOptions` | `String` | The selectable item limit.
`displayType` | `String` | The front-end display type.
`useSearchable` | `Boolean` | Whether the front-end dropdown is filterable when `displayType` is `dropdown`.
`labelSource` | `String` | Where option labels should come from.
`orderBy` | `String` | The element query sort order.
`multi` | `Boolean` | Whether multiple values can be selected.
`layout` | `String` | The option layout.
`defaultValue` | `String` | The default value as a string.
`defaultProduct` | `ProductInterface` | The default product element.
`products` | `[ProductInterface]` | The selectable product elements.

### Radio

Radio inherits option field settings.

Field | Type | Description
--- | --- | ---
`multi` | `Boolean` | Whether multiple values can be selected.
`layout` | `String` | The option layout.
`options` | `[FieldOption]` | The field options.

### Recipients

Field | Type | Description
--- | --- | ---
`displayType` | `String` | The front-end display type.
`useSearchable` | `Boolean` | Whether the front-end dropdown is filterable when `displayType` is `dropdown`.
`multiple` | `Boolean` | Whether multiple recipients can be selected.
`options` | `[FieldOption]` | The recipient options.

### Repeater

Field | Type | Description
--- | --- | ---
`nestedRows` | `[RowInterface]` | The field’s nested rows.
`fields` | `[FieldInterface]` | The field’s nested fields.
`minRows` | `Int` | The minimum number of rows.
`maxRows` | `Int` | The maximum number of rows.
`addLabel` | `String` | The add-row button label.

### Section

Field | Type | Description
--- | --- | ---
`borderStyle` | `String` | The section border style.
`borderWidth` | `Int` | The section border width.
`borderColor` | `String` | The section border color.

### Signature

Field | Type | Description
--- | --- | ---
`backgroundColor` | `String` | The signature pad background color.
`penColor` | `String` | The signature pen color.
`penWeight` | `String` | The signature pen weight.

### Single-Line Text

Field | Type | Description
--- | --- | ---
`limit` | `Boolean` | Whether content length limits are enabled.
`min` | `Int` | The minimum length.
`minType` | `String` | Whether the minimum is counted as characters or words.
`max` | `Int` | The maximum length.
`maxType` | `String` | Whether the maximum is counted as characters or words.
`uniqueValue` | `Boolean` | Whether the value must be unique.

### Summary

Summary only exposes the shared `FieldInterface` fields.

### Table

Field | Type | Description
--- | --- | ---
`columns` | `[TableColumn]` | The table columns.

### Tags

Tags inherits element field settings.

Field | Type | Description
--- | --- | ---
`sources` | `String` | The selected sources as a JSON string.
`source` | `String` | The selected source.
`limitOptions` | `String` | The selectable item limit.
`displayType` | `String` | The front-end display type.
`useSearchable` | `Boolean` | Whether the front-end dropdown is filterable when `displayType` is `dropdown`.
`labelSource` | `String` | Where option labels should come from.
`orderBy` | `String` | The element query sort order.
`multi` | `Boolean` | Whether multiple values can be selected.
`layout` | `String` | The option layout.
`defaultValue` | `String` | The default value as a string.
`defaultTag` | `TagInterface` | The default tag element.
`tags` | `[TagInterface]` | The selectable tag elements.

### Users

Users inherits element field settings.

Field | Type | Description
--- | --- | ---
`sources` | `String` | The selected sources as a JSON string.
`source` | `String` | The selected source.
`limitOptions` | `String` | The selectable item limit.
`displayType` | `String` | The front-end display type.
`useSearchable` | `Boolean` | Whether the front-end dropdown is filterable when `displayType` is `dropdown`.
`labelSource` | `String` | Where option labels should come from.
`orderBy` | `String` | The element query sort order.
`multi` | `Boolean` | Whether multiple values can be selected.
`layout` | `String` | The option layout.
`defaultValue` | `String` | The default value as a string.
`defaultUser` | `UserInterface` | The default user element.
`users` | `[UserInterface]` | The selectable user elements.

### Variants

Variants inherits element field settings.

Field | Type | Description
--- | --- | ---
`sources` | `String` | The selected sources as a JSON string.
`source` | `String` | The selected source.
`limitOptions` | `String` | The selectable item limit.
`displayType` | `String` | The front-end display type.
`useSearchable` | `Boolean` | Whether the front-end dropdown is filterable when `displayType` is `dropdown`.
`labelSource` | `String` | Where option labels should come from.
`orderBy` | `String` | The element query sort order.
`multi` | `Boolean` | Whether multiple values can be selected.
`layout` | `String` | The option layout.
`defaultValue` | `String` | The default value as a string.
`defaultVariant` | `VariantInterface` | The default variant element.
`variants` | `[VariantInterface]` | The selectable variant elements.

## Supporting Types

These small object types are reused by field settings throughout the form schema.

### FieldAttribute

`FieldAttribute` is used for custom attributes on fields and page buttons, such as `containerAttributes`, `inputAttributes` and `datePickerOptions`.

Field | Type | Description
--- | --- | ---
`label` | `String` | The attribute name.
`value` | `String` | The attribute value.

### FieldOption

`FieldOption` is used by option-based fields such as Dropdown, Checkboxes, Radio and Recipients.

Field | Type | Description
--- | --- | ---
`label` | `String` | The option label. For optgroups, this is resolved from the optgroup label.
`value` | `String` | The option value. Optgroups return an empty string.
`isOptgroup` | `Boolean` | Whether the row is an optgroup.
`isDefault` | `Boolean` | Whether the option is selected by default.
`disabled` | `Boolean` | Whether the option is disabled.

### CountryOption

`CountryOption` is used by Phone fields for country-code options.

Field | Type | Description
--- | --- | ---
`label` | `String` | The country label.
`value` | `String` | The country value.
`code` | `String` | The country calling code.

### Table Columns

Table field settings expose `columns`, where each column is generated as a `KeyValueType` with these fields.

Field | Type | Description
--- | --- | ---
`heading` | `String` | The column heading.
`handle` | `String` | The column handle.
`width` | `String` | The column width.
`type` | `String` | The table column type.

Table field content also generates a form-specific table row type. The row fields come from the table’s column handles, and the value type depends on the column type.

Column type | GraphQL value type
--- | ---
`date` | `DateTime`
`time` | `DateTime`
`number` | `Number`
`lightswitch` | `Boolean`
Other column types | `String`
