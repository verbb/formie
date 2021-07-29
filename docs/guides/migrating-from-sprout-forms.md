# Migrating from Sprout Forms
If your existing site has forms, submissions and email notifications from [Sprout Forms](https://sprout.barrelstrengthdesign.com/docs/forms/), it can be easily migrated over to Formie. Your existing content with Sprout Forms will not be touched.

To migrate your forms and form data, install Formie, and navigate to Formie → Settings → Migrations → Sprout Forms. You'll need to have Sprout Forms installed and enabled for this setting to appear.

You'll be shown a screen to select from any existing Sprout Forms forms. Select which ones you want to migrate, and hit "Migrate Forms". The next screen will show you the result of the migration, detailing what forms, email notifications and submissions were copied across and what errors or exceptions were encountered.

Any form that is attempted to be imported with the same handle will have a number affixed to the form. So if you already have a Formie form with a handle `contactForm` and also have a `contactForm` in Sprout Forms, the migration tool will name this new form to `contactForm1`.

## Support
The following Sprout Forms fields are currently unsupported in Formie:

- Private Notes
- Regular Expression
