# Dropdown

Use Dropdown when someone should choose from a defined list and the options do not need to be visible all at once.

Use Dropdown for compact choice fields, especially when the list is long. If the choices are few and should stay visible, Radio or Checkboxes may be clearer.

## Key settings

- **Options** - Define the available choices. Keep option values stable once submissions, exports or integrations depend on them.
- **Multiple selections** - Allow one selected value or a list of selected values.
- **Default value** - Preselect one or more options for new submissions.
- **Placeholder** - Show an initial prompt before a value is selected.
- **Required** - Force a choice before the form can be submitted.

## Bulk Add Options

Use Bulk add options when you need to add a long list, such as countries, states, currencies or another repeated option set. You can start from Formie’s predefined options, choose which source field should be used for the label and value, then append those options to the field or replace the existing options.

You can also paste your own options into the bulk editor. Use one option per line:

```text
Australia|AU
New Zealand|NZ
United States|US
```

If you only provide one value on a line, Formie uses it for both the label and value.

## Option Sources

Instead of copying a long predefined or integration list into the static options table, set **Options** to **Predefined** or **Integration**. Formie resolves the list at render time and stores the selected label with the submission value.

See [Option Sources](/fields/option-sources) for predefined lists, Mailchimp groups, CRM picklists, template mode, converting to static options, and validation behaviour.

## Option availability

Use the row menu on an option to set its **availability**:

- **Visible** — Shown and selectable (default).
- **Hidden** — Removed from the front-end form without deleting the option. Existing submissions keep their stored value and label in the control panel, exports and email notifications.
- **Disabled** — Shown on the front-end form with HTML `disabled` so it is visible but cannot be selected.

Only one state applies at a time. Prefer hiding over deleting an option once submissions may reference its value.

## Overriding Options

If the options need to come from template logic, set the field’s **Options** type to **Template** in the form builder, then override the field’s `options` before rendering the form. See [Option Sources](/fields/option-sources#template) and [Overriding Settings](/templates/overriding-settings).

## Submitted value

Dropdown stores the selected option value. When multiple selections are enabled, it stores a list of selected option values.

When querying or saving submissions through GraphQL, the field handle is used as the field name. Query the form’s `formFields` and include `inputTypeName` if you need to confirm the generated input type for a specific form.

## Theme config

The Dropdown field can be targeted with the `dropdown` theme config key.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        dropdown: {
            fieldInput: {
                attributes: {
                    class: 'my-dropdown-input',
                },
            },
        },
    },
}) }}
```

Use theme config for class and attribute changes. Use a template override only when the `<select>` markup or option rendering needs to change.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Related fields

- Use [Radio](/fields/radio) when a short list should stay visible.
- Use [Checkboxes](/fields/checkboxes) when several choices can be selected and visibility matters more than compactness.

