# Get Form Support

Open **Formie → Settings → Get Support** to send a support request with the information needed to investigate your form.

Send the request from the installation where the issue occurs. For a production issue, use the production control panel. A request sent from your local site describes only that installation and cannot show production configuration or permissions.

Your request includes your contact details, message, chosen attachments and:

- Craft and Formie versions, and the Formie license.
- Formie logs and your `composer.json` and `composer.lock` files.
- The selected form’s settings, fields and notifications.
- Custom form, email and PDF templates, when used.
- Installation diagnostics: environment name, hostname, collection time, PHP version, development mode and whether admin changes are allowed.
- Your administrator status and relevant Formie permissions.
- CAPTCHA providers and integrations referenced by the form: availability, enabled state, storage scope, and credential presence and resolution status. Spam rules and provider settings are local to the installation sending the request.

The diagnostics file contains no credential values, environment-variable names or spam-rule contents. Logs, templates, form content and attachments can contain sensitive information; review them before sending. Do not attach your `.env` file or credentials.
