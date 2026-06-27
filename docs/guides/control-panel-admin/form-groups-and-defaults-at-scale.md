# Form groups and defaults at scale

When a project grows past a handful of forms, the control panel gets noisy and new forms drift from your standards. Form groups organise the forms index, enforce policies, and layer group-level defaults on top of global Formie settings — without changing how forms render on the front end.

## Prerequisites

- [Form Groups](/forms/form-groups)
- [Configuration](/get-started/configuration) — global defaults in `config/formie.php` and **Settings → Defaults**

## When to use groups

Form groups help when:

- Several teams each own a set of forms
- You want sidebar filters instead of one long forms list
- New forms should land in a known bucket with consistent defaults

If you only have a few forms, skip groups — **All forms** works fine.

Groups do **not** change front-end rendering, templates, or existing submission behaviour until you change form settings.

## Step 1 — Plan your group structure

A typical agency or enterprise layout:

| Group | Purpose | Site policy |
| --- | --- | --- |
| Marketing | Lead gen, newsletters | All sites or regional subset |
| Support | Tickets, feedback | Internal site only |
| HR | Applications, onboarding | Created-site-only propagation |

Create groups under **Formie → Settings → Form Groups**. Each group has a name, handle, and sort order. Definitions live in [project config](/get-started/configuration#form-groups-project-config) and travel with the repo.

## Step 2 — Configure site policy (multi-site)

On multi-site Craft installs, each group restricts **which sites** forms may exist on and how new forms **propagate**.

Under **General → Site Policy**:

- **Enabled Sites** — tick only the sites forms in this group may use
- **Site Propagation** — control how new forms spread (all enabled sites, created site only, same language, same site group)

Example: an **Australia Forms** group with only the Australia site enabled, propagation set to **All enabled sites**. Forms in that group never appear on Global or New Zealand sites.

See [Translating forms across Craft sites](/guides/control-panel-admin/translating-forms-across-craft-sites) for how availability differs from translation.

## Step 3 — Set group defaults

Blank group settings inherit from global **Formie → Settings → Defaults**. Override only what the group needs:

| Tab | Example override |
| --- | --- |
| **Form Defaults** | `collectIp: true`, `submitMethod: ajax`, default submission status |
| **Field Defaults** | File Upload volume, Date display type |
| **Validation Messages** | Plugin-wide validation copy for this team's forms |
| **Notification Defaults** | From address, Reply-To, attach PDF default |
| **Integration Defaults** | Captcha enabled/disabled for new forms |

New forms created in the group pick up these defaults. Existing forms are unchanged until you edit them.

### Submission limits vs groups

[Submission limits](/forms/submission-limits) are **per form**, not per group. If your group often uses per-user or per-IP limits, preset **Collect User** or **Collect IP** on the group **Form Defaults** tab so new forms inherit the privacy settings those limits need.

Global abuse throttling lives under **Settings → Spam Protection** — separate from form submission limits.

## Step 4 — Custom field palette

Enable **Use custom field palette** on the **Field Palette** tab when a team should only see a subset of field types — for example, no Payment or Repeater for marketing self-service authors.

When disabled, the group inherits the global palette from **Settings → Fields**.

## Step 5 — Allowed submission statuses

Restrict which submission statuses forms in the group may use. Choose **All** to allow every status, or pick a subset for workflow consistency (for example, Support forms may only use **New**, **In progress**, **Closed**).

This policy applies to all forms in the group; individual forms cannot override it.

## Step 6 — Work in the forms index

When at least one group exists, the sidebar shows:

- **All forms**
- **Groups** — one source per group
- **Ungrouped** — forms with no group

Creating a form from a group source pre-assigns that group. Use **Move to group** bulk actions to reorganise later.

The submissions index groups forms under the same headings when groups exist.

## Step 7 — Layer config file defaults

For developer-controlled defaults that apply before the control panel, use `config/formie.php`:

```php
return [
    '*' => [
        'defaultFormStencil' => 'contactForm',
        'formDefaults' => [
            'collectIp' => false,
            'submitMethod' => 'ajax',
        ],
        'notificationDefaults' => [
            'from' => 'notifications@example.com',
        ],
    ],
];
```

Precedence in practice:

1. **Global config** (`formie.php`) — baseline for all environments
2. **Global CP defaults** — **Settings → Defaults**
3. **Group defaults** — override global where set
4. **Individual form** — final authority once saved

Document which layer owns which setting so editors know where to look.

## Step 8 — Combine with stencils

Groups organise *where* forms live; [stencils](/forms/stencils) define *what* new forms start with. Set `defaultFormStencil` globally or train authors to pick a stencil when creating forms in each group.

See [Stencils for repeatable form types](/guides/control-panel-admin/stencils-for-repeatable-form-types).

## Related

- [Form Groups](/forms/form-groups)
- [Configuration](/get-started/configuration)
- [Stencils for repeatable form types](/guides/control-panel-admin/stencils-for-repeatable-form-types)
- [Translating forms across Craft sites](/guides/control-panel-admin/translating-forms-across-craft-sites)
