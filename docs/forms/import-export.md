# Import & Export

::: tip
For moving forms between environments and staging-to-production workflow, see [Import and export between environments](/guides/control-panel-admin/import-and-export-between-environments).
:::

Form import and export is for moving form definitions, not submission data.

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

Choose **Preserve relationships** in the import preview to carry synced fields across environments. Formie creates any missing shared definitions and reuses them when you import other forms containing the same synced fields. You can import those forms separately, in either order.

Matching uses the shared definition’s UID. A field with the same label or handle is not automatically considered the same field. Formie creates new field placements while retaining the shared identity; **Required** remains separate for each placement.

For each shared definition, the preview shows whether it will be created, reused, or has different settings on the destination. It also lists the forms using an existing definition. Choose how to handle it:

| Choice | Result |
| --- | --- |
| **Keep destination settings** | Link to the existing definition without changing its settings or translations. This is the default. |
| **Update shared definition** | Apply the imported settings to the shared definition, affecting every form that uses it. |
| **Import as independent fields** | Create separate definitions for the imported placements. |

The **Synced field relationships** control can make all imported fields independent at once. This is useful when copying a form to an unrelated project. When updating an existing form, this choice detaches its fields from their shared definitions while keeping the field placements.

Reusing or updating a shared definition requires permission to manage every form using it, including uses inside nested fields. If the definition has a different field type, import it independently. If a definition changes after you preview an update, Formie asks you to review it again before applying the change.

### Updating Previously Imported Forms

Use **Update existing form** for subsequent deployments. Matching field placements keep their identities and existing submission values.

If a field was previously imported as an independent copy, select **Link existing fields to the imported shared definitions** to replace its relationship explicitly. Formie matches fields within the updated form by reference or handle path. Review the shared-definition choices before importing, because the field will use the selected shared settings afterward.

An existing independent parent field, such as a Group or Name field, cannot be relinked this way: replacing its nested field identities could make existing answers unreadable. Import that parent independently or create a new form. Shared parent fields that already have the matching identity can be reused and updated.

Exports without shared-definition UIDs cannot reconstruct relationships. Export the source forms again when those identities are available.
