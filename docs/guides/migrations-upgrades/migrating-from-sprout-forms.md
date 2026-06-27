# Migrating from Sprout Forms

If your Craft site uses [Sprout Forms](https://sprout.barrelstrengthdesign.com/docs/forms/), Formie's migration tool copies forms, email notifications, and submissions into Formie without modifying Sprout data. Sprout Forms layouts are typically simpler than Freeform — migration is often straightforward with few unsupported field types.

## Prerequisites

- Formie installed
- Sprout Forms still installed and **enabled**
- Database backup before production migration

## Before you migrate

1. **Inventory forms** — handles, notification rules, front-end embed locations
2. **Export Sprout submissions** if you need CSV archives beyond what migration copies
3. **Run on staging** with both plugins enabled
4. **Plan template updates** — Sprout render syntax differs from Formie

## Run the migration

1. Navigate to **Formie → Settings → Migrations → Sprout Forms**
2. Select Sprout forms to migrate
3. Click **Migrate Forms**
4. Review results — copied forms, notifications, submissions, and errors

Sprout Forms data is not modified.

## Handle collisions

Duplicate handles get a numeric suffix — `contactForm` becomes `contactForm1` if Formie already owns `contactForm`. Rename in the Formie builder and update templates.

## Unsupported fields

| Sprout Forms field | Formie approach |
| --- | --- |
| Private Notes | Use HTML cosmetic field or editor note — not a direct equivalent |
| Regular Expression | Use Single-Line Text with validation pattern |

Most common Sprout field types (Single Line, Paragraph, Dropdown, Email, Number, Checkboxes, Radio) migrate cleanly.

## After migration checklist

- Verify each form in the form builder
- Update Twig from Sprout embed syntax to `craft.formie.renderForm()`
- Configure spam protection (**Settings → Spam Protection**) — Sprout's approach may differ
- Set up email notifications if Sprout used different notification mechanics
- Test front-end submit and admin notification delivery
- Compare submission counts
- Update any Sprout-specific reporting or exports to Formie submissions index

## Front-end cutover

Sprout templates often use:

```twig
{# Sprout — replace after migration #}
{{ craft.sproutForms.renderForm('handle') }}
```

Formie equivalent:

```twig
{{ craft.formie.renderForm('handle') }}
```

For headless sites, follow [Building a headless contact form with Formie React](/guides/frontend-headless/building-a-headless-contact-form-with-formie-react).

Include Formie front-end assets — see [Frontend Assets](/frontend/frontend-assets). Sprout's JS/CSS do not carry over.

## Notifications

Migrated notifications become Formie email notifications. Verify:

- **From** / **Reply-To** — see [deliverability guide](/guides/email-notifications/how-to-keep-email-notifications-out-of-your-junk-emails)
- Variable tokens — Formie uses [field references](/developers/reference-tokens), not Sprout's token syntax; re-pick fields in the notification editor
- Conditions — rebuild if Sprout used rule-based sending

## When to keep Sprout temporarily

Run parallel if Sprout forms are embedded in hard-to-update legacy templates. Hide Sprout forms from navigation while Formie forms go live on new templates.

Uninstall Sprout Forms only after all active forms migrate and historical submissions are accessible in Formie (or exported).

## Related

- [Migrating from Freeform](/guides/migrations-upgrades/migrating-from-freeform)
- [Major upgrade — real-project checklist](/guides/migrations-upgrades/major-upgrade-real-project-checklist)
- [Template compatibility audit after upgrade](/guides/migrations-upgrades/template-compatibility-audit-after-upgrade)
