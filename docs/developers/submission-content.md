# Submission Content

Formie stores a submission once, then returns that content in different shapes depending on what you are doing with it.

That is why the same field can look different in a template, an export, a summary screen, an email, or an integration payload. A simple text field might come back as a plain string in one place, while an address, name, or repeater field may need a structured array in another.

When a value does not look the way you expect, the first question to ask is not "is this field wrong?" but "am I reading it in the right format for this job?"

## Reading Submission Values

The `Submission` element gives you helper methods for the most common formats.

### Get the normal field value

Use `getFieldValue()` when you want the field's normal value without forcing a particular format.

::: code-group
```php [PHP]
$submission = \verbb\formie\elements\Submission::find()
    ->id(123)
    ->one();

$firstName = $submission->getFieldValue('firstName');
$billingAddress = $submission->getFieldValue('billingAddress');

// Shorthand syntax is also available for direct field access.
$firstName = $submission->firstName;
```

```twig [Twig]
{% set firstName = submission.getFieldValue('firstName') %}
{% set billingAddress = submission.getFieldValue('billingAddress') %}

{# Shorthand syntax is also available for direct field access. #}
{% set firstName = submission.firstName %}
```
:::

For simple fields, this is often enough. For more complex fields such as Address, Name, Group, Repeater, or Table, you will usually want one of the more explicit methods below.

### Get a string value

Use `getFieldValueAsString()` when you need plain text. This is useful for logs, simple output, spam tools, or anywhere you need a field collapsed into a readable string.

::: code-group
```php [PHP]
use verbb\formie\models\ValueContext;

$fullName = $submission->getFieldValueAsString('fullName');
$message = $submission->getFieldValueAsString('message');

// This is the lower-level equivalent if you need to pass a context explicitly.
$message = $submission->getFieldValue('message', ValueContext::string());
```

```twig [Twig]
{{ submission.getFieldValueAsString('fullName') }}
{{ submission.getFieldValueAsString('message') }}
```
:::

Formie also provides a submission-wide string projection when you need all non-cosmetic values in one pass.

::: code-group
```php [PHP]
$values = $submission->getValuesAsString();
```

```twig [Twig]
{% set values = submission.getValuesAsString() %}
```
:::

### Get an array value

Use `getFieldValueAsArray()` when a field has meaningful structure that you want to preserve. This is common for Address, Name, and more complex custom or nested fields.

::: code-group
```php [PHP]
use verbb\formie\models\ValueContext;

$name = $submission->getFieldValueAsArray('fullName');
$address = $submission->getFieldValueAsArray('billingAddress');

// This is the lower-level equivalent if you need to pass a context explicitly.
$address = $submission->getFieldValue('billingAddress', ValueContext::array());
```

```twig [Twig]
{% set name = submission.getFieldValueAsArray('fullName') %}
{% set address = submission.getFieldValueAsArray('billingAddress') %}

{{ address.address1 }}
{{ address.locality }}
{{ address.postalCode }}
```
:::

You can do the same for the whole submission.

::: code-group
```php [PHP]
$values = $submission->getValuesAsArray();
```

```twig [Twig]
{% set values = submission.getValuesAsArray() %}
```
:::

This is usually the safest format when you are building your own payloads or working with field data that is more than a single text value.

### Get export values

Use `getFieldValueForExport()` or `getValuesForExport()` when the result is going into a CSV, spreadsheet, or report.

::: code-group
```php [PHP]
use verbb\formie\models\ValueContext;

$paymentTotal = $submission->getFieldValueForExport('payment');
$exportValues = $submission->getValuesForExport();

// This is the lower-level equivalent if you need to pass a context explicitly.
$paymentTotal = $submission->getFieldValue('payment', ValueContext::export());
```

```twig [Twig]
{% set paymentTotal = submission.getFieldValueForExport('payment') %}
{% set exportValues = submission.getValuesForExport() %}
```
:::

