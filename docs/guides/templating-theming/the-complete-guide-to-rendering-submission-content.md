# The complete guide to rendering submission content (Formie 3)

Once you have fetched a Submission element, you can iterate through it's Pages, Rows or Fields.

```twig
{# Find a submission #}
{% set submission = craft.formie.submissions.one() %}

{# Loop through each page, then row, then field #}
{% for page in submission.getPages() %}
    {% for row in page.getRows() %}
        {% for field in row.getFields() %}
            {{ field.label }}
        {% endfor %}
    {% endfor %}
{% endfor %}

{# Loop through each row, then field #}
{% for row in submission.getRows() %}
    {% for field in row.getFields() %}
        {{ field.label }}
    {% endfor %}
{% endfor %}

{# Loop through each field #}
{% for field in submission.getFields() %}
    {{ field.label }}
{% endfor %}
```

How you decide to loop through your field layout is up to you! There may be instances you want to group field content by page, row or both, for example.

## Getting field values
Now that we've got a handle on getting the fields defined in the field layout for a submission, we'll want to get the content for the field. This is a little challenging, as not every field is created equally!

Take, for example, a Single-Line Text field and a Checkboxes field. The value of a Single-Line Text field might be something like `I'm interested in your product`, which is simple text. Checkboxes, however, have multiple values that users can pick, and their value is stored as an array `['Option A', 'Option C']`.

Before we get too ahead of ourselves, let's start with getting the raw value from the submission from a field.

```twig
{% for field in submission.getFields() %}
    {% set value = submission.getFieldValue(field.handle) %}
{% endfor %}
```

The value of `value` will vary widely depending on the type of field that it is. It could be a string, a number, an array or even an object. 

Now that we have our value, we've got options on how to display it. Fortunately, we have a few helpers to help (😏) you out.

### As a string
The most common approach is to show the value as a string. Even if the value for the field is something more complex, like an array or object, Formie will convert it to a string. If the value was an array, it would be comma-delimited, or if an object cast as a string.

```twig
{% set value = submission.getFieldValue(field.handle) %}

{{ field.getValueAsString(value, submission) }}
```

Easy! But we might also like to cater for some of the advanced fields like sub-field enabled (Address, multi-Name), and nested-field (Repeater, Group). Maybe you want to treat the sub/nested fields differently, rather than comma-delimit them?

```twig
{# For any sub-field fields, loop through all the sub-fields and show their individual values #}
{% if field.hasSubfields() %}
    {% for subField in field.getSubfieldOptions() %}
        <p>
            <strong>{{ field.name }}: {{ subField.label }}</strong><br>

            {{ field.getValueAsString(value[subField.handle], submission) }}
        </p><br>
    {% endfor %}
{% endif %}

{# For any nesting fields, loop through all the nested fields and show their individual values #}
{# Don't forget to handle nested, sub-fields — oof! #}
{% if field.hasNestedFields() %}
    {% for row in value.all() %}
        {% set rowIndex = loop.index %}

        {% for nestedField in row.getFields() %}
            {% set subValue = row.getFieldValue(nestedField.handle) %}

            <p>
                <strong>{{ field.name }}: Row {{ rowIndex }}: {{ nestedField.name }}</strong><br>

                {{ nestedField.getValueAsString(subValue, row) }}
            </p><br>

            {% if nestedField.hasSubfields() %}
                {% for nestedSubField in nestedField.getSubfieldOptions() %}
                    <p>
                        <strong>{{ field.name }}: Row {{ rowIndex }}: {{ nestedField.name }}: {{ nestedSubField.label }}</strong><br>
                        {{ nestedField.getValueAsString(subValue[nestedSubField.handle], row) }}
                    </p><br>
                {% endfor %}
            {% endif %}
        {% endfor %}
    {% endfor %}
{% endif %}
```

The above is a bit lengthy, but purely optional depending on your use case. 

Lastly, we'll also want to check if the field is "cosmetic" — as in a field that doesn't have a value, and is purely for display, like a HTML, Section or similar field.

To finish up the basics of displaying content as strings, here's it all put together (in its basic form).

```twig
{# Get the submission #}
{% set submission = craft.formie.submissions.id(1234).one() %}

{# Loop through each field in the field layout #}
{% for field in submission.getFields() %}
    {% if not field.getIsCosmetic() %}
        {# Get the raw value for the field, from the submission #}
        {% set value = submission.getFieldValue(field.handle) %}

        <strong>{{ field.name }}</strong><br>
        {{ field.getValueAsString(value, submission) }}
    {% endif %}
{% endfor %}
```

