# Upgrading from v2
While the [changelog](https://github.com/verbb/formie/blob/craft-5/CHANGELOG.md) is the most comprehensive list of changes, this guide provides high-level overview and organizes changes by category.

## Field and Field Layouts
Fields and Field Layouts have been completely revamped in Formie 3. Previously, Formie extended the base [Field](https://github.com/craftcms/cms/blob/5.0/src/base/Field.php) class from Craft for all fields, including Element fields like Assets and Entries. While convenient to make use of Craft's implementation for field functionality, it's proved more of a hindrance with limiting what Formie fields can do, and causing issues when Craft update their own field classes.

Similarly, Craft has the concept of a Field Layout, which is attached to an element (in our case, a Submission) and defines what content an element has. But these are heavily opinionated and tied to control-panel behaviour, and Formie forms are designed to be used on the front-end. They're also more complex with Pages/Rows/Fields, as opposed to Tabs/Fields in Craft.

Finally, there's performance. Formie fields were stored alongside Craft's own fields, and made use of Craft's field layouts. Having potentially hundreds of fields stored alongside Craft fields meant that Craft would initialise these fields on every request, when they don't need to. Alongside content table changes from Craft itself, this meant big changes were in order.

So — Formie now stores all Fields, Rows, Pages and Field Layouts in its own database tables, with it's own logic. Submissions now also make use of our own Field Layout.

We've strived to make things backward compatible, and deprecated existing behaviour to ease the migration. This should only affect you if:

- You have created a custom field class
- You have custom JavaScript validation for fields
- You iterate through or access [submission content](https://verbb.io/craft-plugins/formie/user-guides/the-complete-guide-to-rendering-submission-content)
- You call `getCustomFields()` in your templates for Submissions
- You call `getFieldLayout()` in your templates for Submissions
- You manually render form [Pages](https://verbb.io/craft-plugins/formie/docs/template-guides/rendering-pages) or [Fields](https://verbb.io/craft-plugins/formie/docs/template-guides/rendering-fields)

### Twig Templates
The following describes Twig template changes for dealing with fields. You no longer need to get the Field Layout for the element, instead just accessing Pages, Rows and Fields directly from the element.

#### Form Fields
Fetching the fields for a Form can be done with `form.getFields()`.

```
// Formie v2
{% for field in form.getCustomFields() %}
    {# ... #}

// Formie v3
{% for field in form.getFields() %}
    {# ... #}
```

#### Submission Fields
Fetching submission content no longer requires you to get the Field Layout for the submission with `submission.getFieldLayout().getCustomFields()`. You can now just use `submission.getFields()`.

```
// Formie v2
{% set fieldLayout = submission.getFieldLayout() %}

{% if fieldLayout %}
    {% for field in fieldLayout.getCustomFields() %}
        {# ... #}

// Formie v3
{% for field in submission.getFields() %}
    {# ... #}
```

#### Page and Row Fields
Pages and Rows can use `getFields()` instead of `getCustomFields()`.

```
// Formie v2
{% for page in form.getPages() %}
    {% for field in page.getCustomFields() %}
        {# ... #}

// Formie v3
{% for page in form.getPages() %}
    {% for field in page.getFields() %}
        {# ... #}
```

In addition, for rows, you should use `row.getFields()` as opposed to `row.fields` (although both will work).

```
// Formie v2
{% for page in form.getPages() %}
    {% for row in page.getRows() %}
        {% for field in row.fields %}
            {# ... #}

// Formie v3
{% for page in form.getPages() %}
    {% for row in page.getRows() %}
        {% for field in row.getFields() %}
            {# ... #}
```

## Custom Field Classes

:::tip
If you haven't created your own custom field class for Formie, you can skip this section.
:::

Because of the re-architecture of Formie fields, this may affect your custom field, depending on what it is. In Formie 2, we mixed classes and traits so that we could extend from the Craft base [Field](https://github.com/craftcms/cms/blob/5.0/src/base/Field.php) class. All traits have now been removed in favour of proper classes that you can extend from.

To outline some of the changes to Formie field classes (in case your field extends one), refer to the below table.

Field | Summary
--- | ---
| `Address` | Now extends `verbb\formie\base\SubField`
| `Categories` | Now extends `verbb\formie\base\ElementField`, and no longer extends `craft\fields\Categories`
| `Checkboxes` | Now extends `verbb\formie\base\OptionsField`
| `Date` | Now extends `verbb\formie\base\SubField`
| `Dropdown` | Now extends `verbb\formie\base\OptionsField`
| `Entries` | Now extends `verbb\formie\base\ElementField`, and no longer extends `craft\fields\Entries`
| `FileUpload` | Now extends `verbb\formie\base\ElementField`, and no longer extends `craft\fields\Assets`
| `Group` | Now extends `verbb\formie\base\SingleNestedField`
| `Heading` | Now extends `verbb\formie\base\CosmeticField`
| `Html` | Now extends `verbb\formie\base\CosmeticField`
| `Name` | Now extends `verbb\formie\base\SubField`
| `Phone` | Now extends `verbb\formie\base\SubField`
| `Products` | Now extends `verbb\formie\base\ElementField`, and no longer extends `craft\commerce\fields\Products`
| `Radio` | Now extends `verbb\formie\base\OptionsField`
| `Repeater` | Now extends `verbb\formie\base\MultiNestedField`
| `Section` | Now extends `verbb\formie\base\CosmeticField`
| `Summary` | Now extends `verbb\formie\base\CosmeticField`
| `Table` | No longer extends `craft\fields\Table`
| `Tags` | Now extends `verbb\formie\base\ElementField`, and no longer extends `craft\fields\Tags`
| `Users` | Now extends `verbb\formie\base\ElementField`, and no longer extends `craft\fields\Users`
| `Variants` | Now extends `verbb\formie\base\ElementField`, and no longer extends `craft\commerce\fields\Variants`

This shouldn't affect the vast majority of custom field classes, but it's a good reference in case you were extending one of the above.

### Updated Class Namespace
We've also cleaned up class namespaces for fields. While we have aliases setup for old classes to ease the migration, these will be removed in Formie 4. If your custom class contains any reference to these, they should be updated.

Old Class | New Class
--- | ---
| `verbb\formie\base\FormField` | `verbb\formie\base\Field`
| `verbb\formie\base\FormFieldInterface` | `verbb\formie\base\FieldInterface`
| `verbb\formie\base\FormFieldTrait` | Removed. Use `verbb\formie\base\Field`
| `verbb\formie\base\NestedFieldTrait` | Removed. Use `verbb\formie\base\NestedField`
| `verbb\formie\base\RelationFieldTrait` | Removed. Use `verbb\formie\base\ElementField`
| `verbb\formie\base\SubfieldInterface` | `verbb\formie\base\SubFieldInterface`
| `verbb\formie\base\SubfieldTrait` | Removed. Use `verbb\formie\base\SubField`
| `verbb\formie\fields\formfields\Address` | `verbb\formie\fields\Address`
| `verbb\formie\fields\formfields\Agree` | `verbb\formie\fields\Agree`
| `verbb\formie\fields\formfields\Calculations` | `verbb\formie\fields\Calculations`
| `verbb\formie\fields\formfields\Categories` | `verbb\formie\fields\Categories`
| `verbb\formie\fields\formfields\Checkboxes` | `verbb\formie\fields\Checkboxes`
| `verbb\formie\fields\formfields\Date` | `verbb\formie\fields\Date`
| `verbb\formie\fields\formfields\Dropdown` | `verbb\formie\fields\Dropdown`
| `verbb\formie\fields\formfields\Email` | `verbb\formie\fields\Email`
| `verbb\formie\fields\formfields\Entries` | `verbb\formie\fields\Entries`
| `verbb\formie\fields\formfields\FileUpload` | `verbb\formie\fields\FileUpload`
| `verbb\formie\fields\formfields\Group` | `verbb\formie\fields\Group`
| `verbb\formie\fields\formfields\Heading` | `verbb\formie\fields\Heading`
| `verbb\formie\fields\formfields\Hidden` | `verbb\formie\fields\Hidden`
| `verbb\formie\fields\formfields\Html` | `verbb\formie\fields\Html`
| `verbb\formie\fields\formfields\MissingField` | `verbb\formie\fields\MissingField`
| `verbb\formie\fields\formfields\MultiLineText` | `verbb\formie\fields\MultiLineText`
| `verbb\formie\fields\formfields\Name` | `verbb\formie\fields\Name`
| `verbb\formie\fields\formfields\Number` | `verbb\formie\fields\Number`
| `verbb\formie\fields\formfields\Password` | `verbb\formie\fields\Password`
| `verbb\formie\fields\formfields\Payment` | `verbb\formie\fields\Payment`
| `verbb\formie\fields\formfields\Phone` | `verbb\formie\fields\Phone`
| `verbb\formie\fields\formfields\Products` | `verbb\formie\fields\Products`
| `verbb\formie\fields\formfields\Radio` | `verbb\formie\fields\Radio`
| `verbb\formie\fields\formfields\Recipients` | `verbb\formie\fields\Recipients`
| `verbb\formie\fields\formfields\Repeater` | `verbb\formie\fields\Repeater`
| `verbb\formie\fields\formfields\Section` | `verbb\formie\fields\Section`
| `verbb\formie\fields\formfields\Signature` | `verbb\formie\fields\Signature`
| `verbb\formie\fields\formfields\SingleLineText` | `verbb\formie\fields\SingleLineText`
| `verbb\formie\fields\formfields\Summary` | `verbb\formie\fields\Summary`
| `verbb\formie\fields\formfields\Table` | `verbb\formie\fields\Table`
| `verbb\formie\fields\formfields\Tags` | `verbb\formie\fields\Tags`
| `verbb\formie\fields\formfields\Users` | `verbb\formie\fields\Users`
| `verbb\formie\fields\formfields\Variants` | `verbb\formie\fields\Variants`

### Changed Methods
We have also changed several methods.

Old | What to do instead
--- | ---
| `Formie::log()` | `Formie::info()`
| `Field::hasSubfields()` | `Field::hasSubFields()`
| `Field::getBaseFieldConfig()` | `Field::getFieldTypeConfig()`
| `Field::getExtraBaseFieldConfig()` | `Field::getFieldTypeConfigData()`
| `Field::getSavedFieldConfig()` | `Field::getFormBuilderConfig()`
| `Field::getFieldDefaults()` | `Field::getFieldTypeDefaults()`.
| `Field::getAllFieldDefaults()` | Set default value on class property, or set in `init()`.
| `Field::getFieldValue()` | `$element->getFieldValue($field->handle)`
| `Field::getContentGqlMutationArgument()` | `Field::getContentGqlMutationArgumentType()`

### Method Signatures
To adhere to more strict typing introduced in PHP 8, we've modified some method signatures. Your custom field classes will need to be updated to reflect these changes.

```php
// Formie v2 vs Formie v3
public function getInputHtml($value, ElementInterface $element = null): string
protected function cpInputHtml(mixed $value, ?ElementInterface $element, bool $inline): string

// Formie v2 vs Formie v3
public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
public function normalizeValue(mixed $value, ElementInterface $element = null): mixed

// Formie v2 vs Formie v3
public function getValueForIntegration(mixed $value, $integrationField, $integration, ?ElementInterface $element = null, $fieldKey = ''): mixed
public function getValueForIntegration(mixed $value, IntegrationField $integrationField, IntegrationInterface $integration, ?ElementInterface $element = null, string $fieldKey = ''): mixed

// Formie v2 vs Formie v3
public function getValueForEmail(mixed $value, $notification, ?ElementInterface $element = null): mixed
public function getValueForEmail(mixed $value, Notification $notification, ?ElementInterface $element = null): mixed

// Formie v2 vs Formie v3
public function populateValue($value): void
public function populateValue(mixed $value): void

// Formie v2 vs Formie v3
public function setForm($value): void
public function setForm(?Form $value): void

// Formie v2 vs Formie v3
public function getDefaultValue($attributePrefix = '')
public function getDefaultValue(): mixed

// Formie v2 vs Formie v3
public function setNamespace($value): void
public function setNamespace(string|bool|null $value): void

// Formie v2 vs Formie v3
public function getConditionsJson($element = null): ?string
public function getConditionsJson(): ?string

// Formie v2 vs Formie v3
public function isConditionallyHidden(Submission $element): bool
public function isConditionallyHidden(Submission $submission): bool

// Formie v2 vs Formie v3
protected function setPrePopulatedValue($value)
protected function setPrePopulatedValue(mixed $value): mixed

// Formie v2 vs Formie v3
protected function defineValueAsString($value, ElementInterface $element = null): string
protected function defineValueAsString(mixed $value, ElementInterface $element = null): string

// Formie v2 vs Formie v3
protected function defineValueAsJson($value, ElementInterface $element = null): mixed
protected function defineValueAsJson(mixed $value, ElementInterface $element = null): mixed

// Formie v2 vs Formie v3
protected function defineValueForExport($value, ElementInterface $element = null): mixed
protected function defineValueForExport(mixed $value, ElementInterface $element = null): mixed

// Formie v2 vs Formie v3
protected function defineValueForIntegration($value, $integrationField, $integration, ElementInterface $element = null, $fieldKey = ''): mixed
protected function defineValueForIntegration(mixed $value, IntegrationField $integrationField, IntegrationInterface $integration, ElementInterface $element = null, string $fieldKey = ''): mixed

// Formie v2 vs Formie v3
protected function defineValueForSummary($value, ElementInterface $element = null): string
protected function defineValueForSummary(mixed $value, ElementInterface $element = null): string

// Formie v2 vs Formie v3
protected function defineValueForEmail($value, $notification, ElementInterface $element = null): string
protected function defineValueForEmail(mixed $value, Notification $notification, ElementInterface $element = null): string

// Formie v2 vs Formie v3
protected function getContentGqlMutationArgument(): Type|array|null
protected function getContentGqlMutationArgumentType(): Type|array
```

## Custom JavaScript Validation

:::tip
If you haven't created your own custom JavaScript validation handlers, you can skip this section.
:::

Client-side validation has been greatly improved, and now aligns more closely to server-side validation from Craft/Yii. Previously, we used [`bouncer.js`](https://github.com/cferdinandi/bouncer) to handle client-side validation, which while excellent wasn't really translating well to some opinionated Formie concepts, or being used to compliment Craft/Yii's server-side validation. 

In fact, we used `bouncer.js` as inspiration for our own validation library.

This breaking change affects how you register your custom validators.

```js
// Formie v2
import { t } from 'vendor/formie/frontend/utils/utils';

let $form = document.querySelector('#formie-form-1');

function customRule() {
    return {
        minLength(field) {
            const limit = field.getAttribute('data-limit');

            // Don't trigger this validation unless there's the `data-limit` attribute
            if (!limit) {
                return false;
            }

            return !(field.value.length > limit);
        },
    };
}

function customMessage() {
    return {
        minLength(field) {
            return t('The value entered must be at least {limit} characters long.', {
                limit: field.getAttribute('data-limit'),
            });
        },
    };
}

$form.addEventListener('registerFormieValidation', (e) => {
    e.preventDefault();

    // Add our custom validations logic and methods
    e.detail.validatorSettings.customValidations = {
        ...e.detail.validatorSettings.customValidations,
        ...this.customRule(),
    };

    // Add our custom messages
    e.detail.validatorSettings.messages = {
        ...e.detail.validatorSettings.messages,
        ...this.customMessage(),
    };

    // ...
});

// Formie v3
let $form = document.querySelector('#formie-form-1');

$form.addEventListener('onFormieThemeReady', (event) => {
    event.detail.addValidator('minLength', ({ input }) => {
        const limit = input.getAttribute('data-limit');

        // Don't trigger this validation unless there's the `data-limit` attribute
        if (!limit) {
            return true;
        }

        return input.value.length > limit;
    }, ({ label, input, t }) => {
        const limit = input.getAttribute('data-limit');

        return t('The value entered in {label} must be at least {limit} characters long.', { label, limit });
    });
});
```

Now, we use a single registration function `addValidator()` to register our custom validator and message. This is in contrast to separate registration functions for the validation logic and message. We also have access to more variables in the respective callbacks for the `field` (Formie's outer field wrapper) the `input` (the `<input>` or similar HTML element) and the `label` of the field.

We can also make use of Craft's translations far easier with the `t()` function available. You could also use string interpolation as well (i.e. `${label} is invalid.`) along with accessing other aspects of the DOM.

We also use the new `onFormieThemeReady` event to register these validations only if you're using Formie's Theme JS (where client-side validation is enabled).

### Return Value
The returned value is now the opposite of what it was. Previously, a `true` return was used if the validation were to be applied. This was somewhat confusing, as returning `true` from a cognitive point of view usually means a positive change. Returning `true` for something that meant a failure didn't seem to make sense. 

As such, validators should now return `true` for passing and `false` for failing. Essentially, describing whether the field value passed validation or not.

```js
// Formie v3
function customRule() {
    return {
        minLength(field) {
            // Don't trigger this validation unless there's the `data-limit` attribute
            if (!field.getAttribute('data-limit')) {
                return false;
            }

            return !(field.value.length > 5);
        },
    };
}

// Formie v3
function({ input }) {
    // Don't trigger this validation unless there's the `data-limit` attribute
    if (!input.getAttribute('data-limit')) {
        return true;
    }

    return input.value.length > 5;
}
```


## Repeater & Group Fields
Repeater and Group fields have had a major overhaul in Formie 3, most notably that their values are no longer elements. This is to simplify the field, provide a lower learning curve to dealing with them, and performance. They also don't use nested field layouts for the same goals. Instead, they are simple arrays.

This should only effect users who customise behaviour of these fields, or when working with the values of these fields. However, it should be much more simplified to do so.

```twig
// Formie v2
{# Fields would return a `verbb\formie\elements\db\NestedBlockQuery` object #}
{% set groupValue = myGroupField.one() %}
{{ groupValue.innerField1 }}
{{ groupValue.innerField2 }}

{# Fields would return a `verbb\formie\elements\db\NestedBlockQuery` object #}
{% set repeaterValue = myRepeaterField.all() %}

{% for row in repeaterValue %}
    {{ row.innerField1 }}
    {{ row.innerField2 }}
{% endfor %}

// Formie v3
{# Fields are now an array #}
{{ myGroupField.innerField1 }}
{{ myGroupField.innerField2 }}

{# Fields are now an array #}
{% for row in myRepeaterField %}
    {{ row.innerField1 }}
    {{ row.innerField2 }}
{% endfor %}
```

In addition, you can also now access field value directly from the submission, with dot-notation syntax.

```twig
{{ submission.getFieldValue('multiName.firstName') }}
{{ submission.getFieldValue('myGroupField.innerField1') }}
{{ submission.getFieldValue('myRepeaterField.0.innerField1') }}
{{ submission.getFieldValue('myRepeaterField.1.innerField1') }}
```

The latter example of a Repeater includes the zero-based index of the row you're fetching values for.


## Sub-Fields
Sub-Fields have also received a revamp in their implementation. We wanted to provide the means to manage more advanced settings for sub-fields, along with being able to re-order them.

Sub-Fields feature a new user interface, similar to a Group field. Sub-fields can be enabled/disabled, re-ordered and can have all their settings managed like regular fields. Sub-fields cannot be removed from their parent field, only disabled - which is the main difference between achieving a similar thing with a Group field.

Additionally, sub-fields are now stored in the `formie_fields` database table as fields within their own layout (again, the same as a Group or Repeater field).

We also use a new "type" for sub-fields under the `verbb\formie\fields\subfields` namespace. This allows us to extend existing fields to provide additional functionality specific to that fields' use-case. If you're creating your own sub-fields, this is completely optional, and any Formie field will be able to be used as a sub-field. For example, an Address Country sub-field extends a Dropdown field, but provides an easier way to manage the number of options to automatically generate based on a list of countries, rather than managing them yourself.

Current Sub-Fields are Name (multi-field enabled), Address, and Date (dropdown and input layouts). Phone fields were previously a sub-field, but are now just a regular field. 

Under the hood, a `verbb\formie\base\SubField` extends the `verbb\formie\base\SingleNestedField` class, which in turn extends `verbb\formie\base\NestedField`. The `SingleNestedField` is the same class that a Group field uses, as the two types of fields are almost identical.

All sub-fields are migrated over to this new implementation. If you have custom sub-field's, you'll need to review your implementation to follow the updated behaviour.

As field settings now exist in their respective sub-field classes and fields, we have removed many properties.

### Address Fields
- `autocompleteIntegration`
- `autocompleteEnabled`
- `autocompleteCollapsed`
- `autocompleteLabel`
- `autocompletePlaceholder`
- `autocompleteDefaultValue`
- `autocompletePrePopulate`
- `autocompleteRequired`
- `autocompleteErrorMessage`
- `autocompleteCurrentLocation`
- `address1Enabled`
- `address1Collapsed`
- `address1Label`
- `address1Placeholder`
- `address1DefaultValue`
- `address1PrePopulate`
- `address1Required`
- `address1ErrorMessage`
- `address1Hidden`
- `address2Enabled`
- `address2Collapsed`
- `address2Label`
- `address2Placeholder`
- `address2DefaultValue`
- `address2PrePopulate`
- `address2Required`
- `address2ErrorMessage`
- `address2Hidden`
- `address3Enabled`
- `address3Collapsed`
- `address3Label`
- `address3Placeholder`
- `address3DefaultValue`
- `address3PrePopulate`
- `address3Required`
- `address3ErrorMessage`
- `address3Hidden`
- `cityEnabled`
- `cityCollapsed`
- `cityLabel`
- `cityPlaceholder`
- `cityDefaultValue`
- `cityPrePopulate`
- `cityRequired`
- `cityErrorMessage`
- `cityHidden`
- `stateEnabled`
- `stateCollapsed`
- `stateLabel`
- `statePlaceholder`
- `stateDefaultValue`
- `statePrePopulate`
- `stateRequired`
- `stateErrorMessage`
- `stateHidden`
- `zipEnabled`
- `zipCollapsed`
- `zipLabel`
- `zipPlaceholder`
- `zipDefaultValue`
- `zipPrePopulate`
- `zipRequired`
- `zipErrorMessage`
- `zipHidden`
- `countryEnabled`
- `countryCollapsed`
- `countryLabel`
- `countryPlaceholder`
- `countryDefaultValue`
- `countryPrePopulate`
- `countryRequired`
- `countryErrorMessage`
- `countryHidden`
- `countryOptionLabel`
- `countryOptionValue`

### Date Fields
- `dayLabel`
- `dayPlaceholder`
- `monthLabel`
- `monthPlaceholder`
- `yearLabel`
- `yearPlaceholder`
- `hourLabel`
- `hourPlaceholder`
- `minuteLabel`
- `minutePlaceholder`
- `secondLabel`
- `secondPlaceholder`
- `ampmLabel`
- `ampmPlaceholder`
- `minYearRange`
- `maxYearRange`
- `timeLabel`
- `includeDate`
- `includeTime`

### Name Fields
- `prefixEnabled`
- `prefixCollapsed`
- `prefixEnabled`
- `prefixCollapsed`
- `prefixLabel`
- `prefixPlaceholder`
- `prefixDefaultValue`
- `prefixPrePopulate`
- `prefixRequired`
- `prefixErrorMessage`
- `firstNameEnabled`
- `firstNameCollapsed`
- `firstNameLabel`
- `firstNamePlaceholder`
- `firstNameDefaultValue`
- `firstNamePrePopulate`
- `firstNameRequired`
- `firstNameErrorMessage`
- `middleNameEnabled`
- `middleNameCollapsed`
- `middleNameLabel`
- `middleNamePlaceholder`
- `middleNameDefaultValue`
- `middleNamePrePopulate`
- `middleNameRequired`
- `middleNameErrorMessage`
- `lastNameEnabled`
- `lastNameCollapsed`
- `lastNameLabel`
- `lastNamePlaceholder`
- `lastNameDefaultValue`
- `lastNamePrePopulate`
- `lastNameRequired`
- `lastNameErrorMessage`

### Phone Fields
- `countryCollapsed`
- `countryShowDialCode`

### Example
If you wish to access the sub-field settings, you'll need to access the sub-field first. For example, if you were in the context of a field class:

```php
// Formie v2
$firstNameEnabled = $this->firstNameEnabled;

if ($firstNameEnabled) {
    $label = $this->firstNameLabel;
}

// Formie v3
$firstName = $this->getFieldByHandle('firstName');

if ($firstName && $firstName->enabled) {
    $label = $firstName->label;
}
```

## Element Queries

### Submission Queries
We have removed the `relatedTo` query parameter, which you might have used for checking against element field content. For example, you might have an Entries field in your form, and you want to find all Submissions that are `relatedTo` a specific entry.

While you can still do this in Formie 3, you should query the specific field's content.

```twig
{% set relatedEntry = craft.entries.id(123).one() %}


// Formie v2
{% set submissions = craft.formie.submissions().form('contactForm').relatedTo(relatedEntry).all() %}


// Formie v3
{% set submissions = craft.formie.submissions().form('contactForm').entriesFieldHandle(relatedEntry).all() %}
```

## Content Tables
Inline with Craft's own improvement of removing the content database table and the about Field/Field Layout changes, Formie now no longer stores submission content in a `fmc_*` database table. Instead, it's a single `content` JSON column in your `formie_submissions` database table. This not only has performance benefits, but makes it easy to view your content directly from the database.

Importantly, this change also means that you can now have **unlimited** fields in a form, so if you've been hanging out to make the biggest web form in history - your time is now!

This won't mean anything for day-to-day use of Formie, but is useful to know if you're familiar with looking at the raw content of a submission.

## Theme Config
The `fieldInputContainer` key for Theme Config has been renamed to `fieldInputWrapper` to follow clear consistency with the terms "wrapper" and "container".

In addition, the `.fui-input-container` class now no longer exists, replaced with `.fui-input-wrapper`, so any CSS overrides you have will need to be updated.

## Integrations

:::tip
If you haven't created your own custom integration class for Formie, you can skip this section.
:::

### OAuth & Auth Module
To simplify and streamline integration logic for OAuth-based providers, we're using the [Auth Module](https://verbb.io/packages/auth) to handle the authentication flow and token handling. For non-OAuth-based providers, there will be no change.

While things should be seamlessly migrated from Formie to Auth, if you have custom integrations that use OAuth, you'll need to integrate those with the Auth module. Formie no longer contains many of the helper methods that an older OAuth integration would've relied on.

In addition, if you were using any of the OAuth-specific functions in Formie, those are now removed.

The following classes have been removed
- `verbb\formie\events\OauthTokenEvent`
- `verbb\formie\events\TokenEvent`
- `verbb\formie\models\Token`
- `verbb\formie\records\Token`
- `verbb\formie\services\Tokens`

The `formie_tokens` database table has been removed and items migrated to the `auth_oauth_tokens` database table.

For the `Integration` class, the following applies:

- `Integration::tokenId` property removed
- `Integration::getAuthorizeUrl()` method removed
- `Integration::getAccessTokenUrl()` method removed
- `Integration::getResourceOwner()` method removed
- `Integration::getOauthScope()` method removed
- `Integration::getOauthAuthorizationOptions()` method removed
- `Integration::oauthVersion()` method removed
- `Integration::oauth2Legged()` method removed
- `Integration::oauthConnect()` method removed
- `Integration::getOauthProviderConfig()` method removed
- `Integration::getOauthProvider()` method removed
- `Integration::beforeFetchAccessToken()` method removed
- `Integration::afterFetchAccessToken()` method removed

Custom Integration classes should now implement a `getOAuthProviderClass()` method which is a [Auth Provider](https://verbb.io/packages/auth/docs/feature-tour/providers).

Refer also to our [OAuth Integrations](https://github.com/verbb/formie/tree/craft-5/src/integrations/crm) for some existing implementations. We also have an [example](https://github.com/verbb/example-formie-oauth-integration) integration to get started with.

## GraphQL
When querying fields or pages, you should use the `label` property instead of `name`.

```graphql
// Formie v2
{
    formieForm (handle: "contactForm") {
        title
        
        pages {
            name

            rows {
                rowFields {
                    name
                }
            }
        }
    }
}

// Formie v3
{
    formieForm (handle: "contactForm") {
        title
        
        pages {
            label

            rows {
                rowFields {
                    label
                }
            }
        }
    }
}
```

### Sub-Fields
Previously, all Sub-Field label, enabled state and more were stored as properties on the outer Sub-Field. These are now nested as individual `FieldInterface` items.

```gql
// Formie v2
{
    formieForm (handle: "contactForm") {
        title
        
        formFields {
            label

            prefixLabel
            firstNameLabel
            middleNameLabel
            lastNameLabel
        }
    }
}

// Formie v3
{
    formieForm (handle: "contactForm") {
        title
        
        formFields {
            label

            ... on Field_Name {
                fields {
                    label
                }
            }
        }
    }
}
```

## Events
The `EVENT_MODIFY_FIELD_CONFIG` event had been moved to be on the individual field class, not the `verbb\formie\services\Fields` service.

```php
// Formie v2
use verbb\formie\events\ModifyFieldConfigEvent;
use verbb\formie\services\Fields;
use yii\base\Event;

Event::on(Fields::class, Fields::EVENT_MODIFY_FIELD_CONFIG, function(ModifyFieldConfigEvent $event) {
    $config = $event->config;
    // ...
});

// Formie v3
use verbb\formie\events\ModifyFieldConfigEvent;
use verbb\formie\fields\formfields\SingleLineText;
use yii\base\Event;

Event::on(SingleLineText::class, SingleLineText::EVENT_MODIFY_FIELD_CONFIG, function(ModifyFieldConfigEvent $event) {
    $config = $event->config;
    // ...
});
```

### Address `modifyFrontEndSubfields` event
The `EVENT_MODIFY_FRONT_END_SUBFIELDS` event had been renamed to `EVENT_MODIFY_NESTED_FIELD_LAYOUT` and differs slightly. Instead of modifying the config of a field as an array, before it's created as a field, you can now modify the created field in the context of a field layout.

```php
// Formie v2
use craft\helpers\ArrayHelper;
use verbb\formie\events\ModifyFrontEndSubfieldsEvent;
use verbb\formie\fields\formfields\Address;
use yii\base\Event;

Event::on(Address::class, Address::EVENT_MODIFY_FRONT_END_SUBFIELDS, function(ModifyFrontEndSubfieldsEvent $event) {
    $address1 = $event->rows[0][0];

    // Modify the `address1` field - a `SingleLineText` field.
    $address1->name = 'Address Line 1';

    $event->rows[0][0] = $address1;

    // Change the order of fields - remove them first
    $address2 = ArrayHelper::remove($event->rows[1], 0);
    $address3 = ArrayHelper::remove($event->rows[2], 0);

    // Add them to the first row
    $event->rows[0][] = $address2;
    $event->rows[0][] = $address3;
});

// Formie v3
use verbb\formie\events\ModifyNestedFieldLayoutEvent;
use verbb\formie\fields\Address;
use yii\base\Event;

Event::on(Address::class, Address::EVENT_MODIFY_NESTED_FIELD_LAYOUT, function(ModifyNestedFieldLayoutEvent $event) {
    // Lookup the last name sub-field. We can no longer rely on a static order
    $address1 = $event->fieldLayout->getFieldByHandle('address1');

    // Modify the `address1` field - a `SingleLineText` field.
    $address1->label = 'Address Line 1';
});
```


### Date `modifyFrontEndSubfields` event
The `EVENT_MODIFY_FRONT_END_SUBFIELDS` event had been renamed to `EVENT_MODIFY_NESTED_FIELD_LAYOUT` and differs slightly. Instead of modifying the config of a field as an array, before it's created as a field, you can now modify the created field in the context of a field layout.

```php
// Formie v2
use verbb\formie\events\ModifyFrontEndSubfieldsEvent;
use verbb\formie\fields\formfields\Date;
use yii\base\Event;
use DateTime;

Event::on(Date::class, Date::EVENT_MODIFY_FRONT_END_SUBFIELDS, function(ModifyFrontEndSubfieldsEvent $event) {
    $field = $event->field;

    $defaultValue = $field->defaultValue ? $field->defaultValue : new DateTime();
    $year = intval($defaultValue->format('Y'));
    $minYear = $year - 10;
    $maxYear = $year + 10;

    $yearOptions = [];

    for ($y = $minYear; $y < $maxYear; ++$y) {
        $yearOptions[] = ['value' => $y, 'label' => $y];
    }

    $event->rows[0]['Y'] = [
        'handle' => 'year',
        'options' => $yearOptions,
        'min' => $minYear,
        'max' => $maxYear,
    ];
});

// Formie v3
use verbb\formie\events\ModifyNestedFieldLayoutEvent;
use verbb\formie\fields\Date;
use yii\base\Event;

Event::on(Date::class, Date::EVENT_MODIFY_NESTED_FIELD_LAYOUT, function(ModifyNestedFieldLayoutEvent $event) {
    // Lookup the last name sub-field. We can no longer rely on a static order
    $year = ArrayHelper::firstWhere($event->fields, 'handle', 'year');

    $yearOptions = [];
    $minYear = 2000;
    $maxYear = 2050;

    for ($y = $minYear; $y < $maxYear; ++$y) {
        $yearOptions[] = ['value' => $y, 'label' => $y];
    }

    // Modify the `year` field - a `SingleLineText` field.
    $year->options = $yearOptions;
    $year->min = $minYear;
    $year->max = $maxYear;
});
```

### Name `modifyFrontEndSubfields` event
The `EVENT_MODIFY_FRONT_END_SUBFIELDS` event had been renamed to `EVENT_MODIFY_NESTED_FIELD_LAYOUT` and differs slightly. Instead of modifying the config of a field as an array, before it's created as a field, you can now modify the created field in the context of a field layout.

```php
// Formie v2
use verbb\formie\events\ModifyFrontEndSubfieldsEvent;
use verbb\formie\fields\formfields\Name;
use yii\base\Event;

Event::on(Name::class, Name::EVENT_MODIFY_FRONT_END_SUBFIELDS, function(ModifyFrontEndSubfieldsEvent $event) {
    $lastName = $event->rows[0][3];

    // Modify the `lastName` field - a `SingleLineText` field.
    $lastName->name = 'Surname';

    $event->rows[0][3] = $lastName;

    // Change the order of fields - remove them first
    $firstName = ArrayHelper::remove($event->rows[0], 1);
    $lastName = ArrayHelper::remove($event->rows[0], 3);

    // Reverse the order
    $event->rows[0][1] = $lastName;
    $event->rows[0]3 = $firstName;
});

// Formie v3
use verbb\formie\events\ModifyNestedFieldLayoutEvent;
use verbb\formie\fields\Name;
use yii\base\Event;

Event::on(Name::class, Name::EVENT_MODIFY_NESTED_FIELD_LAYOUT, function(ModifyNestedFieldLayoutEvent $event) {
    // Lookup the last name sub-field. We can no longer rely on a static order
    $lastName = $event->fieldLayout->getFieldByHandle('lastName');

    // Modify the `lastName` field - a `SingleLineText` field.
    $lastName->label = 'Surname';
});
```

### Name `modifyPrefixOptions` event
The `EVENT_MODIFY_PREFIX_OPTIONS` has been moved to the inner Prefix sub-field class.

```php
// Formie v2
use verbb\formie\events\ModifyNamePrefixOptionsEvent;
use verbb\formie\fields\Name;
use yii\base\Event;

Event::on(Name::class, Name::EVENT_MODIFY_PREFIX_OPTIONS, function(ModifyNamePrefixOptionsEvent $event) {
    $event->options[] = ['label' => Craft::t('formie', 'Mx.'), 'value' => 'mx'];
});

// Formie v3
use verbb\formie\events\ModifyNamePrefixOptionsEvent;
use verbb\formie\fields\subfields\NamePrefix;
use yii\base\Event;

Event::on(NamePrefix::class, NamePrefix::EVENT_MODIFY_PREFIX_OPTIONS, function(ModifyNamePrefixOptionsEvent $event) {
    $event->options[] = ['label' => Craft::t('formie', 'Mx.'), 'value' => 'mx'];
});
```
