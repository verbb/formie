# Upgrading from v2
While the [changelog](https://github.com/verbb/formie/blob/craft-5/CHANGELOG.md) is the most comprehensive list of changes, this guide provides high-level overview and organizes changes by category.

## Field and Field Layouts
Fields and Field Layouts have been completely revamped in Formie 3. Previously, Formie extended the base [Field](https://github.com/craftcms/cms/blob/5.0/src/base/Field.php) class from Craft for all fields, including Element fields like Assets and Entries. While convenient to make use of Craft's implementation for field functionality, it's proved more of a hindrance with limiting what Formie fields can do, and causing issues when Craft update their own field classes.

Similarly, Craft has the concept of a Field Layout, which is attached to an element (in our case, a Submission) and defines what content an element has. But these are heavily opinionated and tied to control-panel behaviour, and Formie forms are designed to be used on the front-end. They're also more complex with Pages/Rows/Fields, as opposed to Tabs/Fields in Craft.

Finally, there's performance. Formie fields were stored alongside Craft's own fields, and made use of Craft's field layouts. Having potentially hundreds of fields stored alongside Craft fields meant that Craft would initialise these fields on every request, when they don't need to. Alongside content table changes from Craft itself, this meant big changes were in order.

So — Formie now stores all Fields, Rows, Pages and Field Layouts in its own database tables, with it's own logic. Submissions now also make use of our own Field Layout.

We've strived to make things backward compatible, and deprecated existing behaviour to ease the migration. This should only affect you if:

- You have created a custom field class
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
| `verbb\formie\base\FormFieldTrait` | Removed
| `verbb\formie\base\NestedFieldTrait` | Removed
| `verbb\formie\base\RelationFieldTrait` | Removed
| `verbb\formie\base\SubfieldInterface` | `verbb\formie\base\SubFieldInterface`
| `verbb\formie\base\SubfieldTrait` | Removed
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
| `Field::getFieldDefaults()` | `Field::getFieldTypeConfigDefaults()`
| `Field::getAllFieldDefaults()` | `Field::getFieldTypeConfigDefaults()`
| `Field::getFieldValue()` | `$element->getFieldValue($field->handle)`

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
public function getDefaultValue(string $attributePrefix = ''): mixed

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

## Content Tables
Inline with Craft's own improvement of removing the content database table and the about Field/Field Layout changes, Formie now no longer stores submission content in a `fmc_*` database table. Instead, it's a single `content` JSON column in your `formie_submissions` database table. This not only has performance benefits, but makes it easy to view your content directly from the database.

Importantly, this change also means that you can now have **unlimited** fields in a form, so if you've been hanging out to make the biggest web form in history - your time is now!

This won't mean anything for day-to-day use of Formie, but is useful to know if you're familiar with looking at the raw content of a submission

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

Refer also to our [OAuth Integrations](https://github.com/verbb/formie/tree/craft-5/src/integrations/crm) for some example implementations.

## Fields
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
