# Populating Forms

Populate forms when you want to prefill values before rendering.

This is useful when the form should start with known information instead of making someone type it again.

```twig
{% set form = craft.formie.forms.handle('contactForm').one() %}

{% do craft.formie.populateFormValues(form, {
    firstName: currentUser.firstName ?? null,
    email: currentUser.email ?? null,
}) %}

{{ craft.formie.renderForm(form) }}
```

> [!NOTE]
> Looking to update or override settings for the field before they are rendered? See [Overriding Settings](/templates/overriding-settings). This includes setting available options for Dropdown, Checkbox, Radio, and similar fields.

## Standard Fields

Most fields accept a simple string value, including text fields, dropdowns, and multi-line text.

```twig
{% do craft.formie.populateFormValues(form, {
    textField: 'Some Value',
    dropdownField: 'Another Value',
    multiLineTextField: 'Another long bit of content',
}) %}
```

## Element Fields

For element fields such as Entries, Categories, Tags, Users, Products, and Variants, pass an array of element IDs.

```twig
{% do craft.formie.populateFormValues(form, {
    entriesField: [123, 5625],
    productsField: [6457],
}) %}
```

## Group

For fields inside a Group field, pass an object keyed by the inner field handles.

```twig
{% do craft.formie.populateFormValues(form, {
    groupFieldHandle: {
        text: 'Some Value',
    },
}) %}
```

## Phone

A Phone field can accept either a simple number, or an object with both the number and country code.

```twig
{% do craft.formie.populateFormValues(form, {
    phoneFieldHandle: '0412345678',
    phoneWithCountryFieldHandle: {
        number: '0412345678',
        country: 'AU',
    },
}) %}
```

## Recipients

Recipients fields can be populated with one email or many, depending on the display type.

```twig
{% do craft.formie.populateFormValues(form, {
    recipientsHidden: 'psherman@wallaby.com.au',
    recipientsCheckboxes: ['psherman@wallaby.com.au', 'asherman@wallaby.com.au'],
    recipientsRadio: 'psherman@wallaby.com.au',
    recipientsDropdown: 'psherman@wallaby.com.au',
}) %}
```

For checkbox, radio, and dropdown display types, Formie uses internal IDs in the HTML rather than exposing the real email address in the page source.

## Repeater

To populate a Repeater field, pass an array of objects. Each object becomes one repeater block.

```twig
{% do craft.formie.populateFormValues(form, {
    repeaterFieldHandle: [
        {
            textField: 'Some Value',
            dropdownField: 'Option 1',
        },
        {
            textField: 'Another Value',
            dropdownField: 'Option 2',
        },
    ],
}) %}
```

## Table

To populate a Table field, pass an array of row objects keyed by the table column handles.

```twig
{% do craft.formie.populateFormValues(form, {
    tableFieldHandle: [
        {
            textColumnHandle: 'Some Value',
            dropdownColumnHandle: 'Option 1',
        },
        {
            textColumnHandle: 'Another Value',
            dropdownColumnHandle: 'Option 2',
        },
    ],
}) %}
```

## Forcing Values

`populateFormValues()` works by setting initial values before render. That means it behaves much like a default value on a field.

If someone already has an incomplete submission, those fields may already have saved values, even if they are blank. In that case, your populated values will not replace them automatically.

When you do need to override an existing incomplete submission, pass `true` as the third argument.

```twig
{% do craft.formie.populateFormValues(form, {
    myHiddenField: 'This value can never be changed',
    entriesField: [123, 456],
}, true) %}
```

This is most useful for values that should always be set from the template, such as hidden fields or relationship fields that should not drift.

## URL Pre-Population

Fields can also be populated from the URL using each field's `Prefill Query Parameter` setting.

For example, a URL like this:

```text
http://mysite.com/contact-us?first_name=Peter&last_name=Sherman&email=psherman@wallaby.com.au&content=I want to know more!
```

can be mapped onto fields by setting each field's `Prefill Query Parameter` to the query parameter you want it to use.

For checkbox fields, you can pass either one value or many:

```text
?checks_field=SomeValue
?checks_field[]=FirstValue&checks_field[]=SecondValue
```

For element fields, pass the selected element ID or IDs:

```text
?entries_field=1234
?entries_field[]=1234&entries_field[]=5678
```

> [!NOTE]
> You can also alter this behaviour using [Field Events](/developers/events/field-events) if your URL parameters have more specific needs. For example, you might want to support a URL parameter such as `entries=2242,1101` for multiple items.

## Value Precedence

Formie distinguishes an absent value from an explicitly empty value. The first applicable source wins:

| Priority | Source |
| --- | --- |
| 1 | A forced server value supplied with `populateFormValues(form, values, true)` |
| 2 | Existing posted or persisted content, including an empty string, null or empty selection |
| 3 | An editable initial value supplied with `populateFormValues(form, values)` |
| 4 | Query prefill captured when the instance is established |
| 5 | The saved field default |

Forced values are retained with the submission configuration and reapplied before the existing condition-clearing step. A visitor cannot replace them by editing HTML or sending a different REST/GraphQL value. With `force=false`, the visitor can edit the initial value, and a saved blank stays blank on resume. `setFieldValues()` remains the underlying Craft element method; use the established `craft.formie.populateFormValues()` call in templates.

The field setting is `prefillQueryParam`. It reads query data when the form instance starts, never submission body data. Later pages, resume and payment returns cannot replace the captured query input. Query values remain untrusted field input and never execute Twig or reference expressions. Client-rendered REST and GraphQL bootstrap callers explicitly supply the host-page parameters through their `query` option; no arbitrary host query string is forwarded automatically.

Hidden fields use `valueSource`. Custom, URL, referrer, cookie and query values remain editable/untrusted input. Template, current-user, date and server IP sources are authoritative and evaluated on the server. Their reusable field definition is never changed to hold the request’s value. Condition clearing still runs after server value application.
