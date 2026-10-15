# Hidden

Use Hidden when the form needs to carry a value without showing it to the person filling out the form.

Hidden fields are useful for context, attribution, and routing data. Do not rely on them for security-sensitive decisions because hidden values can still be changed before submission.

## Key Settings

- **Default value** - Set the value submitted with the form.
- **Value source** - Populate from a preset, **Custom Value**, **Template**, query string parameter, or cookie where supported.
- **Custom Value** - Literal text with optional variable-picker tokens (`{field:handle}`, `{form:handle}`, and so on). The posted value is stored, including an explicitly empty value.
- **Template** - An explicit opt-in to sandboxed Twig, using Craft [object-template syntax](https://craftcms.com/docs/5.x/system/object-templates.html), resolved **only on the server**. Posted values are ignored. Use this for trusted computed values such as event context, custom `currentUser` fields, or query parameters via `{request.param.myParam}`.
- **Prefill query parameter** - Read a value from a specific query string parameter.
- **Handle** - Choose a stable field handle for integrations, templates and exports.

## Template Availability

An administrator must enable **Settings → Fields → Allow Hidden Field Templates** before editors can choose Template. It is off on new installs; upgrades preserve availability. When disabled later, existing Template fields return an empty value and still ignore posted input. Their source is retained. To save an affected form, choose another source or ask an administrator to restore template access.

## Custom Value vs Template

| | Custom Value | Template |
|---|---|---|
| Editor | Variable picker | Object-template code editor |
| Posted value | Stored, including an empty value | **Ignored** — server re-resolves on every save |
| Trust model | User-modifiable | Server-authoritative |

Use **Template** when you need Twig logic and the browser must not override its result. For example, `{form.handle}` reads the form handle, while a Twig conditional can choose a value from the available context. The sandbox restricts which Twig features and object properties are available. Values read from visitor-controlled sources, such as query parameters, still need validation. Use **Custom Value** for text and references that may legitimately vary per submission.

Extend template context in PHP with `HiddenDefaultTemplateResolver::EVENT_DEFINE_CONTEXT` when you need entry lookups or other project-specific variables.

## Submitted Value

Hidden stores a submitted value like other fields, even though the user does not see a visible control. For **Custom Value** and preset sources, treat the stored value as user-modifiable browser data unless you validate it server-side. **Template** defaults are re-resolved on the server and ignore posted input.

When querying or saving submissions through GraphQL, the field handle is used as the field name. Query the form’s `formFields` and include `inputTypeName` if you need to confirm the generated input type for a specific form.

## Theme Config

The Hidden field can be targeted with the `hiddenField` theme config key.

```twig
{{ craft.formie.renderForm('contactForm', {
    themeConfig: {
        hiddenField: {
            field: {
                attributes: {
                    data: {
                        tracking: 'campaign',
                    },
                },
            },
        },
    },
}) }}
```

Hidden fields usually need little presentation customisation. Use a template override only when the hidden input output itself needs to change.

For full Tailwind, Bootstrap and other framework examples, see [Formie theme configs](https://github.com/verbb/formie-theme-configs).

## Front-End Reference

Hidden fields are still rendered into the page HTML. Do not place secrets, trusted prices, permission flags or other security-sensitive values in a hidden field without server-side validation.

- [Hidden](https://docs.verbb.io/formie/browser/ui-reference/fields/hidden)

## Related Fields

- Use [Single-Line Text](/fields/single-line-text) when the user should see and edit the value.
