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

### Element Fields
For some element fields (Entries, Categories, Tags, Users, Products, Variants), you'll need to supply the value of an element query, instead of just the ID of the element you want to populate. For example, for an Entries field:

```twig
{% do craft.formie.populateFormValues(form, {
    entriesField: craft.entries.id(123),
}) %}
```

Here, we're settings the Entries field (with a handle `entriesField`) to an Entry query. Note how we're _not_ using `.one()` here. This is because element fields require a query, not just an ID.

You can do the same for other elements:

```twig
{% do craft.formie.populateFormValues(form, {
    productsField: craft.products.id(6457),
}) %}
```

### Recipients
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
            text: 'Some Value',
        },
        {
            text: 'Another Value',
        },
    ],
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

## Populating from URL
You can also make use of populating fields from a URL, using parameters in a query string. For each field, you'll have the option to specify the parameter in the URL query string you want to populate the field with. This provides the flexibility of your URL not having to match the field handles of each field.

To provide a practical example, let's say we have an email newsletter that goes out to users. In this email, we contain a link to a contact form you want to pre-populate with information. The URL might look something like:

http://mysite.com/contact-us?first_name=Peter&last_name=Sherman&email=psherman@wallaby.com.au&content=I want to know more!&utm=xxxxxxxxxxx

Clicking this link would navigate to your site, and a template that shows the form "Contact Form". This form would contain a Name (First Name and Last Name), Email and Multi-line Text fields. For each field, you would set the "Pre-Populate Value" like so:

- Name: First Name = `first_name`
- Name: Last Name = `last_name`
- Email = `email`
- Multi-line Text = `content`

The handles for each of these fields doesn't matter, as we use this "Pre-Populate Value" to connect the query string with fields. You'll also notice the URL contains other query parameters, which is totally fine, as they are ignored.

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
