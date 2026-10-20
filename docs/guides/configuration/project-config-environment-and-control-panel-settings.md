# Project Config, Environment, and Control Panel Settings

Formie does not store everything in one place. Some settings version with your Craft project, some live in `config/formie.php` on each server, and some stay in the database so privileged admins can edit them in production even when `allowAdminChanges` is off.

This guide explains where to change settings and what moves when you deploy from staging to production.

## Prerequisites

- [Configuration](/get-started/configuration) — full list of `formie.php` options
- [Spam Protection](/forms/spam-protection)
- Craft [project config](https://craftcms.com/docs/5.x/system/project-config.html) basics

## The Problem This Solves

These situations usually mean a setting is in the wrong scope — or you expected it to sync when it does not:

- Production Turnstile keys changed after copying the staging database
- Spam keywords on staging never showed up on production
- OAuth integrations worked on staging but fail after a database copy
- A form group change deployed, but the contact form layout did not

Before deploying, establish where the configuration is stored and which environment should manage it.

<span id="three-scopes-—-one-sentence-each"></span>

## Where Settings Are Stored

**Project config** stores configuration you commit and deploy with Craft, including form groups, project integrations and project stencils. **Environment config** provides developer-controlled overrides in `config/formie.php`, such as queue behaviour and development webhook URLs.

**Instance-level settings** stay in the current installation’s database. An **instance** is a Craft installation, such as development, staging or production, rather than an individual site in a multisite installation. Editing something in the control panel does not determine its storage: project-managed configuration is edited there too.

## Walkthrough — Promoting Staging to Production

Imagine you finished work on staging and you are about to deploy. Here is what each scope does in that moment.

### 1. Project Config Syncs Structure

When production runs `project-config/sync`, Formie updates versioned resources such as:

- Form groups (including which sites a group applies to)
- Project stencils
- Project integrations created with **New project integration**
- Submission and form statuses
- Reports and scheduled report definitions

These are the things your team typically wants identical across environments — checked into `config/project/` and deployed with Craft.

Individual forms still point at a group by UID in the database; the group definition comes from project config.

### 2. Environment Config Applies on the Server

`config/formie.php` is not synced by project config. Each environment reads its own file (or env-specific sections within it).

Typical per-environment differences:

- `paymentWebhookProxyUrl` — dev tunnel vs real URLs on production
- `redirectUri` — OAuth callback base URL
- `useQueueForNotifications` / `useQueueForIntegrations` — queue behaviour
- `formDefaults` — default submit method, retention, and similar plugin-wide defaults

See [Configuration](/get-started/configuration) for every option. The point for deployment: **changing `formie.php` on staging does nothing to production until you deploy that file**.

<span id="_3-control-panel-settings-stay-environment-local"></span>

### 3. Instance-Level Settings Stay Local

Project config sync leaves instance-level integrations, stencils, spam rules and provider settings unchanged, whether project configuration is updated or removed. Users with the relevant permissions can manage these local settings even when `allowAdminChanges` is `false`.

Integrations and stencils offer separate instance-level and project creation choices. Spam rules and CAPTCHA/spam filtering provider settings always stay in the installation’s database. Configure them on production to let authorised clients manage them there. See [integration scope](/integrations/overview#integrations-and-project-integrations), [stencil scope](/forms/stencils#project-stencils-developers) and [production spam protection setup](/forms/spam-protection#setting-up-production).

A database copy can overwrite local settings and content. Follow the [checks after copying a database](#after-copying-a-database-between-environments) before accepting submissions on the target environment.

### 4. Forms Do Not Ride Along with Project Config

Form layout, notifications, fields, and per-site label overrides are database content. Promoting them is a separate step — export JSON on staging, import on production — not something project config handles.

Site **availability** for forms is tied to form group site policy (project config) plus `elements_sites` when you save a form. The form's fields and messages are still database rows.

## Decision Guide — Where Do I Change This?

Use this when you know *what* you want to change but not *where*:

| I want to change… | Where |
| --- | --- |
| Default submit method for new forms | `formDefaults` in `formie.php`, or **Settings → Defaults** |
| Spam keywords | **Spam Protection → Spam Rules** — stored in the installation’s database |
| Turnstile or reCAPTCHA secret | **Spam Protection → CAPTCHAs** |
| Which sites a form group applies to | **Settings → Form Groups** (project config) |
| A reusable starter layout for new forms | **Formie → Stencils** — choose instance-level or project storage ([Stencil scope](/forms/stencils#project-stencils-developers)) |
| Fields and notifications on a live form | Form builder (database) — or import/export between environments |
| French field labels on one site | Form builder site switcher (database overrides) |
| Stripe webhook proxy for local dev | `paymentWebhookProxyUrl` in `formie.php` |
| A client-managed integration connection | **Formie → Integrations → New Integration** (database) |
| An integration connection deployed with the project | **Formie → Integrations**, then **New project integration** from the button’s arrow menu (project config) |
| Mailchimp OAuth token | Integrations CP — reconnect after DB copy |

Before deploying a project integration, configure its [credentials and OAuth connection](/integrations/overview#integrations-and-project-integrations) for the target environment. Deploying its configuration does not transfer OAuth tokens.

## After Copying a Database Between Environments

Database copies are common for staging refreshes or bad promotions. Treat this as a checklist, not an automatic sync:

- Re-enter captcha secrets and integration tokens (or confirm env-var-backed settings resolve correctly)
- Review spam keywords — staging test rules should not block real users on production
- Reconnect OAuth integrations (tokens are environment-specific)
- Verify form handles and group assignments still make sense on the target site set

Project config sync alone does not undo a bad database copy.

## Stencils — Two Types, Two Scopes

Deploying a project stencil makes its starter template available on the target environment. It does not deploy forms created from that stencil or instance-level copies made with **Save a copy**. Follow [Stencils for Repeatable Form Types](/guides/control-panel-admin/stencils-for-repeatable-form-types#step-8-—-deploy-across-environments) for the deployment sequence and checks.

## Security Habits Worth Keeping

- Prefer env vars for API keys where the CP supports `$VAR` syntax
- Do not commit CP-stored secrets into project YAML
- Keep `allowedGraphqlOrigins` narrow for headless forms
- Leave `enableCsrfValidationForGuests` enabled unless you have a documented exception
