# The complete guide to rendering submission content

Formie stores a submission once, then returns that content in different shapes depending on what you are doing with it. The same Address field might be a plain string in a log, a structured array in a template, multiple CSV columns in an export, or HTML in a summary screen.

This guide focuses on **querying submissions**, **field-type recipes**, and **common mistakes**. For every read method, Twig/PHP examples, and context-specific helpers, see [Submission Content](/developers/submission-content).

## Prerequisites

- [Submission Content](/developers/submission-content) reference
- [Submission Queries](/getting-elements/submission-queries)
- Basic Twig or PHP templating

## Fetch a submission

Submissions are Craft elements. Query them in Twig or PHP:

::: code
```twig [Twig]
{% set submission = craft.formie.submissions()
    .form('contactForm')
    .id(123)
    .one() %}
```

```php [PHP]
$submission = \verbb\formie\elements\Submission::find()
    ->form('contactForm')
    ->id(123)
    ->one();
```
:::

Common query patterns:

```twig
{# By UID (preferred for public-facing pages) #}
{% set submission = craft.formie.submissions()
    .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    .one() %}

{# Recent submissions for a form #}
{% set submissions = craft.formie.submissions()
    .form('contactForm')
    .limit(10)
    .all() %}

{# Filter by field value #}
{% set submissions = craft.formie.submissions()
    .form('contactForm')
    .emailAddress('jane@example.com')
    .all() %}
```

By default, queries exclude incomplete and spam submissions. Use `.anyStatus()` when you need those too. See [Submission Queries](/getting-elements/submission-queries) for the full parameter list.

## Choose the right read method

Pick the helper that matches your output context — full examples live on [Submission Content](/developers/submission-content#reading-submission-values):

| Method | Use when |
| --- | --- |
| `getFieldValue()` | Default value; fine for simple fields |
| `getFieldValueAsString()` | Plain text — logs, labels, simple output |
| `getFieldValueAsArray()` | Structured data — Address, Name, nested fields |
| `getFieldValueForSummary()` | Review screens, confirmation HTML |
| `getFieldValueForExport()` | CSV, spreadsheets, reports |
| `getFieldValueForReference()` | Singular string for notifications/integrations |
| `getFieldValueForReferenceBlock()` | Rich block HTML for notification field rendering |

Submission-wide equivalents: `getValuesAsString()`, `getValuesAsArray()`, `getValuesForSummary()`, `getValuesForExport()`.

Use dot notation for nested paths (`billingAddress.postalCode`, `attendees.0.email`) — see [Work with nested field paths](/developers/submission-content#work-with-nested-field-paths).

## Loop all fields

When you do not know handles ahead of time, iterate the submission's fields:

```twig
{% for field in submission.getFields() %}
    {% set value = submission.getFieldValueAsString(field.handle) %}

    {% if value %}
        <p><strong>{{ field.name }}:</strong> {{ value }}</p>
    {% endif %}
{% endfor %}
```

Skip cosmetic fields (Headings, Sections, HTML blocks) if you only want user-entered data — filter by field type or check whether the field has a value.

For HTML-aware output per field, use summary or reference-block helpers — see [Get summary values](/developers/submission-content#get-summary-values).

## Field-type patterns

### Address and Name

Prefer `getFieldValueAsArray()` or dot notation. String output collapses to a single line:

```twig
{# Single line #}
{{ submission.getFieldValueAsString('billingAddress') }}

{# Parts #}
{% set address = submission.getFieldValueAsArray('billingAddress') %}
{{ address.address1 }}, {{ address.locality }}
```

### Repeater, Group, and Table

These return structured arrays. Loop rows explicitly:

```twig
{% set attendees = submission.getFieldValueAsArray('attendees') %}

{% for row in attendees %}
    <p>{{ row.firstName }} — {{ row.email }}</p>
{% endfor %}
```

### File Upload

Summary and reference-block output include download links. String output is usually the filename:

```twig
{{ submission.getFieldValueForSummary('resume')|raw }}
```

### Element fields (Entries, Categories, Users)

String output is typically titles or labels. Array output may include element IDs and metadata depending on the field:

```twig
{% set entries = submission.getFieldValue('relatedArticles') %}
```

For relation-heavy templates, query the elements separately using IDs from the submission value.

### Payment

Check payment records on the submission:

```twig
{% for payment in submission.getPayments() %}
    <p>{{ payment.amount }} — {{ payment.status }}</p>
{% endfor %}
```

## Notifications and custom module code

Email notifications and integrations resolve reference tokens at send time. In PHP, use `getFieldValueForReference()` and `getFieldValueForReferenceBlock()` — see [Use context-specific helpers](/developers/submission-content#use-context-specific-helpers).

## Editing submissions on the front end

When rendering a form pre-filled from an existing submission, Formie handles value population through the normal render flow. See [Editing submissions on the front end](/guides/submissions-workflows/editing-submissions-on-the-front-end) for the full pattern.

Read methods are the same — you are still working with a `Submission` element.

## Common mistakes

**Using `getFieldValue()` for display of complex fields.** A Repeater or Address may return an object or array you cannot print directly. Reach for `getFieldValueAsString()` or `getFieldValueForSummary()`.

**Using summary HTML in exports or integrations.** Summary output includes markup. Exports want `getFieldValueForExport()`.

**Guessing submission IDs on public pages.** Use UID in URLs. See [Build a success page](/guides/templating-theming/build-a-success-page-for-your-form).

**Forgetting query status filters.** Incomplete save-and-continue submissions are excluded by default. Call `.anyStatus()` or `.isIncomplete(true)` when you need them.

**Mixing Twig field handles with notification tokens.** Notification settings use reference tokens like `{field:a1b2c3}`, not `{field:myHandle}`. See [Reference tokens](/developers/reference-tokens).

## Quick reference

```twig
{# Simple text #}
{{ submission.getFieldValueAsString('message') }}

{# Structured #}
{% set name = submission.getFieldValueAsArray('fullName') %}

{# Nested path #}
{{ submission.getFieldValue('billingAddress.postalCode') }}

{# Display HTML #}
{{ submission.getFieldValueForSummary('signature')|raw }}

{# All fields as summary items #}
{% set items = submission.getValuesForSummary() %}

{# Shorthand property access (simple fields) #}
{{ submission.firstName }}
```

## Related

- [Submission Content](/developers/submission-content)
- [Submission Queries](/getting-elements/submission-queries)
- [Editing submissions on the front end](/guides/submissions-workflows/editing-submissions-on-the-front-end)
- [Build a success page for your form](/guides/templating-theming/build-a-success-page-for-your-form)
- [Reference tokens](/developers/reference-tokens)
