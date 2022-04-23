# Populating Forms
When rendering a form, you might like to populate the values of a field with some default values.

```twig
{# Sets the field with handle `text` to "Some Value" for the form with a handle `contactForm` #}
{% do craft.formie.populateFormValues('contactForm', { text: 'Some Value' }) %}

{# or use a `form` object #}
{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}
{% do craft.formie.populateFormValues(form, { text: 'Some Value' }) %}

{# Must be done before the `renderForm()` #}
{{ craft.formie.renderForm('contactForm') }}
```

:::tip
Looking to update or override settings for the field before they're rendered? Look at [Override Field Settings](docs:template-guides/rendering-fields#override-field-settings). This includes setting available options for Dropdown, Checkbox, Radio and similar fields.
:::

## Standard Fields
Most fields will accept a single value as a string. Fields like Single-Line Text, Multi-Line text, Dropdown and more.

```twig
{% do craft.formie.populateFormValues(form, {
    textField: 'Some Value',
    dropdownField: 'Another Value',
    multiLineTextField: 'Another long bit of content',
}) %}
```

Ensure you replace the key for the Twig object above with the Formie field handle you want to set values on.

## Element Fields
For element fields (Entries, Categories, Tags, Users, Products, Variants), you'll need to supply an array of element IDs for the elements you want to populate. For example, for an Entries field:

```twig
{% do craft.formie.populateFormValues(form, {
    entriesField: [123, 5625],
}) %}
```

Here, we're settings the Entries field (with a handle `entriesField`) to contain two entries, one with the ID of `123`, the other to `5625`.

You can do the same for other elements:

```twig
{% do craft.formie.populateFormValues(form, {
    productsField: [6457],
}) %}
```

## Group
To populate values for fields within a Group field, you'll need to supply both the handle for the Group field, and the values for the inner fields as you normally would.

```twig
{% do craft.formie.populateFormValues(form, {
    groupFieldHandle: {
        text: 'Some Value',
    },
}) %}
```

## Phone
Because a Phone field can handle the country code and the actual phone number, you have two options when populating the field. You can either provide just the phone number, or an object with the number and the two-letter ISO country code to pre-select the country dropdown.

```twig
{% do craft.formie.populateFormValues(form, {
    {# For a phone number field #}
    phoneFieldHandle: '0412345678',

    {# For a phone number field with a country dropdown #}
    phoneWithCountryFieldHandle: {
        number: '0412345678',
        country: 'AU',
    },
}) %}
```

## Recipients
When using a Recipients field, you can hard-code the recipient for the field. Depending on what display type you've chosen will depend on the available options to set. Notably, only the "Checkboxes" and "Hidden" display types can support multiple recipients.

```twig
{% do craft.formie.populateFormValues(form, {
    {# For hidden display type, use either a single email, or multiple #}
    recipientsHidden: 'psherman@wallaby.com.au',
    recipientsHidden: ['psherman@wallaby.com.au', 'asherman@wallaby.com.au'],

    {# For checkboxes display type, use either a single email, or multiple #}
    recipientsCheckboxes: ['psherman@wallaby.com.au', 'asherman@wallaby.com.au'],
    recipientsCheckboxes: 'psherman@wallaby.com.au',

    {# For radio and dropdown display types, use only a single email #}
    recipientsRadio: 'psherman@wallaby.com.au',
    recipientsDropdown: 'psherman@wallaby.com.au',
}) %}
```

For all options, the email address is never exposed in the HTML source of the page, keeping your recipient's emails safe. For Checkboxes, Radio and Dropdown, an ID value is used to reference the real email defined in the field settings. For a Hidden field, which allows for much more arbitrary template-level email definitions, the provided email values are encoded with a string unique to your site.

## Repeater
To populate a Repeater field, you'll also be creating the "blocks", as well as defining the inner field values. For instance, you might like to create 2 Repeater blocks on the page, with the first having one value, the next another. You'll need to provide the value as an array of objects.

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

The above will create two "blocks" for the repeater field.

## Forcing Values
The way populating values work in Formie is by setting the default value for a field. This means that when you start a new submission, the values you set in `populateFormValues()` will be applied to the field, the same way a default value would.

However, there's one caveat with this approach, to do with incomplete submissions. If you were to try to populate an incomplete submission with field values, you'll find it won't work. This is because the submission technically already has a value - even if it's a blank value.

You can even test this in effect for a multi-page form by submitting the first page of a form, **then** adding your `populateFormValues()` call to your templates, so see that it'll have no effect on any of the fields on any page. This is because Formie can't determine if the empty value a field might have is "correct" (the user intentionally left it blank), or whether to populate (override) the value.

But there are scenarios where you want certain field to **always** have a set value, even for incomplete submissions, or if for example a user is coming back to a submission at a later stage. You can use the `force` option for `populateFormValues()` to achieve this.

```twig
{% do craft.formie.populateFormValues(form, {
    myHiddenField: 'This value can never be changed',
    entriesField: [123, 456],
}, true) %}
```

As you can see, by passing in `true` as the third parameter, you can force the field to always use the values you define in your templates. This might be useful for hidden fields, or even element fields, where you really do want the value to always be the same.

## Populating from URL
You can also make use of populating fields from a URL, using parameters in a query string. For each field, you can use the **Pre-Populate Value** setting to specify the parameter in the URL query string you want to populate the field with. This provides the flexibility of your URL not having to match the field handles of each field.

To provide a practical example, let's say we have an email newsletter that goes out to users. In this email, we contain a link to a contact form you want to pre-populate with information. The URL might look something like:

```
http://mysite.com/contact-us?first_name=Peter&last_name=Sherman&email=psherman@wallaby.com.au&content=I want to know more!&utm=xxxxxxxxxxx
```

Clicking this link would navigate to your site, and a template that shows the form "Contact Form". This form would contain a Name (First Name and Last Name), Email and Multi-line Text fields. For each field, you would set the **Pre-Populate Value** field setting like so:

- Name: First Name = `first_name`
- Name: Last Name = `last_name`
- Email = `email`
- Multi-line Text = `content`

The handles for each of these fields doesn't matter, as we use this **Pre-Populate Value** to connect the query string with fields. You'll also notice the URL contains other query parameters, which is totally fine, as they are ignored.

Now, when the page loads, you'll have the form populated with content!

```
**First Name:**
Peter

**Last Name:**
Sherman

**Email**
psherman@wallaby.com

**Message**
I want to know more!
```

Of course, you can achieve the above functionality in your templates with `populateFormValues()`, but this method allows content editors to control the query string parameters freely in case the URL needs changing. Otherwise, they would rely on a developer to make template changes when adding new fields, or changing the URL parameters.

### Checkboxes Field
For a checkboxes field, you can either supply a single value, or an array of values.

```twig
{# URL when using a single value #}
?checks_field=SomeValue

{# URL when using multiple value #}
?checks_field[]=FirstValue&checks_field[]=SecondValue
```

### Element Fields
For element fields (Entries, Categories, Tags, Users, Products, Variants), you can either supply a single value, or an array of values - but all must be the ID of the elements you want to populate.

```twig
{# URL when using a single ID #}
?entries_field=1234

{# URL when using multiple IDs #}
?entries_field[]=1234&entries_field[]=5678
```

### Element Fields
For element fields (Entries, Categories, Tags, Users, Products, Variants), you can either supply a single value, or an array of values - but all must be the ID of the elements you want to populate.

:::tip
You can also alter this behaviour using [events](docs:developers/events) if your URL params have specific needs. For example, if you wanted to use a URL param like `entries=2242,1101` to handle multiple items.
:::
