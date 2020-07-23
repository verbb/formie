# Rendering Options
There are a number of options you can pass into the [`craft.formie.renderForm`](docs:template-guides/rendering-options) function.


## Base Options

These options are used by the default Formie template. If you use your own template, these options will not necessarily have an effect.

Option | Description
--- | ---
`fields` | Affect the attributes of specific fields. Accepts a [Fields Options](#fields-options) object as the value.
`buttons` | Affect the attributes of the submit/next page and previous page buttons. Accepts a [Buttons Options](#buttons-options) object as the value.
`fieldTag` | Set the tag for the outer-most field container. This is `div` by default.


## Buttons Options

You can affect the attributes of the page buttons. These attributes are merged into the default buttons attributes and rendered using the [`attr()`](https://docs.craftcms.com/v3/dev/functions.html#attr) function.

Option | Description
--- | ---
`submit` | Set the attributes of the submit/next page button.
`prev` | Set the attributes of the previous page button.


## Fields Options

Option | Description
--- | ---
`fieldHandle` | Set the key to the handle of the field you would like to affect. Accepts a [Field Options](#field-options) object as the value

### Field Options

Option | Description
--- | ---
`fieldTag` | Set the tag for the outer-most field container. This is `div` by default. Takes precedence over the [Base Options](#base-options) `fieldTag`.
`attributes` | Set the attributes of the outer-most field container. These attributes are merged into the default field container attributes and rendered using the [`attr()`](https://docs.craftcms.com/v3/dev/functions.html#attr) function.
