# Migrating from Freeform
If your existing site has forms, submissions and email notifications from [Solspace Freeform v3](https://docs.solspace.com/craft/freeform/v3/), it can be easily migrated over to Formie. Your existing content with Freeform will not be touched.

To migrate your forms and form data, install Formie, and navigate to Formie → Settings → Migrations → Freeform. You'll need to have Freeform installed and enabled for this setting to appear.

You'll be shown a screen to select from any existing Freeform forms. Select which ones you want to migrate, and hit "Migrate Forms". The next screen will show you the result of the migration, detailing what forms, email notifications and submissions were copied across and what errors or exceptions were encountered.

Any form that is attempted to be imported with the same handle will have a number affixed to the form. So if you already have a Formie form with a handle `contactForm` and also have a `contactForm` in Freeform, the migration tool will name this new form to `contactForm1`.

## Support
The following Freeform fields are currently unsupported in Formie:

- Payments fields (Pro)
- Opinion Scale Field (Pro)
- Rating Field (Pro)
- Regex Field (Pro)
- Rich Text Field (Pro)
- Dynamic Recipient Field
- Mailing List Field
- Recaptcha Field

