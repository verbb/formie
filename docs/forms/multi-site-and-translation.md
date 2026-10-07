# Multi-Site & Translation

::: tip
For a full translation workflow across Craft sites, see [Translating forms across Craft sites](/guides/control-panel-admin/translating-forms-across-craft-sites).
:::

If your Craft project runs [multiple sites](https://craftcms.com/docs/5.x/system/sites.html), Formie activates multi-site features automatically. There is no plugin setting to turn this on or off — when Craft is multi-site, Formie is too.

This page explains the two things Formie handles in a multi-site setup, how to configure them, and what you will see in the control panel and on the front end.

## Different to Your Usual Multi-Site

If you have managed [entries](https://craftcms.com/docs/5.x/system/entries.html) across Craft sites, multi-site forms can feel familiar at first — but there are important differences.

A Formie form shares its fields, pages and settings across the sites where it is available. You can translate labels and messages for each site and change whether an existing field is required there.

- **One layout for all enabled sites.** Fields, handles, pages, conditions, integrations and CAPTCHA settings are shared. Adding a field or reordering pages changes the form on every enabled site.
- **Per-site differences are mostly text.** Each site can have its own labels, messages and similar wording. A field's **Required** state can also differ when a market has a different validation requirement.
- **Handles stay consistent.** A field is always `yourName` (or whatever you set on the source site), because submissions, integrations, notifications and templates use the same handles across sites.
- **Availability is controlled by the form group.** Choose where forms are enabled with the [form group's](/forms/form-groups) **Site Policy**.

For example, an enquiry form can ask the same questions on English and French sites, with translated labels and messages. If the questions need to differ, use **separate forms** restricted to the appropriate sites through groups, or **[conditions](/forms/conditions)** to show or hide fields.

Configure **site availability** to choose where a form appears, and **content translation** to choose its wording on each site.

For how Formie translations relate to static translation files (`formie.php`, `site.php`), see [Translations](/forms/translations).

## Two Separate Ideas

Multi-site work in Formie falls into two buckets. They are related, but they solve different problems:

### Site Availability

This is about whether a form is enabled or disabled for a given Craft site — the same idea as entries or categories in a multi-site install. You configure it in the [form group](/forms/form-groups) **Site Policy** (**Enabled Sites** and **Site Propagation**).

### Content Translation

Content translation covers titles, labels, messages and similar wording. Use the **form builder's site switcher** to edit the values that should differ. A secondary site can also override whether an existing field is **Required**. Values you leave unchanged use the source site's defaults, and all sites keep the same field layout.

A form might be **available** on English and French sites, with **translated** labels on the French site. Or it might only be **available** on a regional site, with no translations because there is only one site to worry about.

Keeping these separate helps avoid a common mistake: restricting a form to one site (availability) is not the same as translating it for another language (content).

## Site Availability

Availability is controlled at the **[form group](/forms/form-groups)** level, under **Formie → Settings → Form Groups → {Your Group} → General → Site Policy**.

There are two controls:

### Enabled Sites

Choose which sites forms in this group are allowed to exist on.

- Check **All** (or leave every site unchecked in the “allow all” pattern) to permit all configured sites. Editors only see choices for sites they can edit.
- Check individual sites — for example, only **Site 2** — to restrict the group to those sites.

When a group is limited to specific sites:

- The group only appears in the forms index sidebar when you are viewing one of those sites.
- **New form** actions for that group are only offered on allowed sites.
- Creating a form on a disallowed site returns an error instead of failing mid-save.

### Site Propagation

Once you know which sites are *allowed*, propagation controls how a form spreads across them when it is created or when group policy changes.

| Mode | What it does | Example |
| --- | --- | --- |
| **All enabled sites** (default) | The form is enabled on every site ticked under **Enabled Sites**. | Enabled Sites = Site 2 and Site 3 → new form exists on both. |
| **Created site only** | The form exists only on the site where it was created. | Created on Site 2 → only Site 2, even if Site 3 is also enabled. |
| **Same language as source site** | From the enabled list, only sites that share the **source site’s** language. The source site is where the form was created. | Created on AU EN (`en-AU`) → other enabled `en-AU` sites get the form. |
| **Same site group as source site** | From the enabled list, only sites in the **source site’s** [site group](https://craftcms.com/docs/5.x/system/sites.html#site-groups). | Created on an Australia site → other enabled Australia-group sites get the form. |

Language and site group modes filter against each form’s **source site** — the site it was created on — not Craft’s global primary site. That means regional form groups that exclude the global primary site still work as expected.

If a propagation mode matches zero enabled sites, Formie blocks the save and shows an error.

Formie updates where the form is enabled whenever you save it or change its group's site policy.

An editor’s site permissions limit where they can manage the form. Saving from one permitted site does not change the group’s availability policy or disable the form on other sites.

### Ungrouped Forms

Forms with no group are available on **all configured sites**. Editors see them in the **Ungrouped** index source on sites they can edit, and can create them from those site contexts.

Use ungrouped forms when the form should be available everywhere. Use a restricted group when it belongs only on certain sites.

### Example: Regional-Only Forms

You run three sites — **Global** (primary), **Australia**, and **New Zealand** — and want a “Contact AU” form only on the Australia site.

1. Create a form group **Australia Forms**.
2. Under **Enabled Sites**, check only **Australia**.
3. Set **Site Propagation** to **All enabled sites** (or **Created site only** if you prefer).
4. Switch the control panel to the Australia site and create the form in that group.

The form will not appear in the Australia group sidebar on Global or New Zealand. It will not render on those sites’ front ends either, because it is not enabled there.

### Forms Index and Site Context

The forms index respects the site you are viewing in the control panel (Craft’s site menu). With site propagation enabled, the list only includes forms enabled for that site.

Switch sites in the CP header to confirm a restricted form appears only where you expect.

## Content Translation

<span id="canonical-content-and-overrides"></span>

### Source Content and Overrides

Each form stores a **source site** — the site it was created on. That site holds the form’s shared structure and default content: field layout, handles, conditions, integrations, and default labels.

When you need different text on another enabled site — a translated title, label, or success message — you add a **site override**. The override applies only to that site. Values you leave unchanged continue to use the source site’s defaults.

On the front end, Formie applies the current site’s overrides to the shared form. You do not need separate forms per language for label changes.

Craft’s global primary site is only relevant when it is also the form’s source site. Regional forms created on non-primary sites use their creation site for their default content.

### What You Can Override

Translatable content includes front-end-facing strings such as:

- Form title
- Page labels and page settings (for example, submit button label)
- Field labels, instructions, placeholders, default values, and validation messages
- Whether an existing field is **Required**
- Static option labels (and values, when you intentionally override both)
- Content shown in HTML and heading fields
- Quiz and survey question copy
- Form messages (error, success, limits, scheduling)
- Notification subject and body in the builder *(see [Notifications](#notifications) below)*

Structural settings are **not** stored per site — field handles, conditions, integrations, captcha choice, and the shape of the layout are shared by every enabled site. You can change them from **any** site in the builder, but those edits update the shared form and apply everywhere, not just the site you are viewing. The **Required** toggle is the deliberate behavioural exception: changing it on a secondary site affects validation for that field placement on that site only.

### Working in the Form Builder

When Craft is multi-site, the form builder adds site-aware editing:

#### Site Switcher

If a form is available on more than one site, a **site switcher** (globe icon and site name) appears in the breadcrumb header area. Use it to preview and edit how the form looks on each site without leaving the builder.

If a form is only available on one site — for example, a group restricted to **Site 2** — the switcher is hidden. There is nothing to switch to.

The switcher only lists sites the form is actually enabled on, not every site in your Craft install.

#### Translation Icons

Beside translatable field labels, a **translation icon** indicates that the value can differ per site. On the source site, you are editing the default value. On other enabled sites, you are editing an override that applies only there.

#### What Gets Saved Where

You can add or remove fields, reorder pages, and change conditions from **any** site — the builder is not locked to the primary site for structural work. What differs is **where** each kind of change is stored when you save:

| You are viewing | What a normal **Save** updates |
| --- | --- |
| **Source site** | The shared form — structure, settings and default content |
| **Another enabled site** | **Site overrides** for wording and whether fields are required; layout and structural edits still update the **shared form** |

So if you add a field while viewing a secondary site, that field appears on every site the form is enabled on. If you only change a label on a secondary site, only the override for that site is updated — the source site’s default label stays the same.

Use the site switcher when you want to check how the form reads and validates for a particular audience, or to edit translated wording. Use any site when you need to change the form’s shape — just keep in mind that structural changes are global.

### Example: Making a Field Optional for One Site

Suppose a shared contact form requires a phone number in every market except New Zealand.

1. Open the form on its source site and make the **Phone number** field required.
2. Use the site switcher to open **New Zealand**.
3. Open the field actions menu and choose **Make optional**.
4. Save the form.

The field remains required on the source site and every site that inherits the source setting. On the New Zealand front end it renders as optional, and server-side validation accepts an empty phone number. Choosing **Make required** again on New Zealand resets the value to the source setting and removes the site override.

### Example: Translating a Contact Form

Your source site is **English**. **French** is another enabled site. Both have the same contact form enabled.

1. Open the form on the **source (English)** site.
2. Set the title to `Contact us` and a field label to `Your name`.
3. Save.
4. Use the site switcher to open **French**.
5. Change the title to `Contactez-nous` and the field label to `Votre nom`.
6. Save again.

Visitors on the French site see `Contactez-nous` and `Votre nom`. Visitors on the English site still see `Contact us` and `Your name`. Any labels you left unchanged on French continue to use the English defaults.

### Nested Fields, Groups, and Repeaters

Child fields inside **Name**, **Address**, **Group**, and **Repeater** fields are translatable the same way as top-level fields. Override the child field’s label (or other translatable property) on the secondary site; siblings you leave unchanged do not create override entries.

### Radio, Dropdown, and Checkbox Options

You can override option **labels** and **values** per site when both need to differ — for example, when the stored value should also be locale-specific. Override only the options that change; unchanged options are inherited from the source site.

## Radically Different Forms per Site

Site overrides are for **different text on the same form**, not different layouts or field sets.

If one site needs a completely different form — different fields, steps, or logic — use one of these approaches:

- **Separate forms**, each in a group scoped to the right sites.
- **Conditions** to show or hide fields based on site (or language) using Craft’s site variables.

## Front-End Rendering

You do not need extra template code for translations. When a form is rendered for a site, Formie applies that site’s overrides automatically.

This applies to:

- `craft.formie.renderForm()` and other [rendering](/templates/rendering-forms) helpers
- Headless and [GraphQL](/graphql/query-forms) form fetches
- The React front-end bootstrap

If a form is **not enabled** for the current site (site availability), it should not render or accept submissions on that site, the same as any Craft element that is disabled for a site.

Submissions continue to store `siteId` so you know which site a submission came from.

## Notifications

Notification templates can be edited per site in the form builder, and overrides are stored with other translation data.

For **sending** email after submission, many projects prefer one of these patterns instead of relying on translated notification bodies:

- **Separate notifications** with [conditions](/forms/conditions) per site or language
- **Variables** in a single notification (for example, site name or language) via the variable picker

That keeps delivery logic explicit and avoids surprises in queued or CLI sends. Choose the approach that fits your project.

## Twig Template Overrides

Twig `setFieldSettings()` overrides apply to the form you render, without changing its saved settings. Settings needed to process the submission are kept when the visitor moves between pages or resumes later. See [Overriding Settings](/templates/overriding-settings).

For permanent per-site wording, use control panel site overrides so content editors can manage copy without deploys.

## Single-Site Projects

If Craft only runs **one site**, none of the above surfaces in the control panel. You will not see a site switcher or translation icons, Formie will not read or write `formie_form_site_overrides`, and forms behave as they always have.

You can ignore this page until you add a second Craft site.
