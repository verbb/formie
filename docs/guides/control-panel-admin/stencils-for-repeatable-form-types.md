# Stencils for Repeatable Form Types

Stencils are reusable starter forms — layout, notifications, integrations, and settings included. They help teams create consistent enquiry, application, or survey forms without adding the same fields every time. This walkthrough builds a stencil, adds synced fields, and deploys it across environments.

## Prerequisites

- [Stencils](/forms/stencils)
- [Synced Fields](/forms/synced-fields) (optional but recommended)
- **Access stencils** permission for authors who should manage stencils

## Stencils vs Live Forms

| | Stencil | Form created from stencil |
| --- | --- | --- |
| Purpose | Starting template | Live, editable form |
| Changes affect | Future forms only | That form's submissions |
| Storage | Database for instance-level stencils; project config for project stencils | Database |

Treat stencils as starting points, not shared live forms.

<span id="step-1-—-build-the-canonical-enquiry-stencil"></span>

## Step 1 — Build the Enquiry Stencil

1. Go to **Formie → Stencils**
2. For this deployable template, open the arrow beside **New Stencil** and choose **New project stencil**. This requires admin changes to be enabled.
3. Name it `Enquiry Form` (handle `enquiryForm`)
4. Build the layout you want every enquiry to start with:
   - Name, Email, Phone, Message fields
   - Admin notification + submitter confirmation
   - Turnstile captcha enabled
   - Ajax submit, success message

Save the stencil.

## Step 2 — Add Synced Fields

For fields that must stay identical across every form — consent checkbox, privacy policy link, UTM hidden fields — use [synced fields](/forms/synced-fields):

1. Create and save the field on a form in the form builder.
2. While editing the stencil, open the existing fields picker and add that field as a **synced field**.

When someone creates a form from the stencil, it uses the same synced field. Changing the field’s shared settings updates every form that uses it. **Required** can still be set separately wherever the field appears.

Formie matches synced fields by their unique identifier (UID), not their handle. If the matching field is missing on an environment, Formie creates an independent copy from the stencil’s saved field settings.

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

Use this when an existing form would make a useful starting point for future forms.

## Step 6 — Project Stencils (Developers)

The enquiry stencil created in Step 1 is managed by your development team. On production with admin changes disabled, authors can use it to create forms but cannot edit the stencil itself.

If a client needs a variation, they can open the view-only stencil and choose **Save a copy**. Give the copy a unique handle, such as `clientEnquiryForm`. It becomes an independent instance-level stencil, so future deployments of `enquiryForm` leave the client’s copy unchanged. See [Project Stencils](/forms/stencils#project-stencils-developers) for the full storage and permission rules.

## Step 7 — Combine with Form Groups

Assign new enquiry forms to a **Marketing** [form group](/forms/form-groups) for sidebar organisation and group defaults. Stencils define structure; groups define CP policies and defaults.

See [Form groups and defaults at scale](/guides/control-panel-admin/form-groups-and-defaults-at-scale).

## Step 8 — Deploy Across Environments

Commit the enquiry stencil’s project config and apply it on the target environment with `project-config/sync`. Instance-level stencils require a separate content migration. Forms already created from either type of stencil also remain database content; use [Import and export between environments](/guides/control-panel-admin/import-and-export-between-environments) to move those forms.

Order of operations on a fresh environment:

1. Deploy project config, including project stencils and form groups
2. Migrate any instance-level stencils you need. To bring across synced fields, export a form that uses them and import it with **Preserve relationships**.
3. Check **Formie → Settings → Synced Fields** for the imported fields before authors create forms from stencils.

On the target environment, confirm that **Enquiry Form** appears in the **New Form** stencil picker, then create a test form and check its fields, notifications and settings.

## Included Default

Formie ships a `Contact Form` stencil as a general starting point. Replace or extend it for your project's conventions.
