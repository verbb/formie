# Signature

Use Signature when the form needs someone to draw a signature with a mouse, finger, or stylus.

Use Signature when a drawn signature is part of the form process. If the user only needs to type their name as acknowledgement, Single-Line Text or Agree will usually be simpler.

## Key settings

- **Background color** - Control the signature pad background.
- **Pen color** - Control the drawing color.
- **Pen weight** - Control the stroke thickness.
- **Required** - Require a signature before the form can be submitted.

## Submitted value

Signature stores image-style signature data that Formie can render in submissions, summaries and notifications. Treat it as captured drawing data, not as a typed name.

When querying or saving submissions through GraphQL, the field handle is used as the field name. Query the form’s `formFields` and include `inputTypeName` if you need to confirm the generated input type for a specific form.

## Theme config

The Signature field can be targeted with the `signature` theme config key.

See [Signature Field theme config](/theming/theme-config#signature-field) for the full list of field-specific theme tags.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        signature: {
            fieldCanvas: {
                attributes: {
                    class: 'my-signature-canvas',
                },
            },
            fieldRemoveButton: {
                attributes: {
                    class: 'my-signature-clear',
                },
            },
        },
    },
}) }}
```

Use theme config for class and attribute changes. Use a template override only when the canvas or hidden input markup needs to change.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Front-end reference

Signature depends on Formie’s front-end JavaScript for the drawing canvas and hidden input value. If you override templates, preserve the canvas, hidden input and clear/remove button behavior.

The front-end docs live on the separate browser UI reference site and cover rendered markup, data attributes, styling classes and JavaScript behavior for custom front-end implementations.

- [Signature](/browser/ui-reference/fields/signature)

## Related fields

- Use [Agree](/fields/agree) for a checkbox acknowledgement.
- Use [Single-Line Text](/fields/single-line-text) when a typed name is enough.

