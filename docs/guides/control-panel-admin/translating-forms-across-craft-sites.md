# Translating forms across Craft sites

Multi-site Craft projects usually need the same form structure everywhere with different wording per site — not a completely different form per language. Formie stores one canonical layout and sparse per-site overrides for translatable text. This walkthrough takes a contact form from English source copy to French overrides without duplicating forms or fighting translation files.

## Prerequisites

- Craft multi-site enabled with at least two sites
- [Multi-Site & Translation](/forms/multi-site)
- [Translations](/forms/translations) — three layers of text in Formie

## Two separate concerns

Keep these distinct:

| Concern | Question | Where to configure |
| --- | --- | --- |
| **Site availability** | Should this form exist on this site? | [Form group](/forms/form-groups) **Site Policy** |
| **Content translation** | What text should visitors see? | Form builder **site switcher** |

A form can be available on English and French with translated labels. Or available on one regional site only with no translations needed.

Formie forms are **not** entry-style per-site documents. One field layout, handles, conditions, and integrations are shared. Per-site differences are mostly text.

## Step 1 — Scope the form to the right sites

Create or assign a [form group](/forms/form-groups) with the correct **Enabled Sites** and **Site Propagation** before authors build content.

Example — global contact form on EN and FR:

1. Group **Global Forms** with both sites enabled
2. Propagation: **All enabled sites**
3. Create the form on the English (source) site

Example — Australia-only form:

1. Group **Australia Forms** with only Australia enabled
2. Create the form while viewing the Australia site in the CP

Ungrouped forms are treated as available on all sites you can edit.

## Step 2 — Build canonical copy on the source site

Each form has a **source site** — where it was created. That site holds default translatable content.

1. Switch the CP to the source site (usually your primary English site)
2. Open the contact form
3. Set title `Contact us`, field label `Your name`, success message, button labels
4. Save

Structural work (adding fields, conditions, integrations) updates the **shared canonical form** regardless of which site you are viewing when you save.

## Step 3 — Add French overrides

1. Use the **site switcher** (globe icon) in the form builder breadcrumb
2. Select **French**
3. Change title to `Contactez-nous`, field label to `Votre nom`, and other French copy
4. Save

Only changed keys are stored in `formie_form_site_overrides`. The English source site defaults stay untouched.

Translation icons beside field labels indicate overridable values. On the source site you edit the default; on other sites you edit overrides.

## Step 4 — Translate nested and option fields

**Name, Address, Group, Repeater** — child field labels translate the same way as top-level fields. Override only the children that change.

**Dropdown, radio, checkbox** — override option **labels** and **values** per site when both need to differ. Leave unchanged options inherited from the canonical form.

**Page settings** — submit, back, and save button labels on each page are translatable.

**Form messages** — error, success, limit, and scheduling messages support per-site overrides.

## Step 5 — Verify front-end rendering

No extra template code is required. When you render for a site, Formie merges overrides automatically:

```twig
{{ craft.formie.renderForm('contactForm') }}
```

This applies to:

- Twig rendering
- [GraphQL](/graphql/query-forms) and headless packages (`siteId`, `locale` props)
- React/Vue bootstrap

Submissions store `siteId` so you know which site they came from.

## Step 6 — What not to put in translation files

Form builder content does **not** belong in `translations/*/formie.php`:

```php
// Don’t translate form builder copy here — use CP site overrides instead
'Your name' => 'Votre nom',
'Contact us' => 'Contactez-nous',
```

Keep `formie.php` for **Formie-owned UI strings** only:

```php
// Plugin UI strings
'{label} cannot be blank.' => '…',
'(optional)' => '…',
```

See [Translations](/forms/translations) for the full split.

## Step 7 — Notifications

Notification subject and body can be edited per site in the builder, with overrides stored alongside other translation data.

Many teams prefer instead:

- **Separate notifications** with [conditions](/forms/conditions) per site or language
- **Variables** in one notification (`{site:name}`, and so on)

See [Multi-site notification content](/guides/email-notifications/multi-site-notification-content).

## When you need different layouts per site

Site overrides are for **different text on the same form**, not different field sets.

For radically different forms:

- **Separate forms** in groups scoped to the right sites
- **[Conditions](/forms/conditions)** to show/hide fields based on site variables

## Troubleshooting

**French site shows English labels**

Add French site overrides in the builder — not entries in `formie.php`.

**Two English sites need different labels**

Use CP site overrides. A single `translations/en/formie.php` cannot distinguish Craft sites.

**Field added on French site appeared on English too**

Structural changes are global. Only text overrides are per-site.

## Related

- [Multi-Site & Translation](/forms/multi-site)
- [Translations](/forms/translations)
- [Form groups and defaults at scale](/guides/control-panel-admin/form-groups-and-defaults-at-scale)
- [Multi-site notification content](/guides/email-notifications/multi-site-notification-content)
