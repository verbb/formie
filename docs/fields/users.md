# Users

Use Users when the user should choose from Craft user elements.

Use Users when the answer should stay connected to Craft user accounts. If the person filling out the form should type a new person’s details, use Name and Email Address instead.

## Key settings

- **User sources or groups** - Choose which users should be available.
- **Selection limit** - Control how many users can be selected.
- **Label format** - Control how users are labelled in the field where supported.
- **Sort order** - Control the order users appear in.
- **Display type** - Choose how user choices appear on the front end.
- **Placeholder** - Set the initial empty option text where the selected display type supports it.

## Submitted value

Users stores references to Craft user elements. Templates, exports and integrations can use the related user data instead of storing only a copied name or email address.

When querying or saving submissions through GraphQL, relation fields can expose element-aware content. Query the form’s `formFields` and include `inputTypeName` when building mutations.

## Theme config

The Users field can be targeted with the `users` theme config key.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        users: {
            fieldInput: {
                attributes: {
                    class: 'my-users-input',
                },
            },
        },
    },
}) }}
```

Use theme config for class and attribute changes. Use a template override only when the user field markup needs to change.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Related fields

- Use [Name](/fields/name) and [Email Address](/fields/email-address) when the person filling out the form should provide a new person’s details.
- Use [Entries](/fields/entries) or [Categories](/fields/categories) for other Craft element relations.

