# Import and export between environments

Form import and export moves **form definitions** between environments — not submission data. Use it when staging a new form for production, cloning a client's enquiry form to another project, or backing up structure before a major edit.

## Prerequisites

- [Import & Export](/forms/import-export)
- Access to **Formie → Settings → Import/Export** on both environments

## What export includes

Exporting a form produces a JSON file containing:

- Form details and settings
- Pages and fields
- Email notifications
- Form template assignment
- Email template assignment
- PDF template assignment

It is a structural snapshot — not a dump of submissions.

## What export does not include

- Submissions
- Integration OAuth tokens (reconnect integrations per environment)
- Spam settings and captcha secrets (see [Project config, environment, and control panel settings](/guides/configuration/project-config-environment-and-control-panel-settings))
- Synced field **definitions** (only placements in the form — ensure definitions exist on the target environment)

## Export workflow

1. On the **source** environment, open **Formie → Settings → Import/Export**
2. Select the form(s) to export
3. Download the JSON file
4. Commit to version control if your team tracks form structure in git (optional)

For multiple forms, export one at a time or automate via console commands if your deployment pipeline supports it.

## Import workflow

1. On the **target** environment, open **Formie → Settings → Import/Export**
2. Upload the JSON file
3. Review the preview — Formie validates the file format
4. Choose how to handle handle collisions:

| Option | When to use |
| --- | --- |
| **Create new form** | Production already has a different form with the same handle; import generates a unique handle (e.g. `contactForm1`) |
| **Update existing form** | Staging → production promotion where handles match and you intend a full overwrite |

> Updating an existing form is a **full overwrite** of structure — fields, notifications, and template assignments. Submissions remain, but field handles that changed can orphan old submission data. Take a backup or export first.

5. Complete the import and verify in the form builder

Formie only supports its own export format. Arbitrary JSON will not import.

## Environment promotion patterns

### Staging → production (same project)

1. Build and test the form on staging
2. Export JSON
3. Import on production with **Update existing form** if handles match
4. Re-test notifications, integrations, and captchas on production URLs
5. Run `project-config/sync` if the import touched project-scoped resources

### Client template → new project

1. Export the canonical template forms from your starter Craft install
2. Import on the client site as **Create new form**
3. Rename handles to client conventions
4. Assign [form groups](/forms/form-groups) and site policies for the client's multi-site setup

### Disaster recovery before a big edit

Export the current form JSON before deleting fields or restructuring. Re-import if the edit goes wrong.

## Combine with project config

Some Formie resources travel via project config instead of import/export:

| Resource | Mechanism |
| --- | --- |
| Form groups | `formie.formGroups` in project config |
| Project stencils | `formie.stencils` in project config |
| Submission statuses | `formie.statuses` |
| Reports | `formie.reports` |

Use import/export for **individual form snapshots**. Use project config for **team-wide defaults and stencils** that should version with code.

See [Stencils for repeatable form types](/guides/control-panel-admin/stencils-for-repeatable-form-types).

## Post-import checklist

- Field handles unchanged (or migration plan for submission data)
- Email notifications send from correct domain on target environment
- Integrations reconnected with production credentials
- Captcha keys set under **Settings → Spam Protection**
- Form enabled on expected sites (form group site policy)
- Front-end template or headless app points at correct form handle
- Test submit on production

## Related

- [Import & Export](/forms/import-export)
- [Stencils for repeatable form types](/guides/control-panel-admin/stencils-for-repeatable-form-types)
- [Project config, environment, and control panel settings](/guides/configuration/project-config-environment-and-control-panel-settings)
