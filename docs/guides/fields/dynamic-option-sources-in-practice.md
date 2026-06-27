# Dynamic option sources in practice

Dropdown, Radio, and Checkboxes fields can pull their options from several source types — not just a manually maintained table. This walkthrough shows when to use each option source and how they behave at render and submit time.

## Prerequisites

- [Option Sources reference](/fields/option-sources)

## The five source types

| Type | Best for |
| --- | --- |
| **Static** | Short, fixed lists you curate in the form builder |
| **Predefined** | Built-in datasets (countries, states, currencies, languages) |
| **Integration** | Remote picklists from connected CRM or marketing tools |
| **Template** | Per-request lists built in Twig |
| **Custom Provider** | Server-resolved Craft data via a registered provider |

Authors choose the type under the field's **Options** setting.

## Static — full control in the builder

Use **Static** when the list is short, changes rarely, and authors should manage it directly.

- Edit rows in the options table.
- Use **Bulk add options** to paste many rows at once.
- Set per-row availability (visible, hidden, disabled).
- Submitted values store the value only — no label snapshot.

Static is the default when the field only defines an `options` table in the builder.

## Predefined — ship common datasets without maintenance

Use **Predefined** when Formie already ships the list:

1. Set **Options** to **Predefined**.
2. Choose the **List** (countries, states/provinces, currencies, languages).
3. Pick which source fields map to **Option Label** and **Option Value**.

Formie resolves the list when the form renders. The **Preview** panel shows a sample of rows.

You can copy a predefined subset into a static table with **Bulk add options** while the field is still **Static**, then curate manually.

At submit time, Formie validates against the resolved list and stores **value and label** so notifications stay accurate if the predefined list changes later.

## Integration — follow connected provider data

Use **Integration** when options should track a connected service:

1. Set **Options** to **Integration**.
2. Choose the **Integration** instance.
3. Choose the **Source** (for example HubSpot form fields vs CRM properties).
4. Complete any additional settings for that source.

Supported integrations include Mailchimp interest groups, HubSpot form fields and CRM properties, Salesforce picklists, Zoho picklists, and Microsoft Dynamics 365 picklists.

Before a source returns options, refresh provider data in the form's **Integrations** tab. In field settings, use **Refresh Preview** to re-resolve from cache, or **Refresh Integration Data** to fetch fresh remote data.

**Convert to Static Options** freezes the current remote list into the static table and drops the provider link — useful when you want to stop depending on the remote source.

## Template — developer-controlled per render

Use **Template** when the list depends on the current page, entry, user, or site — and a developer controls the Twig template:

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

For nested fields, include the parent handle: `contactDetails.department`. See [Overriding Settings](/templates/overriding-settings).

### Template caveats

- **No strict validation** — Formie does not enforce an `in` range against a stored list. Your template is responsible for sensible choices.
- **No builder preview** — the form builder cannot know what Twig will output.
- **Cached pages** — template overrides run when Twig renders the form. Statically cached HTML keeps options from the render that built the cache. See [Cached Forms](/frontend/cached-forms).
- **Prefer Predefined or Integration** when a stable provider exists — you get preview, refresh, and stricter validation.

## Custom Provider — local Craft data with builder UI

Use **Custom Provider** when options come from Craft elements or custom PHP logic and authors need configurable params in the builder:

1. Register a provider class (see [Creating a custom option source provider](/guides/fields/creating-a-custom-option-source-provider)).
2. Set **Options** to **Custom Provider**.
3. Choose the provider and complete its param fields.

Formie resolves rows at render and submit time, with the same validation behaviour as Predefined and Integration sources.

## Picking Craft elements

When authors need users to pick **entries, categories, users, or tags** as relations — not flat option rows — use the dedicated element field types instead of option sources. Element fields return element IDs, which fits relations, GraphQL, and integration mapping better.

## Choosing a source — decision flow

```
Is the list the same for every render?
├─ Yes → Is it a built-in dataset Formie ships?
│         ├─ Yes → Predefined
│         └─ No → Is it from a connected integration?
│                   ├─ Yes → Integration
│                   └─ No → Static (or Custom Provider if PHP logic is needed)
└─ No → Does it depend on Twig context (entry, user, site)?
          ├─ Yes → Template (watch caching)
          └─ No → Custom Provider
```

Keep option **values** stable once submissions, integrations, or reports depend on them. Changing a value invalidates older submissions during strict validation.

## Related

- [Option Sources](/fields/option-sources)
- [Creating a custom option source provider](/guides/fields/creating-a-custom-option-source-provider)
- [Overriding Settings](/templates/overriding-settings)