### As a JSON array
Another approach is to fetch values as JSON, which will return an array or object if the value for a field is complex. Any primitive values like a string, number, etc will not be converted into an object.

To illustrate, let's look at some examples for different field types.

```twig
{# Get the submission #}
{% set submission = craft.formie.submissions.id(1234).one() %}

{# Loop through each field in the field layout #}
{% for field in submission.getFields() %}
    {% if not field.getIsCosmetic() %}
        {# Get the raw value for the field, from the submission #}
        {% set value = submission.getFieldValue(field.handle) %}

        <strong>{{ field.name }}</strong><br>
        {{ field.getValueAsJson(value, submission) | json_encode }}
    {% endif %}
{% endfor %}

Single-Line Text: "Some text"

Checkboxes: [{"label": "Option 2 Label", "value": "Option 2 Value"}, {"label": "Option 3 Label", "value": "Option 3 Value"}]

Multi Name: {"firstName": "Peter", "lastName": "Sherman", "isMultiple": true}

Phone Number: {"number": "673476578", "country": null, "hasCountryCode": false}
```

### For Summary field
Another method you can employ is to get the value of the field, as used by the Summary field. If you're not familiar with this field, it allows you to place it within a form, and it'll show a summary of the content of the submission so far. Very useful to place it on the last page of a form for users to review their submission.

```twig
{# Get the submission #}
{% set submission = craft.formie.submissions.id(1234).one() %}

{# Loop through each field in the field layout #}
{% for field in submission.getFields() %}
    {% if not field.getIsCosmetic() %}
        {# Get the raw value for the field, from the submission #}
        {% set value = submission.getFieldValue(field.handle) %}

        <strong>{{ field.name }}</strong><br>
        {{ field.getValueForSummary(value, submission) }}
    {% endif %}
{% endfor %}
```

The result is very similar to `getValueAsString()`, the only real difference being that it supports rendering items in HTML. This can be helpful for Element or File Upload fields that render an anchor (`<a>`) element.
  
### For export
Similarly, we have a specific function for the values used when generating a CSV export of a form. A word of warning that some values are arrays, such as for Table, Repeater and Group fields, because these are designed to span over multiple columns in a CSV.

```twig
{# Get the submission #}
{% set submission = craft.formie.submissions.id(1234).one() %}

{# Loop through each field in the field layout #}
{% for field in submission.getFields() %}
    {% if not field.getIsCosmetic() %}
        {# Get the raw value for the field, from the submission #}
        {% set value = submission.getFieldValue(field.handle) %}

        <strong>{{ field.name }}</strong><br>
        {{ field.getValueForExport(value, submission) }}
    {% endif %}
{% endfor %}
```

### For integrations
This is the more advanced of the "conversion" functions and is specifically for use with integrations, but you might find it useful for some use cases.

The difference with this function is that it handles converting values from a source format (the Formie field defines this) to the destination format (the integration defines this).

Say, for example, we want to map the values from a Checkboxes field to our Mailchimp integration. This process allows us to pick a custom field in Mailchimp to map the Formie field to. Now this destination field could be a text field, a checkboxes field or something else entirely. When the integration is run, we need to convert the array content from Formie into either a string or keep it as an array — all depending on the type of field we map to.

For this reason, we need to define what sort of destination format the value needs to be converted to.

```twig
{# Get the integration — some integrations have specific conversion logic #}
{% set integration = craft.formie.plugin.integrations.getIntegrationByHandle('mailchimp') %}

{# Get the raw value for the field #}
{% set value = submission.getFieldValue(field.handle) %}

{# Create an `IntegrationField` to act as the destination field #}
{% set integrationField = create({
    class: 'verbb\\formie\\models\\IntegrationField',
    type: 'string',
}) %}

{{ field.getValueForIntegration(value, integrationField, integration, submission) }}
```

The first difference is that we create a `verbb\formie\models\IntegrationField` model class. This acts as the setting for the destination field — essentially what we want to convert the value to. It's given a type, or which the following are available:
- `string`
- `number`
- `float`
- `boolean`
- `date`
- `datetime`
- `array`

Because we've decided to convert it to a string, the origin Checkboxes field will convert the array value into a comma-delimited string.
