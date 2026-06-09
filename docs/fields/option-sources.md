# Option Sources

Use the **Options** setting on Dropdown, Radio and Checkboxes when the available choices should come from something other than a manually maintained options table.

Option sources are useful for long or changing lists — countries, CRM picklists, Mailchimp interest groups, HubSpot form properties — without copying hundreds of rows into the field or relying on Twig overrides on every render.

## Choosing an option type

In the form builder, open the field’s **Options** setting and pick one of the available types:

| Option type | Use when |
| --- | --- |
| **Static** | You define options manually in the options table, or import them once with **Bulk add options**. |
| **Predefined** | You want a built-in list such as countries, states, currencies or languages. Formie resolves the list at render time. |
| **Integration** | You want options from a connected integration’s cached data — for example Mailchimp interests or a CRM picklist. |
| **Template** | A developer supplies the option list in Twig when the form is rendered. Formie does not store or strictly validate that list. |

**Integration** only appears when at least one enabled integration in your project supports option sources.

Predefined and Integration both resolve options at render time. The form builder still shows them as separate choices because the setup steps are different.

## Static

Use **Static** for short, fixed lists you want full control over in the form builder.

- Edit options directly in the options table.
- Use **Bulk add options** to paste many rows at once, or start from a predefined list and copy rows into the static table.
- Option **availability** (visible, hidden, disabled) is managed per row.
- Submitted values are stored without a separate label snapshot.

Static is the default for legacy fields that only define an `options` table.

## Predefined

Use **Predefined** for common datasets Formie ships with, without maintaining hundreds of rows yourself.

1. Set **Options** to **Predefined**.
2. Choose the **List** — for example countries, states/provinces, currencies or languages.
3. Choose which source fields to use for **Option Label** and **Option Value**.

Formie resolves the list when the form is rendered. The **Preview** panel shows a sample of the resolved rows.

You can still use **Bulk add options** while the field is **Static** if you want to copy a predefined subset into the options table and then keep curating it manually.

## Integration

Use **Integration** when options should follow data from a connected provider.

1. Set **Options** to **Integration**.
2. Choose the **Integration** instance.
3. If the integration exposes more than one source, choose the **Source** — for example HubSpot **Form Fields** vs **CRM Properties**.
4. Complete any additional settings shown for that source — such as the HubSpot form, CRM object, or remote field.

| Integration | What you can select |
| --- | --- |
| **Mailchimp** | Interest groups and tags from a list |
| **HubSpot** | Form fields, or CRM properties on contacts, companies, deals and tickets |
| **Salesforce** | Picklist fields on contacts, leads, opportunities, accounts and cases |
| **Zoho** | Picklist fields on contacts, deals, leads, accounts and quotes |
| **Microsoft Dynamics 365** | Picklist fields on contacts, leads, opportunities, accounts and cases |

Before an integration source can return options, refresh the provider data in the form’s **Integrations** tab — for example field mapping for CRM objects, or HubSpot forms for form-field sources.

In the field settings **Preview** panel, use **Refresh** to:

- **Refresh Preview** — re-resolve options from the current cached integration data.
- **Refresh Integration Data** — fetch fresh data from the provider, then reload the source configuration.

### Convert to Static Options

When a **Predefined** or **Integration** source is configured, **Convert to Static Options** resolves the current remote list and copies the rows into the static options table. The field switches to **Static** and drops the provider link. Use this when you want to freeze a snapshot or stop depending on the remote source.

## Template

Use **Template** when the option list should be built in Twig for a specific page, user, entry or request — and you accept that Formie will not manage that list in the form builder.

When **Template** is selected, the options table and provider settings are hidden. The form builder shows a short note that options are supplied at render time.

### How it works

1. Save the field with **Options** set to **Template**. You do not define rows in the form builder.
2. In your template, load the form and override the field’s `options` before rendering.
3. Formie renders whatever option array you supplied for that request.

```twig
{% set form = craft.formie.forms.handle('contactForm').one() %}

{% set departments = craft.entries()
    .section('departments')
    .orderBy('title')
    .all() %}

{% do form.setFieldSettings('department', {
    options: departments|map(entry => {
        label: entry.title,
        value: entry.slug,
        default: false,
    }),
}) %}

{{ craft.formie.renderForm(form) }}
```

You can also pass a plain array when the list is not element-driven:

```twig
{% do form.setFieldSettings('priority', {
    options: [
        { label: 'Low', value: 'low', default: false },
        { label: 'Normal', value: 'normal', default: true },
        { label: 'High', value: 'high', default: false },
    ],
}) %}
```

For nested fields inside Group or Repeater fields, include the parent handle — for example `contactDetails.department`. See [Overriding Settings](/templates/overriding-settings) for more field-override examples.

### Caveats

**No strict option validation.** Formie does not apply an `in` range against a stored list for template fields. Submitted values are accepted as long as they pass the field’s other rules (required, format, and so on). Your template is responsible for supplying sensible choices.

**No builder preview.** The form builder cannot know what your template will output, so there is no meaningful option preview while editing the field.

**Nothing is stored on the field.** Template mode does not persist an option list in the field settings. If you forget to override `options` in Twig, the field may render with no choices.

**Cached pages need extra care.** Template overrides run when Twig renders the form. On a statically cached page, the cached HTML keeps the options from the render that built the cache. Formie’s cached-form support refreshes request tokens, but it does not re-run Twig overrides or rebuild field markup on each page load. See [Cached Forms](/frontend/cached-forms) and [Overriding Settings](/templates/overriding-settings).

**Reporting and integrations.** Different page renders may expose different option sets to the same field handle. That can be intentional, but plan for it in exports, reporting and CRM mappings.

**Prefer Predefined or Integration when possible.** If the list comes from a stable provider Formie already supports, use **Predefined** or **Integration** instead. You get builder preview, refresh controls, stricter validation and consistent behaviour on cached forms.

### When Template mode is a good fit

- Options depend on the current entry, category, site or logged-in user.
- A developer controls the template and wants full flexibility per render.
- The list is intentionally different on different templates that reuse the same form.

### When to use something else

- Authors should manage or preview the list in the form builder → **Static**, **Predefined** or **Integration**.
- The list must be validated strictly against known values at submit time → **Static**, **Predefined** or **Integration**.
- The form is often served from static cache without per-request Twig execution → avoid **Template**, or ensure the cache varies per context.

## Submitted values

| Option type | What is stored |
| --- | --- |
| **Static** | The selected value only. |
| **Predefined** / **Integration** | The selected value and label. |
| **Template** | The selected value and label from the options you supplied at render time. |

Storing the label with the value keeps email notifications, exports and control panel previews accurate when the remote list changes later.

Keep option **values** stable once submissions, integrations or reports depend on them. Changing a value in the provider can invalidate older submissions during strict validation.

## Validation

**Static**, **Predefined** and **Integration** validate submitted values against the resolved option list at submit time.

**Template** does not enforce an `in` range against a stored list. Use it only when a developer controls the render-time options deliberately.

## Picking entries, categories, users or tags

**Predefined** and **Integration** on Dropdown, Radio and Checkboxes cover built-in lists and integration data only.

When authors need users to pick Craft elements, use the dedicated field types instead:

- [Entries](/fields/entries)
- [Categories](/fields/categories)
- [Users](/fields/users)
- [Tags](/fields/tags)

Those fields use element queries and return element IDs, which fits relations, GraphQL and integration mapping better than treating elements as flat option rows.
