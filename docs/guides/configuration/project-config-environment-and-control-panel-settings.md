# Project config, environment, and control panel settings

Formie does not store everything in one place. Some settings version with your Craft project, some live in `config/formie.php` on each server, and some stay in the database so privileged admins can edit them in production even when `allowAdminChanges` is off.

This guide gives you a mental model for **where to change a setting** and **what happens when you deploy** — so staging promotions do not silently break captchas, spam rules, or integrations on production.

## Prerequisites

- [Configuration](/get-started/configuration) — full list of `formie.php` options
- [Spam Protection](/forms/spam-protection)
- Craft [project config](https://craftcms.com/docs/5.x/system/project-config.html) basics

## The problem this solves

These situations usually mean a setting is in the wrong scope — or you expected it to sync when it does not:

- Production Turnstile keys disappeared after `project-config/sync`
- Spam keywords on staging never showed up on production
- OAuth integrations worked on staging but fail after a database copy
- A form group change deployed, but the contact form layout did not

The fix is not memorising every table and YAML key. It is knowing which of three scopes owns the thing you are changing.

## Three scopes — one sentence each

| Scope | One-line summary |
| --- | --- |
| **Project config** | Structure you want in git — form groups, project stencils, statuses, reports |
| **Environment config** | Developer-controlled behaviour in `config/formie.php` — queues, defaults, dev tunnels |
| **Control panel settings** | Secrets and policy admins edit in the CP — spam keywords, captcha keys, integration credentials |

Forms themselves (fields, notifications, layout) are **database content**. They do not move with project config alone. Use [Import and export between environments](/guides/control-panel-admin/import-and-export-between-environments) to promote form structure.

## Walkthrough — promoting staging to production

Imagine you finished work on staging and you are about to deploy. Here is what each scope does in that moment.

### 1. Project config syncs structure

When production runs `project-config/sync`, Formie updates versioned resources such as:

- Form groups (including which sites a group applies to)
- Project stencils
- Submission and form statuses
- Reports and scheduled report definitions

These are the things your team typically wants identical across environments — checked into `config/project/` and deployed with Craft.

Individual forms still point at a group by UID in the database; the group definition comes from project config.

### 2. Environment config applies on the server

`config/formie.php` is not synced by project config. Each environment reads its own file (or env-specific sections within it).

Typical per-environment differences:

- `paymentWebhookProxyUrl` — dev tunnel vs real URLs on production
- `redirectUri` — OAuth callback base URL
- `useQueueForNotifications` / `useQueueForIntegrations` — queue behaviour
- `compatibilityMode` — usually off on production after an upgrade
- `formDefaults` — default submit method, retention, and similar plugin-wide defaults

See [Configuration](/get-started/configuration) for every option. The point for deployment: **changing `formie.php` on staging does nothing to production until you deploy that file**.

### 3. Control panel settings stay environment-local

Spam keywords, captcha provider secrets, and integration API keys live in dedicated database tables — **Settings → Spam Protection** and the integrations control panel — not in `plugins.formie.settings` project YAML.

That is deliberate:

- Privileged admins can update production captcha keys when the CP is otherwise read-only
- Secrets do not land in git through project config exports
- Some rows can be project-scoped defaults (seeded on deploy) while site-scoped rows stay local

So after `project-config/sync`, **these database rows are not replaced wholesale**. If you copied a staging database to production, you may have copied staging secrets — which is often wrong. Re-enter production captcha keys and reconnect OAuth integrations on the target environment.

For keyword syntax and examples, see [Spam keywords in detail](/guides/configuration/spam-keywords-in-detail).

### 4. Forms do not ride along with project config

Form layout, notifications, fields, and per-site label overrides are database content. Promoting them is a separate step — export JSON on staging, import on production — not something project config handles.

Site **availability** for forms is tied to form group site policy (project config) plus `elements_sites` when you save a form. The form's fields and messages are still database rows.

## Decision guide — where do I change this?

Use this when you know *what* you want to change but not *where*:

| I want to change… | Where |
| --- | --- |
| Default submit method for new forms | `formDefaults` in `formie.php`, or **Settings → Defaults** |
| Spam keywords | **Settings → Spam Protection** |
| Turnstile or reCAPTCHA secret | **Settings → Spam Protection → Captchas** |
| Which sites a form group applies to | **Settings → Form Groups** (project config) |
| A reusable starter layout for new forms | **Stencils** — CP stencils in the database, or project stencils in YAML ([Stencils guide](/guides/control-panel-admin/stencils-for-repeatable-form-types)) |
| Fields and notifications on a live form | Form builder (database) — or import/export between environments |
| French field labels on one site | Form builder site switcher (database overrides) |
| Stripe webhook proxy for local dev | `paymentWebhookProxyUrl` in `formie.php` |
| Mailchimp OAuth token | Integrations CP — reconnect after DB copy |

Integration settings can reference Craft env syntax (`$STRIPE_SECRET_KEY`) in the CP; values are resolved when Formie reads them, not stored in exported project YAML.

## After copying a database between environments

Database copies are common for staging refreshes or bad promotions. Treat this as a checklist, not an automatic sync:

- Re-enter captcha secrets and integration tokens (or confirm env-var-backed settings resolve correctly)
- Review spam keywords — staging test rules should not block real users on production
- Reconnect OAuth integrations (tokens are environment-specific)
- Verify form handles and group assignments still make sense on the target site set

Project config sync alone does not undo a bad database copy.

## Stencils — two types, two scopes

Stencils confuse people because both kinds look similar in the CP:

| Type | Scope | On production with `allowAdminChanges: false` |
| --- | --- | --- |
| CP stencil | Database | Editable when admin changes are allowed |
| Project stencil | Project config | View-only; use **Save a copy** to fork into a CP stencil |

Handles must be unique across both types. See [Stencils for repeatable form types](/guides/control-panel-admin/stencils-for-repeatable-form-types) for when to use each.

## Security habits worth keeping

- Prefer env vars for API keys where the CP supports `$VAR` syntax
- Do not commit CP-stored secrets into project YAML
- Keep `allowedGraphqlOrigins` narrow for headless forms
- Leave `enableCsrfValidationForGuests` enabled unless you have a documented exception
- Turn off `compatibilityMode` on production once deprecations are clear ([template compatibility audit](/guides/migrations-upgrades/template-compatibility-audit-after-upgrade))

## Related

- [Configuration](/get-started/configuration)
- [Spam Protection](/forms/spam-protection)
- [Spam keywords in detail](/guides/configuration/spam-keywords-in-detail)
- [Import and export between environments](/guides/control-panel-admin/import-and-export-between-environments)
- [Stencils for repeatable form types](/guides/control-panel-admin/stencils-for-repeatable-form-types)
