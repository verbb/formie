# Populating Forms
When rendering a form, you might like to populate the values of a field with some default values.

```twig
{# Sets the field with handle `text` to "Some Value" for the form with a handle `contactForm` #}
{% do craft.formie.populateFormValues('contactForm', { text: "Some Value" }) %}

{# or use a `form` object #}
{% set form = craft.formie.forms({ handle: 'contactForm' }).one() %}
{% do craft.formie.populateFormValues(form, { text: "Some Value" }) %}

{# Must be done before the `renderForm()` #}
{{ craft.formie.renderForm('contactForm') }}
```

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
