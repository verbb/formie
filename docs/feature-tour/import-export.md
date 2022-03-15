# Import/Export
Formie allows you to import and export your forms. This can be greatly beneficial when working across multiple environments, where you want to migrate forms and their fields. You could also use this to manage your forms across multiple projects as well.

## Export
Navigate to **Formie** → **Settings** → **Import/Export** and in the Export section, select the form you want to export. This will download a JSON file containing everything that makes up your form:

- Form
- Form Settings
- Pages
- Rows
- Fields
- Email Notifications
- Form Template
- Email Template
- PDF Template

With this file, you'll be able to import it on the same, or another install of Formie.

## Import
Navigate to **Formie** → **Settings** → **Import/Export** and in the Import section, upload the JSON file you have exported previously. You'll be able to configure and review this at the next step.

Formie will only support the JSON file you've exported - an arbitrary JSON file won't do.

Formie will detect if you have an existing form with the same handle. If one is found, you'll have two choices:

- Create a new form
- Update existing form

When selecting **Create a new form** a new form will be created with a unique handle (which you can change later). When selecting **Update existing form** you'll be prompted to confirm a full overwrite will occur. This will completely overwrite your existing form, fields, notification (as above), so ensure you are sure that's what you want to do.