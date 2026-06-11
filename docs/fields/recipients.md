# Recipients

Use Recipients when the person filling out the form should choose where an email notification is sent.

Use Recipients when the submitter’s choice should affect notification routing. If the choice is only normal submitted content, use Dropdown, Radio, or Checkboxes instead.

## Key settings

- **Recipients** - Define the available recipient labels and email destinations.
- **Display type** - Show the field as hidden, dropdown, checkboxes or radio buttons.
- **Use searchable dropdown** - When **Display type** is **Dropdown**, allow users to filter options by typing. See [Dropdown → Searchable dropdown](/fields/dropdown#searchable-dropdown).
- **Default recipient** - Preselect or set the recipient when the field is hidden.
- **Multiple recipients** - Allow one or more recipients depending on the chosen display type.

## Submitted value

Recipients stores the selected recipient option value and resolves it to protected recipient email addresses for notification routing. The front-end form should not need to expose the real email addresses.

When querying or saving submissions through GraphQL, the field handle is used as the field name. Query the form’s `formFields` and include `inputTypeName` if you need to confirm the generated input type for a specific form.

## Recipients vs choice fields

Use Recipients when a selection should control who receives an email notification. The field stores recipient options, but Formie uses those options to route notifications without exposing the real email addresses on the front end.

Use [Dropdown](/fields/dropdown), [Radio](/fields/radio) or [Checkboxes](/fields/checkboxes) when the selection is just part of the submitted answer. Choice fields store the selected value for templates, exports and integrations, but they do not route notifications by themselves.

## Theme config

The Recipients field can be targeted with the `recipients` theme config key.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        recipients: {
            fieldInput: {
                attributes: {
                    class: 'my-recipients-input',
                },
            },
        },
    },
}) }}
```

Use theme config for class and attribute changes. Use a template override only when the recipient field markup needs to change.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Front-end reference

The front-end docs live on the separate browser UI reference site and cover rendered markup, data attributes, styling classes and JavaScript behavior for custom front-end implementations.

- [Recipients](/browser/ui-reference/fields/recipients)

## Related fields

- Use [Dropdown](/fields/dropdown), [Radio](/fields/radio) or [Checkboxes](/fields/checkboxes) when the selected value is normal submitted content.
- Use [Email Address](/fields/email-address) when the user should enter their own email address.

