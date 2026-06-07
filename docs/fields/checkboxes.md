# Checkboxes

Use Checkboxes when someone can choose more than one option from a list.

Use Checkboxes for multiple-choice questions where more than one answer can be true. If only one answer should be selected, use Radio or Dropdown instead.

## Key settings

- **Options** - Define the available choices. Keep option values stable once submissions, exports or integrations depend on them.
- **Layout** - Choose whether options appear vertically or horizontally.
- **Toggle checkbox** - Adds a single control for selecting or clearing the full option set.
- **Minimum and maximum selections** - Enforce how many options must or can be selected.
- **Default value** - Preselect one or more options for new submissions.

## Bulk Add Options

Use Bulk add options when you need to add a long list, such as interests, countries, services or another repeated option set. You can start from Formie’s predefined options, choose which source field should be used for the label and value, then append those options to the field or replace the existing options.

You can also paste your own options into the bulk editor. Use one option per line:

```text
Support|support
Billing|billing
Sales|sales
```

If you only provide one value on a line, Formie uses it for both the label and value.

## Option availability

Use the row menu on an option to set its **availability**:

- **Visible** — Shown and selectable (default).
- **Hidden** — Removed from the front-end form without deleting the option. Existing submissions keep their stored value and label in the control panel, exports and email notifications.
- **Disabled** — Shown on the front-end form with HTML `disabled` so it is visible but cannot be selected.

Only one state applies at a time. Prefer hiding over deleting an option once submissions may reference its value.

## Overriding Options

If the options need to come from template logic, you can override the field’s `options` before rendering the form. See [Overriding Settings](/templates/overriding-settings).

## Submitted value

Checkboxes store multiple selected option values. In templates, exports and integrations, treat the value as a list of selected options rather than a single string.

When querying or saving submissions through GraphQL, the field handle is used as the field name. Query the form’s `formFields` and include `inputTypeName` if you need to confirm the generated input type for a specific form.

## Theme config

The Checkboxes field can be targeted with the `checkboxes` theme config key.

See [Checkboxes Field theme config](/theming/theme-config#checkboxes-field) for the full list of field-specific theme tags.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        checkboxes: {
            fieldOptions: {
                attributes: {
                    class: 'my-checkbox-options',
                },
            },
            fieldOptionLabel: {
                attributes: {
                    class: 'my-checkbox-label',
                },
            },
        },
    },
}) }}
```

Use theme config for class and attribute changes. Use a template override only when you need to change the option markup itself.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Front-end reference

The front-end docs live on the separate browser UI reference site and cover rendered markup, data attributes, styling classes and JavaScript behavior for custom front-end implementations.

- [Checkboxes](/browser/ui-reference/fields/checkboxes)

## Related fields

- Use [Radio](/fields/radio) when exactly one visible option should be selected.
- Use [Dropdown](/fields/dropdown) when the option list is long or should take less space.

