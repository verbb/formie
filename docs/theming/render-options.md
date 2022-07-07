# Render Options
There are a number of options you can pass into the render functions for [Forms](docs:template-guides/rendering-forms), [Pages](docs:template-guides/rendering-pages) and [Fields](docs:template-guides/rendering-fields).

Option | Description
--- | ---
`fieldNamespace` | Set the namespace used by all fields. This is `fields` by default.
`sessionKey` | Set a unique key used for session-tracking of incomplete submissions.
`themeConfig` | Set the [Theme Config](docs:theming/theme-config) used for the form.

## Session Key
Setting the `sessionKey` when rendering a form gives you to opportunity to set a scope for the form's submission. When a user fills out a form, and submits the page, their data is either saved as a complete submission (single-page forms) or their data is saved as an incomplete submission (multi-page forms). In addition, we also store the current submisson in the users' session, so that if they navigate away, or even close their browser, their content won't be lost.

However, there are scenarios where this is unwanted. For example, let's say you have an Events channel of entries, and each entry page has a registration form using Formie. This form is a multi-page form, where users can come back to later.

The user fills out the form for one event, but decides to come back later. They then go to another event to fill in the form - and their content from their other event has been populated!

To get around this, we can set a scope for the form on how to save this session data. In this case, we'd scope the submission content to something unique about the Event entry, like its slug or ID.

```twig
{# Where `entry` is the Craft entry for the Event #}
{{ craft.formie.renderForm('contactForm', {
    sessionKey: entry.id,
}) }}
```

Now, any submissions created will have their session data scoped to this form **and** the provided `entry.id`.

## Custom Variables
In addition to the set above options, you can also supply your own. These won't do anything with the default Formie templates, but if you are using [Custom Rendering](docs:theming/custom-rendering) or [Template Overrides](docs:theming/template-overrides) this can be useful in supplying your own variables.

For example, we have provide some variables when rendering the form.

```twig
{% set renderOptions = {
    myVariable: 'some-value',
} %}

{{ craft.formie.renderForm('contactForm', renderOptions) }}
```

Let's say we're using [Template Overrides](docs:theming/template-overrides) to override the Dropdown field's template, where we want to access this variable. We could do the following in `fields/dropdown`

```twig
{% set myVariable = renderOptions.myVariable ?? null %}
```

Here, we check whether or the `myVariable` exists in render options, and then set a variable `myVariable` with that value, or otherwise `null`. We can then use that variable in our Dropdown field's template.