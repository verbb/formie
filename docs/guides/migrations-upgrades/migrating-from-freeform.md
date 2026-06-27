# Migrating from Freeform

If your Craft site runs [Solspace Freeform](https://docs.solspace.com/craft/freeform/v5/), Formie's migration tool copies forms, email notifications, and submissions into Formie without modifying Freeform data. This guide walks through the migration UI, handle collisions, unsupported fields, and what to verify before switching production forms to Formie.

## Prerequisites

- Formie installed
- Freeform still installed and **enabled** (required for the migration screen to appear)
- Backup of database and `storage/` before migrating production

## Supported Freeform versions

- Freeform 3.x
- Freeform 4.x
- Freeform 5.x

## Before you migrate

1. **Audit forms** — list handles, integrations, and payment flows in Freeform
2. **Note unsupported fields** — see below; plan manual rebuilds
3. **Install Formie** on staging first; keep Freeform enabled until verification completes
4. **Review notifications** — Freeform admin notifications map to Formie email notifications; test deliverability after migration
5. **Plan front-end cutover** — replace Freeform render tags with `craft.formie.renderForm()` or headless packages

## Run the migration

1. Navigate to **Formie → Settings → Migrations → Freeform**
2. Select the Freeform forms to migrate
3. Click **Migrate Forms**
4. Review the results screen — forms, notifications, and submissions copied, plus any errors

Your Freeform data remains untouched. Migration creates **new** Formie forms.

## Handle collisions

If Formie already has a form with handle `contactForm` and Freeform also has `contactForm`, the migrated form becomes `contactForm1` (numeric suffix).

Rename handles in the Formie builder after migration if you need cleaner names — update templates and integrations that reference the handle.

## Unsupported fields

The following Freeform field types are **not** migrated and require manual recreation in Formie:

| Freeform field | Notes |
| --- | --- |
| Payments (Pro) | Rebuild with Formie [Payment](/fields/payment) + [Stripe](/integrations/payments/stripe) |
| Opinion Scale (Pro) | Use Radio, Dropdown, or custom field |
| Rating (Pro) | Use custom field or third-party |
| Regex (Pro) | Use validation rules on Single-Line Text |
| Rich Text (Pro) | Use Multi-Line Text with rich text enabled |
| Dynamic Recipient | Use Recipients field or notification conditions |
| Mailing List | Use email marketing integration |
| Recaptcha | Enable Turnstile/reCAPTCHA under **Settings → Spam Protection** |

Submissions for unsupported fields may lose that column's data — export from Freeform first if you need archives.

## After migration checklist

- Open each migrated form in the builder — verify field layout and handles
- Reconfigure captchas on **Settings → Spam Protection** and per form
- Reconnect CRM/marketing integrations (OAuth tokens do not copy)
- Update Twig templates from Freeform tags to Formie render helpers
- Update headless/GraphQL clients to Formie endpoints
- Test submit on staging — notifications, integrations, spam screening
- Compare submission counts Freeform vs Formie for migrated forms
- Run [template compatibility audit](/guides/migrations-upgrades/template-compatibility-audit-after-upgrade) if you are upgrading Formie at the same time

## Cutover strategy

**Parallel run (recommended):** Keep Freeform forms on hidden URLs while Formie forms go live on production pages. Compare submissions for a week, then disable Freeform render tags.

**Hard cutover:** Swap templates in one deploy during low traffic. Have Freeform export/submission archive ready.

Disable or uninstall Freeform only after you are confident Formie owns all active forms.

## After migration

Migrated forms land as standard Formie forms — submission guards, spam settings, client events, and database-backed save-and-continue apply immediately. Review:

- [Spam Protection](/forms/spam-protection) — legacy Freeform honeypot maps to submission guards
- [Submission Screening](/forms/submission-screening) — understand the `screen` stage
- [Upgrading From v3](/get-started/upgrading-from-v3) if you are also upgrading from an older Formie install

## Related

- [Major upgrade — real-project checklist](/guides/migrations-upgrades/major-upgrade-real-project-checklist)
- [Template compatibility audit after upgrade](/guides/migrations-upgrades/template-compatibility-audit-after-upgrade)
