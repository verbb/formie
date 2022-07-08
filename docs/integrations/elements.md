# Elements
Element integrations are one of the provided integrations with Formie, and are used to populate and create elements when a submission is made. For instance, you might want to create an Entry element when someone submits a form.

Elements have settings at the plugin level, as well as per-form, allowing you to customise their behaviour for particular forms, or globally for all forms.

## Supported Elements
Formie integrates with the following elements:
- Entries
- Users

## Entry
For a form, you can configure entries to be created for submissions. You'll need to configure:

- Entry Type
- Default Entry Author
- Entry Attribute Mapping
- Entry Field Mapping

### Mapping
For both the entries attributes (Title, Post Date, etc.) and any custom fields, you can assign a field's content to be mapped to that attribute or field.

For instance, you might have a Date, Users and Single-Line Text field for your form. With this integration, you could map these fields to the entry Post Date, Author and Title respectively.

When a form submission is created successfully, a queue job will run to create the entry element after the users' submission.

## User
For a form, you can configure users to be created for submissions. You can configure:

- User Groups
- Activate User
- Send Activation Email
- User Attribute Mapping
- User Field Mapping

