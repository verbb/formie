# Import & Export

::: tip
For moving forms between environments and staging-to-production workflow, see [Import and export between environments](/guides/control-panel-admin/import-and-export-between-environments).
:::

Form import and export moves a form’s structure and settings. It does not include submission data.

Use it when you need to move a form between environments, reuse it on another project, or keep a portable copy of the form setup. You can manage both from `Formie → Settings → Import/Export`.

## Exporting a Form

When you export a form, Formie downloads a JSON file containing the pieces that make up the form itself, including:

- form details
- form settings
- pages and fields, including synced field settings and relationships
- email notifications
- form template
- email template
- PDF template

This is a structural export of the form, not a dump of what people have submitted through it.

## Importing a Form

To import a form, upload a JSON file that was previously exported from Formie. Formie will review that file first, then step you through how it should be applied.

Formie only supports its own export format here. An arbitrary JSON file will not import correctly.

If Formie finds an existing form with the same handle, you can either:

- create a new form
- update the existing form

Creating a new form will generate a separate form with a unique handle that you can change later. Updating an existing form is a full overwrite of that form’s structure, including its fields, notifications, and template assignments, so it should be used carefully.


## Importing Synced Fields

Synced-field relationships are preserved automatically during import. Formie creates any missing synced fields and reuses them when you import other forms containing those fields. You can import those forms separately, in either order.

Formie matches synced fields by their unique identifier (UID) in the export. A field with the same label or handle is not automatically considered the same field. Each imported form uses the same synced field settings, while **Required** can be set separately wherever the field appears.

The preview lists new and existing synced fields. When settings differ, choose how to handle them:

| Choice | Result |
| --- | --- |
| **Keep destination settings** | Link to the existing synced field without changing its settings or translations. This is the default. |
| **Update synced field** | Apply the imported settings to the synced field, affecting every form that uses it. |

Choosing **Update synced field** shows the affected forms before you import. Fields with matching settings need no choice.

Reusing or updating a synced field requires permission to manage every form using it, including uses inside nested fields. If you lack those permissions or the matching field has a different type, the import is blocked until the issue is resolved. If a synced field changes after you preview an update, Formie asks you to review it again before applying the change.

### Updating Previously Imported Forms

Use **Update existing form** for subsequent deployments. Matching fields keep their identities and existing submission values.

If a field was previously imported as an independent copy, select **Link existing fields to the imported synced fields** to replace its relationship explicitly. Formie matches fields within the updated form by reference or handle path. Review the synced field choices before importing, because the field will use the selected shared settings afterward.

An existing independent parent field, such as a Group or Name field, cannot be relinked this way: replacing its nested field identities could make existing answers unreadable. Create a new form instead. Shared parent fields that already have the matching identity can be reused and updated.

Exports without synced field UIDs cannot reconstruct relationships. Export the source forms again when those identities are available.
