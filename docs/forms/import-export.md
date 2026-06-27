# Import & Export

::: tip
For moving forms between environments and staging-to-production workflow, see [Import and export between environments](/guides/control-panel-admin/import-and-export-between-environments).
:::

Form import and export is for moving form definitions, not submission data.

Use it when you need to move a form between environments, reuse it on another project, or keep a portable copy of the form setup. You can manage both from `Formie → Settings → Import/Export`.

## Exporting a form

When you export a form, Formie downloads a JSON file containing the pieces that make up the form itself, including:

- form details
- form settings
- pages and fields
- email notifications
- form template
- email template
- PDF template

This is a structural export of the form, not a dump of what people have submitted through it.

## Importing a form

To import a form, upload a JSON file that was previously exported from Formie. Formie will review that file first, then step you through how it should be applied.

Formie only supports its own export format here. An arbitrary JSON file will not import correctly.

If Formie finds an existing form with the same handle, you can either:

- create a new form
- update the existing form

Creating a new form will generate a separate form with a unique handle that you can change later. Updating an existing form is a full overwrite of that form’s structure, including its fields, notifications, and template assignments, so it should be used carefully.
