# Multi-Site & Translation

If your Craft project runs [multiple sites](https://craftcms.com/docs/5.x/system/sites.html), Formie activates multi-site features automatically. There is no plugin setting to turn this on or off — when Craft is multi-site, Formie is too.

This page explains the two things Formie handles in a multi-site setup, how to configure them, and what you will see in the control panel and on the front end.

## Different to your usual multi-site

If you have managed [entries](https://craftcms.com/docs/5.x/system/entries.html) across Craft sites, multi-site forms can feel familiar at first — but there are important differences.

With **entries**, each site gets its own slice of the element. You can enable or disable an entry per site, and for every enabled site you edit a full set of fields: title, slug, body, and any custom fields. The English and French versions of a news article can share nothing except the entry ID. They are genuinely separate content.

**Formie forms do not work that way.** A form is closer to a shared blueprint than a bundle of independent per-site documents.

- **One layout for all enabled sites.** The field tree, handles, pages, conditions, integrations, and captcha settings are canonical. Every site that has the form enabled runs the same structure. You cannot give the French site an extra field that the English site does not have, or reorder pages on one site only, using site overrides alone.
- **Per-site differences are mostly text.** Secondary sites store sparse overrides for labels, messages, and similar copy. The database does not hold a full duplicate of the form per site.
- **Handles stay consistent.** A field is always `yourName` (or whatever you set on the primary site), because submissions, integrations, notifications, and templates all rely on stable handles across sites.
- **Availability is group-level, not per form.** Whether a form exists on a site is controlled by the [form group](/forms/form-groups) **Site Policy**, not a “Sites” tab on the form itself. That part *does* line up with Craft’s mental model — Formie toggles `elements_sites` rows so a form can be enabled on Site A and disabled on Site B — but the *content* model is not entry-style.

That design matches how forms are usually used in multi-site projects: the same enquiry or checkout flow everywhere, with translated labels and messages, not a completely different form definition per locale. When you do need something structurally different, the supported paths are **separate forms** (each scoped to the right sites via groups) or **[conditions](/forms/conditions)** to show or hide fields — not a second full layout hidden inside one form record.

The sections below split the two concerns Formie *does* handle: **site availability** (Craft-like enable/disable) and **content translation** (shared structure, local wording).

## Two separate ideas

Multi-site work in Formie falls into two buckets. They are related, but they solve different problems:

### Site availability

**“Should this form exist on this site at all?”**

This is about whether a form is enabled or disabled for a given Craft site — the same idea as entries or categories in a multi-site install. You configure it in the [form group](/forms/form-groups) **Site Policy** (**Enabled Sites** and **Site Propagation**).

### Content translation

**“What text should visitors see on this site?”**

This is about wording — titles, labels, messages, and similar copy — while the underlying form structure stays shared. You configure it in the **form builder**, switching between sites and editing the strings that differ. Only values you change are stored, as sparse JSON in `formie_form_site_overrides`. Every site shares one canonical field layout; secondary sites can override specific text on top of it.

A form might be **available** on English and French sites, with **translated** labels on the French site. Or it might only be **available** on a regional site, with no translations because there is only one site to worry about.

Keeping these separate helps avoid a common mistake: restricting a form to one site (availability) is not the same as translating it for another language (content).

## Site availability

Availability is controlled at the **[form group](/forms/form-groups)** level, under **Formie → Settings → Form Groups → {Your Group} → General → Site Policy**.

There are two controls:

### Enabled Sites

Choose which sites forms in this group are allowed to exist on.

- Check **All** (or leave every site unchecked in the “allow all” pattern) to permit any site you can edit in the control panel.
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
| **Same language as primary site** | From the enabled list, only sites that share the primary site’s language. | Primary is `en-US`; only other `en-US` sites get the form. |
| **Same site group as primary site** | From the enabled list, only sites in the primary site’s [site group](https://craftcms.com/docs/5.x/system/sites.html#site-groups). | Useful when primary and regional sites are grouped separately. |

Formie syncs Craft’s `elements_sites` table when a form is saved or when group policy changes, so the form’s enabled state matches the policy.

### Ungrouped forms

Forms with no group are treated as available on **all sites you can edit**. They appear in the **Ungrouped** index source on every site, and you can create them from any site context.

Use ungrouped forms when the same form should genuinely be global. Use a restricted group when only certain sites should see or manage forms in that bucket.

### Example: regional-only forms

You run three sites — **Global** (primary), **Australia**, and **New Zealand** — and want a “Contact AU” form only on the Australia site.

1. Create a form group **Australia Forms**.
2. Under **Enabled Sites**, check only **Australia**.
3. Set **Site Propagation** to **All enabled sites** (or **Created site only** if you prefer).
4. Switch the control panel to the Australia site and create the form in that group.

The form will not appear in the Australia group sidebar on Global or New Zealand. It will not render on those sites’ front ends either, because it is not enabled there.

### Forms index and site context

The forms index respects the site you are viewing in the control panel (Craft’s site menu). With site propagation enabled, the list only includes forms enabled for that site.

Switch sites in the CP header to confirm a restricted form appears only where you expect.

## Content translation

### Canonical content and overrides

Formie stores **one canonical copy** of each form’s structure and content, tied to Craft’s **primary site**. That includes the field layout, handles, conditions, integrations, and default labels.

When you need different text on another site — a translated title, label, or success message — you add a **site override**. Overrides are stored separately in `formie_form_site_overrides`, keyed by form and site. Only values you actually change are stored; unchanged fields are not duplicated.

On the front end, Formie merges overrides on top of the canonical form for the current site. You do not need separate forms per language for label changes.

### What you can translate

Translatable content includes front-end-facing strings such as:

- Form title
- Page labels and page settings (for example, submit button label)
- Field labels, instructions, placeholders, default values, and validation messages
- Static option labels (and values, when you intentionally override both)
- Content shown in HTML and heading fields
- Quiz and survey question copy
- Form messages (error, success, limits, scheduling)
- Notification subject and body in the builder *(see [Notifications](#notifications) below)*

Structural settings are **not** stored per site — field handles, conditions, integrations, captcha choice, and the shape of the layout are shared by every enabled site. You can change them from **any** site in the builder, but those edits update the canonical form and apply everywhere, not just the site you are viewing.

### Working in the form builder

When Craft is multi-site, the form builder adds site-aware editing:

#### Site switcher

If a form is available on more than one site, a **site switcher** (globe icon and site name) appears in the breadcrumb header area. Use it to preview and edit how the form looks on each site without leaving the builder.

If a form is only available on one site — for example, a group restricted to **Site 2** — the switcher is hidden. There is nothing to switch to.

The switcher only lists sites the form is actually enabled on, not every site in your Craft install.

#### Translation icons

Beside translatable field labels, a **translation icon** indicates that the value can differ per site. On the primary site, you are editing the canonical default. On other sites, you are editing an override that applies only there.

#### What gets saved where

You can add or remove fields, reorder pages, and change conditions from **any** site — the builder is not locked to the primary site for structural work. What differs is **where** each kind of change is stored when you save:

| You are viewing | What a normal **Save** updates |
| --- | --- |
| **Primary site** | The canonical form — structure, settings, and default translatable content |
| **Another site** | Sparse **translation overrides** for any translatable strings you changed; layout and structural edits still update the **shared** canonical form |

So if you add a field while viewing a secondary site, that field appears on every site the form is enabled on. If you only change a label on a secondary site, only the override for that site is updated — the primary site’s default label stays the same.

Use the site switcher when you want to check how the form reads on a particular audience, or to edit translated wording. Use any site when you need to change the form’s shape — just keep in mind that structural changes are global.

### Example: translating a contact form

Your primary site is **English**. **French** is a secondary site. Both have the same contact form enabled.

1. Open the form on the **primary (English)** site.
2. Set the title to `Contact us` and a field label to `Your name`.
3. Save.
4. Use the site switcher to open **French**.
5. Change the title to `Contactez-nous` and the field label to `Votre nom`.
6. Save again.

In the database, the French site override will contain only the keys that changed — not a full copy of the form. On the French front end, visitors see the French strings. On the English site, they see the canonical English strings.

### Nested fields, groups, and repeaters

Child fields inside **Name**, **Address**, **Group**, and **Repeater** fields are translatable the same way as top-level fields. Override the child field’s label (or other translatable property) on the secondary site; siblings you leave unchanged do not create override entries.

### Radio, dropdown, and checkbox options

You can override option **labels** and **values** per site when both need to differ — for example, when the stored value should also be locale-specific. Override only the options that change; unchanged options are inherited from the canonical form.

## Radically different forms per site

Site overrides are for **different text on the same form**, not different layouts or field sets.

If one site needs a completely different form — different fields, steps, or logic — use one of these approaches:

- **Separate forms**, each in a group scoped to the right sites.
- **Conditions** to show or hide fields based on site (or language) using Craft’s site variables.

## Front-end rendering

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

## Twig template overrides

Twig `setFieldSettings()` overrides in templates are **runtime-only** for that render. They do not persist in the database.

For permanent per-site wording, use control panel site overrides so content editors can manage copy without deploys.

## Single-site projects

If Craft only runs **one site**, none of the above surfaces in the control panel. You will not see a site switcher or translation icons, Formie will not read or write `formie_form_site_overrides`, and forms behave as they always have.

You can ignore this page until you add a second Craft site.
