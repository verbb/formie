# Stencils for Repeatable Form Types

Stencils are reusable starter forms — layout, notifications, integrations, and settings included. They help teams spin up consistent enquiry, application, or survey forms without rebuilding the same field tree every time. This walkthrough builds a stencil, wires synced fields, and deploys it across environments.

## Prerequisites

- [Stencils](/forms/stencils)
- [Synced Fields](/forms/synced-fields) (optional but recommended)
- **Access stencils** permission for authors who should manage stencils

## Stencils vs Live Forms

| | Stencil | Form created from stencil |
| --- | --- | --- |
| Purpose | Starting template | Live, editable form |
| Changes affect | Future forms only | That form's submissions |
| Storage | CP stencils + optional project config | Database |

Treat stencils as starting points, not shared live forms.

## Step 1 — Build the Canonical Enquiry Stencil

1. Go to **Formie → Stencils**
2. Click **New stencil**
3. Name it `Enquiry Form` (handle `enquiryForm`)
4. Build the layout you want every enquiry to start with:
   - Name, Email, Phone, Message fields
   - Admin notification + submitter confirmation
   - Turnstile captcha enabled
   - Ajax submit, success message

Save the stencil.

## Step 2 — Add Synced Fields

For fields that must stay identical across every form — consent checkbox, privacy policy link, UTM hidden fields — use [synced fields](/forms/synced-fields):

1. Create the shared field definition once (**Formie → Synced Fields**)
2. While editing the stencil, add the field as a **synced field** placement (not a one-off copy)

When someone creates a form from the stencil, Formie adds a placement pointing at the same shared definition. Update the definition once; all forms update.

If a shared definition is missing on an environment (handle not deployed yet), Formie falls back to an independent field copy from the stencil snapshot.

## Step 3 — Set Default Stencil for New Forms

Developers can auto-apply a stencil when authors create forms without picking one:

```php [config/formie.php]
return [
    '*' => [
        'defaultFormStencil' => 'enquiryForm',
    ],
];
```

Authors can still choose a different stencil or start blank from the **New Form** dialog.

## Step 4 — Create Forms from the Stencil

1. **Formie → Forms → New Form**
2. Pick **Enquiry Form** stencil
3. Customise title, handle, and any form-specific copy
4. Save

The result is an independent form. Editing the stencil later does not change existing forms — only forms created after the stencil change (unless you manually re-import or rebuild).

## Step 5 — Save an Existing Form as a Stencil

From the form builder on any live form:

1. Open the form menu
2. Choose **Save as stencil**
3. Name the new stencil

Useful when a one-off form became the de facto standard and you want to codify it.

## Step 6 — Project Stencils (Developers)

For starter kits versioned with the site, create **project stencils** stored in project config:

```yaml
# config/project/formie/stencils/…
```

Project stencils:

- Require admin changes enabled to create/edit in CP (typically local/staging)
- Are **view-only** on production when admin changes are disabled
- Support **Save a copy** to fork into an editable CP stencil

Handles must be unique across regular and project stencils.

On deploy, project stencils appear in the **New Form** stencil picker alongside CP stencils.

## Step 7 — Combine with Form Groups

Assign new enquiry forms to a **Marketing** [form group](/forms/form-groups) for sidebar organisation and group defaults. Stencils define structure; groups define CP policies and defaults.

See [Form groups and defaults at scale](/guides/control-panel-admin/form-groups-and-defaults-at-scale).

## Step 8 — Deploy Across Environments

Project stencils travel via `project-config/sync`. CP-created stencils are database content — export/import them with [Import and export between environments](/guides/control-panel-admin/import-and-export-between-environments) or include them in your content migration process.

Order of operations on a fresh environment:

1. Deploy project config (project stencils, synced field definitions, form groups)
2. Import or sync CP stencils if needed
3. Verify synced field handles resolve before authors create forms from stencils

## Included Default

Formie ships a `Contact Form` stencil as a general starting point. Replace or extend it for your project's conventions.
