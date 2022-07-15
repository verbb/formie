# Field

A Field object represents a form field of a particular type. Each field has its own unique attributes and functionality. Whenever you're dealing with a field in your template, you're actually working with a `Field` object.

## Attributes

Attribute | Description
--- | ---
`id` | ID of the field.
`name` | The name of the field.
`label` | An alias to `name`.
`handle` | The handle of the field.
`type` | The type of the field.
`form` | The [Form](docs:developers/form) this field belongs to.
`formId` | The [Form](docs:developers/form) ID for the form this field belongs to.
`rowId` | The [Row](docs:developers/row) ID for the row this field belongs to.
`rowIndex` | The [Row](docs:developers/row) index for the row this field belongs to. This is used for field ordering.
`settings` | A collection of settings for the field. See [Field Settings](#field-settings).

## Methods

Method | Description
--- | ---
`getSvgIcon()` | Returns the contents of an SVG icon used for a field type.
`getSvgIconPath()` | Returns the path to the SVG icon used for a field type.
`getIsNew()` | Denotes whether this field is new.
`hasLabel()` | Whether the field has a label or not. Some fields do not have one.
`getHtmlId()` | Returns a string for the `id` HTML attribute when rendering the field.
`getHtmlDataId()` | Returns a string as a `data-id` HTML attribute with the field handle.
`getHtmlName()` | Returns a string for the `name` HTML attribute when rendering the field.
`getExtraBaseFieldConfig()` | Returns any base-level configuration data for the field.
`getFieldDefaults()` | Returns any defaults for the field, when it's created.
`getContainerAttributes()` | Returns an array of options for container attributes.
`getInputAttributes()` | Returns an array of options for input attributes.
`renderHtmlTag()` | Returns a HtmlTag object for a provided theming key.
`defineHtmlTag()` | Allows fields to define what HtmlTag objects it should use.
`getParentField()` | Returns the parent field, if applicable. Only set for sub-field and nested-field types.
`setParentField()` | Sets the parent field instance, including applicable namespace.
`getFrontEndInputHtml()` | Returns the HTML for a the front-end template for a field.
`getFrontEndInputOptions()` | Returns an object of variables that can be used for front-end fields.
`getEmailHtml()` | Returns the HTML for an email notification for a field.
`afterCreateField()` | A function called after a field has been created in the control panel.


## Field Settings
The settings for a field will differ per-type, but the following are general settings applicable to all fields.

Attribute | Description
--- | ---
`labelPosition` | The position of the field's label.
`instructionsPosition` | The position of the field's instructions.
`cssClasses` | Any CSS classes, applied on the outer container of a field.
`containerAttributes` | A collection of attributes added to the outer container of a field.
`inputAttributes` | A collection of attributes added to the `input` element of a field - where applicable.

### Address
Setting | Description
--- | ---
`autocompleteLabel` | The label for the Autocomplete sub-field.
`autocompletePlaceholder` | The placeholder for the Autocomplete sub-field.
`autocompleteDefaultValue` | The default value for the Autocomplete sub-field.
`autocompleteRequired` | Whether the Autocomplete sub-field should be required.
`autocompleteErrorMessage` | The error message for the Autocomplete sub-field.
`autocompleteCollapsed` | Whether the Autocomplete sub-field is collapsed in the control panel.
`autocompleteEnabled` | Whether the Autocomplete sub-field is enabled in the control panel.
`autocompleteCurrentLocation` | Whether the Autocomplete sub-field should show a "Show location" link.
`autocompletePrePopulate` | The field’s pre-populated value extracted from the query string.
`autocompleteIntegration` | The handle of the Address Provider integration, if set.
`address1Label` | The label for the Address 1 sub-field.
`address1Placeholder` | The placeholder for the Address 1 sub-field.
`address1DefaultValue` | The default value for the Address 1 sub-field.
`address1Required` | Whether the Address 1 sub-field should be required.
`address1ErrorMessage` | The error message for the Address 1 sub-field.
`address1Collapsed` | Whether the Address 1 sub-field is collapsed in the control panel.
`address1Enabled` | Whether the Address 1 sub-field is enabled in the control panel.
`address1Hidden` | Whether the Address 1 sub-field is hidden.
`address2Label` | The label for the Address 2 sub-field.
`address2Placeholder` | The placeholder for the Address 2 sub-field.
`address2DefaultValue` | The default value for the Address 2 sub-field.
`address2Required` | Whether the Address 2 sub-field should be required.
`address2ErrorMessage` | The error message for the Address 2 sub-field.
`address2Collapsed` | Whether the Address 2 sub-field is collapsed in the control panel.
`address2Enabled` | Whether the Address 2 sub-field is enabled in the control panel.
`address2Hidden` | Whether the Address 2 sub-field is hidden.
`address3Label` | The label for the Address 3 sub-field.
`address3Placeholder` | The placeholder for the Address 3 sub-field.
`address3DefaultValue` | The default value for the Address 3 sub-field.
`address3Required` | Whether the Address 3 sub-field should be required.
`address3ErrorMessage` | The error message for the Address 3 sub-field.
`address3Collapsed` | Whether the Address 3 sub-field is collapsed in the control panel.
`address3Enabled` | Whether the Address 3 sub-field is enabled in the control panel.
`address3Hidden` | Whether the Address 3 sub-field is hidden.
`cityLabel` | The label for the City sub-field.
`cityPlaceholder` | The placeholder for the City sub-field.
`cityDefaultValue` | The default value for the City sub-field.
`cityRequired` | Whether the City sub-field should be required.
`cityErrorMessage` | The error message for the City sub-field.
`cityCollapsed` | Whether the City sub-field is collapsed in the control panel.
`cityEnabled` | Whether the City sub-field is enabled in the control panel.
`cityHidden` | Whether the City sub-field is hidden.
`stateLabel` | The label for the State sub-field.
`statePlaceholder` | The placeholder for the State sub-field.
`stateDefaultValue` | The default value for the State sub-field.
`stateRequired` | Whether the State sub-field should be required.
`stateErrorMessage` | The error message for the State sub-field.
`stateCollapsed` | Whether the State sub-field is collapsed in the control panel.
`stateEnabled` | Whether the State sub-field is enabled in the control panel.
`stateHidden` | Whether the State sub-field is hidden.
`zipLabel` | The label for the Zip sub-field.
`zipPlaceholder` | The placeholder for the Zip sub-field.
`zipDefaultValue` | The default value for the Zip sub-field.
`zipRequired` | Whether the Zip sub-field should be required.
`zipErrorMessage` | The error message for the Zip sub-field.
`zipCollapsed` | Whether the Zip sub-field is collapsed in the control panel.
`zipEnabled` | Whether the Zip sub-field is enabled in the control panel.
`zipHidden` | Whether the Zip sub-field is hidden.
`countryLabel` | The label for the Country sub-field.
`countryPlaceholder` | The placeholder for the Country sub-field.
`countryDefaultValue` | The default value for the Country sub-field.
`countryRequired` | Whether the Country sub-field should be required.
`countryErrorMessage` | The error message for the Country sub-field.
`countryCollapsed` | Whether the Country sub-field is collapsed in the control panel.
`countryEnabled` | Whether the Country sub-field is enabled in the control panel.
`countryHidden` | Whether the Country sub-field is hidden.
`countryOptions` | `[FieldAttribute]` | An array of options available to pick a country from.


### Agree
Setting | Description
--- | ---
`description` | The description for the field. This will be shown next to the checkbox.
`descriptionHtml` | The HTML description for the field.
`checkedValue `| The value of this field when it is checked.
`uncheckedValue` | The value of this field when it is unchecked.
`defaultValue` | The default value for the field when it loads.


### Calculations
Setting | Description
--- | ---
`formula` | The raw formula used in the field, before it's been parsed.

Method | Description
--- | ---
`getFormula()` | Returns the parsed formula, given the current submission's context.


### Categories
Setting | Description
--- | ---
`placeholder` | The option shown initially, when no option is selected.
`source` | Which source do you want to select categories from?
`branchLimit` | Limit the number of selectable category branches.
`rootCategory` | The category to act as the root, if set.
`showStructure` | Whether the structure of categories should be shown.


### Checkboxes
Setting | Description
--- | ---
`options` | Define the available options for users to select from.
`layout` | Select which layout to use for these fields.
`toggleCheckbox` | Whether to add an additional checkbox to toggle all checkboxes in this field by. Either `null`, `top`, `bottom`.
`toggleCheckboxLabel` | The label for the toggle checkbox field.


### Date/Time
Setting | Description
--- | ---
`defaultValue` | Entering a default value will place the value in the field when it loads.
`displayType` | The display layout for this field. Either `calendar`, `dropdowns` or `inputs`.
`dateFormat` | The chosen format for the date.
`timeFormat` | The chosen format for the time.
`includeDate` | Whether this field should include the date.
`includeTime` | Whether this field should include the time.
`timeLabel` | The label for the time sub-field.
`dayLabel` | The label for the day sub-field.
`dayPlaceholder` | The placeholder for the day sub-field.
`monthLabel` | The label for the month sub-field.
`monthPlaceholder` | The placeholder for the month sub-field.
`yearLabel` | The label for the year sub-field.
`yearPlaceholder` | The placeholder for the year sub-field.
`hourLabel` | The label for the hour sub-field.
`hourPlaceholder` | The placeholder for the hour sub-field.
`minuteLabel` | The label for the minute sub-field.
`minutePlaceholder` | The placeholder for the minute sub-field.
`secondLabel` | The label for the second sub-field.
`secondPlaceholder` | The placeholder for the second sub-field.
`ampmLabel` | The label for the AM/PM sub-field.
`ampmPlaceholder` | The placeholder for the AM/PM sub-field.
`useDatePicker` | Whether this field should use the Flatpickr datepicker.
`datePickerOptions` | A collection of options for the Flatpickr datepicker.
`minDate` | The minimum allowed date.
`maxDate` | The maximum allowed date.


### Dropdown
Setting | Description
--- | ---
`multiple` | Whether this field should allow multiple options to be selected.
`options` | Define the available options for users to select from.


### Email Address
Setting | Description
--- | ---
`placeholder` | The text that will be shown if the field doesn’t have a value.
`defaultValue` | Entering a default value will place the value in the field when it loads.
`validateDomain` | Whether to validate the domain when the value is saved.
`blockedDomains` | A list of domains to block values from.
`uniqueValue` | Whether to the value of this field should be unique across all submissions for the form.


### Entries
Setting | Description
--- | ---
`placeholder` | The option shown initially, when no option is selected.
`sources` | Which sources do you want to select entries from?
`limit` | Limit the number of selectable entries.


### File Upload
Setting | Description
--- | ---
`uploadLocationSource` | The volume for files to be uploaded into.
`uploadLocationSubpath` | The sub-path for the files to be uploaded into.
`limitFiles` | Limit the number of files a user can upload.
`sizeLimit` | Limit the size of the files a user can upload.
`allowedKinds` | A collection of allowed mime-types the user can upload.


### Heading
Setting | Description
--- | ---
`headingSize` | Choose the size for the heading.


### Hidden
Setting | Description
--- | ---
`defaultOption` | The selected option for the preset default value chosen.
`defaultValue` | Entering a default value will place the value in the field when it loads. This will be dependant on the value chosen for the `defaultOption`.
`queryParameter` | If `query string` is selected for the `defaultOption`, this will contain the query string parameter to look up.
`cookieName` | If `cookie` is selected for the `defaultOption`, this will contain the cookie name to look up.


### Html
Setting | Description
--- | ---
`htmlContent` | Enter HTML content to be rendered for this field.


### Multi-Line Text
Setting | Description
--- | ---
`placeholder` | The text that will be shown if the field doesn’t have a value.
`defaultValue` | Entering a default value will place the value in the field when it loads.
`limit` | Whether to limit the content of this field.
`limitType` | The field’s limiting type. Either `characters` or `words`.
`limitAmount` | The field’s number of characters/words to limit, based on `limitType`.
`useRichText` | Whether the front-end of the field should use a Rich Text editor. This is powered by [Pell](https://github.com/jaredreich/pell).
`richTextButtons` | An array of available buttons the Rich Text field should use. Consult the [Pell](https://github.com/jaredreich/pell) docs for these options.


### Name
Setting | Description
--- | ---
`useMultipleFields` | Whether this field should use multiple fields for users to enter their details.
`prefixLabel` | The label for the Prefix sub-field.
`prefixPlaceholder` | The placeholder for the Prefix sub-field.
`prefixDefaultValue` | The default value for the Prefix sub-field.
`prefixRequired` | Whether the Prefix sub-field should be required.
`prefixErrorMessage` | The error message for the Prefix sub-field.
`prefixCollapsed` | Whether the Prefix sub-field is collapsed in the control panel.
`prefixEnabled` | Whether the Prefix sub-field is enabled in the control panel.
`prefixOptions` | An array of options available to pick a prefix from.
`firstNameLabel` | The label for the First Name sub-field.
`firstNamePlaceholder` | The placeholder for the First Name sub-field.
`firstNameDefaultValue` | The default value for the First Name sub-field.
`firstNameRequired` | Whether the First Name sub-field should be required.
`firstNameErrorMessage` | The error message for the First Name sub-field.
`firstNameCollapsed` | Whether the First Name sub-field is collapsed in the control panel.
`firstNameEnabled` | Whether the First Name sub-field is enabled in the control panel.
`middleNameLabel` | The label for the Middle Name sub-field.
`middleNamePlaceholder` | The placeholder for the Middle Name sub-field.
`middleNameDefaultValue` | The default value for the Middle Name sub-field.
`middleNameRequired` | Whether the Middle Name sub-field should be required.
`middleNameErrorMessage` | The error message for the Middle Name sub-field.
`middleNameCollapsed` | Whether the Middle Name sub-field is collapsed in the control panel.
`middleNameEnabled` | Whether the Middle Name sub-field is enabled in the control panel.
`lastNameLabel` | The label for the Last Name sub-field.
`lastNamePlaceholder` | The placeholder for the Last Name sub-field.
`lastNameDefaultValue` | The default value for the Last Name sub-field.
`lastNameRequired` | Whether the Last Name sub-field should be required.
`lastNameErrorMessage` | The error message for the Last Name sub-field.
`lastNameCollapsed` | Whether the Last Name sub-field is collapsed in the control panel.
`lastNameEnabled` | Whether the Last Name sub-field is enabled in the control panel.


### Number
Setting | Description
--- | ---
`placeholder` | The text that will be shown if the field doesn’t have a value.
`defaultValue` | Entering a default value will place the value in the field when it loads.
`limit` | Whether to limit the numbers for this field.
`min` | The minimum number that can be entered for this field.
`max` | The maximum number that can be entered for this field.
`decimals` | Set the number of decimal points to format the field value.


### Payment
This field's settings will differ depending on the [Payment Integration](docs:integrations/payments) chosen.

Setting | Description
--- | ---
`paymentIntegration` | The handle of the [Payment Integration](docs:integrations/payments) chosen.
`paymentIntegrationType` | The class of the [Payment Integration](docs:integrations/payments) chosen.
`providerSettings` | A collection of settings for the payment provider to use.

Method | Description
--- | ---
`getPaymentIntegration()` | Returns the [Payment Integration](docs:integrations/payments) for the field.
`getPaymentHtml()` | Returns the HTML for the front-end field.
`getFrontEndJsModules()` | Returns the JavaScript modules for the front-end field.


### Phone
Setting | Description
--- | ---
`showCountryCode` | Whether to show an additional dropdown for selecting the country code.
`countryShowDialCode` | Whether to show an the dial code.
`countryAllowed` | A collection of allowed countries.
`countryLabel` | The label for the Country sub-field.
`countryPlaceholder` | The placeholder for the Country sub-field.
`countryDefaultValue` | The default value for the Country sub-field.
`countryCollapsed` | Whether the Country sub-field is collapsed in the control panel.
`countryEnabled` | Whether the Country sub-field is enabled in the control panel.
`countryOptions` | An array of options available to pick a country from.
`numberLabel` | The label for the Number sub-field.
`numberPlaceholder` | The placeholder for the Number sub-field.
`numberDefaultValue` | The default value for the Number sub-field.
`numberCollapsed` | Whether the Number sub-field is collapsed in the control panel.


### Products
Setting | Description
--- | ---
`placeholder` | The option shown initially, when no option is selected.
`sources` | Which sources do you want to select products from?
`limit` | Limit the number of selectable products.


### Radio
Setting | Description
--- | ---
`options` | Define the available options for users to select from.
`layout` | Select which layout to use for these fields. Either `vertical` or `horizontal`,


### Recipients
Setting | Description
--- | ---
`displayType` | What sort of field to show on the front-end for users. Either `hidden`, `dropdown`, `checkboxes` or `radio`.
`options` | Define the available options for users to select from.


### Repeater
Setting | Description
--- | ---
`addLabel` | The label for the button that adds another instance.
`minRows` | The minimum required number of instances of this repeater's fields that must be completed.
`maxRows` | The maximum required number of instances of this repeater's fields that must be completed.


### Section
Setting | Description
--- | ---
`border` | Add a border to this section.
`borderWidth` | Set the border width (in pixels).
`borderColor` | Set the border color.


### Signature
Setting | Description
--- | ---
`backgroundColor` | Set the background color.
`penColor` | Set the pen color.
`penWeight` | Set the line thickness (weight) for the pen.


### Single-Line Text
Setting | Description
--- | ---
`placeholder` | The text that will be shown if the field doesn’t have a value.
`defaultValue` | Entering a default value will place the value in the field when it loads.
`limit` | Whether to limit the content of this field.
`limitType` | The field’s limiting type. Either `characters` or `words`.
`limitAmount` | The field’s number of characters/words to limit, based on `limitType`.


### Table
Setting | Description
--- | ---
`columns` | Define the columns your table should have.
`defaults` | Define the default values for the field.
`addRowLabel` | The label for the button that adds another row.
`static` | Whether this field should disallow adding more rows, showing only the default rows.
`minRows` | The minimum required number of rows in this table that must be completed.
`maxRows` | The maximum required number of rows in this table that must be completed.


### Tags
Setting | Description
--- | ---
`placeholder` | The option shown initially, when no option is selected.
`source` | Which source do you want to select tags from?


### Users
Setting | Description
--- | ---
`placeholder` | The option shown initially, when no option is selected.
`sources` | Which sources do you want to select users from?
`limit` | Limit the number of selectable users.


### Variants
Setting | Description
--- | ---
`placeholder` | The option shown initially, when no option is selected.
`source `| Which source do you want to select variants from?
`limit` | Limit the number of selectable variants.

## Custom Validation
You can add custom validation to field, to handle all manner of scenarios. To do this, you'll need to create a custom module to contain PHP code for the validation logic.

:::tip
Make sure you’re comfortable [creating a plugin or module for Craft CMS](https://craftcms.com/docs/3.x/extend/). Take a look at this [Knowledge Base](https://craftcms.com/knowledge-base/custom-module-events) article for a complete example.
:::

If you write your own plugin or module, you’ll want to use its `init()` method to subscribe to an event on the `Submission` object to add your validation rules. Your event listener can add additional [validation rules](https://www.yiiframework.com/doc/guide/2.0/en/input-validation#declaring-rules) for fields.

For example, let's say we have a field with a handle `emailAddress` which we'd like required. We could do this with the following.

```php
use verbb\formie\elements\Submission;
use verbb\formie\events\SubmissionRulesEvent;
use yii\base\Event;

Event::on(Submission::class, Submission::EVENT_DEFINE_RULES, function(SubmissionRulesEvent $event) {
    $event->rules[] = [['field:emailAddress'], 'required'];
});
```

Or, maybe we only want a field required when another field is a certain value.

```php
Event::on(Submission::class, Submission::EVENT_DEFINE_RULES, function(SubmissionRulesEvent $event) {
    $event->rules[] = [['field:emailAddress'], 'required', 'when' => function($model) {
        return $model->subscribeMe;
    }]];
});
```

Here, we check if a field with handle `subscribeMe` is truthy, and if so make the `emailAddress` field required.

Another example could be limiting a field to be numeric, and a maximum length.

```php
Event::on(Submission::class, Submission::EVENT_DEFINE_RULES, function(SubmissionRulesEvent $event) {
    $event->rules[] = [[‘field:accountNumber'], 'number', 'integerOnly' => true, ‘max’ => 9];
});
```

**However**, the above rules are applied to _every_ submission, which will throw an error if you set a rule for a field that doesn't exist on the form for the submission you're creating. The above example assumes you have a field with the handle `emailAddress` for every form, which may not always be the case, especially if you have multiple forms.

Instead, you'll want to add a conditional check what form you're creating a submission on.

```php
Event::on(Submission::class, Submission::EVENT_DEFINE_RULES, function(SubmissionRulesEvent $event) {
    $form = $event->submission->getForm();

    // Only apply this custom validation for the form with handle `contactForm`
    if ($form->handle === 'contactForm') {
        $event->rules[] = [['field:emailAddress'], 'required'];
    }
});
```

If you have a lot of forms, or would rather not conditionally check _every_ form for your site, you can loop through the available fields for the submission, to add a check whether the field exists. This can be useful if you want to enforce a validation for all email fields across your site, but not every form has an `emailAddress` field.

```php
Event::on(Submission::class, Submission::EVENT_DEFINE_RULES, function(SubmissionRulesEvent $event) {
    if ($fieldLayout = $event->submission->getFieldLayout()) {
        foreach ($fieldLayout->getCustomFields() as $field) {
            // Check against the handle of the field
            if ($field->handle === 'emailAddress') {
                $event->rules[] = [['field:emailAddress'], 'required'];
            }

            // Or, for a more global-check - against the type of the field
            if ($field instanceof \verbb\formie\fields\formfields\Email) {
                $event->rules[] = [['field:emailAddress'], 'required'];
            }
        }
    }
});
```
 