Export output can flatten complex fields into multiple columns. For example, an Address field may become several export columns instead of one nested array.

### Get summary values

Use `getFieldValueForSummary()` or `getValuesForSummary()` when you are building a review screen, confirmation step, or summary output.

::: code-group
```php [PHP]
use verbb\formie\models\ValueContext;

$summaryValue = $submission->getFieldValueForSummary('billingAddress');
$summaryItems = $submission->getValuesForSummary();

// This is the lower-level equivalent if you need to pass a context explicitly.
$summaryValue = $submission->getFieldValue('billingAddress', ValueContext::summary());

foreach ($summaryItems as $item) {
    $field = $item['field'];
    $html = $item['html'];
    $text = $item['text'];
}
```

```twig [Twig]
{% set summaryValue = submission.getFieldValueForSummary('billingAddress') %}
{% set summaryItems = submission.getValuesForSummary() %}

{% for item in summaryItems %}
    <h3>{{ item.field.name }}</h3>

    {% if item.html %}
        {{ item.html }}
    {% else %}
        {{ item.text }}
    {% endif %}
{% endfor %}
```
:::

Summary values are designed for display, not for integrations or exports.

### Work with nested field paths

You can access nested values with dot notation. This is useful for Group fields, Address sub-values, and repeater rows.

::: code-group
```php [PHP]
$street = $submission->getFieldValue('billingAddress.address1');
$city = $submission->getFieldValue('billingAddress.locality');
$firstRepeaterEmail = $submission->getFieldValue('attendees.0.email');
```

```twig [Twig]
{{ submission.getFieldValue('billingAddress.address1') }}
{{ submission.getFieldValue('billingAddress.locality') }}
{{ submission.getFieldValue('attendees.0.email') }}
```
:::

This is often the simplest way to pull out one specific nested value without working with the whole structured array.

### Use context-specific helpers

Formie also includes helpers for values that are being prepared for a specific system or job:

- `getFieldValueForReference()`
- `getFieldValueForReferenceBlock()`
- `getFieldValueForIntegration()`
- `getFieldValueForCondition()`

These are most useful in custom module or plugin code, where you already have the email notification or integration objects available.

::: code-group
```php [PHP]
use verbb\formie\models\ValueContext;

$referenceValue = $submission->getFieldValueForReference('billingAddress', $notification);
$referenceBlockValue = $submission->getFieldValueForReferenceBlock('billingAddress', $notification);
$conditionValue = $submission->getFieldValueForCondition('subscribe');
$integrationValue = $submission->getFieldValueForIntegration('billingAddress', $integrationField, $integration, 'address');

// These are the lower-level equivalents if you need to pass a context explicitly.
$referenceValue = $submission->getFieldValue('billingAddress', ValueContext::reference($notification));
$referenceBlockValue = $submission->getFieldValue('billingAddress', ValueContext::referenceBlock($notification));
$conditionValue = $submission->getFieldValue('subscribe', ValueContext::condition());
$integrationValue = $submission->getFieldValue('billingAddress', ValueContext::integration($integrationField, $integration, 'address'));
```

```twig [Twig]
{% set referenceValue = submission.getFieldValueForReference('billingAddress', notification) %}
{% set referenceBlockValue = submission.getFieldValueForReferenceBlock('billingAddress', notification) %}
{% set conditionValue = submission.getFieldValueForCondition('subscribe') %}
{% set integrationValue = submission.getFieldValueForIntegration('billingAddress', integrationField, integration, 'address') %}
```
:::

Use **reference** when you need the singular, string-like field value. Use **reference block** when you need the richer block value used by notification field rendering. Deprecated aliases such as `getFieldValueForEmail()` and `ValueContext::email()` still work while you upgrade, but new code should prefer the reference / reference-block names.

If you need full control, `getFieldValue()` also accepts an explicit context object, but the convenience methods above are usually clearer.
