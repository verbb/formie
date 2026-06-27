# Multi-site notification content

Multi-site forms often need notification emails in the right language with the right recipients — without duplicating every form. Formie supports per-site notification overrides in the builder, conditional notifications, and variable-driven single templates. This guide compares the patterns and recommends one for common setups.

## Prerequisites

- Craft multi-site with Formie forms enabled on multiple sites
- [Email Notifications](/forms/email-notifications)
- [Multi-Site & Translation](/forms/multi-site)
- [Translations](/forms/translations)

## The problem

A contact form runs on English and French sites. You need:

- French confirmation email copy for French submissions
- English copy for English submissions
- Possibly different internal recipients per region

Form field labels use [site overrides](/forms/multi-site#content-translation). Notifications need a deliberate strategy too.

## Pattern 1 — Per-site notification overrides (builder)

Notification subject and body can be edited per site in the form builder. Overrides store in `formie_form_site_overrides` with other translation data.

**Workflow:**

1. Build the notification on the source site (English)
2. Switch site in the builder to French
3. Edit the notification subject and body in French
4. Save

**Pros:** Editor-managed, no deploy, matches how field labels are translated

**Cons:** Queued sends and CLI context must resolve the submission's site correctly (Formie does this when the submission has `siteId`); harder to see all languages at once

**Best for:** Marketing teams managing copy in the CP

## Pattern 2 — Separate notifications with conditions

Create one notification per language (or region), each with a [condition](/forms/conditions) on site or language:

| Notification | Condition | Recipients |
| --- | --- | --- |
| Admin — EN | Site is English | `enquiries@example.com` |
| Admin — FR | Site is French | `demandes@example.com` |
| User confirmation — EN | Site is English | `{field:email}` |
| User confirmation — FR | Site is French | `{field:email}` |

Use Craft site variables in conditions (current site at submit time matches submission site).

**Pros:** Explicit delivery logic; easy to audit in the notifications list; clear enable/disable per language

**Cons:** More rows in the notifications tab; condition edits require builder access

**Best for:** Different recipients per site, or when legal requires separate templates per jurisdiction

## Pattern 3 — Single notification with variables

One notification body using the [variable picker](/developers/reference-tokens):

```text
Thank you for contacting {site:name}.

{allFields}
```

Or custom Twig in an [email template](/guides/email-notifications/building-an-email-notification-template-from-scratch) that branches on `{site:handle}` or locale.

**Pros:** One notification to maintain; good when only small differences (site name, footer address)

**Cons:** Complex branching in one template gets messy; translators cannot use the simple content editor for full bodies

**Best for:** Minor per-site differences, shared layout

## What about translation files?

Do **not** put notification subject/body in `translations/*/formie.php`. Notification copy edited in the builder belongs in the database (or site overrides) — same rule as field labels. See [Translations](/forms/translations).

Plugin-owned strings in email *templates* (button labels, footer legal boilerplate shared across notifications) can use `site.php` or Twig `craft.app.language`.

## Submission site matters

Submissions store `siteId`. When Formie sends notifications:

1. The submission's site determines which [site overrides](/forms/multi-site) merge into field values in `{allFields}` output
2. Conditions evaluate against submission context
3. Variable tokens resolve for that site

Ensure headless submits pass the correct `siteId` when creating submissions via GraphQL.

## Queue and CLI behaviour

With `useQueueForNotifications` enabled (recommended), notifications queue after submit. The queued job carries submission ID and site context — per-site overrides apply the same as synchronous sends.

Test both:

- Front-end submit on each site
- Control panel test send while viewing each site in the builder

## Recommended starting point

| Scenario | Pattern |
| --- | --- |
| EN/FR labels only, same recipients | Pattern 1 (overrides) or Pattern 3 (variables) |
| Different inboxes per country | Pattern 2 (conditions) |
| Legal requires separate templates | Pattern 2 |
| Headless multi-site SPA | Pattern 2 + explicit `siteId` on submit |

Many projects combine Pattern 2 for admin emails (fixed recipients per site) with Pattern 1 for user confirmations (translated copy).

## Deliverability reminder

Multi-site does not change mail best practices — use a consistent **From** you control, put the submitter in **Reply-To**, and set up SPF/DKIM/DMARC. See [How to keep Email Notifications out of your junk emails](/guides/email-notifications/how-to-keep-email-notifications-out-of-your-junk-emails).

## Related

- [Email Notifications](/forms/email-notifications)
- [Multi-Site & Translation](/forms/multi-site)
- [Translating forms across Craft sites](/guides/control-panel-admin/translating-forms-across-craft-sites)
- [How to keep Email Notifications out of your junk emails](/guides/email-notifications/how-to-keep-email-notifications-out-of-your-junk-emails)
