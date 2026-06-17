# Form Groups

Form groups help you organise forms in the control panel. They are useful when you manage many forms and want sidebar filters, clearer navigation, and a consistent place to create new forms.

Form groups are **control panel organisation** with an optional **policies layer** for defaults and restrictions. They do not change how a form renders on the front end, which template an existing form uses, or how existing submissions behave until you change form settings.

## Group settings

When editing a form group under **Formie → Settings → Form Groups**, you can configure optional group settings across several tabs:

- **General** — name, handle, and allowed submission statuses
- **Field Palette** — optional custom palette (toggle on tab; inherits global when off)
- **Form Defaults**, **Field Defaults**, **Validation Messages**, **Notification Defaults**, **Integration Defaults** — same panels as global **Settings → Defaults**, with blank values inheriting from global settings

Blank group settings inherit from global **Formie → Settings** defaults.

### Allowed submission statuses

Use the **Allowed Submission Statuses** control in **General** to restrict which statuses are available for forms in the group. Choose **All** to allow every status. This policy applies to all forms in the group; individual forms cannot override it.

### Custom field palette

Open the **Field Palette** tab and enable **Use custom field palette**. Forms in this group use that palette instead of the global one. When disabled, the group inherits the global palette from **Settings → Fields**.

### Submission limits

[Submission limits](/forms/submission-limits) are configured **per form**, not at the group level. A form group does not enforce a shared entry cap across all of its forms.

If your group often uses per-user or per-IP limits, preset **Collect User** or **Collect IP** on the group **Form Defaults** tab so new forms inherit the privacy settings those limits rely on.

Global abuse throttling lives under **Settings → Spam Protection** and applies site-wide — it is separate from form submission limits.

## When to use form groups

Form groups are especially helpful when:

- several teams each own a set of forms
- you want sidebar filters instead of one long forms list
- you create new forms into a known bucket by default

If you only have a handful of forms, you can skip groups entirely. The forms index works fine with just **All forms**.

## Managing groups

Create and manage groups under **Formie → Settings → Form Groups**.

Each group has:

- a name
- a handle
- a sort order

Groups are stored in [project config](/get-started/configuration), so they can travel with the project across environments like statuses and stencils. Form group UIDs are used in the forms index source keys (`group:{uid}`), so sidebar filters stay stable after a database sync.

## Forms index

When at least one group exists, the forms index sidebar shows:

- **All forms**
- a **Groups** heading with one source per group
- **Ungrouped** for forms with no group assigned

When no groups exist, only **All forms** is shown.

### Creating a new form

On **All forms** or **Ungrouped**, the **New Form** button works like Craft’s entries and categories indexes: you can pick which group the new form should belong to from the split-button menu.

When you are already viewing a specific group, the primary **New Form** action creates a form in that group. Use the menu to pick a different group if needed.

New forms created from a group source are pre-assigned to that group. You can change the group later in the form builder **Settings** tab.

## Assigning and moving forms

Assign a group per form in the form builder **Settings** tab. The setting is hidden when no groups exist or when editing a stencil.

On the forms index, use the **Move to group** bulk action to move one or more forms into a group or back to **Ungrouped**.

## Submissions index

When groups exist, the submissions index sidebar groups forms under their form group headings, with an **Ungrouped** section only when ungrouped forms are visible.

## Form templates

Form templates control front-end rendering. They are separate from form groups. The forms index no longer groups forms by template; use form groups for CP organisation and templates for rendering.
