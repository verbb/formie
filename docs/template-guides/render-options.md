# Render Options
There are a number of options you can pass into the render functions for [Forms](docs:template-guides/rendering-forms), [Pages](docs:template-guides/rendering-pages) and [Fields](docs:template-guides/rendering-fields).

Option | Description
--- | ---
`fieldNamespace` | Set the namespace used by all fields. This is `fields` by default.
`themeConfig` | Set the [Theme Config](docs:theming/theme-config) used for the form.

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