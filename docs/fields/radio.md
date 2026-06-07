# Radio

Use Radio when someone should choose exactly one option from a short list.

Use Radio when the options are few and the choice should be visible. Use Dropdown when the list is long, and Checkboxes when more than one option can be selected.

## Key settings

- **Options** - Define the available choices. Keep option values stable once submissions, exports or integrations depend on them.
- **Layout** - Choose whether options appear vertically or horizontally.
- **Default value** - Preselect one option for new submissions.
- **Required** - Force a choice before the form can be submitted.

## Bulk Add Options

Use Bulk add options when you need to add a list quickly, especially if the options come from a known set that you do not want to type row by row. You can start from Formie’s predefined options, choose which source field should be used for the label and value, then append those options to the field or replace the existing options.

You can also paste your own options into the bulk editor. Use one option per line:

```text
Yes|yes
No|no
Not sure|not-sure
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

Radio stores the selected option value as a single value. The visible label can change later, but the stored value is what templates, exports and integrations usually depend on.

When querying or saving submissions through GraphQL, the field handle is used as the field name. Query the form’s `formFields` and include `inputTypeName` if you need to confirm the generated input type for a specific form.

## Theme config

The Radio field can be targeted with the `radioButtons` theme config key.

See [Radio Field theme config](/theming/theme-config#radio-field) for the full list of field-specific theme tags.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        radioButtons: {
            fieldOptions: {
                attributes: {
                    class: 'my-radio-options',
                },
            },
            fieldOptionLabel: {
                attributes: {
                    class: 'my-radio-label',
                },
            },
        },
    },
}) }}
```

Use theme config for class and attribute changes. Use a template override only when the radio option structure itself needs to change.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Front-end reference

The front-end docs live on the separate browser UI reference site and cover rendered markup, data attributes, styling classes and JavaScript behavior for custom front-end implementations.

- [Radio](/browser/ui-reference/fields/radio)

## Related fields

- Use [Dropdown](/fields/dropdown) when the option list is long.
- Use [Checkboxes](/fields/checkboxes) when more than one option can be selected.

