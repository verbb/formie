# Import and Export Between Environments

Form import and export moves **form definitions** between environments — not submission data. Use it when staging a new form for production, cloning a client's enquiry form to another project, or backing up structure before a major edit.

## Prerequisites

- [Import & Export](/forms/import-export)
- Access to **Formie → Settings → Import/Export** on both environments

## What Export Includes

Exporting a form produces a JSON file containing:

- Form details and settings
- Pages and fields, including synced field definitions and relationships
- Email notifications
- Form template assignment
- Email template assignment
- PDF template assignment

It is a structural snapshot — not a dump of submissions.

## What Export Does Not Include

- Submissions
- Integration OAuth tokens (reconnect integrations per environment)
- Spam settings and captcha secrets (see [Project config, environment, and control panel settings](/guides/configuration/project-config-environment-and-control-panel-settings))

## Export Workflow

1. On the **source** environment, open **Formie → Settings → Import/Export**
2. Select the form(s) to export
3. Download the JSON file
4. Commit to version control if your team tracks form structure in git (optional)

For multiple forms, export one at a time or automate via console commands if your deployment pipeline supports it.

## Import Workflow

1. On the **target** environment, open **Formie → Settings → Import/Export**
2. Upload the JSON file
3. Review the preview — Formie validates the file format
4. Choose how to handle handle collisions:

| Option | When to use |
| --- | --- |
| **Create new form** | Production already has a different form with the same handle; import generates a unique handle (e.g. `contactForm1`) |
| **Update existing form** | Staging → production promotion where handles match and you intend a full overwrite |

> Updating an existing form is a **full overwrite** of structure — fields, notifications, and template assignments. Submissions remain, but field handles that changed can orphan old submission data. Take a backup or export first.

5. Review **Synced Fields**, if present. Keep **Preserve relationships** to create missing shared definitions and link matching ones. The preview lists existing uses and offers a choice between keeping destination settings, updating the shared definition, or importing independent fields.
6. Complete the import and verify in the form builder

Formie only supports its own export format. Arbitrary JSON will not import.

## Environment Promotion Patterns

### Staging → Production (Same Project)

1. Build and test the form on staging
2. Export JSON
3. Import on production with **Update existing form** if handles match
4. Re-test notifications, integrations, and captchas on production URLs
5. Run `project-config/sync` if the import touched project-scoped resources

### Promoting Forms That Share a Field

Suppose your local Contact and Quote forms share an Email Address field. Export both forms, then import Contact on production with **Preserve relationships**. Formie creates the shared Email Address definition if it is missing. Import Quote with the same choice, and it links to that definition automatically. You can also import Quote first; the order does not matter.

If production already has that shared definition, **Keep destination settings** leaves its settings and translations intact. To deploy a change to the shared field itself, choose **Update shared definition** and review the listed forms: they all receive that change, even if you are only importing one form. You must be able to manage every affected form.

After importing, open both forms and confirm the field is marked as synced. Check that each placement has the intended **Required** state, then submit a test entry through each form.

The same name or handle does not establish a relationship. Formie uses the definition’s UID from the export. For forms previously imported as independent copies, see [Updating previously imported forms](/forms/import-export#updating-previously-imported-forms).

### Client Template → New Project

1. Export the canonical template forms from your starter Craft install
2. Import on the client site as **Create new form**. Select **Import as independent fields** if the new forms should not share definitions with other imports from that template
3. Rename handles to client conventions
4. Assign [form groups](/forms/form-groups) and site policies for the client's multi-site setup

### Disaster Recovery Before a Big Edit

Export the current form JSON before deleting fields or restructuring. Re-import if the edit goes wrong.

## Combine with Project Config

Some Formie resources travel via project config instead of import/export:

| Resource | Mechanism |
| --- | --- |
| Form groups | `formie.formGroups` in project config |
| Project stencils | `formie.stencils` in project config |
| Submission statuses | `formie.statuses` |

Use import/export for **individual form snapshots**. Use project config for **team-wide defaults and stencils** that should version with code.

See [Stencils for repeatable form types](/guides/control-panel-admin/stencils-for-repeatable-form-types).

## Post-Import Checklist

- Field handles unchanged (or migration plan for submission data)
- Email notifications send from correct domain on target environment
- Integrations reconnected with production credentials
- Captcha keys set under **Spam Protection**
- Form enabled on expected sites (form group site policy)
- Front-end template or headless app points at correct form handle
- Test submit on production
